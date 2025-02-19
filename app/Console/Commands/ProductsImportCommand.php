<?php

namespace App\Console\Commands;

use App\Imports\ProductsImport as ProductsExcelImport;
use App\Models\BulkOrder;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ProductsImportCommand extends Command
{
    protected $signature = 'products:import {file}';

    public function handle()
    {
        $bulkOrderName = $this->choice(
            'Für welche Sammelbestellung sollen die Produkte importiert werden?',
            BulkOrder::pluck('name')->toArray()
        );

        $bulkOrder = BulkOrder::where('name', $bulkOrderName)->firstOrFail();

        $import = new ProductsExcelImport();
        $import->bulkOrder = $bulkOrder;
        Excel::import($import, $this->argument('file'));

        return 0;
    }
}
