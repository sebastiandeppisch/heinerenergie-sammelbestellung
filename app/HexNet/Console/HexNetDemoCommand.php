<?php

declare(strict_types=1);

namespace App\HexNet\Console;

use Database\Seeders\HexNetDemoSeeder;
use Illuminate\Console\Command;

class HexNetDemoCommand extends Command
{
    protected $signature = 'hexnet:demo';

    protected $description = 'Legt das Demo-HexaNetz „Darmstadt wird grün“ mit ca. 130 Trittsteinen an (nicht in Produktion)';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Nicht in Produktion.');

            return self::FAILURE;
        }

        $this->call('db:seed', ['--class' => HexNetDemoSeeder::class, '--force' => true]);

        return self::SUCCESS;
    }
}
