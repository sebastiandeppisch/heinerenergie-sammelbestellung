<?php

namespace App\Console\Commands;

use App\Models\Advice;
use Illuminate\Console\Command;

class AnonymizeAdvicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'advices:anonymize';

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
        Advice::all()->each(function ($advice) {
            $advice->firstName = fake()->firstName();
            $advice->lastName = fake()->lastName();
            $advice->email = fake()->safeEmail();
            $advice->phone = fake()->phoneNumber();
            $advice->save();
        });

        return Command::SUCCESS;
    }
}
