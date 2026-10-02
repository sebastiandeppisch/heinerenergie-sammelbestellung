<?php

declare(strict_types=1);

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

/**
 * Images of form submissions were public files below /storage. They move to the private image disk and are
 * served by a route that checks access. The stored paths stay the same.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->move(Storage::disk('public'), Storage::disk('images'));
    }

    public function down(): void
    {
        $this->move(Storage::disk('images'), Storage::disk('public'));
    }

    private function move(Filesystem $from, Filesystem $to): void
    {
        foreach ($from->allFiles('form-images') as $path) {
            $to->writeStream($path, $from->readStream($path));
            $from->delete($path);
        }

        $from->deleteDirectory('form-images');
    }
};
