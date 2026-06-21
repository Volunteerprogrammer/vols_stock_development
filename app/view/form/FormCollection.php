<?php
namespace app\view\form;
use \lib\StdLib as lib;
class FormCollection {
    private $trace = false;
    private array $instances = [];

    public function __construct(private \fw\factory\ClassFactory $factory) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>\n"; }
    }

    public function LoginForm(): LoginForm {
        return $this->instances[LoginForm::class] ??= $this->factory->getClass(LoginForm::class);
    }
    public function ConfigForm(): ConfigForm {
        return $this->instances[ConfigForm::class] ??= $this->factory->getClass(ConfigForm::class);
    }
    public function MenuitemForm(): MenuitemForm {
        return $this->instances[MenuitemForm::class] ??= $this->factory->getClass(MenuitemForm::class);
    }
    public function StartNewPasswordForm(): StartNewPasswordForm {
        return $this->instances[StartNewPasswordForm::class] ??= $this->factory->getClass(StartNewPasswordForm::class);
    }
    public function EnterNewPasswordForm(): EnterNewPasswordForm {
        return $this->instances[EnterNewPasswordForm::class] ??= $this->factory->getClass(EnterNewPasswordForm::class);
    }
    public function ConfirmCodeForm(): ConfirmCodeForm {
        return $this->instances[ConfirmCodeForm::class] ??= $this->factory->getClass(ConfirmCodeForm::class);
    }
    public function RosterForm(): RosterForm {
        return $this->instances[RosterForm::class] ??= $this->factory->getClass(RosterForm::class);
    }
    public function UserProfileForm(): UserProfileForm {
        return $this->instances[UserProfileForm::class] ??= $this->factory->getClass(UserProfileForm::class);
    }
    public function ClientAdminForm(): ClientAdminForm {
        return $this->instances[ClientAdminForm::class] ??= $this->factory->getClass(ClientAdminForm::class);
    }
    public function ClientVolsForm(): ClientVolsForm {
        return $this->instances[ClientVolsForm::class] ??= $this->factory->getClass(ClientVolsForm::class);
    }
    public function TaskForm(): TaskForm {
        return $this->instances[TaskForm::class] ??= $this->factory->getClass(TaskForm::class);
    }
    public function RoleForm(): RoleForm {
        return $this->instances[RoleForm::class] ??= $this->factory->getClass(RoleForm::class);
    }
    public function ReportForm(): ReportForm {
        return $this->instances[ReportForm::class] ??= $this->factory->getClass(ReportForm::class);
    }
    public function UserForm(): UserForm {
        return $this->instances[UserForm::class] ??= $this->factory->getClass(UserForm::class);
    }
    public function ActionForm(): ActionForm {
        return $this->instances[ActionForm::class] ??= $this->factory->getClass(ActionForm::class);
    }
    public function PageForm(): PageForm {
        return $this->instances[PageForm::class] ??= $this->factory->getClass(PageForm::class);
    }
    public function AttendanceAdminForm(): AttendanceAdminForm {
        return $this->instances[AttendanceAdminForm::class] ??= $this->factory->getClass(AttendanceAdminForm::class);
    }
    public function AttendanceVolsForm(): AttendanceVolsForm {
        return $this->instances[AttendanceVolsForm::class] ??= $this->factory->getClass(AttendanceVolsForm::class);
    }
    public function AttendanceReportForm(): AttendanceReportForm {
        return $this->instances[AttendanceReportForm::class] ??= $this->factory->getClass(AttendanceReportForm::class);
    }
    public function SessionForm(): SessionForm {
        return $this->instances[SessionForm::class] ??= $this->factory->getClass(SessionForm::class);
    }
    public function StockCategoryForm(): StockCategoryForm {
        return $this->instances[StockCategoryForm::class] ??= $this->factory->getClass(StockCategoryForm::class);
    }
    public function StockForm(): StockForm {
        return $this->instances[StockForm::class] ??= $this->factory->getClass(StockForm::class);
    }
    public function StocktakeForm(): StocktakeForm {
        return $this->instances[StocktakeForm::class] ??= $this->factory->getClass(StocktakeForm::class);
    }
    public function DeliveryForm(): DeliveryForm {
        return $this->instances[DeliveryForm::class] ??= $this->factory->getClass(DeliveryForm::class);
    }
    public function StockoutForm(): StockoutForm {
        return $this->instances[StockoutForm::class] ??= $this->factory->getClass(StockoutForm::class);
    }
    public function StockLevelReportForm(): StockLevelReportForm {
        return $this->instances[StockLevelReportForm::class] ??= $this->factory->getClass(StockLevelReportForm::class);
    }
    public function DamagedStockForm(): DamagedStockForm {
        return $this->instances[DamagedStockForm::class] ??= $this->factory->getClass(DamagedStockForm::class);
    }
    public function StockUsageReportForm(): StockUsageReportForm {
        return $this->instances[StockUsageReportForm::class] ??= $this->factory->getClass(StockUsageReportForm::class);
    }
    public function LocationForm(): LocationForm {
        return $this->instances[LocationForm::class] ??= $this->factory->getClass(LocationForm::class);
    }
    public function StockSupplierForm(): StockSupplierForm {
        return $this->instances[StockSupplierForm::class] ??= $this->factory->getClass(StockSupplierForm::class);
    }
    public function StocktakeEventForm(): StocktakeEventForm {
        return $this->instances[StocktakeEventForm::class] ??= $this->factory->getClass(StocktakeEventForm::class);
    }
    public function DeliveryEventForm(): DeliveryEventForm {
        return $this->instances[DeliveryEventForm::class] ??= $this->factory->getClass(DeliveryEventForm::class);
    }
    public function TransferEventForm(): TransferEventForm {
        return $this->instances[TransferEventForm::class] ??= $this->factory->getClass(TransferEventForm::class);
    }
    public function AdjustmentEventForm(): AdjustmentEventForm {
        return $this->instances[AdjustmentEventForm::class] ??= $this->factory->getClass(AdjustmentEventForm::class);
    }
    public function StocktakeVarianceReportForm(): StocktakeVarianceReportForm {
        return $this->instances[StocktakeVarianceReportForm::class] ??= $this->factory->getClass(StocktakeVarianceReportForm::class);
    }
    public function StockClientForm(): StockClientForm {
        return $this->instances[StockClientForm::class] ??= $this->factory->getClass(StockClientForm::class);
    }
    public function DeliveriesReportForm(): DeliveriesReportForm {
        return $this->instances[DeliveriesReportForm::class] ??= $this->factory->getClass(DeliveriesReportForm::class);
    }
    public function StockSupplierCategoryForm(): StockSupplierCategoryForm {
        return $this->instances[StockSupplierCategoryForm::class] ??= $this->factory->getClass(StockSupplierCategoryForm::class);
    }
    public function BelowMinimumReportForm(): BelowMinimumReportForm {
        return $this->instances[BelowMinimumReportForm::class] ??= $this->factory->getClass(BelowMinimumReportForm::class);
    }
}
