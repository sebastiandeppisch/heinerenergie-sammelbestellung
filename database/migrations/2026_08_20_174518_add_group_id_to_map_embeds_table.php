<?php

declare(strict_types=1);

use App\Models\Group;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('map_embeds', function (Blueprint $table): void {
            $table->foreignIdFor(Group::class)->nullable()->constrained();
        });
    }

    public function down(): void
    {
        Schema::table('map_embeds', function (Blueprint $table): void {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });
    }
};
