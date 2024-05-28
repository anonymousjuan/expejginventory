<?php

namespace App\Providers;

use BranchManagement\BranchManagementService;
use BranchManagementContracts\IBranchManagementService;
use IdentityAndAccess\IdentityAndAccessService;
use IdentityAndAccessContracts\IIdentityAndAccessService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use ProductManagement\EventHandlers\GenerateSku;
use ProductManagement\ProductManagementService;
use ProductManagementContracts\IProductManagementService;
use StockManagement\StockManagementService;
use StockManagementContracts\IStockManagementService;
use SupplierManagement\SupplierManagementService;
use SupplierManagementContracts\ISupplierManagementService;
use Transfer\TransferService;
use TransferContracts\ITransferService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->bind(IIdentityAndAccessService::class, IdentityAndAccessService::class);
        $this->app->bind(IBranchManagementService::class, BranchManagementService::class);
        $this->app->bind(ISupplierManagementService::class, SupplierManagementService::class);
        $this->app->bind(IProductManagementService::class, ProductManagementService::class);
        $this->app->bind(IStockManagementService::class, StockManagementService::class);
        $this->app->bind(ITransferService::class, TransferService::class);

        Event::subscribe(GenerateSku::class);
    }
}
