<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SystemAdminController extends Controller
{
    /**
     * Show the system admin page
     */
    public function index()
    {
        $migrateResult = session()->pull('migrate_result');
        $seedResult = session()->pull('seed_result');

        return Inertia::render('SystemAdmin', [
            'migrateResult' => $migrateResult,
            'seedResult' => $seedResult,
        ]);
    }

    /**
     * Execute artisan migrate --force command
     */
    public function migrate()
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
    public function seed()
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
