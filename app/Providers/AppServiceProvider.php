<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use maxh\Nominatim\Nominatim;
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
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
        LogViewer::auth(function ($request) {
            return $request->user()?->email === config('app.admin_email');
        });

        $this->app->bind(Nominatim::class, function () {
            $url = "http://nominatim.openstreetmap.org/";
            $defaultHeader = [
                'User-Agent' => 'heiner*energie CMS'
            ];
            return new Nominatim($url, $defaultHeader);
        });
    }
}
