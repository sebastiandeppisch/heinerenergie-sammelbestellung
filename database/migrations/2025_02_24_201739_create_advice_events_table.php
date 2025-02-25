<?php

use App\Models\Advice;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('advice_events', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Advice::class)->constrained();
            $table->foreignIdFor(User::class)->nullable()->constrained();
            $table->longText('event');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advice_events');
    }
};
