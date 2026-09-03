<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class)->in('Feature');
uses(TestCase::class)->in('Unit');
uses(TestCase::class)->in('Browser');

pest()->browser()->timeout(15_000);

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', fn () => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something(): void
{
    // ..
}

/**
 * Switch the cache over to the file store the application actually runs on.
 *
 * The array store configured in phpunit.xml keeps its payloads as live objects and
 * never serializes them, so it cannot surface serialization problems. Tests that
 * care about what comes back out of the cache need a store that round trips
 * through serialize()/unserialize().
 *
 * @return string the throwaway cache directory
 */
function useFileCacheStore(): string
{
    $path = storage_path('framework/testing/cache-'.Str::random(8));

    config([
        'cache.default' => 'file',
        'cache.stores.file.path' => $path,
    ]);

    Cache::purge('file');

    return $path;
}
