<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Exceptions\UpdateException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'install & updates the application';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->artisan('migrate --force --seed');
            $this->cmd(['npm', 'install', '--only=prod']);
            $this->cmd(['npm', 'run', 'production']);
        } catch (UpdateException) {
            $this->error('Command failed');

            return 1;
        }

        return 0;
    }

    /**
     * @param  string[]  $cmds
     */
    private function cmd(array $cmds): void
    {
        $process = new Process($cmds);
        $process->run();
        $this->info($process->getOutput());
        if (! $process->isSuccessful()) {
            $this->error($process->getErrorOutput());
            throw new UpdateException;
        }
    }

    private function artisan(string $cmd): void
    {
        if (Artisan::call($cmd) !== 0) {
            throw new UpdateException;
        }
    }
}
