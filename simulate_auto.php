<?php
/**
 * Stock Simulation Runner — Auto mode
 *
 * Runs all events automatically using the per-event delays from the data.
 * Just open this page and leave the tab open; no clicking required.
 * Check results retrospectively in the completed-events log below.
 *
 * Usage: http://localhost/stock/simulate_auto.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DS',       DIRECTORY_SEPARATOR);
define('ROOT_DIR', dirname(__FILE__) . DS);
define('FW_DIR',   ROOT_DIR . 'vendor' . DS . 'fw' . DS);
define('APP_DIR',  ROOT_DIR . 'app' . DS);
define('VERBOSE',  false);

function gtab($direction = 0) { return ''; }

date_default_timezone_set('Australia/Melbourne');

require FW_DIR . 'library' . DS . 'StdLib.php';
require_once FW_DIR . 'autoload' . DS . 'AutoLoader.php';

$loader = new AutoLoader();
$loader->register();
$loader->addNamespace('app',      ROOT_DIR . 'app');
$loader->addNamespace('fw',       ROOT_DIR . 'vendor' . DS . 'fw');
$loader->addNamespace('apptable', ROOT_DIR . 'app' . DS . 'database' . DS . 'table');
$loader->addNamespace('database', ROOT_DIR . 'app' . DS . 'database');
$loader->addNamespace('database', ROOT_DIR . 'vendor' . DS . 'fw' . DS . 'database');
$loader->addNamespace('lib',      ROOT_DIR . 'vendor' . DS . 'fw' . DS . 'library');
$loader->addNamespace('shared',   ROOT_DIR . 'app' . DS . 'shared');

session_start();

// =============================================================================
// SIMULATION DATA
// =============================================================================
$events = [
    [
        'label' => 'Event 1 — Delivery · Location 3 · Supplier 1',
        'delay' => 2, 'weight' => 43.65,
        'type' => 'delivery', 'location1_id' => 3, 'location2_id' => null,
        'supplier_id' => 1, 'stock_client_id' => null,
        'movements' => [
            ['stock_id' => 2, 'qty' => 10],
            ['stock_id' => 3, 'qty' => 20],
            ['stock_id' => 4, 'qty' =>  5],
            ['stock_id' => 5, 'qty' => 50],
            ['stock_id' => 6, 'qty' =>  5],
        ],
    ],
    [
        'label' => 'Event 2 — Delivery · Location 3 · Supplier 4',
        'delay' => 2, 'weight' => 37.2,
        'type' => 'delivery', 'location1_id' => 3, 'location2_id' => null,
        'supplier_id' => 4, 'stock_client_id' => null,
        'movements' => [
            ['stock_id' => 2, 'qty' => 25],
            ['stock_id' => 3, 'qty' =>  1],
            ['stock_id' => 4, 'qty' =>  5],
            ['stock_id' => 5, 'qty' => 50],
        ],
    ],
    [
        'label' => 'Event 3 — Transfer · Location 3 → 5',
        'delay' => 2, 'weight' => null,
        'type' => 'transfer', 'location1_id' => 3, 'location2_id' => 5,
        'supplier_id' => null, 'stock_client_id' => null,
        'movements' => [
            ['stock_id' => 2, 'qty' => 15],
            ['stock_id' => 3, 'qty' => 21],
            ['stock_id' => 4, 'qty' => 10],
            ['stock_id' => 5, 'qty' => 10],
            ['stock_id' => 6, 'qty' =>  5],
        ],
    ],
    [
        'label' => 'Event 4 — Adjustment · Location 5',
        'delay' => 2, 'weight' => null,
        'type' => 'adjustment', 'location1_id' => 5, 'location2_id' => null,
        'supplier_id' => null, 'stock_client_id' => null,
        'movements' => [
            ['stock_id' => 2, 'qty' => -2],
            ['stock_id' => 3, 'qty' => -5],
            ['stock_id' => 4, 'qty' => -5],
        ],
    ],
    [
        'label' => 'Event 5 — Stocktake · Location 5',
        'delay' => 7, 'weight' => null,
        'type' => 'stocktake', 'location1_id' => 5, 'location2_id' => null,
        'supplier_id' => null, 'stock_client_id' => null,
        'movements' => [
            ['stock_id' => 2, 'stock_qoh' => 12],
            ['stock_id' => 3, 'stock_qoh' => 17],
            ['stock_id' => 4, 'stock_qoh' =>  0],
            ['stock_id' => 5, 'stock_qoh' =>  2],
            ['stock_id' => 6, 'stock_qoh' =>  2],
        ],
    ],
    [
        'label' => 'Event 6 — Transfer · Location 3 → 5',
        'delay' => 2, 'weight' => null,
        'type' => 'transfer', 'location1_id' => 3, 'location2_id' => 5,
        'supplier_id' => null, 'stock_client_id' => null,
        'movements' => [
            ['stock_id' => 2, 'qty' => 3],
            ['stock_id' => 5, 'qty' => 8],
        ],
    ],
    [
        'label' => 'Event 7 — Delivery · Location 3 · Supplier 1',
        'delay' => 2, 'weight' => 47.9,
        'type' => 'delivery', 'location1_id' => 3, 'location2_id' => null,
        'supplier_id' => 1, 'stock_client_id' => null,
        'movements' => [
            ['stock_id' => 2, 'qty' => 12],
            ['stock_id' => 3, 'qty' =>  4],
            ['stock_id' => 4, 'qty' => 25],
            ['stock_id' => 5, 'qty' => 50],
            ['stock_id' => 6, 'qty' =>  5],
        ],
    ],
    [
        'label' => 'Event 8 — Delivery · Location 5 · Supplier 8',
        'delay' => 2, 'weight' => 7.5,
        'type' => 'delivery', 'location1_id' => 5, 'location2_id' => null,
        'supplier_id' => 8, 'stock_client_id' => null,
        'movements' => [
            ['stock_id' => 2, 'qty' =>  4],
            // stock 3: qty empty — omitted
            ['stock_id' => 4, 'qty' =>  1],
            ['stock_id' => 5, 'qty' => 10],
            // stock 6: qty empty — omitted
        ],
    ],
    [
        'label' => 'Event 9 — Stocktake · Location 5',
        'delay' => 7, 'weight' => null,
        'type' => 'stocktake', 'location1_id' => 5, 'location2_id' => null,
        'supplier_id' => null, 'stock_client_id' => null,
        'movements' => [
            ['stock_id' => 2, 'stock_qoh' => 14],
            ['stock_id' => 3, 'stock_qoh' => 17],
            ['stock_id' => 4, 'stock_qoh' =>  0],
            ['stock_id' => 5, 'stock_qoh' => 13],
            ['stock_id' => 6, 'stock_qoh' =>  0],
        ],
    ],
    [
        'label' => 'Event 10 — Transfer · Location 3 → 5',
        'delay' => 2, 'weight' => null,
        'type' => 'transfer', 'location1_id' => 3, 'location2_id' => 5,
        'supplier_id' => null, 'stock_client_id' => null,
        'movements' => [
            ['stock_id' => 2, 'qty' =>  1],
            ['stock_id' => 3, 'qty' =>  4],
            ['stock_id' => 4, 'qty' => 25],
            ['stock_id' => 6, 'qty' =>  5],
        ],
    ],
    [
        'label' => 'Event 11 — Delivery · Location 3 · Supplier 8',
        'delay' => 2, 'weight' => 13.5,
        'type' => 'delivery', 'location1_id' => 3, 'location2_id' => null,
        'supplier_id' => 8, 'stock_client_id' => null,
        'movements' => [
            ['stock_id' => 2, 'qty' =>  4],
            ['stock_id' => 3, 'qty' => 12],
            // stock 4: qty empty — omitted
            ['stock_id' => 5, 'qty' => 10],
            ['stock_id' => 6, 'qty' =>  5],
        ],
    ],
    [
        'label' => 'Event 12 — Stocktake · Location 5',
        'delay' => 7, 'weight' => null,
        'type' => 'stocktake', 'location1_id' => 5, 'location2_id' => null,
        'supplier_id' => null, 'stock_client_id' => null,
        'movements' => [
            ['stock_id' => 2, 'stock_qoh' =>  6],
            ['stock_id' => 3, 'stock_qoh' => 11],
            ['stock_id' => 4, 'stock_qoh' => 12],
            ['stock_id' => 5, 'stock_qoh' => 13],
            ['stock_id' => 6, 'stock_qoh' =>  1],
        ],
    ],
    [
        'label' => 'Event 13 — Transfer · Location 3 → 5',
        'delay' => 2, 'weight' => null,
        'type' => 'transfer', 'location1_id' => 3, 'location2_id' => 5,
        'supplier_id' => null, 'stock_client_id' => null,
        'movements' => [
            ['stock_id' => 2, 'qty' =>  9],
            ['stock_id' => 3, 'qty' => 12],
            // stocks 4, 5: qty empty — omitted
            ['stock_id' => 6, 'qty' =>  4],
        ],
    ],
    [
        'label' => 'Event 14 — Delivery · Location 3 · Supplier 8',
        'delay' => 2, 'weight' => 10.1,
        'type' => 'delivery', 'location1_id' => 3, 'location2_id' => null,
        'supplier_id' => 8, 'stock_client_id' => null,
        'movements' => [
            ['stock_id' => 2, 'qty' =>  4],
            ['stock_id' => 3, 'qty' =>  4],
            // stock 4: qty empty — omitted
            ['stock_id' => 5, 'qty' => 10],
            ['stock_id' => 6, 'qty' =>  5],
        ],
    ],
    [
        'label' => 'Event 15 — Stocktake · Location 5',
        'delay' => 7, 'weight' => null,
        'type' => 'stocktake', 'location1_id' => 5, 'location2_id' => null,
        'supplier_id' => null, 'stock_client_id' => null,
        'movements' => [
            ['stock_id' => 2, 'stock_qoh' =>  9],
            ['stock_id' => 3, 'stock_qoh' => 15],
            ['stock_id' => 4, 'stock_qoh' =>  3],
            ['stock_id' => 5, 'stock_qoh' =>  9],
            ['stock_id' => 6, 'stock_qoh' =>  0],  // QOH empty → 0
        ],
    ],
    [
        'label' => 'Event 16 — Transfer · Location 3 → 5',
        'delay' => 2, 'weight' => null,
        'type' => 'transfer', 'location1_id' => 3, 'location2_id' => 5,
        'supplier_id' => null, 'stock_client_id' => null,
        'movements' => [
            ['stock_id' => 2, 'qty' => 6],
            ['stock_id' => 3, 'qty' => 4],
            // stock 4: qty empty — omitted
            ['stock_id' => 5, 'qty' => 1],
            ['stock_id' => 6, 'qty' => 5],
        ],
    ],
];

$TOTAL = count($events);

// =============================================================================
// ACTIONS
// =============================================================================

// Use dedicated session keys so this page is independent of simulate.php.
// On a fresh GET (no ?running=1), wipe any previous auto-run state and redirect
// to ?running=1. The POST→redirect→GET chain always carries ?running=1, so only
// a direct navigation to the bare URL triggers the reset.
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET['running'])) {
    unset($_SESSION['auto_next'], $_SESSION['auto_last_time'], $_SESSION['auto_log']);
    header('Location: simulate_auto.php?running=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reset'])) {
        unset($_SESSION['auto_next'], $_SESSION['auto_last_time'], $_SESSION['auto_log']);
        header('Location: simulate_auto.php');
        exit;
    }

    if (isset($_POST['jump'])) {
        $start = max(1, min($TOTAL, (int)($_POST['start_event'] ?? 1)));
        $_SESSION['auto_next']      = $start - 1;
        $_SESSION['auto_last_time'] = 0;
        $_SESSION['auto_log']       = [];
        header('Location: simulate_auto.php?running=1');
        exit;
    }

    if (isset($_POST['run'])) {
        $idx = (int)($_SESSION['auto_next'] ?? 0);
        if ($idx < $TOTAL) {
            $_SESSION['auto_log'][$idx] = runEvent($events[$idx]);
            $_SESSION['auto_next']      = $idx + 1;
            $_SESSION['auto_last_time'] = time();
        }
        header('Location: simulate_auto.php?running=1');
        exit;
    }
}

// =============================================================================
// HELPERS
// =============================================================================
function buildManager(): \app\controller\manager\StockEventManager {
    $db = new \database\MySqlDB();
    $db->connect('localhost', 'root', '', 'stock');

    $eventTable    = new \apptable\StockEventTable();
    $movementTable = new \apptable\StockMovementTable();
    $locationTable = new \apptable\StockLocationTable();
    $stockTable    = new \apptable\StockTable();

    $eventTable->init($db, 1);
    $movementTable->init($db, 1);
    $locationTable->init($db, 1);
    $stockTable->init($db, 1);

    return new \app\controller\manager\StockEventManager(
        $eventTable, $movementTable, $locationTable, $stockTable
    );
}

function runEvent(array $ev): array {
    $mgr = buildManager();
    $log = ['label' => $ev['label'], 'steps' => [], 'ok' => true];

    $event_id = 0;
    $err      = '';
    $ok = $mgr->createevent(
        $ev['type'], $ev['location1_id'], $ev['location2_id'],
        $ev['supplier_id'], $ev['stock_client_id'], $event_id, $err
    );
    if (!$ok) {
        $log['steps'][] = ['ok' => false, 'msg' => "Create event failed: $err"];
        $log['ok'] = false;
        return $log;
    }
    $log['steps'][] = ['ok' => true, 'msg' => "Event created (db id={$event_id})"];

    if ($ev['type'] === 'delivery' && !empty($ev['weight'])) {
        $werr = '';
        if (!$mgr->saveweight($event_id, $ev['weight'], $werr)) {
            $log['steps'][] = ['ok' => false, 'msg' => "Save weight failed: $werr"];
            $log['ok'] = false;
        } else {
            $log['steps'][] = ['ok' => true, 'msg' => "Weight saved ({$ev['weight']} kg)"];
        }
    }

    $is_stocktake = ($ev['type'] === 'stocktake');
    $is_transfer  = ($ev['type'] === 'transfer');

    foreach ($ev['movements'] as $m) {
        $stock_id = $m['stock_id'];
        if ($is_stocktake) {
            $value  = (string)($m['stock_qoh'] ?? 0);
            $loc_id = $ev['location1_id'];
        } elseif ($is_transfer) {
            $value  = (string)abs((float)$m['qty']);
            $loc_id = 0;
        } else {
            $value  = (string)$m['qty'];
            $loc_id = $ev['location1_id'];
        }
        $mid = 0; $merr = '';
        $ok = $mgr->savemovement($stock_id, $event_id, $loc_id, $value, $ev['type'], $mid, $merr);
        $label = $is_stocktake ? "stock_id={$stock_id} count={$value}" : "stock_id={$stock_id} qty={$value}";
        $log['steps'][] = $ok
            ? ['ok' => true,  'msg' => "Movement {$label}"]
            : ['ok' => false, 'msg' => "Movement {$label} failed: $merr"];
        if (!$ok) $log['ok'] = false;
    }

    $cerr = ''; $warn = '';
    $ok = $mgr->closeevent($event_id, true, $cerr, $warn);
    if (!$ok) {
        $log['steps'][] = ['ok' => false, 'msg' => "Close event failed: $cerr"];
        $log['ok'] = false;
    } else {
        $log['steps'][] = ['ok' => true, 'msg' => 'Event closed'];
        if ($warn) $log['steps'][] = ['ok' => 'warn', 'msg' => "Warning: $warn"];
    }

    return $log;
}

// =============================================================================
// DISPLAY STATE
// =============================================================================
$next     = (int)($_SESSION['auto_next']      ?? 0);
$lastTime = (int)($_SESSION['auto_last_time'] ?? 0);
$now      = time();
$done     = ($next >= $TOTAL);
$log      = $_SESSION['auto_log'] ?? [];

$started   = ($lastTime > 0);
$delayMins = (!$done) ? (int)$events[$next]['delay'] : 0;
$delaySecs = $delayMins * 60;
$secsLeft  = ($lastTime === 0) ? 0 : max(0, ($lastTime + $delaySecs) - $now);
$ready     = ($secsLeft === 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Stock Simulation — Auto</title>
<style>
  body { font-family: system-ui, sans-serif; margin: 0; background: #f3f4f6; color: #1f2937; }
  .wrap { max-width: 820px; margin: 2rem auto; padding: 0 1rem; }
  h1 { font-size: 1.4rem; margin-bottom: 0.25rem; }
  .sub { color: #6b7280; font-size: 0.875rem; margin-bottom: 1.5rem; }
  .progress-bar { background: #e5e7eb; border-radius: 8px; height: 10px; margin-bottom: 0.5rem; }
  .progress-fill { background: #2563eb; border-radius: 8px; height: 10px; transition: width 0.3s; }
  .progress-label { font-size: 0.8rem; color: #6b7280; margin-bottom: 1.5rem; }

  .status-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.1); padding: 1.25rem 1.5rem; margin-bottom: 1rem; text-align: center; }
  .status-label { font-size: 0.85rem; color: #6b7280; margin-bottom: 0.25rem; }
  .status-event { font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; }
  .countdown { font-size: 2.5rem; font-weight: 700; color: #2563eb; }
  .running-msg { font-size: 1rem; color: #059669; font-weight: 600; }

  .badge { display: inline-block; font-size: 0.7rem; font-weight: 600; padding: 2px 8px; border-radius: 12px; text-transform: uppercase; }
  .badge-delivery   { background: #dbeafe; color: #1d4ed8; }
  .badge-transfer   { background: #fef9c3; color: #92400e; }
  .badge-adjustment { background: #fce7f3; color: #9d174d; }
  .badge-stocktake  { background: #d1fae5; color: #065f46; }

  .btn-reset { padding: 0.45rem 1.1rem; border-radius: 6px; border: none; font-size: 0.85rem; font-weight: 600; cursor: pointer; background: #fee2e2; color: #b91c1c; }

  .log-entry { border-radius: 6px; padding: 0.75rem 1rem; margin-bottom: 0.75rem; }
  .log-ok  { background: #f0fdf4; border: 1px solid #bbf7d0; }
  .log-err { background: #fef2f2; border: 1px solid #fecaca; }
  .log-title { font-weight: 600; margin-bottom: 0.4rem; font-size: 0.9rem; }
  .log-steps { list-style: none; padding: 0; margin: 0; font-size: 0.82rem; font-family: monospace; }
  .log-steps li { padding: 1px 0; }
  .step-ok   { color: #166534; }
  .step-err  { color: #991b1b; font-weight: 600; }
  .step-warn { color: #92400e; }

  .done-banner { background: #d1fae5; border: 1px solid #6ee7b7; border-radius: 8px; padding: 1.5rem; text-align: center; margin-bottom: 1rem; }
  .done-banner h2 { margin: 0; color: #065f46; }

  /* Hidden auto-submit form */
  #autoForm { display: none; }
