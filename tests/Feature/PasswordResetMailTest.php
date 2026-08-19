<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('password reset mail is rendered in german', function () {
    $user = User::factory()->create();

    $mail = new ResetPassword('reset-token')->toMail($user);

    expect($mail->subject)->toBe('Passwort zurücksetzen');

    expect((string) $mail->render())
        ->toContain('Hallo!')
        ->toContain('Du erhältst diese E-Mail, weil wir eine Anfrage zum Zurücksetzen des Passworts für Dein Konto erhalten haben.')
        ->toContain('Passwort zurücksetzen')
        ->toContain('Wenn Du kein Zurücksetzen des Passworts angefordert hast, sind keine weiteren Schritte nötig.')
        ->toContain('Viele Grüße')
        ->toContain('Falls Du Probleme hast');
});
