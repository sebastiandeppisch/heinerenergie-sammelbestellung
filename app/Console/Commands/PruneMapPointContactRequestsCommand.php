<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\MapPointContactRequest;
use Illuminate\Console\Command;

class PruneMapPointContactRequestsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'map-contact:prune';

    /**
     * @var string
     */
    protected $description = 'Delete contact requests to map points once their retention period is over';

    public function handle(): int
    {
        $confirmed = MapPointContactRequest::query()
            ->whereNotNull('confirmed_at')
            ->where('created_at', '<', now()->subDays((int) config('map_contact.retention.confirmed_days')))
            ->delete();

        $unconfirmed = MapPointContactRequest::query()
            ->whereNull('confirmed_at')
            ->where('created_at', '<', now()->subDays((int) config('map_contact.retention.unconfirmed_days')))
            ->delete();

        $this->info("Deleted {$confirmed} confirmed and {$unconfirmed} unconfirmed contact requests.");

        return Command::SUCCESS;
    }
}
