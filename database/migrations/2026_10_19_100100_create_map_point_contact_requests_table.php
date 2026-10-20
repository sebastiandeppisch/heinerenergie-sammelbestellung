<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('map_point_contact_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('map_point_id')->constrained()->cascadeOnDelete();
            $table->string('sender_name');
            $table->string('sender_email');
            $table->text('message');
            $table->string('confirmation_token_hash', 64)->unique();
            $table->timestamp('confirmation_expires_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('forwarded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('map_point_contact_requests');
    }
};
