<?php

use App\Models\FormDefinition;
use App\Services\FormEmbedAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function embedAccessService(): FormEmbedAccessService
{
    return app(FormEmbedAccessService::class);
}

test('a freshly issued token is valid', function () {
    $formDefinition = FormDefinition::factory()->create();

    $token = embedAccessService()->issueToken($formDefinition);

    expect(embedAccessService()->verifyToken($formDefinition, $token))->toBeTrue();
});

test('an expired token is rejected', function () {
    $formDefinition = FormDefinition::factory()->create();

    $token = Carbon::withTestNow(now()->subHour(), function () use ($formDefinition) {
        return embedAccessService()->issueToken($formDefinition);
    });

    expect(embedAccessService()->verifyToken($formDefinition, $token))->toBeFalse();
});

test('a tampered token is rejected', function () {
    $formDefinition = FormDefinition::factory()->create();

    $token = embedAccessService()->issueToken($formDefinition);

    expect(embedAccessService()->verifyToken($formDefinition, $token.'x'))->toBeFalse();
});

test('a token issued for another form is rejected', function () {
    $formDefinitionA = FormDefinition::factory()->create();
    $formDefinitionB = FormDefinition::factory()->create();

    $token = embedAccessService()->issueToken($formDefinitionA);

    expect(embedAccessService()->verifyToken($formDefinitionB, $token))->toBeFalse();
});

test('a missing token is rejected', function () {
    $formDefinition = FormDefinition::factory()->create();

    expect(embedAccessService()->verifyToken($formDefinition, null))->toBeFalse();
});

test('non-iframe requests are always allowed regardless of the whitelist', function () {
    $formDefinition = FormDefinition::factory()->create(['allowed_embed_domains' => null]);

    $request = Request::create('/forms/'.$formDefinition->uuid, 'GET');

    expect(embedAccessService()->isEmbedAllowed($formDefinition, $request))->toBeTrue();
});

test('iframe requests are blocked when the domain whitelist is empty', function () {
    $formDefinition = FormDefinition::factory()->create(['allowed_embed_domains' => null]);

    $request = Request::create('/forms/'.$formDefinition->uuid, 'GET', server: [
        'HTTP_SEC_FETCH_DEST' => 'iframe',
        'HTTP_REFERER' => 'https://example.com/some-page',
    ]);

    expect(embedAccessService()->isEmbedAllowed($formDefinition, $request))->toBeFalse();
});

test('iframe requests are blocked when the referer domain is not whitelisted', function () {
    $formDefinition = FormDefinition::factory()->create(['allowed_embed_domains' => ['allowed.example']]);

    $request = Request::create('/forms/'.$formDefinition->uuid, 'GET', server: [
        'HTTP_SEC_FETCH_DEST' => 'iframe',
        'HTTP_REFERER' => 'https://not-allowed.example/some-page',
    ]);

    expect(embedAccessService()->isEmbedAllowed($formDefinition, $request))->toBeFalse();
});

test('iframe requests are allowed when the referer domain is whitelisted', function () {
    $formDefinition = FormDefinition::factory()->create(['allowed_embed_domains' => ['allowed.example']]);

    $request = Request::create('/forms/'.$formDefinition->uuid, 'GET', server: [
        'HTTP_SEC_FETCH_DEST' => 'iframe',
        'HTTP_REFERER' => 'https://allowed.example/some-page',
    ]);

    expect(embedAccessService()->isEmbedAllowed($formDefinition, $request))->toBeTrue();
});

test('iframe requests without a referer are blocked', function () {
    $formDefinition = FormDefinition::factory()->create(['allowed_embed_domains' => ['allowed.example']]);

    $request = Request::create('/forms/'.$formDefinition->uuid, 'GET', server: [
        'HTTP_SEC_FETCH_DEST' => 'iframe',
    ]);

    expect(embedAccessService()->isEmbedAllowed($formDefinition, $request))->toBeFalse();
});
