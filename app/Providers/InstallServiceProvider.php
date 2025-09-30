<?php

namespace App\Providers;

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
        if (strlen(env('APP_KEY')) === 0) {
            Artisan::call('key:generate --force');
        }

        if (! $this->isDatabaseMigrated()) {
            Artisan::call('migrate --force --seed');
        }

        if (Request::root() !== env('APP_URL')) {
            echo 'Du musst die APP_URL in der .env Datei anpassen. Aktuell ist sie: <b>'.env('APP_URL').'</b> Bitte ändere sie auf die URL, unter der du die Anwendung erreichen möchtest, vermutlich <b>'.Request::root().'</b>.';
            dd(Request::root(), env('APP_URL'));
            exit;
        }
    }

    private function isDatabaseMigrated(): bool
    {
        try {
            DB::table('migrations')->first();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
