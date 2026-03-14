<?php

namespace App\Providers;

use App\Models\Poll;
use App\Policies\PollPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Poll::class => PollPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
