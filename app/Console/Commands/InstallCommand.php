<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Exceptions\UpdateException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Artisan;

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
     *
     * @return int
     */
    public function handle()
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

    private function cmd(array $cmds): void
    {
        $process = new Process($cmds);
        $process->run();
        $this->info($process->getOutput());
        if (! $process->isSuccessful()) {
            $this->error($process->getErrorOutput());
            throw new UpdateException();
        }
    }

    private function artisan(string $cmd): void
    {
        if (Artisan::call($cmd) !== 0) {
            throw new UpdateException();
        }
    }
}
