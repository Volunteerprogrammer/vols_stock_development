<?php
// =============================================================================
// Test: Stock Pages Integration Test (Pages 401-407)
// Tests all DB operations for the stock tracking system.
// Run from CLI: /path/to/php.exe tests/test_stock_pages.php
//
// WARNING: This test clears the stock_movement, stock, and stock_category
// tables before running. Do not run against a database with live data.
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

// Date-based stock level calculation (matches getstockwithlevels())
function stocklevel($mysqli, $stock_id) {
    $r = $mysqli->query(
        "SELECT COALESCE((SELECT sm1.qty FROM stock_movement sm1"
       ."  WHERE sm1.stock_id={$stock_id} AND sm1.movement_type='stocktake_adjustment'"
       ."  ORDER BY sm1.movement_date DESC LIMIT 1), 0)"
       ." + COALESCE((SELECT SUM(sm2.qty) FROM stock_movement sm2"
       ."  WHERE sm2.stock_id={$stock_id} AND sm2.movement_type='delivery'"
       ."  AND sm2.movement_date > COALESCE((SELECT MAX(sm3.movement_date) FROM stock_movement sm3"
       ."    WHERE sm3.stock_id={$stock_id} AND sm3.movement_type='stocktake_adjustment'), '1970-01-01')), 0)"
       ." - COALESCE((SELECT SUM(sm4.qty) FROM stock_movement sm4"
       ."  WHERE sm4.stock_id={$stock_id} AND sm4.movement_type IN ('stockout','damaged')"
       ."  AND sm4.movement_date > COALESCE((SELECT MAX(sm5.movement_date) FROM stock_movement sm5"
       ."    WHERE sm5.stock_id={$stock_id} AND sm5.movement_type='stocktake_adjustment'), '1970-01-01')), 0) as lvl"
    );
    return (float)$r->fetch_row()[0];
}

// ============================================================
echo "\n=== Clean slate ===\n";
q($mysqli, "DELETE FROM stock_movement");
q($mysqli, "DELETE FROM stock");
q($mysqli, "DELETE FROM stock_category");
echo "  Tables cleared.\n";

// ============================================================
echo "\n=== Page 401: Stock Categories ===\n";

q($mysqli, "INSERT INTO stock_category (Name) VALUES ('Tinned Goods')");
$cat1 = $mysqli->insert_id;
q($mysqli, "INSERT INTO stock_category (Name) VALUES ('Dry Goods')");
$cat2 = $mysqli->insert_id;
q($mysqli, "INSERT INTO stock_category (Name) VALUES ('Delete Me')");
$cat3 = $mysqli->insert_id;

check("Insert category 1", $cat1 > 0);
check("Insert category 2", $cat2 > 0);
check("Category count", (int)scalar($mysqli, "SELECT COUNT(*) FROM stock_category"), 3);

q($mysqli, "UPDATE stock_category SET Name='Tinned & Packaged' WHERE id={$cat1}");
check("Update category name", scalar($mysqli, "SELECT Name FROM stock_category WHERE id={$cat1}"), 'Tinned & Packaged');

q($mysqli, "DELETE FROM stock_category WHERE id={$cat3}");
check("Delete category", (int)scalar($mysqli, "SELECT COUNT(*) FROM stock_category"), 2);

// ============================================================
echo "\n=== Page 402: Stock Items ===\n";

q($mysqli, "INSERT INTO stock (Name, Code, category_id) VALUES ('Baked Beans', 'BB', {$cat1})");
$s1 = $mysqli->insert_id;
q($mysqli, "INSERT INTO stock (Name, Code, category_id) VALUES ('Tomato Soup', 'TS', {$cat1})");
$s2 = $mysqli->insert_id;
q($mysqli, "INSERT INTO stock (Name, Code, category_id) VALUES ('Pasta', 'PA', {$cat2})");
$s3 = $mysqli->insert_id;