</style>
</head>
<body>
<div class="wrap">
  <h1>Stock Simulation Runner — Auto</h1>
  <p class="sub">Runs automatically &bull; per-event delays &bull; real timestamps &bull; keep this tab open</p>

  <div class="progress-bar">
    <div class="progress-fill" style="width:<?= $TOTAL > 0 ? round($next / $TOTAL * 100) : 0 ?>%"></div>
  </div>
  <div class="progress-label"><?= $next ?> of <?= $TOTAL ?> events run</div>

<?php if ($done): ?>
  <div class="done-banner">
    <h2>Simulation complete!</h2>
    <p>All <?= $TOTAL ?> events have been created and closed.</p>
  </div>

<?php else:
    $ev = $events[$next];
?>
  <div class="status-card">
    <div class="status-label">Next event</div>
    <div class="status-event">
      <?= htmlspecialchars($ev['label']) ?>
      &nbsp;<span class="badge badge-<?= $ev['type'] ?>"><?= $ev['type'] ?></span>
    </div>
<?php if (!$started): ?>
    <div class="status-label">Set a start event below, or click Start to begin from event 1.</div>
<?php elseif ($ready): ?>
    <div class="running-msg">&#9654; Running now&hellip;</div>
<?php else: ?>
    <div class="status-label">Starting in</div>
    <div class="countdown" id="cd"><?= gmdate('i:s', $secsLeft) ?></div>
