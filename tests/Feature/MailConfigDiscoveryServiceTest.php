<?php

use App\Services\MailConfigDiscoveryService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
});

$autoconfigXml = fn (string $imapHost, int $imapPort, string $smtpHost, int $smtpPort) => <<<XML
    <?xml version="1.0"?>
    <clientConfig version="1.1">
        <emailProvider id="example.com">
            <incomingServer type="imap">
                <hostname>{$imapHost}</hostname>
                <port>{$imapPort}</port>
            </incomingServer>
            <outgoingServer type="smtp">
                <hostname>{$smtpHost}</hostname>
                <port>{$smtpPort}</port>
            </outgoingServer>
        </emailProvider>
    </clientConfig>
    XML;

test('discovers config from ISPDB (XML format)', function () use ($autoconfigXml) {
    Http::fake([
        'v1.ispdb.net/*' => Http::response(
            $autoconfigXml('imap.example.com', 993, 'smtp.example.com', 587),
            200,
            ['Content-Type' => 'application/xml'],
        ),
    ]);

    $result = app(MailConfigDiscoveryService::class)->discover('user@example.com');

    expect($result)->not->toBeNull();
    expect($result->imapHost)->toBe('imap.example.com');
    expect($result->imapPort)->toBe(993);
    expect($result->smtpHost)->toBe('smtp.example.com');
    expect($result->smtpPort)->toBe(587);
});

test('picks imap server when XML lists multiple incomingServer types', function () {
    Http::fake([
        'v1.ispdb.net/*' => Http::response(
            '<?xml version="1.0"?>
            <clientConfig version="1.1"><emailProvider id="example.com">
                <incomingServer type="pop3"><hostname>pop.example.com</hostname><port>995</port></incomingServer>
                <incomingServer type="imap"><hostname>imap.example.com</hostname><port>993</port></incomingServer>
                <outgoingServer type="smtp"><hostname>smtp.example.com</hostname><port>587</port></outgoingServer>
            </emailProvider></clientConfig>',
            200,
        ),
    ]);

    $result = app(MailConfigDiscoveryService::class)->discover('user@example.com');

    expect($result->imapHost)->toBe('imap.example.com');
});

test('falls back to Mozilla autoconfig when ISPDB returns 404', function () use ($autoconfigXml) {
    Http::fake([
        'v1.ispdb.net/*' => Http::response('', 404),
        'autoconfig.example.com/*' => Http::response(
            $autoconfigXml('imap.example.com', 993, 'smtp.example.com', 465),
            200,
            ['Content-Type' => 'application/xml'],
        ),
    ]);

    $result = app(MailConfigDiscoveryService::class)->discover('user@example.com');

    expect($result)->not->toBeNull();
    expect($result->imapHost)->toBe('imap.example.com');
    expect($result->smtpPort)->toBe(465);
});

test('falls back to Microsoft Autodiscover when previous sources fail', function () {
    Http::fake([
        'v1.ispdb.net/*' => Http::response('', 404),
        'autoconfig.example.com/*' => Http::response('', 404),
        'autodiscover.example.com/*' => Http::response(
            '<?xml version="1.0"?>
            <Autodiscover xmlns="http://schemas.microsoft.com/exchange/autodiscover/responseschema/2006">
                <Response>
                    <Account>
                        <Protocol>
                            <Type>IMAP</Type>
                            <Server>imap.example.com</Server>
                            <Port>993</Port>
                        </Protocol>
                        <Protocol>
                            <Type>SMTP</Type>
                            <Server>smtp.example.com</Server>
                            <Port>587</Port>
                        </Protocol>
                    </Account>
                </Response>
            </Autodiscover>',
            200,
            ['Content-Type' => 'application/xml'],
        ),
    ]);

    $result = app(MailConfigDiscoveryService::class)->discover('user@example.com');

    expect($result)->not->toBeNull();
    expect($result->imapHost)->toBe('imap.example.com');
    expect($result->smtpHost)->toBe('smtp.example.com');
});

test('returns null when all sources fail', function () {
    Http::fake([
        'v1.ispdb.net/*' => Http::response('', 404),
        'autoconfig.example.com/*' => Http::response('', 404),
        'autodiscover.example.com/*' => Http::response('', 404),
    ]);

    expect(app(MailConfigDiscoveryService::class)->discover('user@example.com'))->toBeNull();
});
