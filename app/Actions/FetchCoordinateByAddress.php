<?php

namespace App\Actions;

use GuzzleHttp\Client;
use App\ValueObjects\Address;
use maxh\Nominatim\Nominatim;
use App\ValueObjects\Coordinate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Exception\ClientException;

class FetchCoordinateByAddress
{
	private readonly Nominatim $nominatim;

	private Address $address;

	public function __construct(
		
	) {
		$this->nominatim = app(Nominatim::class);
	}

	public function __invoke(Address $address): ?Coordinate
	{
		Log::debug('Fetching coordinates for address', ['address' => $address]);
		$this->address = $address;
		return Cache::rememberForever($this->key(), fn() => $this->handle());
	}

	private function key(): string
	{
		return 'coordinates.' . $this->address->hash();
	}

	private function handle(): ?Coordinate
	{
		$address = $this->address;
		$search = $this->nominatim->newSearch()
			->country('Deutschland')
			->postalCode($address->zip)
			->street($address->streetWithNumber())
			->city($address->city);

		try{
			$result = $this->nominatim->find($search);
			Log::debug('Nominatim search result', ['result' => $result, 'address' => $address]);
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