<?php endif; ?>
  </div>

  <!-- Hidden form that auto-submits to run the next event -->
  <form id="autoForm" method="post">
    <input type="hidden" name="run" value="1">
  </form>

<?php if ($started): ?>
  <script>
    (function(){
      var secs = <?= $secsLeft ?>;
<?php if ($ready): ?>
      setTimeout(function(){ document.getElementById('autoForm').submit(); }, 800);
<?php else: ?>
      var cd = document.getElementById('cd');
      function fmt(s) {
        var m = Math.floor(s/60), r = s%60;
        return (m<10?'0':'')+m+':'+(r<10?'0':'')+r;
      }
      var t = setInterval(function(){
        secs--;
        if (secs <= 0) { clearInterval(t); document.getElementById('autoForm').submit(); }
        else { cd.textContent = fmt(secs); }
      }, 1000);
<?php endif; ?>
    })();
  </script>
<?php endif; ?>
<?php endif; ?>

  <!-- Log of completed events (most recent first) -->
<?php if (!empty($log)): ?>
  <h2 style="font-size:1rem; margin-top:1rem">Completed events</h2>
<?php foreach (array_reverse(array_keys($log), true) as $i):
    $entry = $log[$i];
?>
  <div class="log-entry <?= $entry['ok'] ? 'log-ok' : 'log-err' ?>">
    <div class="log-title"><?= htmlspecialchars($entry['label']) ?></div>
    <ul class="log-steps">
