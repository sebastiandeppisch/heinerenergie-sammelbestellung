<?php

namespace App\Http\Controllers;

use App\Contracts\MailCredentialsRepository;
use App\Contracts\MailServiceContract;
use App\Data\MailConfigData;
use App\Data\MailCredentialsData;
use App\Http\Requests\StoreMailAccountRequest;
use App\Services\MailConfigDiscoveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class MailAccountController extends Controller
{
    public function __construct(
        private readonly MailCredentialsRepository $repository,
        private readonly MailConfigDiscoveryService $discoveryService,
        private readonly MailServiceContract $mailService,
    ) {}

    public function show(Request $request): Response
    {
        $cookieValue = $request->cookie('enc_key');
        $hasEncKey = (bool) $cookieValue;
        $hasAccount = false;

        if ($hasEncKey) {
            try {
                app()->instance('user.enc_key', base64_decode($cookieValue));
                $hasAccount = $this->repository->get() !== null;
            } catch (Throwable) {
            }
        }

        return Inertia::render('Mail/Account', [
            'hasEncKey' => $hasEncKey,
            'hasAccount' => $hasAccount,
            'discoveredConfig' => session()->pull('mail_discovered_config'),
            'discoverFailed' => session()->pull('mail_discover_failed', false),
        ]);
    }

    public function store(StoreMailAccountRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $credentials = new MailCredentialsData(
            imapHost: $validated['imap_host'],
            imapPort: (int) $validated['imap_port'],
            smtpHost: $validated['smtp_host'],
            smtpPort: (int) $validated['smtp_port'],
            username: $validated['username'],
            password: $validated['password'],
        );

        $cookieValue = $request->cookie('enc_key');

        if ($cookieValue) {
            app()->instance('user.enc_key', base64_decode($cookieValue));
        }

        try {
            $this->mailService->testConnection($credentials);
        } catch (Throwable $e) {
            return redirect()->back()
                ->withErrors(['connection' => 'IMAP-Verbindung fehlgeschlagen: '.$e->getMessage()])
                ->withInput();
        }

        $this->repository->store($credentials);

        return redirect()->route('mail.account.show');
    }

    public function destroy(): RedirectResponse
    {
        $this->repository->clear();

        return redirect()->route('mail.account.show');
    }

    public function discover(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $config = $this->discoveryService->discover($request->input('email'));

        if ($config) {
            session()->flash('mail_discovered_config', new MailConfigData(
                imapHost: $config->imapHost,
                imapPort: $config->imapPort,
                smtpHost: $config->smtpHost,
                smtpPort: $config->smtpPort,
                username: $request->input('email'),
            ));
        } else {
            session()->flash('mail_discover_failed', true);
        }

        return redirect()->back();
    }
}
