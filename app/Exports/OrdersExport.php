<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{

    private $structure = [
        ['key' => 'id', 'heading' => 'Fortlaufende Nummer'],
        ['key' => 'firstName', 'heading' => 'Vorname'],
        ['key' => 'lastName', 'heading' => 'Nachname'],
        ['key' => 'streetWithNumber', 'heading' => 'Straße'],
        ['key' => 'zip', 'heading' => 'PLZ'],
        ['key' => 'city', 'heading' => 'Wohnort'],
        ['key' => 'email', 'heading' => 'Email'],
        ['key' => 'phone', 'heading' => 'Telefon'],
        ['key' => 'product.name', 'heading' => 'Artikel-Bezeichnung'],
        ['key' => 'product.quantity', 'heading' => 'Anzahl'],
        ['key' => 'product.sku', 'heading' => 'Artikel-Nr'],
        ['key' => 'product.price', 'heading' => 'Gesamtpreis'],
    ];

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $rows = collect();
        foreach(Order::all() as $order){
            foreach($order->orderItems as $orderItem){
                $row = collect();
                foreach(collect($this->structure)->pluck('key') as $key){
                    $row->push($this->getData($key, $order, $orderItem));
                }
                $rows->push($row);
            }
        }
        return $rows;
    }

    private function getData(string $key, Order $order, OrderItem $orderItem){
        if(Str::of($key)->startsWith('product.')){
            $productKey = Str::replace('product.', '', $key);
            if($productKey === 'quantity'){
                return $orderItem->quantity;
            }
            if($productKey === 'price'){
                return $orderItem->quantity*$orderItem->product->price;
            }
            return $orderItem->product->{$productKey};
        }
        return $order->{$key};
    }

    public function headings(): array{
        return collect($this->structure)->pluck('heading')->toArray();
    }

    public function styles(Worksheet $sheet): void{
        $sheet->freezePane($this->coordinateToSheet(1, 1 + $this->getIndexByKey('lastName')));
    }

    private function coordinateToSheet(int $i, int $j): string{
		return Coordinate::stringFromColumnIndex($j + 1).($i + 1);
	}

    private function getIndexByKey(string $key): int{
        for($i=0; $i<count($this->structure); $i++){
            if($this->structure[$i]['key'] == $key){
                return $i;
            }
        }
    }
}
