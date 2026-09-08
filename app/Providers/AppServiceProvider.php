<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\MailCredentialsRepository;
use App\Contracts\MailServiceContract;
use App\Contracts\NextcloudFileClientContract;
use App\Contracts\NextcloudUserClientContract;
use App\Nextcloud\NextcloudConfig;
use App\Nextcloud\NextcloudUserClient;
use App\Nextcloud\WebDavNextcloudFileClient;
use App\Repositories\SessionMailCredentialsRepository;
use App\Services\CurrentGroupService;
use App\Services\MailService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;
use Override;
use Tests\Support\MockNextcloudFileClient;
use Tests\Support\MockNextcloudUserClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        $this->app->bind(
            MailCredentialsRepository::class,
            SessionMailCredentialsRepository::class,
        );

        $this->app->bind(
            MailServiceContract::class,
            MailService::class,
        );

        $nextcloudConfigured = (new NextcloudConfig)->isConfigured();

        $this->app->singleton(
            NextcloudFileClientContract::class,
            $nextcloudConfigured ? WebDavNextcloudFileClient::class : MockNextcloudFileClient::class
        );

        $this->app->singleton(
            NextcloudUserClientContract::class,
            $nextcloudConfigured ? NextcloudUserClient::class : MockNextcloudUserClient::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        LogViewer::auth(fn ($request): bool => $request->user()?->email === config('app.admin_email'));

        $this->app->singleton(fn (): CurrentGroupService => new CurrentGroupService);

        Model::shouldBeStrict(! $this->app->isProduction());
    }
}
