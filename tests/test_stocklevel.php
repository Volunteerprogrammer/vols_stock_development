<?php
// =============================================================================
// Test: Stock Level Report Query (Page 406)
// Tests getstockwithlevels() breakdown columns and date-based calculation.
// Run from CLI: /path/to/php.exe tests/test_stocklevel.php
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

function runreport($mysqli) {
    $last_st  = "SELECT MAX(sm_st.movement_date) FROM stock_movement sm_st"
              . " WHERE sm_st.stock_id = s.id AND sm_st.movement_type = 'stocktake_adjustment'";
    $query  = "SELECT s.id, s.Name, s.Code, s.category_id, sc.Name as category_name,";
    $query .= " (SELECT sm1.movement_date FROM stock_movement sm1"
            . "   WHERE sm1.stock_id = s.id AND sm1.movement_type = 'stocktake_adjustment'"
            . "   ORDER BY sm1.movement_date DESC LIMIT 1) as stocktake_date,";
    $query .= " COALESCE((SELECT sm1.qty FROM stock_movement sm1"
            . "   WHERE sm1.stock_id = s.id AND sm1.movement_type = 'stocktake_adjustment'"
            . "   ORDER BY sm1.movement_date DESC LIMIT 1), 0) as stocktake_qty,";
    $query .= " COALESCE((SELECT SUM(sm2.qty) FROM stock_movement sm2"
            . "   WHERE sm2.stock_id = s.id AND sm2.movement_type = 'delivery'"
            . "   AND sm2.movement_date > COALESCE(({$last_st}), '1970-01-01')), 0) as deliveries_since,";
    $query .= " COALESCE((SELECT SUM(sm3.qty) FROM stock_movement sm3"
            . "   WHERE sm3.stock_id = s.id AND sm3.movement_type = 'stockout'"
            . "   AND sm3.movement_date > COALESCE(({$last_st}), '1970-01-01')), 0) as stockouts_since,";
    $query .= " COALESCE((SELECT SUM(sm4.qty) FROM stock_movement sm4"
            . "   WHERE sm4.stock_id = s.id AND sm4.movement_type = 'damaged'"
            . "   AND sm4.movement_date > COALESCE(({$last_st}), '1970-01-01')), 0) as damaged_since,";
    $query .= " COALESCE((SELECT sm1.qty FROM stock_movement sm1"
            . "   WHERE sm1.stock_id = s.id AND sm1.movement_type = 'stocktake_adjustment'"
            . "   ORDER BY sm1.movement_date DESC LIMIT 1), 0)"
            . " + COALESCE((SELECT SUM(sm2.qty) FROM stock_movement sm2"
            . "   WHERE sm2.stock_id = s.id AND sm2.movement_type = 'delivery'"
            . "   AND sm2.movement_date > COALESCE(({$last_st}), '1970-01-01')), 0)"
            . " - COALESCE((SELECT SUM(sm4.qty) FROM stock_movement sm4"
            . "   WHERE sm4.stock_id = s.id AND sm4.movement_type IN ('stockout','damaged')"
            . "   AND sm4.movement_date > COALESCE(({$last_st}), '1970-01-01')), 0) as current_qty";
    $query .= " FROM stock s";
    $query .= " LEFT JOIN stock_category sc ON s.category_id = sc.id";
    $query .= " ORDER BY sc.Name, s.Name";
    $r = $mysqli->query($query);
    if (!$r) { echo "  SQL ERROR: " . $mysqli->error . "\n"; return []; }
    return $r->fetch_all(MYSQLI_ASSOC);
}

// ============================================================
echo "\n=== Setup: seed categories and stock items ===\n";
q($mysqli, "DELETE FROM stock_movement");
q($mysqli, "DELETE FROM stock");
q($mysqli, "DELETE FROM stock_category");

q($mysqli, "INSERT INTO stock_category (Name) VALUES ('Tinned Goods')");
$cat1 = $mysqli->insert_id;
q($mysqli, "INSERT INTO stock_category (Name) VALUES ('Dry Goods')");
$cat2 = $mysqli->insert_id;
q($mysqli, "INSERT INTO stock (Name, Code, category_id) VALUES ('Baked Beans', 'BB', {$cat1})");
$s1 = $mysqli->insert_id;
q($mysqli, "INSERT INTO stock (Name, Code, category_id) VALUES ('Tomato Soup', 'TS', {$cat1})");
$s2 = $mysqli->insert_id;
q($mysqli, "INSERT INTO stock (Name, Code, category_id) VALUES ('Rice', 'RC', {$cat2})");
$s3 = $mysqli->insert_id;
echo "  Seeded 2 categories, 3 stock items.\n";

// ============================================================
echo "\n=== No movements: all quantities zero ===\n";

$rows = runreport($mysqli);
check("Returns 3 rows", count($rows), 3);
$bb = array_values(array_filter($rows, fn($r) => $r['Name']==='Baked Beans'))[0];
check("No movement: current_qty=0",     (float)$bb['current_qty'],      0.0);
check("No movement: stocktake_qty=0",   (float)$bb['stocktake_qty'],    0.0);
check("No movement: deliveries_since=0",(float)$bb['deliveries_since'], 0.0);
check("No movement: stockouts_since=0", (float)$bb['stockouts_since'],  0.0);
check("No movement: damaged_since=0",   (float)$bb['damaged_since'],    0.0);
check("No movement: stocktake_date null",  $bb['stocktake_date'],       null);

