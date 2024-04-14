<?php
namespace App\Imports;

use App\Models\Product;
use App\Models\BulkOrder;
use App\Models\ProductCategory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToModel, WithHeadingRow
{
	public BulkOrder $bulkOrder; 

    public function model(array $row)
    {	
		ProductCategory::upsert([
			'name' => $row['kategorie'],
			'bulk_order_id' => $this->bulkOrder->id,
		], ['name'], ['bulk_order_id']);

		$category = ProductCategory::where('name', $row['kategorie'])->where('bulk_order_id', $this->bulkOrder->id)->firstOrFail();

		$price = str_replace('€', '', $row['preis']);
		$price = str_replace(',', '.', $price);
		$price = str_replace(' ', '', $price);
		$price = (float) $price;

		$product = new Product([
			'name' => $row['bezeichnung'],
			'price' => $price,
			'sku' => $row['sku'],
			'is_supplier_product' => true,
			'bulk_order_id' => $this->bulkOrder->id,
			'product_category_id' => $category->id,
			'panelsCount' => 0,
        ]);
		$product->bulkOrder()->associate($this->bulkOrder);

		$product->save();

		if(array_key_exists('link', $row) && $row['link'] !== null){
			$product->downloads()->create([
				'name' => 'Link zum Händler',
				'url' => $row['link'],
			]);
		}
		return $product;
    }
}