check("Insert stock item 1", $s1 > 0);
check("Insert stock item 2", $s2 > 0);
check("Insert stock item 3", $s3 > 0);
check("Stock count", (int)scalar($mysqli, "SELECT COUNT(*) FROM stock"), 3);

q($mysqli, "UPDATE stock SET Code='BEAN' WHERE id={$s1}");
check("Update stock item code", scalar($mysqli, "SELECT Code FROM stock WHERE id={$s1}"), 'BEAN');

$row = $mysqli->query("SELECT s.Name, sc.Name as cat FROM stock s JOIN stock_category sc ON sc.id=s.category_id WHERE s.id={$s1}")->fetch_assoc();
check("Category join for stock item", $row['cat'], 'Tinned & Packaged');

// ============================================================
echo "\n=== Page 403: Stocktake ===\n";

$t1 = '2026-01-10 10:00:00';
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s1}, 'stocktake_adjustment', 24, 'can', 1, '{$t1}')");
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s2}, 'stocktake_adjustment', 12, 'can', 1, '{$t1}')");
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s3}, 'stocktake_adjustment', 10, 'kg',  1, '{$t1}')");

check("Stocktake movements inserted", (int)scalar($mysqli, "SELECT COUNT(*) FROM stock_movement WHERE movement_type='stocktake_adjustment'"), 3);
check("Level after stocktake only (BB=24)", stocklevel($mysqli, $s1), 24.0);

// ============================================================
echo "\n=== Page 404: Deliveries ===\n";

$t2 = '2026-01-15 09:00:00';
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s1}, 'delivery', 48, 'can', 1, '{$t2}')");
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s3}, 'delivery',  5, 'kg',  1, '{$t2}')");

check("Delivery movements inserted", (int)scalar($mysqli, "SELECT COUNT(*) FROM stock_movement WHERE movement_type='delivery'"), 2);
check("Level after delivery (BB: 24+48=72)", stocklevel($mysqli, $s1), 72.0);

// ============================================================
echo "\n=== Page 405: Stock Usage (Stockout) ===\n";

$t3 = '2026-01-20 11:00:00';
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s2}, 'stockout', 5, 'can', 1, '{$t3}')");
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s3}, 'stockout', 3, 'kg',  1, '{$t3}')");

check("Stockout movements inserted", (int)scalar($mysqli, "SELECT COUNT(*) FROM stock_movement WHERE movement_type='stockout'"), 2);
check("Level after stockout (TS: 12-5=7)", stocklevel($mysqli, $s2), 7.0);
check("Level after delivery+stockout (Pasta: 10+5-3=12)", stocklevel($mysqli, $s3), 12.0);

// ============================================================
echo "\n=== Page 407: Damaged Stock ===\n";

$t4 = '2026-01-22 14:00:00';
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s1}, 'damaged', 6, 'can', 1, '{$t4}')");

check("Damaged movement type accepted", (int)scalar($mysqli, "SELECT COUNT(*) FROM stock_movement WHERE movement_type='damaged'"), 1);
check("Level after damaged (BB: 72-6=66)", stocklevel($mysqli, $s1), 66.0);
check("Undamaged item unaffected (TS=7)", stocklevel($mysqli, $s2), 7.0);

// ============================================================
echo "\n=== Page 406: Stock Level Report (full query with breakdown) ===\n";

$last_st = "SELECT MAX(sm_st.movement_date) FROM stock_movement sm_st"
         . " WHERE sm_st.stock_id = s.id AND sm_st.movement_type = 'stocktake_adjustment'";
