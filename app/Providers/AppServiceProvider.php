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
        
        // مشاركة متغيرات اللغة مع جميع الصفحات
        view()->composer('*', function ($view) {
            $currentLocale = session('locale', config('app.locale', 'ar'));
            $view->with('currentLocale', $currentLocale);
            $view->with('isRTL', $currentLocale === 'ar');
            $view->with('direction', $currentLocale === 'ar' ? 'rtl' : 'ltr');
            
            // تعيين اللغة للتطبيق
            app()->setLocale($currentLocale);
        });
        
        // تسجيل Observers لتسجيل الأنشطة
        Project::observe(ProjectObserver::class);
        Employee::observe(EmployeeObserver::class);
        Client::observe(ClientObserver::class);
    }
}
