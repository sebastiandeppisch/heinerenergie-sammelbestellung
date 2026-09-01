<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\MailConfigData;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;
use Throwable;

class MailConfigDiscoveryService
{
    private const int TIMEOUT = 5;

    public function discover(string $email): ?MailConfigData
    {
        $domain = substr($email, strpos($email, '@') + 1);

        return $this->fromIspdb($domain)
            ?? $this->fromMozillaAutoconfig($domain)
            ?? $this->fromMicrosoftAutodiscover($domain);
    }

    private function fromIspdb(string $domain): ?MailConfigData
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->get("https://v1.ispdb.net/{$domain}");

            if (! $response->ok()) {
                return null;
            }

            return $this->parseAutoconfigXml($response->body());
        } catch (Throwable) {
            return null;
        }
    }

    private function fromMozillaAutoconfig(string $domain): ?MailConfigData
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->get("https://autoconfig.{$domain}/mail/config-v1.1.xml");

            if (! $response->ok()) {
                return null;
            }

            return $this->parseAutoconfigXml($response->body());
        } catch (Throwable) {
            return null;
        }
    }

    private function fromMicrosoftAutodiscover(string $domain): ?MailConfigData
    {
        try {
            $response = Http::timeout(self::TIMEOUT)
                ->post("https://autodiscover.{$domain}/Autodiscover/Autodiscover.xml", []);

            if (! $response->ok()) {
                return null;
            }

            $xml = simplexml_load_string($response->body());
            if (! $xml) {
                return null;
            }

            $namespaces = $xml->getNamespaces(true);
            $ns = array_values($namespaces)[0] ?? '';
            $xml->registerXPathNamespace('a', $ns);

            $imapProtocols = $xml->xpath('//a:Protocol[a:Type="IMAP"]');
            $smtpProtocols = $xml->xpath('//a:Protocol[a:Type="SMTP"]');

            if (empty($imapProtocols) || empty($smtpProtocols)) {
                return null;
            }

            return new MailConfigData(
                imapHost: (string) $imapProtocols[0]->Server,
                imapPort: (int) $imapProtocols[0]->Port,
                smtpHost: (string) $smtpProtocols[0]->Server,
                smtpPort: (int) $smtpProtocols[0]->Port,
            );
        } catch (Throwable) {
            return null;
        }
    }

    /** Parses Mozilla Autoconfig / ISPDB XML format (clientConfig). */
    private function parseAutoconfigXml(string $body): ?MailConfigData
    {
        $xml = simplexml_load_string($body);
        if (! $xml || ! isset($xml->emailProvider)) {
            return null;
        }

        $imap = $this->findServer($xml->emailProvider->incomingServer ?? [], 'imap');
        $smtp = $this->findServer($xml->emailProvider->outgoingServer ?? [], 'smtp');

        if (! $imap instanceof SimpleXMLElement || ! $smtp instanceof SimpleXMLElement) {
            return null;
        }

        return new MailConfigData(
            imapHost: (string) $imap->hostname,
            imapPort: (int) $imap->port,
            smtpHost: (string) $smtp->hostname,
            smtpPort: (int) $smtp->port,
        );
    }

    private function findServer(mixed $nodes, string $type): ?SimpleXMLElement
    {
        foreach ($nodes as $node) {
            if (strtolower((string) $node['type']) === $type) {
                return $node;
            }
        }

        return null;
    }
}
