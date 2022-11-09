<?php

namespace App\Jobs;

use App\Models\Advice;
use Illuminate\Bus\Queueable;
use maxh\Nominatim\Nominatim;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

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
        $street = sprintf("%s %s", $advice->street, $advice->streetNumber);
        $search = $nominatim->newSearch()
            ->country('Deutschland')
            ->postalCode($advice->zip)
            ->street($street);
        $result = $nominatim->find($search);
        if(count($result) > 0){
            $result = $result[0];
            $advice->lat = $result['lat'];
            $advice->long = $result['lon'];
            $advice->save();
        }
    }
}
