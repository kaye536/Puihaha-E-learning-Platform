<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('pagination.custom');

        // RBAC: every permission in config/rbac.php becomes a Gate (@can / can: middleware).
        foreach (array_keys(config('rbac.permissions', [])) as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }

        // Public application form: 3 submissions/minute and 10/hour per IP address.
        RateLimiter::for('membership-apply', fn (Request $request) => [
            Limit::perMinute(3)->by($request->ip()),
            Limit::perHour(10)->by($request->ip()),
        ]);

        // Usage in Blade: @money($transaction->amount)
        Blade::directive('money', function ($expression) {
            return '<?php echo e(\\App\\Support\\Format::money('.$expression.')); ?>';
        });
    }
}
