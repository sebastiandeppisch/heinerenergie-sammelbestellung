<?php

namespace App\Console\Commands;

use App\Models\AdviceStatus;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AdvicesImport as Importer;

class AdvicesImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'advices:import {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $status = $this->choice(
            'Welchen Status sollten die Beratungen erhalten?',
            AdviceStatus::pluck('name')->toArray()
        );
        $statusId = AdviceStatus::where('name', $status)->first()->id;

        $import = new Importer(); 
        $import->statusId = $statusId;
        Excel::import($import, $this->argument('file'));

    }
}
