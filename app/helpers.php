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
