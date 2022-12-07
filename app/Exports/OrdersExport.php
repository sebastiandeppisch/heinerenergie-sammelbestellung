<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\BulkOrder;
use App\Models\OrderItem;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{

    public ?BulkOrder $bulkorder = null;

    public string $products = 'all';

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

    public function collection(): Collection
    {
        $rows = collect();
        foreach($this->getOrders() as $order){
            foreach($this->getOrderItems($order) as $orderItem){
                $row = collect();
                foreach(collect($this->structure)->pluck('key') as $key){
                    $row->push($this->getData((string) $key, $order, $orderItem));
                }
                $rows->push($row);
            }
        }
        return $rows;
    }

    private function getOrders(): Collection{
        if($this->bulkorder !== null){
            return $this->bulkorder->orders;
        }
        return Order::all();
    }

    private function getOrderItems(Order $order): Collection{
        if($this->products === 'all'){
            return $order->orderItems;
        }
        if($this->products === 'supplier'){
            return $order->orderItems->filter(function($orderItem){
                return $orderItem->product->is_supplier_product;
            });
        }
        if($this->products === 'own'){
            return $order->orderItems->filter(function($orderItem){
                return !$orderItem->product->is_supplier_product;
            });
        }
        throw new InvalidArgumentException('Invalid value for $products');
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
