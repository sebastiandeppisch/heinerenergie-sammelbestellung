<?php

use App\Enums\AdviceStatusResult;
use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->timestamps();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('accepts_transfers')->default(true);
            $table->json('consulting_area')->nullable();
        });

        Schema::table('groups', function (Blueprint $table): void {
            $table->foreignIdFor(Group::class, 'parent_id')->nullable()->constrained('groups');
        });

        Schema::create('group_user', function (Blueprint $table): void {
            $table->foreignIdFor(Group::class)->constrained();
            $table->foreignIdFor(User::class)->constrained();
            $table->boolean('is_admin')->default(false);
            $table->timestamps();

            $table->unique(['group_id', 'user_id']);
        });

        $this->createDefaultAdviceStatuses();
    }

    /**
     * A fresh installation needs one status per result. Finer grained statuses
     * can be defined later on, so the default statuses are simply named after
     * their result.
     */
    private function createDefaultAdviceStatuses(): void
    {
        if (DB::table('advice_status')->exists()) {
            return;
        }

        DB::table('advice_status')->insert(
            array_map(fn (AdviceStatusResult $result): array => [
                'uuid' => (string) Str::uuid(),
                'name' => $result->defaultStatusName(),
                'result' => $result->value,
                'created_at' => now(),
                'updated_at' => now(),
            ], AdviceStatusResult::cases())
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_user');
        Schema::dropIfExists('groups');
    }
};
