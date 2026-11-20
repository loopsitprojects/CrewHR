<?php

use App\Models\Setting;

if (!function_exists('app_logo')) {
    /**
     * Get the application logo URL.
     *
     * @param string $type 'dark' or 'light'
     * @return string
     */
    function app_logo(string $type = 'dark'): string
    {
        return Setting::getLogoUrl($type);
    }
}

if (!function_exists('app_icon')) {
    /**
     * Get the application favicon/icon URL.
     *
     * @return string
     */
    function app_icon(): string
    {
        return Setting::getIconUrl();
    }
}

if (!function_exists('app_setting')) {
    /**
     * Get an application setting by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function app_setting(string $key, $default = null)
    {
        return Setting::get($key, $default);
    }
}

if (!function_exists('is_compensation_enabled')) {
    /**
     * Check if the employee compensation & payroll section is enabled.
     *
     * @return bool
     */
    function is_compensation_enabled(): bool
    {
        return Setting::isCompensationEnabled();
    }
}

if (!function_exists('is_settings_module_enabled')) {
    /**
     * Check if a settings module is enabled in navigation and settings hub.
     *
     * @param string $module
     * @return bool
     */
    function is_settings_module_enabled(string $module): bool
    {
        return Setting::isSettingsModuleEnabled($module);
    }
}
