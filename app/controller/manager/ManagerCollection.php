<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class ManagerCollection {
    private $trace = false;
    private array $instances = [];

    public function __construct(private \fw\factory\ClassFactory $factory) {
        if ($this->trace) { echo "Visit ".__METHOD__."<br>\n"; }
    }

    public function MenuManager(): MenuManager {
        return $this->instances[MenuManager::class] ??= $this->factory->getClass(MenuManager::class);
    }
    public function LogManager(): LogManager {
        return $this->instances[LogManager::class] ??= $this->factory->getClass(LogManager::class);
    }
    public function ConfigManager(): ConfigManager {
        return $this->instances[ConfigManager::class] ??= $this->factory->getClass(ConfigManager::class);
    }
    public function UserManager(): UserManager {
        return $this->instances[UserManager::class] ??= $this->factory->getClass(UserManager::class);
    }
    public function ClientManager(): ClientManager {
        return $this->instances[ClientManager::class] ??= $this->factory->getClass(ClientManager::class);
    }
    public function TaskManager(): TaskManager {
        return $this->instances[TaskManager::class] ??= $this->factory->getClass(TaskManager::class);
    }
    public function RosterManager(): RosterManager {
        return $this->instances[RosterManager::class] ??= $this->factory->getClass(RosterManager::class);
    }
    public function RoleManager(): RoleManager {
        return $this->instances[RoleManager::class] ??= $this->factory->getClass(RoleManager::class);
    }
    public function PageManager(): PageManager {
        return $this->instances[PageManager::class] ??= $this->factory->getClass(PageManager::class);
    }
    public function ActionManager(): ActionManager {
        return $this->instances[ActionManager::class] ??= $this->factory->getClass(ActionManager::class);
    }
    public function SessionManager(): SessionManager {
        return $this->instances[SessionManager::class] ??= $this->factory->getClass(SessionManager::class);
    }
    public function EMailManager(): EMailManager {
        return $this->instances[EMailManager::class] ??= $this->factory->getClass(EMailManager::class);
    }
    public function ReportManager(): ReportManager {
        return $this->instances[ReportManager::class] ??= $this->factory->getClass(ReportManager::class);
    }
    public function StockCategoryManager(): StockCategoryManager {
        return $this->instances[StockCategoryManager::class] ??= $this->factory->getClass(StockCategoryManager::class);
    }
    public function StockManager(): StockManager {
        return $this->instances[StockManager::class] ??= $this->factory->getClass(StockManager::class);
    }
    public function StocktakeManager(): StocktakeManager {
        return $this->instances[StocktakeManager::class] ??= $this->factory->getClass(StocktakeManager::class);
    }
    public function DeliveryManager(): DeliveryManager {
        return $this->instances[DeliveryManager::class] ??= $this->factory->getClass(DeliveryManager::class);
    }
    public function StockoutManager(): StockoutManager {
        return $this->instances[StockoutManager::class] ??= $this->factory->getClass(StockoutManager::class);
    }
    public function StockLevelReportManager(): StockLevelReportManager {
        return $this->instances[StockLevelReportManager::class] ??= $this->factory->getClass(StockLevelReportManager::class);
    }
    public function DamagedStockManager(): DamagedStockManager {
        return $this->instances[DamagedStockManager::class] ??= $this->factory->getClass(DamagedStockManager::class);
    }
    public function StockUsageReportManager(): StockUsageReportManager {
        return $this->instances[StockUsageReportManager::class] ??= $this->factory->getClass(StockUsageReportManager::class);
    }
    public function LocationManager(): LocationManager {
        return $this->instances[LocationManager::class] ??= $this->factory->getClass(LocationManager::class);
    }
    public function StockSupplierManager(): StockSupplierManager {
        return $this->instances[StockSupplierManager::class] ??= $this->factory->getClass(StockSupplierManager::class);
    }
    public function StockEventManager(): StockEventManager {
        return $this->instances[StockEventManager::class] ??= $this->factory->getClass(StockEventManager::class);
    }
    public function StocktakeVarianceReportManager(): StocktakeVarianceReportManager {
        return $this->instances[StocktakeVarianceReportManager::class] ??= $this->factory->getClass(StocktakeVarianceReportManager::class);
    }
    public function StockClientManager(): StockClientManager {
        return $this->instances[StockClientManager::class] ??= $this->factory->getClass(StockClientManager::class);
    }
    public function DeliveriesReportManager(): DeliveriesReportManager {
        return $this->instances[DeliveriesReportManager::class] ??= $this->factory->getClass(DeliveriesReportManager::class);
    }
    public function StockSupplierCategoryManager(): StockSupplierCategoryManager {
        return $this->instances[StockSupplierCategoryManager::class] ??= $this->factory->getClass(StockSupplierCategoryManager::class);
    }
    public function BelowMinimumReportManager(): BelowMinimumReportManager {
        return $this->instances[BelowMinimumReportManager::class] ??= $this->factory->getClass(BelowMinimumReportManager::class);
    }
    public function HelpManager(): HelpManager {
        return $this->instances[HelpManager::class] ??= $this->factory->getClass(HelpManager::class);
    }
}
