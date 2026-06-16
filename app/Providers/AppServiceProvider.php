<?php

namespace App\Providers;

use App\Http\Middleware\EnsureUserActive;
use App\Models\Feedback;
use App\Models\Student;
use App\Policies\FeedbackPolicy;
use App\Policies\StudentPolicy;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(Feedback::class, FeedbackPolicy::class);

        Broadcast::routes(['middleware' => ['web', 'auth', EnsureUserActive::class]]);
    }
}
