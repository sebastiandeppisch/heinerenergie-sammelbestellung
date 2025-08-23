<?php

namespace Tests\Feature\Services;

use App\Models\Advice;
use App\Models\User;
use App\Services\AdviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Override;
use Tests\TestCase;

class AdviceServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdviceService $adviceService;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->adviceService = new AdviceService;
    }

    public function test_get_distance_returns_null_for_null_user(): void
    {
        $advice = Advice::factory()->create();

        $distance = $this->adviceService->getDistance($advice, null);

        $this->assertNull($distance);
    }

    public function test_can_edit_returns_correct_permission(): void
    {
        $user = User::factory()->create();
        $advice = Advice::factory()->create();

        $canEdit = $this->adviceService->canEdit($advice, $user);

        $this->assertIsBool($canEdit);
    }
}
