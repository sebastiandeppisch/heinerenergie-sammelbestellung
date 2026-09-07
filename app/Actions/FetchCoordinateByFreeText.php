<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\NominatimUnavailableException;
use App\Services\GeocodeCache;
use App\ValueObjects\Coordinate;
use Illuminate\Support\Facades\Log;
use maxh\Nominatim\Nominatim;
use Throwable;

class FetchCoordinateByFreeText
{
    private readonly Nominatim $nominatim;

    private string $text;

    public function __construct(

    ) {
        $this->nominatim = app(Nominatim::class);
    }

    /**
     * Returns null when OpenStreetMap does not know the location.
     *
     * @throws NominatimUnavailableException if OpenStreetMap could not be reached
     */
    public function __invoke(string $text): ?Coordinate
    {
        Log::debug('Fetching coordinates for text', ['text' => $text]);
        $this->text = $text;

        return app(GeocodeCache::class)->remember($this->key(), fn (): ?Coordinate => $this->handle());
    }

    private function key(): string
    {
        return 'coordinates.text.'.md5($this->text);
    }

    private function handle(): ?Coordinate
    {
        $text = $this->text;
        $search = $this->nominatim->newSearch()
            ->query($text);

        try {
            $result = $this->nominatim->find($search);
        } catch (Throwable $e) {
            Log::error('Nominatim free text search failed', ['text' => $text, 'exception' => $e]);

            throw NominatimUnavailableException::requestFailed($e);
        }

        Log::debug('Nominatim free text search result', ['result' => $result, 'address' => $text]);

        if (count($result) > 0) {
            return Coordinate::fromArray($result[0]);
        }

        return null;
    }
}
