<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class UpdateException extends Exception{

}

class Update extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'pulls from repo and updates this application';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try{
            $this->cmd(['git', 'pull']);
            $this->cmd(['composer', 'install']);
            $this->cmd(['php', 'artisan', 'migrate']);
            $this->cmd(['npm', 'install']);
            $this->cmd(['npm', 'run', 'prod']);
        }catch(UpdateException $e){
            $this->error('Command failed');
            return 1;
        }
        return 0;
    }


    private function cmd(array $cmds): void{
        $process = new Process($cmds);
        $process->run();
        if (!$process->isSuccessful()) {
            $this->error($process->getErrorOutput());
            throw new UpdateException();
        }
        $this->info($process->getOutput());
    }
}
