<?php
// =============================================================================
// Smoke test: FormCollection and ManagerCollection lazy-loading via ClassFactory
//
// Verifies that every getter on both collections returns an object of the
// correct type. Does NOT require a live DB connection — classes are
// instantiated structurally by the factory (with unconnected DB stubs).
//
// Run from project root: php tests/smoke_collections.php
// =============================================================================

$tc = 0;
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_DIR', dirname(__DIR__) . DS);
define('FW_DIR',  ROOT_DIR . 'vendor' . DS . 'fw' . DS);
define('APP_DIR', ROOT_DIR . 'app' . DS);

require FW_DIR . 'bootstrap/bootstrap.php';

function gtab($direction = 0) {}
function datetimestring($dt) { return $dt->format('Y-m-d H:i:s'); }

// ---- Test harness ------------------------------------------------------------
$pass = 0; $fail = 0;

function check(string $label, bool $result): void {
    global $pass, $fail;
    if ($result) {
        echo "  PASS: $label\n";
        $pass++;
    } else {
        echo "  FAIL: $label\n";
        $fail++;
    }
}

function checkInstance(string $label, mixed $obj, string $expectedClass): void {
    if ($obj === null) {
        echo "  FAIL: $label — returned null\n";
        global $fail; $fail++;
        return;
    }
    check($label, $obj instanceof $expectedClass);
}

// ---- Bootstrap the factory ---------------------------------------------------
$factory = new \fw\factory\ClassFactory();

// ---- FormCollection ---------------------------------------------------------
echo "\n=== FormCollection ===\n";

$forms = new \app\view\form\FormCollection($factory);
check('FormCollection instantiated', $forms instanceof \app\view\form\FormCollection);

$formTests = [
    'LoginForm'                    => \app\view\form\LoginForm::class,
    'ConfigForm'                   => \app\view\form\ConfigForm::class,
    'MenuitemForm'                 => \app\view\form\MenuitemForm::class,
    'StartNewPasswordForm'         => \app\view\form\StartNewPasswordForm::class,
    'EnterNewPasswordForm'         => \app\view\form\EnterNewPasswordForm::class,
    'ConfirmCodeForm'              => \app\view\form\ConfirmCodeForm::class,
    'RosterForm'                   => \app\view\form\RosterForm::class,
    'UserProfileForm'              => \app\view\form\UserProfileForm::class,
    'ClientAdminForm'              => \app\view\form\ClientAdminForm::class,
    'ClientVolsForm'               => \app\view\form\ClientVolsForm::class,
    'TaskForm'                     => \app\view\form\TaskForm::class,
    'RoleForm'                     => \app\view\form\RoleForm::class,
    'ReportForm'                   => \app\view\form\ReportForm::class,
    'UserForm'                     => \app\view\form\UserForm::class,
    'ActionForm'                   => \app\view\form\ActionForm::class,
    'PageForm'                     => \app\view\form\PageForm::class,
    'AttendanceAdminForm'          => \app\view\form\AttendanceAdminForm::class,
    'AttendanceVolsForm'           => \app\view\form\AttendanceVolsForm::class,
    'AttendanceReportForm'         => \app\view\form\AttendanceReportForm::class,
    'SessionForm'                  => \app\view\form\SessionForm::class,
    'StockCategoryForm'            => \app\view\form\StockCategoryForm::class,
    'StockForm'                    => \app\view\form\StockForm::class,
    'StocktakeForm'                => \app\view\form\StocktakeForm::class,
    'DeliveryForm'                 => \app\view\form\DeliveryForm::class,
    'StockoutForm'                 => \app\view\form\StockoutForm::class,
    'StockLevelReportForm'         => \app\view\form\StockLevelReportForm::class,
    'DamagedStockForm'             => \app\view\form\DamagedStockForm::class,
    'StockUsageReportForm'         => \app\view\form\StockUsageReportForm::class,
    'LocationForm'                 => \app\view\form\LocationForm::class,
    'StockSupplierForm'            => \app\view\form\StockSupplierForm::class,
    'StocktakeEventForm'           => \app\view\form\StocktakeEventForm::class,
    'DeliveryEventForm'            => \app\view\form\DeliveryEventForm::class,
    'TransferEventForm'            => \app\view\form\TransferEventForm::class,
    'AdjustmentEventForm'          => \app\view\form\AdjustmentEventForm::class,
    'StocktakeVarianceReportForm'  => \app\view\form\StocktakeVarianceReportForm::class,
    'StockClientForm'              => \app\view\form\StockClientForm::class,
    'DeliveriesReportForm'         => \app\view\form\DeliveriesReportForm::class,
    'StockSupplierCategoryForm'    => \app\view\form\StockSupplierCategoryForm::class,
    'BelowMinimumReportForm'       => \app\view\form\BelowMinimumReportForm::class,
];

