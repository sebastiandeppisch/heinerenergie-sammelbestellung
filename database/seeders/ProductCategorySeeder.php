<?php

namespace Database\Seeders;

use App\Models\BulkOrder;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(ProductCategory::count() === 0){
            $bulkOrder = BulkOrder::first();
            foreach(['Modulpakete', 'Montagepakete', 'Einzelzubehör'] as $name){
                $category = new ProductCategory();
                $category->name = $name;
                $category->bulk_order_id = $bulkOrder->id;
                $category->save();
            }
        }
    }
}
