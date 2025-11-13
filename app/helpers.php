<?php

use App\Context\GroupContextContract;
use App\Models\Setting;

if (! function_exists('app_name')) {
    /**
     * Get the application name.
     * Returns the group name if a group context is available, otherwise returns the default name setting.
     *
     * @return string
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
     *
     * @return string
     */
    function app_logo(): string
    {
        // Try to get the group context
        if (app()->bound(GroupContextContract::class)) {
            $groupContext = app(GroupContextContract::class);
            $currentGroup = $groupContext->getCurrentGroup();
            
            if ($currentGroup !== null && $currentGroup->logo_path) {
                return $currentGroup->logo_path;
            }
        }
        
        // Fallback to default logo setting
        return Setting::get('defaultLogo');
    }
}

if (! function_exists('app_favicon')) {
    /**
     * Get the application favicon path.
     *
     * @return string|null
     */
    function app_favicon(): ?string
    {
        return Setting::get('defaultFavicon');
    }
}