<?php foreach ($entry['steps'] as $step):
    $cls  = match($step['ok']) { true => 'step-ok', false => 'step-err', default => 'step-warn' };
    $icon = match($step['ok']) { true => '✓', false => '✗', default => '⚠' };
?>
      <li class="<?= $cls ?>"><?= $icon ?> <?= htmlspecialchars($step['msg']) ?></li>
<?php endforeach; ?>
    </ul>
  </div>
<?php endforeach; ?>
<?php endif; ?>

  <!-- Controls -->
  <div style="margin-top:1rem; display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap">
    <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap">
      <form method="post" style="display:flex; align-items:center; gap:0.5rem">
        <label style="font-size:0.875rem; color:#374151; white-space:nowrap">Start from event:</label>
        <input type="number" name="start_event" min="1" max="<?= $TOTAL ?>"
               style="width:4rem; padding:0.35rem 0.3rem; border-radius:4px; border:1px solid #d1d5db; font-size:0.875rem; text-align:center">
        <button name="jump" style="padding:0.4rem 0.9rem; border-radius:6px; border:none; font-size:0.85rem; font-weight:600; cursor:pointer; background:#2563eb; color:#fff">Go</button>
      </form>
<?php if (!$started && !$done): ?>
      <form method="post">
        <button name="run" style="padding:0.4rem 1.1rem; border-radius:6px; border:none; font-size:0.85rem; font-weight:600; cursor:pointer; background:#059669; color:#fff">&#9654; Start</button>
      </form>
<?php endif; ?>
    </div>
    <form method="post" onsubmit="return confirm('Reset the simulation session? (DB records are NOT deleted)')">
      <button class="btn-reset" name="reset">Start over</button>
    </form>
  </div>
</div>
</body>
</html>
