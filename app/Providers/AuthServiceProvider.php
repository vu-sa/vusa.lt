<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\Calendar;
use App\Models\Duty;
use App\Models\DutyInstitution;
use App\Models\DutyUser;
use App\Models\MainPage;
use App\Models\Navigation;
use App\Models\News;
use App\Models\Page;
use App\Models\SaziningaiExam;
use App\Models\SaziningaiExamFlow;
use App\Models\SaziningaiExamObserver;
use App\Policies\BannersPolicy;
use App\Models\User;
use App\Policies\CalendarPolicy;
use App\Policies\DutiesPolicy;
use App\Policies\DutyUserPolicy;
use App\Policies\DutyInstitutionsPolicy;
use App\Policies\MainPagePolicy;
use App\Policies\NavigationPolicy;
use App\Policies\NewsPolicy;
use App\Policies\PagesPolicy;
use App\Policies\SaziningaiExamFlowsPolicy;
use App\Policies\SaziningaiExamObserversPolicy;
use App\Policies\SaziningaiExamPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Banner::class => BannersPolicy::class,
        Calendar::class => CalendarPolicy::class,
        Duty::class => DutiesPolicy::class,
        DutyUser::class => DutyUserPolicy::class,
        DutyInstitution::class => DutyInstitutionsPolicy::class,
        MainPage::class => MainPagePolicy::class,
        News::class => NewsPolicy::class,
        Navigation::class => NavigationPolicy::class,
        Page::class => PagesPolicy::class,
        SaziningaiExam::class => SaziningaiExamPolicy::class,
        SaziningaiExamFlow::class => SaziningaiExamFlowsPolicy::class,
        SaziningaiExamObserver::class => SaziningaiExamObserversPolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Implicitly grant "Super Admin" role all permissions
        // This works in the app by using gate-related functions like auth()->user->can() and @can()
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }
}