foreach ($formTests as $method => $expectedClass) {
    try {
        $obj = $forms->$method();
        checkInstance($method, $obj, $expectedClass);
    } catch (\Throwable $e) {
        echo "  FAIL: $method — threw " . get_class($e) . ": " . $e->getMessage() . "\n";
        global $fail; $fail++;
    }
}

// ---- ManagerCollection ------------------------------------------------------
echo "\n=== ManagerCollection ===\n";

$managers = new \app\controller\manager\ManagerCollection($factory);
check('ManagerCollection instantiated', $managers instanceof \app\controller\manager\ManagerCollection);

$managerTests = [
    'MenuManager'                      => \app\controller\manager\MenuManager::class,
    'LogManager'                       => \app\controller\manager\LogManager::class,
    'ConfigManager'                    => \app\controller\manager\ConfigManager::class,
    'UserManager'                      => \app\controller\manager\UserManager::class,
    'ClientManager'                    => \app\controller\manager\ClientManager::class,
    'TaskManager'                      => \app\controller\manager\TaskManager::class,
    'RosterManager'                    => \app\controller\manager\RosterManager::class,
    'RoleManager'                      => \app\controller\manager\RoleManager::class,
    'PageManager'                      => \app\controller\manager\PageManager::class,
    'ActionManager'                    => \app\controller\manager\ActionManager::class,
    'SessionManager'                   => \app\controller\manager\SessionManager::class,
    'EMailManager'                     => \app\controller\manager\EMailManager::class,
    'ReportManager'                    => \app\controller\manager\ReportManager::class,
    'StockCategoryManager'             => \app\controller\manager\StockCategoryManager::class,
    'StockManager'                     => \app\controller\manager\StockManager::class,
    'StocktakeManager'                 => \app\controller\manager\StocktakeManager::class,
    'DeliveryManager'                  => \app\controller\manager\DeliveryManager::class,
    'StockoutManager'                  => \app\controller\manager\StockoutManager::class,
    'StockLevelReportManager'          => \app\controller\manager\StockLevelReportManager::class,
    'DamagedStockManager'              => \app\controller\manager\DamagedStockManager::class,
    'StockUsageReportManager'          => \app\controller\manager\StockUsageReportManager::class,
    'LocationManager'                  => \app\controller\manager\LocationManager::class,
    'StockSupplierManager'             => \app\controller\manager\StockSupplierManager::class,
    'StockEventManager'                => \app\controller\manager\StockEventManager::class,
    'StocktakeVarianceReportManager'   => \app\controller\manager\StocktakeVarianceReportManager::class,
    'StockClientManager'               => \app\controller\manager\StockClientManager::class,
    'DeliveriesReportManager'          => \app\controller\manager\DeliveriesReportManager::class,
    'StockSupplierCategoryManager'     => \app\controller\manager\StockSupplierCategoryManager::class,
    'BelowMinimumReportManager'        => \app\controller\manager\BelowMinimumReportManager::class,
];

foreach ($managerTests as $method => $expectedClass) {
    try {
        $obj = $managers->$method();
        checkInstance($method, $obj, $expectedClass);
    } catch (\Throwable $e) {
        echo "  FAIL: $method — threw " . get_class($e) . ": " . $e->getMessage() . "\n";
        global $fail; $fail++;
    }
}

// ---- BodyCollection ---------------------------------------------------------
echo "\n=== BodyCollection ===\n";

$bodies = new \app\view\body\BodyCollection($factory);
check('BodyCollection instantiated', $bodies instanceof \app\view\body\BodyCollection);
checkInstance('LoginBody',   $bodies->LoginBody(),   \app\view\body\LoginBody::class);
checkInstance('StandardBody',$bodies->StandardBody(),\app\view\body\StandardBody::class);

// ---- Caching check ----------------------------------------------------------
echo "\n=== Caching (same instance returned on second call) ===\n";
check('FormCollection caches LoginForm',        $forms->LoginForm()        === $forms->LoginForm());
check('FormCollection caches StockForm',        $forms->StockForm()        === $forms->StockForm());
check('ManagerCollection caches ClientManager', $managers->ClientManager() === $managers->ClientManager());
check('ManagerCollection caches StockManager',  $managers->StockManager()  === $managers->StockManager());
check('BodyCollection caches LoginBody',        $bodies->LoginBody()       === $bodies->LoginBody());
check('BodyCollection caches StandardBody',     $bodies->StandardBody()    === $bodies->StandardBody());

// ---- Summary ----------------------------------------------------------------
echo "\n" . str_repeat('=', 50) . "\n";
echo "PASSED: $pass   FAILED: $fail\n";
exit($fail > 0 ? 1 : 0);
