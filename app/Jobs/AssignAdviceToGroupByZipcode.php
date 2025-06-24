<?php

namespace App\Jobs;

use App\Actions\FetchCoordinateByFreeText;
use App\Models\Advice;
use App\Models\Group;
use App\Models\User;
use App\Notifications\NewAdviceAssignedToGroup;
use App\Notifications\SystemErrorNotification;
use App\Services\GroupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class AssignAdviceToGroupByZipcode implements ShouldQueue
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

        // Geocode postal code with FetchCoordinateByFreeText
        $zipCoordinate = app(FetchCoordinateByFreeText::class)($advice->zip);

        if ($zipCoordinate) {
            // Find the nearest main group (parent_id is null)
            $mainGroup = $groupService->findNearestMainGroup($zipCoordinate);

            if ($mainGroup) {
                // Assign the advice to the main group
                $groupService->assignAdviceToGroup($advice, $mainGroup);

                // Notify the admin of the main group
                $this->notifyGroupAdmin($mainGroup, $advice);
            } else {
                // No main group found - should be rare
                $this->notifySystemAdmins("No main group found for postal code: {$advice->zip}");
            }
        } else {
            // Even postal code geocoding failed - notify system admin
            $this->notifySystemAdmins("Postal code geocoding failed for advice ID: {$advice->id}");
        }
    }

    /**
     * Notify the administrators of a group
     */
    private function notifyGroupAdmin(Group $group, Advice $advice)
    {
        $group->load('admins');
        foreach ($group->admins as $admin) {
            // Create new admin notification
            $admin->notify(new NewAdviceAssignedToGroup($advice, $group));
        }
    }

    /**
     * Notify the system administrators
     */
    private function notifySystemAdmins(string $message)
    {
        // Get system admins (Users with is_admin = true)
        $systemAdmins = User::where('is_admin', true)->get();

        foreach ($systemAdmins as $admin) {
            $admin->notify(new SystemErrorNotification(
                'Problem with advice assignment',
                $message,
                $this->advice
            ));
        }
    }

    /**
     * Executed on final failure
     */
    public function failed(Throwable $exception)
    {
        // Notify system admins on final failure
        $systemAdmins = User::where('is_admin', true)->get();

        foreach ($systemAdmins as $admin) {
            $admin->notify(new SystemErrorNotification(
                'AssignAdviceToGroupByZipcode failed',
                $exception->getMessage(),
                $this->advice
            ));
        }
    }
}
