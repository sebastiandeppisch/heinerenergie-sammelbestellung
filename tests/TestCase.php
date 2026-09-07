<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Deferred callbacks only run when a real HTTP response terminates or a
     * console command finishes. Without this, every job on the "deferred"
     * connection would silently never run in tests. Tests that assert the
     * deferring itself can opt back in with withDefer() or use Queue::fake().
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutDefer();
    }
}
