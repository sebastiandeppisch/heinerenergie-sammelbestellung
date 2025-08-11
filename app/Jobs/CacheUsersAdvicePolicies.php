<?php

namespace App\Jobs;

use App\Context\GroupContextContract;
use App\Models\Advice;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CacheUsersAdvicePolicies implements ShouldQueue
{
    use Dispatchable;

    public function __construct(public User $user, public GroupContextContract $groupContext)
    {
        //
    }

    public function handle(): void
    {
        app()->instance(GroupContextContract::class, $this->groupContext);

        foreach (Advice::all() as $advice) {
            $this->user->can('view', $advice);
            $this->user->can('viewDataProtected', $advice);
        }
    }
}
