<?php

declare(strict_types=1);

namespace App\Mail\MapPointContact;

use App\Context\FixedGroupContext;
use App\Context\GroupContextContract;
use App\Models\MapPoint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Wnx\Sends\Support\StoreMailables;

/**
 * Renders in the context of the point's initiative, so app_name() and the layout show its name.
 */
abstract class MapPointContactMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;
    use StoreMailables;

    public string $initiative;

    public function __construct(public MapPoint $mapPoint)
    {
        $this->initiative = $mapPoint->group->name;
    }

    abstract protected function compose(): static;

    public function build(): static
    {
        $previousContext = app()->bound(GroupContextContract::class)
            ? app(GroupContextContract::class)
            : null;

        app()->instance(GroupContextContract::class, new FixedGroupContext($this->mapPoint->group));

        $this->withSymfonyMessage(function () use ($previousContext): void {
            if ($previousContext !== null) {
                app()->instance(GroupContextContract::class, $previousContext);
            } else {
                app()->forgetInstance(GroupContextContract::class);
            }
        });

        $this->storeClassName()->associateWith($this->mapPoint);

        return $this->compose();
    }
}
