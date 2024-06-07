<?php

namespace App\Jobs;

use App\Models\Advice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use maxh\Nominatim\Nominatim;

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
        $nominatim = new Nominatim('https://nominatim.openstreetmap.org/');
        $street = sprintf('%s %s', $advice->street, $advice->streetNumber);
        $search = $nominatim->newSearch()
            ->country('Deutschland')
            ->postalCode($advice->zip)
            ->street($street);
        $result = $nominatim->find($search);
        if (count($result) > 0) {
            $result = $result[0];
            $advice->lat = $result['lat'];
            $advice->long = $result['lon'];
            $advice->save();
        }
    }
}
