<?php

namespace App\Actions;

use App\ValueObjects\Coordinate;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use maxh\Nominatim\Nominatim;

class FetchCoordinateByFreeText
{
    private readonly Nominatim $nominatim;

    private string $text;

    public function __construct(

    ) {
        $this->nominatim = app(Nominatim::class);
    }

    public function __invoke(string $text): ?Coordinate
    {
        Log::debug('Fetching coordinates for text', ['text' => $text]);
        $this->text = $text;

        return Cache::rememberForever($this->key(), fn () => $this->handle());
    }

    private function key(): string
    {
        return 'coordinates.'.md5($this->text);
    }

    private function handle(): ?Coordinate
    {
        $text = $this->text;
        $search = $this->nominatim->newSearch()
            ->query($text);
        try {
            $result = $this->nominatim->find($search);
            Log::debug('Nominatim free text search result', ['result' => $result, 'address' => $text]);
            if (count($result) > 0) {
                $result = $result[0];

                return Coordinate::fromArray($result);
            }
        } catch (ClientException $e) {
            Log::error($e->getResponse()->getBody()->getContents());
            throw $e;
        }

        return null;

    }
}
