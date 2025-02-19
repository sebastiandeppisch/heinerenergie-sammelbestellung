<?php

namespace App\Jobs;

use App\Actions\FetchCoordinateByAddress;
use App\Models\Advice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateCoordinatesForAdvice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public Advice $advice)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $advice = $this->advice->fresh();

        $advice->coordinate = app(FetchCoordinateByAddress::class)($advice->address);
        $advice->save();
    }
}
