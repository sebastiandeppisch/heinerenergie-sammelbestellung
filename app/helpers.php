<?php

use App\Context\GroupContextContract;
use App\Models\Setting;

if (! function_exists('app_name')) {
    /**
     * Get the application name.
     * Returns the group name if a group context is available, otherwise returns the default name setting.
     */
    function app_name(): string
    {
        // Try to get the group context
        if (app()->bound(GroupContextContract::class)) {
            $groupContext = app(GroupContextContract::class);
            $currentGroup = $groupContext->getCurrentGroup();

            if ($currentGroup !== null) {
                return $currentGroup->name;
            }
        }

        // Fallback to default name setting
        $defaultName = Setting::get('defaultName');

        return $defaultName ?? 'CRM-System';
    }
}

if (! function_exists('app_logo')) {
    /**
     * Get the application logo path.
     * Returns the group logo if a group context is available and the group has a logo, otherwise returns the default logo setting.
     */
    function app_logo(): string
    {
        // Try to get the group context
        if (app()->bound(GroupContextContract::class)) {
            $groupContext = app(GroupContextContract::class);
            $currentGroup = $groupContext->getCurrentGroup();

            if ($currentGroup !== null && $currentGroup->logo_path) {
                return url('storage/'.$currentGroup->logo_path);
            }
        }

        // Fallback to default logo setting
        return url(Setting::get('defaultLogo') ?? 'img/logo_without_background.png');
    }
}

if (! function_exists('app_favicon')) {
    /**
     * Get the application favicon path.
     */
    function app_favicon(): string
    {
        return Setting::get('defaultFavicon') ?? 'favicon.ico';
    }
}

if (! function_exists('app_url')) {
    /**
     * Get the application URL.
     * Returns the group URL if a group context is available and the group has a URL, otherwise returns APP_URL.
     */
    function app_url(): string
    {
        if (app()->bound(GroupContextContract::class)) {
            $groupContext = app(GroupContextContract::class);
            $currentGroup = $groupContext->getCurrentGroup();

            if ($currentGroup !== null && ! empty($currentGroup->url)) {
                return $currentGroup->url;
            }
        }

        return config('app.url');
    }
}
