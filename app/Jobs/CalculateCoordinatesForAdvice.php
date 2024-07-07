<?php

namespace App\Jobs;

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
        $result = Cache::rememberForever($this->key(), function () use ($advice) {
            return $this->fetchCoordinates($advice->street, $advice->streetNumber, $advice->zip);
        });

        if($result) {
            $advice->lat = $result['lat'];
            $advice->long = $result['lon'];
            $advice->save();
        }
    }

    private function key(): string
    {
        return 'advice.coordinates.'.md5($this->advice->street.$this->advice->streetNumber.$this->advice->zip);
    }

    private function fetchCoordinates(string $street, string $streetNumber, int $zip): ?array
    {
        $nominatim = new Nominatim('https://nominatim.openstreetmap.org/');
        $search = $nominatim->newSearch()
            ->country('Deutschland')
            ->postalCode($zip)
            ->street(sprintf('%s %s', $street, $streetNumber));

        try{
            $result = $nominatim->find($search);
            if (count($result) > 0) {
                return $result[0];
            }
            return null;
        }catch(ClientException $e) {
            Log::error($e->getResponse()->getBody()->getContents());
            throw $e;
        }
        return null;
    }
}
