<?php
// =============================================================================
// Test: Damaged Stock Page (Page 407)
// Tests DB operations and stock level impact of damaged stock movements.
// Run from CLI: /path/to/php.exe tests/test_damaged_stock.php
//
// WARNING: Clears stock_movement, stock, and stock_category before running.
// Do not run against a database with live data.
// =============================================================================
$mysqli = new mysqli('localhost', 'root', '', 'vols');
if ($mysqli->connect_error) { die("DB connection failed: " . $mysqli->connect_error . "\n"); }

$pass = 0; $fail = 0;

function check($label, $result, $expected=true) {
    global $pass, $fail;
    if ($result === $expected) {
        echo "  PASS: {$label}\n";
        $pass++;
    } else {
        echo "  FAIL: {$label} (got " . var_export($result,true) . ", expected " . var_export($expected,true) . ")\n";
        $fail++;
    }
}

function q($mysqli, $sql) {
    $r = $mysqli->query($sql);
    if (!$r) { echo "  SQL ERROR: " . $mysqli->error . "\n  SQL: $sql\n"; return false; }
    return $r;
}

function scalar($mysqli, $sql) {
    $r = $mysqli->query($sql);
    if (!$r) return false;
    $row = $r->fetch_row();
    return $row ? $row[0] : null;
}

function stocklevel($mysqli, $stock_id) {
    $r = $mysqli->query(
        "SELECT COALESCE((SELECT sm1.qty FROM stock_movement sm1 WHERE sm1.stock_id={$stock_id} AND sm1.movement_type='stocktake_adjustment' ORDER BY sm1.id DESC LIMIT 1),0)"
       ." + COALESCE((SELECT SUM(sm2.qty) FROM stock_movement sm2 WHERE sm2.stock_id={$stock_id} AND sm2.movement_type='delivery' AND sm2.id > COALESCE((SELECT MAX(sm3.id) FROM stock_movement sm3 WHERE sm3.stock_id={$stock_id} AND sm3.movement_type='stocktake_adjustment'),0)),0)"
       ." - COALESCE((SELECT SUM(sm4.qty) FROM stock_movement sm4 WHERE sm4.stock_id={$stock_id} AND sm4.movement_type IN ('stockout','damaged') AND sm4.id > COALESCE((SELECT MAX(sm5.id) FROM stock_movement sm5 WHERE sm5.stock_id={$stock_id} AND sm5.movement_type='stocktake_adjustment'),0)),0) as lvl"
    );
    return (float)$r->fetch_row()[0];
}

// ============================================================
echo "\n=== Setup: seed categories and stock items ===\n";
q($mysqli, "DELETE FROM stock_movement");
q($mysqli, "DELETE FROM stock");
q($mysqli, "DELETE FROM stock_category");

q($mysqli, "INSERT INTO stock_category (Name) VALUES ('Tinned Goods')");
$cat1 = $mysqli->insert_id;
q($mysqli, "INSERT INTO stock (Name, Code, category_id) VALUES ('Baked Beans', 'BB', {$cat1})");
$s1 = $mysqli->insert_id;
q($mysqli, "INSERT INTO stock (Name, Code, category_id) VALUES ('Tomato Soup', 'TS', {$cat1})");
$s2 = $mysqli->insert_id;
echo "  Categories and stock items seeded.\n";

// ============================================================
echo "\n=== ENUM: damaged movement type accepted ===\n";

$now = date('Y-m-d H:i:s');
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s1}, 'stocktake_adjustment', 50, 'can', 1, '{$now}')");
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s2}, 'stocktake_adjustment', 30, 'can', 1, '{$now}')");

$testinsert = q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s1}, 'damaged', 5, 'can', 1, '{$now}')");
check("Insert 'damaged' movement type accepted", $testinsert !== false);
check("Damaged record saved in DB", (int)scalar($mysqli, "SELECT COUNT(*) FROM stock_movement WHERE movement_type='damaged'"), 1);

// ============================================================
echo "\n=== Stock level: damaged reduces inventory ===\n";

// BB: stocktake=50, damaged=5 → should be 45
check("Level after stocktake+damaged (BB: 50-5=45)", stocklevel($mysqli, $s1), 45.0);

// Add more damaged
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s1}, 'damaged', 3, 'can', 1, '{$now}')");
check("Level after two damaged records (BB: 50-5-3=42)", stocklevel($mysqli, $s1), 42.0);

// TS: stocktake=30, no movements yet
check("Unaffected item unchanged (TS=30)", stocklevel($mysqli, $s2), 30.0);

// ============================================================
echo "\n=== Stock level: damaged + stockout both reduce inventory ===\n";

q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s1}, 'stockout', 10, 'can', 1, '{$now}')");
// BB: stocktake=50, damaged=5+3=8, stockout=10 → 50-8-10=32
check("Level with damaged+stockout (BB: 50-8-10=32)", stocklevel($mysqli, $s1), 32.0);

// ============================================================
echo "\n=== Stock level: damaged before stocktake is not counted ===\n";

// Record a new stocktake — resets the baseline
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s1}, 'stocktake_adjustment', 60, 'can', 1, '{$now}')");
// Previous damaged/stockout movements are before the new stocktake baseline — should be ignored
check("New stocktake resets baseline (BB=60)", stocklevel($mysqli, $s1), 60.0);

// New damaged after new stocktake
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s1}, 'damaged', 4, 'can', 1, '{$now}')");
check("Damaged after new stocktake counted (BB: 60-4=56)", stocklevel($mysqli, $s1), 56.0);

// ============================================================
echo "\n=== movement_date is recorded ===\n";

$row = $mysqli->query("SELECT movement_date FROM stock_movement WHERE movement_type='damaged' ORDER BY id DESC LIMIT 1")->fetch_assoc();
check("movement_date is not null", $row['movement_date'] !== null);
check("movement_date is not empty", $row['movement_date'] !== '');
echo "  movement_date value: {$row['movement_date']}\n";

// ============================================================
echo "\n=== getmovementsbytype returns damaged records ===\n";

$r = $mysqli->query("SELECT sm.id, sm.stock_id, sm.movement_type, sm.qty, sm.unit, sm.movement_date, s.Name as stock_name FROM stock_movement sm JOIN stock s ON sm.stock_id=s.id WHERE sm.movement_type='damaged' ORDER BY sm.id DESC");
$rows = $r->fetch_all(MYSQLI_ASSOC);
check("getmovementsbytype returns damaged rows", count($rows) > 0);
check("stock_name JOIN works", $rows[0]['stock_name'] === 'Baked Beans');
echo "\n  Damaged stock records:\n";
foreach ($rows as $row) {
    printf("    %s  %-15s  qty=%-4s  unit=%-6s  date=%s\n",
        $row['movement_type'], $row['stock_name'], $row['qty'], $row['unit'], substr($row['movement_date'],0,10));
}

// ============================================================
echo "\n=== Summary ===\n";
echo "  Passed: {$pass}\n";
echo "  Failed: {$fail}\n";

$mysqli->close();