$query  = "SELECT s.id, s.Name, s.Code, sc.Name as category_name,";
$query .= " (SELECT sm1.movement_date FROM stock_movement sm1 WHERE sm1.stock_id=s.id AND sm1.movement_type='stocktake_adjustment' ORDER BY sm1.movement_date DESC LIMIT 1) as stocktake_date,";
$query .= " COALESCE((SELECT sm1.qty FROM stock_movement sm1 WHERE sm1.stock_id=s.id AND sm1.movement_type='stocktake_adjustment' ORDER BY sm1.movement_date DESC LIMIT 1),0) as stocktake_qty,";
$query .= " COALESCE((SELECT SUM(sm2.qty) FROM stock_movement sm2 WHERE sm2.stock_id=s.id AND sm2.movement_type='delivery' AND sm2.movement_date > COALESCE(({$last_st}),'1970-01-01')),0) as deliveries_since,";
$query .= " COALESCE((SELECT SUM(sm3.qty) FROM stock_movement sm3 WHERE sm3.stock_id=s.id AND sm3.movement_type='stockout' AND sm3.movement_date > COALESCE(({$last_st}),'1970-01-01')),0) as stockouts_since,";
$query .= " COALESCE((SELECT SUM(sm4.qty) FROM stock_movement sm4 WHERE sm4.stock_id=s.id AND sm4.movement_type='damaged' AND sm4.movement_date > COALESCE(({$last_st}),'1970-01-01')),0) as damaged_since,";
$query .= " COALESCE((SELECT sm1.qty FROM stock_movement sm1 WHERE sm1.stock_id=s.id AND sm1.movement_type='stocktake_adjustment' ORDER BY sm1.movement_date DESC LIMIT 1),0)"
        . " + COALESCE((SELECT SUM(sm2.qty) FROM stock_movement sm2 WHERE sm2.stock_id=s.id AND sm2.movement_type='delivery' AND sm2.movement_date > COALESCE(({$last_st}),'1970-01-01')),0)"
        . " - COALESCE((SELECT SUM(sm4.qty) FROM stock_movement sm4 WHERE sm4.stock_id=s.id AND sm4.movement_type IN ('stockout','damaged') AND sm4.movement_date > COALESCE(({$last_st}),'1970-01-01')),0) as current_qty";
$query .= " FROM stock s LEFT JOIN stock_category sc ON s.category_id=sc.id ORDER BY sc.Name, s.Name";

$r = $mysqli->query($query);
check("Report query executes", $r !== false);
$rows = $r->fetch_all(MYSQLI_ASSOC);
check("Report returns 3 rows", count($rows), 3);

$bb = array_values(array_filter($rows, fn($r) => $r['Name']==='Baked Beans'))[0];
// BB: stocktake=24, delivery=48, damaged=6, stockout=0 → current=66
check("BB current_qty=66",        (float)$bb['current_qty'],      66.0);
check("BB stocktake_qty=24",      (float)$bb['stocktake_qty'],    24.0);
check("BB deliveries_since=48",   (float)$bb['deliveries_since'], 48.0);
check("BB stockouts_since=0",     (float)$bb['stockouts_since'],   0.0);
check("BB damaged_since=6",       (float)$bb['damaged_since'],     6.0);
check("BB stocktake_date set",    substr($bb['stocktake_date'],0,10), '2026-01-10');

echo "\n  Full report output:\n";
$currentcat = null;
foreach ($rows as $row) {
    if ($row['category_name'] !== $currentcat) {
        $currentcat = $row['category_name'];
        echo "    [{$currentcat}]\n";
    }
    $stdate = $row['stocktake_date'] ? substr($row['stocktake_date'],0,10) : '—';
    printf("      %-12s %-6s  last_st=%-12s  st=%4.0f  +del=%4.0f  -used=%4.0f  -dmgd=%4.0f  =curr=%4.0f\n",
        $row['Name'], $row['Code'], $stdate,
        (float)$row['stocktake_qty'], (float)$row['deliveries_since'],
        (float)$row['stockouts_since'], (float)$row['damaged_since'],
        (float)$row['current_qty']);
}

// ============================================================
echo "\n=== Summary ===\n";
echo "  Passed: {$pass}\n";
echo "  Failed: {$fail}\n";

$mysqli->close();
