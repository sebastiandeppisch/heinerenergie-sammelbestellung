<?php
namespace App\Imports;

use Exception;
use App\Enums\AdviceType;
use App\Models\Advice;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

class AdvicesImport implements ToModel, WithHeadingRow
{
	public int $statusId;

	public function __construct()
	{
		HeadingRowFormatter::extend('custom', function($value) {
			return Str::lower($value);
		});
	}
	

    public function model(array $row)
    {	
		if($row['vorname'] === null && $row['nachname'] === null) {
			return null;
		}

		$street = explode(' ', $row['strasse']);
		$streetNumber = array_pop($street);
		$street = implode(' ', $street);

		$type = $row['beratungswunsch'];
		if(Str::contains($type, 'telefonisch')) {
			$type = AdviceType::Virtual;
		} elseif(Str::contains($type, 'direkt')) {
			$type = AdviceType::DirectOrder;
		} elseif(Str::contains($type, 'vor Ort')) {
			$type = AdviceType::Home;
		} else {
			throw new Exception('Unknown advice type: ' . $type);
		}

        $advice =  new Advice([
			'firstName' => $row['vorname'],
			'lastName' => $row['nachname'],
			'street' => $street,
			'streetNumber' => $streetNumber,
			'zip' => $row['plz'],
			'city' => $row['wohnort'],
			'email' => $row['email'],
			'phone' => $row['telefon'],
			'commentary' => $row['kommentare_besteller'],
			'type' => $type,
			'advice_status_id' => $this->statusId,
        ]);

		return $advice;
    }
}
