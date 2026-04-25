<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Transfer;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Policies\CrudPolicy;
use App\Policies\TransactionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        Category::class => CrudPolicy::class,
        Brand::class => CrudPolicy::class,
        Unit::class => CrudPolicy::class,
        Warehouse::class => CrudPolicy::class,
        Supplier::class => CrudPolicy::class,
        Customer::class => CrudPolicy::class,
        Product::class => CrudPolicy::class,
        Stock::class => CrudPolicy::class,
        StockMovement::class => CrudPolicy::class,
        Purchase::class => TransactionPolicy::class,
        Sale::class => TransactionPolicy::class,
        Transfer::class => TransactionPolicy::class,
    ];

    public function boot(): void
    {
        Gate::before(function ($user): ?bool {
            return $user?->isAdmin() ? true : null;
        });

        $this->registerPolicies();
    }
}
