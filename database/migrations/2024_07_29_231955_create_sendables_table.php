<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sendables', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('send_id')->index();
            $table->morphs('sendable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sendables');
    }
};
