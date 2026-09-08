<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\GeocodingStatus;
use App\Models\Advice;
use App\Services\NominatimThrottle;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class SystemAdminController extends Controller
{
    /**
     * Show the system admin page
     */
    public function index(): Response
    {
        $migrateResult = session()->pull('migrate_result');
        $seedResult = session()->pull('seed_result');
        $geocodingResult = session()->pull('geocoding_result');

        return Inertia::render('SystemAdmin', [
            'migrateResult' => $migrateResult,
            'seedResult' => $seedResult,
            'geocodingResult' => $geocodingResult,
            'geocoding' => $this->geocodingStatus(),
        ]);
    }

    /**
     * What the operator needs to judge the geocoding without shell access: how
     * often OpenStreetMap may be asked, whether requests are currently backing
     * up behind the rate limit, and how many advices are waiting.
     *
     * @return array{interval: float, currentWait: float, maxWait: float, queueConnection: string, counts: array<string, int>}
     */
    private function geocodingStatus(): array
    {
        $counts = [];

        foreach (GeocodingStatus::cases() as $status) {
            $counts[$status->value] = Advice::where('geocoding_status', $status)->count();
        }

        $counts['unknown'] = Advice::whereNull('geocoding_status')->count();

        $throttle = app(NominatimThrottle::class);

        return [
            'interval' => $throttle->interval(),
            'currentWait' => round($throttle->currentWait(), 1),
            'maxWait' => $throttle->maxWait(),
            'queueConnection' => (string) config('queue.default'),
            'counts' => $counts,
        ];
    }

    /**
     * Retries the coordinate lookup for advices that are still pending or
     * failed. Deliberately a small batch, because every request to
     * OpenStreetMap is spaced two seconds apart and the browser is waiting.
     */
    public function geocodePending(): RedirectResponse
    {
        try {
            Log::info('System admin running the geocoding catch up', [
                'user' => Auth::user()?->email,
            ]);

            Artisan::call('advices:geocode-pending', ['--limit' => 10]);

            session()->put('geocoding_result', [
                'output' => Artisan::output(),
            ]);

            return redirect()->route('system-admin')->with('success', 'Geocoding ausgeführt');
        } catch (Exception $e) {
            Log::error('Error running the geocoding catch up', [
                'error' => $e->getMessage(),
                'user' => Auth::user()?->email,
            ]);

            session()->put('geocoding_result', [
                'output' => $e->getMessage(),
            ]);

            return redirect()->route('system-admin')->with('error', 'Fehler beim Geocoding: '.$e->getMessage());
        }
    }

    /**
     * Execute artisan migrate --force command
     */
    public function migrate(): RedirectResponse
    {
        try {
            Log::info('System admin executing migrate command', [
                'user' => Auth::user()?->email,
            ]);

            Artisan::call('migrate', ['--force' => true]);

            $output = Artisan::output();

            session()->put('migrate_result', [
                'output' => $output,
            ]);

            return redirect()->route('system-admin')->with('success', 'Migration erfolgreich ausgeführt');
        } catch (Exception $e) {
            Log::error('Error executing migrate command', [
                'error' => $e->getMessage(),
                'user' => Auth::user()?->email,
            ]);

            session()->put('migrate_result', [
                'output' => $e->getMessage(),
            ]);

            return redirect()->route('system-admin')->with('error', 'Fehler beim Ausführen der Migration: '.$e->getMessage());
        }
    }

    /**
     * Execute artisan db:seed --force command
     */
    public function seed(): RedirectResponse
    {
        try {
            Log::info('System admin executing seed command', [
                'user' => Auth::user()?->email,
            ]);

            Artisan::call('db:seed', ['--force' => true]);

            $output = Artisan::output();

            session()->put('seed_result', [
                'output' => $output,
            ]);

            return redirect()->route('system-admin')->with('success', 'Seeding erfolgreich ausgeführt');
        } catch (Exception $e) {
            Log::error('Error executing seed command', [
                'error' => $e->getMessage(),
                'user' => Auth::user()?->email,
            ]);

            session()->put('seed_result', [
                'output' => $e->getMessage(),
            ]);

            return redirect()->route('system-admin')->with('error', 'Fehler beim Ausführen des Seedings: '.$e->getMessage());
        }
    }
}
