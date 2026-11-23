<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use App\Helpers\LocaleHelper;
use App\Models\Project;
use App\Models\Employee;
use App\Models\Client;
use App\Observers\ProjectObserver;
use App\Observers\EmployeeObserver;
use App\Observers\ClientObserver;
use App\Policies\ProjectPolicy;

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
        // تسجيل Policies
        Gate::policy(Project::class, ProjectPolicy::class);
        
        // إضافة helper functions للتعامل مع اللغة
        Blade::directive('currentLocale', function () {
            return "<?php echo App\Helpers\LocaleHelper::getCurrentLocale(); ?>";
        });
        
        Blade::directive('isRTL', function () {
            return "<?php echo App\Helpers\LocaleHelper::isRTL() ? 'rtl' : 'ltr'; ?>";
        });
        
        // مشاركة متغيرات اللغة مع جميع الصفحات مرة واحدة لكل طلب
        $sharedOnce = false;
        view()->composer('*', function () use (&$sharedOnce) {
            if ($sharedOnce) {
                return;
            }
            $sharedOnce = true;
            $currentLocale = session('locale', config('app.locale', 'ar'));
            app()->setLocale($currentLocale);
            \Illuminate\Support\Facades\View::share([
                'currentLocale' => $currentLocale,
                'isRTL'         => $currentLocale === 'ar',
                'direction'     => $currentLocale === 'ar' ? 'rtl' : 'ltr',
            ]);
        });
        
        // تسجيل Observers لتسجيل الأنشطة
        Project::observe(ProjectObserver::class);
        Employee::observe(EmployeeObserver::class);
        Client::observe(ClientObserver::class);
    }
}
