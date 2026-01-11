<?php

namespace App\Providers;

use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use Override;

class InstallServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    #[Override]
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (app()->runningInConsole()) {
            // only shared hosting admins who dont have access to console needs this
            return;
        }

        // TODO this is just a hack, find a better solution
        if (strlen((string) config('app.key')) === 0) {
            Artisan::call('key:generate --force');
        }

        if (! $this->isDatabaseMigrated()) {
            Artisan::call('migrate --force --seed');
        }

        if (Request::root() !== config('app.url') && !app()->environment('local')) {
            echo 'Du musst die APP_URL in der .env Datei anpassen. Aktuell ist sie: <b>'.config('app.url').'</b> Bitte ändere sie auf die URL, unter der du die Anwendung erreichen möchtest, vermutlich <b>'.Request::root().'</b>.';
            exit;
        }
    }

    private function isDatabaseMigrated(): bool
    {
        try {
            DB::table('migrations')->first();

            return true;
        } catch (Exception) {
            return false;
        }
    }
}