// ============================================================
echo "\n=== Stocktake sets baseline ===\n";

$t1 = '2026-01-10 10:00:00';
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s1}, 'stocktake_adjustment', 100, 'can', 1, '{$t1}')");
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s2}, 'stocktake_adjustment', 40,  'can', 1, '{$t1}')");
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s3}, 'stocktake_adjustment', 200, 'bag', 1, '{$t1}')");

$rows = runreport($mysqli);
$bb = array_values(array_filter($rows, fn($r) => $r['Name']==='Baked Beans'))[0];
check("After stocktake: current_qty=100",   (float)$bb['current_qty'],    100.0);
check("After stocktake: stocktake_qty=100", (float)$bb['stocktake_qty'],  100.0);
check("After stocktake: stocktake_date set", substr($bb['stocktake_date'],0,10), '2026-01-10');
check("After stocktake: deliveries=0",  (float)$bb['deliveries_since'], 0.0);
check("After stocktake: stockouts=0",   (float)$bb['stockouts_since'],  0.0);
check("After stocktake: damaged=0",     (float)$bb['damaged_since'],    0.0);

// ============================================================
echo "\n=== Delivery after stocktake increases level ===\n";

$t2 = '2026-01-15 09:00:00';
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s1}, 'delivery', 24, 'can', 1, '{$t2}')");

$rows = runreport($mysqli);
$bb = array_values(array_filter($rows, fn($r) => $r['Name']==='Baked Beans'))[0];
check("After delivery: current_qty=124",   (float)$bb['current_qty'],     124.0);
check("After delivery: deliveries_since=24",(float)$bb['deliveries_since'], 24.0);
check("After delivery: stocktake_qty=100", (float)$bb['stocktake_qty'],   100.0);

// ============================================================
echo "\n=== Stockout and damaged after stocktake reduce level ===\n";

$t3 = '2026-01-20 11:00:00';
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s1}, 'stockout', 10, 'can', 1, '{$t3}')");
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s1}, 'damaged',   3, 'can', 1, '{$t3}')");

$rows = runreport($mysqli);
$bb = array_values(array_filter($rows, fn($r) => $r['Name']==='Baked Beans'))[0];
// 100 + 24 - 10 - 3 = 111
check("After stockout+damaged: current_qty=111", (float)$bb['current_qty'],    111.0);
check("stockouts_since=10",                      (float)$bb['stockouts_since'],  10.0);
check("damaged_since=3",                         (float)$bb['damaged_since'],     3.0);

// ============================================================
echo "\n=== Delivery BEFORE stocktake is not counted ===\n";

// Insert a delivery with a date BEFORE the stocktake — should be ignored
$tbefore = '2026-01-05 08:00:00';
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s2}, 'delivery', 999, 'can', 1, '{$tbefore}')");

$rows = runreport($mysqli);
$ts = array_values(array_filter($rows, fn($r) => $r['Name']==='Tomato Soup'))[0];
// Tomato Soup: stocktake=40, delivery before stocktake (ignored), no movements after → should still be 40
check("Delivery before stocktake ignored: current_qty=40", (float)$ts['current_qty'],     40.0);
check("deliveries_since=0 (pre-stocktake delivery excluded)", (float)$ts['deliveries_since'], 0.0);

// ============================================================
echo "\n=== New stocktake resets baseline (date-based) ===\n";

$t4 = '2026-02-01 09:00:00';
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s1}, 'stocktake_adjustment', 80, 'can', 1, '{$t4}')");

$rows = runreport($mysqli);
$bb = array_values(array_filter($rows, fn($r) => $r['Name']==='Baked Beans'))[0];
// Previous stockout(10), damaged(3), delivery(24) are all before new stocktake — ignored
check("New stocktake resets: current_qty=80",   (float)$bb['current_qty'],   80.0);
check("New stocktake resets: stocktake_qty=80", (float)$bb['stocktake_qty'], 80.0);
check("New stocktake: deliveries_since=0",      (float)$bb['deliveries_since'], 0.0);
check("New stocktake: stockouts_since=0",       (float)$bb['stockouts_since'],  0.0);
check("New stocktake: damaged_since=0",         (float)$bb['damaged_since'],    0.0);
check("New stocktake: stocktake_date updated",  substr($bb['stocktake_date'],0,10), '2026-02-01');

// ============================================================
echo "\n=== Ordering: grouped by category then name ===\n";

$rows = runreport($mysqli);
$names = array_column($rows, 'Name');
$cats  = array_column($rows, 'category_name');
// Dry Goods (Rice) should come before Tinned Goods (Baked Beans, Tomato Soup)
check("Rice before Baked Beans (category sort)", array_search('Rice',$names) < array_search('Baked Beans',$names));
check("Baked Beans before Tomato Soup (name sort)", array_search('Baked Beans',$names) < array_search('Tomato Soup',$names));

// ============================================================
echo "\n=== Display breakdown columns ===\n";
$rows = runreport($mysqli);
echo "\n";
$currentcat = null;
foreach ($rows as $row) {
    $cat = $row['category_name'] ?? 'Uncategorised';
    if ($cat !== $currentcat) {
        $currentcat = $cat;
        printf("  --- %s ---\n", $cat);
    }
    $stdate = $row['stocktake_date'] ? substr($row['stocktake_date'],0,10) : '—';
    printf("  %-15s %-5s  last_st=%-12s  st_qty=%4.0f  +deliv=%4.0f  -used=%4.0f  -dmgd=%4.0f  =curr=%4.0f\n",
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
