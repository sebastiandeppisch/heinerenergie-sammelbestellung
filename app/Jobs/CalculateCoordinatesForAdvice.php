<?php

namespace App\Jobs;

use App\Actions\FetchCoordinate;
use App\Models\Advice;
use Illuminate\Bus\Queueable;
use maxh\Nominatim\Nominatim;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

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
        
        $advice->coordinate = app(FetchCoordinate::class)($advice->address);
        $advice->save();
    }
}
