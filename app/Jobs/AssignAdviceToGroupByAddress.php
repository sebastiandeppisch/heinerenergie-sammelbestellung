<?php

namespace App\Jobs;

use App\Actions\FetchCoordinateByAddress;
use App\Models\Advice;
use App\Models\Group;
use App\Models\User;
use App\Notifications\SystemErrorNotification;
use App\Services\AdviceService;
use App\Services\GroupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class AssignAdviceToGroupByAddress implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of attempts
     */
    public $tries = 3;

    /**
     * Backoff between attempts in seconds (exponential: 10s, 20s, 40s)
     */
    public $backoff = [10, 20, 40];

    /**
     * Create a new job instance.
     */
    public function __construct(public Advice $advice)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $advice = $this->advice->fresh();
        $groupService = app(GroupService::class);
        $adviceService = app(AdviceService::class);

        // Check if we already have coordinates
        if ($advice->lat === null || $advice->lng === null) {
            // Use the existing geocoding action
            $advice->coordinate = app(FetchCoordinateByAddress::class)($advice->address);
            $advice->save();
        }

        // Check if geocoding was successful
        if ($advice->coordinate) {
            // Try to find an initiative whose polygon contains these coordinates
            $group = $groupService->findGroupContainingCoordinates($advice->coordinate);

            if ($group) {
                // Assign the advice to the found group
                $groupService->assignAdviceToGroup($advice, $group);

                // Use existing job to notify advisors
                SendNewAdviceInfoToAdvisors::dispatch($advice, $adviceService);
            } else {
                // No initiative found at the coordinates, fallback to postal code
                AssignAdviceToGroupByZipcode::dispatch($advice);
            }
        } else {
            // Geocoding failed, fallback to postal code
            AssignAdviceToGroupByZipcode::dispatch($advice);
        }
    }

    /**
     * Executed on final failure
     */
    public function failed(Throwable $exception)
    {
        // Notify system admins (Users with is_admin = true)
        $systemAdmins = User::where('is_admin', true)->get();

        foreach ($systemAdmins as $admin) {
            $admin->notify(new SystemErrorNotification(
                'AssignAdviceToGroupByAddress failed',
                $exception->getMessage(),
                $this->advice
            ));
        }

        // Try fallback
        AssignAdviceToGroupByZipcode::dispatch($this->advice);
    }
}
