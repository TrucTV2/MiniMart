<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Super admin có toàn quyền.
        Gate::before(fn (User $user) => $user->hasRole(Role::SuperAdmin->value) ? true : null);

        // Bắt lỗi N+1, gán thuộc tính sai... ngay khi dev; tắt trên production.
        Model::shouldBeStrict(! $this->app->isProduction());

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
