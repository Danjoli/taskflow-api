<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Observers\CategoryObserver;
use App\Observers\ProjectObserver;
use App\Observers\TagObserver;
use App\Observers\TaskObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower((string) $request->input('email'));

            return Limit::perMinute(config('taskflow.rate_limits.login_per_minute'))
                ->by("{$email}|{$request->ip()}");
        });

        RateLimiter::for('register', fn (Request $request) => Limit::perHour(
            config('taskflow.rate_limits.register_per_hour')
        )->by("ip:{$request->ip()}"));

        RateLimiter::for('api', function (Request $request) {
            $key = $request->user()
                ? "user:{$request->user()->getAuthIdentifier()}"
                : "ip:{$request->ip()}";

            return Limit::perMinute(config('taskflow.rate_limits.api_per_minute'))
                ->by($key);
        });

        Task::observe(TaskObserver::class);
        Project::observe(ProjectObserver::class);
        Category::observe(CategoryObserver::class);
        Tag::observe(TagObserver::class);
    }
}
