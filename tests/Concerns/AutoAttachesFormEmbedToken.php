<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\FormDefinition;
use App\Services\FormEmbedAccessService;

/**
 * Public form submissions require a stateless anti-spam token that is normally
 * issued when the form page is loaded (see FormEmbedAccessService). Tests using
 * this trait post directly to the submit endpoint without loading the page
 * first, so it transparently attaches a valid token unless the test already
 * set one (e.g. to explicitly test invalid-token behaviour).
 *
 * Only mix this into test files that post to `form.submit` without first
 * calling `form.show` — see FormEmbedSecurityTest for tests that exercise the
 * token flow itself.
 */
trait AutoAttachesFormEmbedToken
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function post($uri, array $data = [], array $headers = [])
    {
        if (! array_key_exists('_form_token', $data) && preg_match('#/forms/([^/?]+)#', (string) $uri, $matches)) {
            $formDefinition = FormDefinition::where('uuid', $matches[1])->first();

            if ($formDefinition) {
                $data['_form_token'] = app(FormEmbedAccessService::class)->issueToken($formDefinition);
            }
        }

        return parent::post($uri, $data, $headers);
    }
}
