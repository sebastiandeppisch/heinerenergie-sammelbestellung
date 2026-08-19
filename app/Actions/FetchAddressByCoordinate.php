<?php

declare(strict_types=1);

namespace App\Actions;

use App\ValueObjects\Coordinate;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use maxh\Nominatim\Nominatim;

class FetchAddressByCoordinate
{
    private readonly Nominatim $nominatim;

    private Coordinate $coordinate;

    public function __construct(

    ) {
        $this->nominatim = app(Nominatim::class);
    }

    public function __invoke(Coordinate $coordinate): ?string
    {
        Log::debug('Fetching address for coordinate', ['coordinate' => $coordinate]);
        $this->coordinate = $coordinate;

        return Cache::rememberForever($this->key(), fn (): ?string => $this->handle());
    }

    private function key(): string
    {
        return 'address.'.$this->coordinate->lat.','.$this->coordinate->lng;
    }

    private function handle(): ?string
    {
        $coordinate = $this->coordinate;
        $reverse = $this->nominatim->newReverse()->latlon($coordinate->lat, $coordinate->lng);

        try {
            $result = $this->nominatim->find($reverse);
            Log::debug('Nominatim reverse geocoding result', ['result' => $result, 'coordinate' => $coordinate]);

            return $result['display_name'] ?? null;
        } catch (ClientException $e) {
            Log::error($e->getResponse()->getBody()->getContents());
            throw $e;
        }
    }
}
