<?php

namespace App\Providers;

use maxh\Nominatim\Nominatim;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;
use App\Actions\FetchCoordinateByAddress;
use App\ValueObjects\Address;
use App\ValueObjects\Coordinate;

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

        if(config('app.env') === 'testing') {
            $this->app->bind(FetchCoordinateByAddress::class, function () {

                $coordinatesOfDarmstadtCenter = new Coordinate(
                    lat: 49.8728475,
                    long: 8.6510204
                );

                return fn (Address $address) => $coordinatesOfDarmstadtCenter;
            });
        }else{
            $this->app->bind(Nominatim::class, function () {
                $url = "http://nominatim.openstreetmap.org/";
                $defaultHeader = [
                    'User-Agent' => 'heiner*energie CMS'
                ];
                return new Nominatim($url, $defaultHeader);
            });
        }

    }
}
