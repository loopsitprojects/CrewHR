<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a setting by key with a fallback default.
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Store or update a setting.
     */
    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Remove a setting and delete any associated uploaded file.
     */
    public static function remove(string $key): void
    {
        $setting = static::where('key', $key)->first();
        if ($setting) {
            if ($setting->value && File::exists(public_path($setting->value))) {
                @unlink(public_path($setting->value));
            }
            $setting->delete();
        }
    }

    /**
     * Get the App Logo URL based on theme ('dark' or 'light').
     */
    public static function getLogoUrl(string $type = 'dark'): string
    {
        if ($type === 'light') {
            $path = static::get('app_logo_light');
            if ($path && File::exists(public_path($path))) {
                return asset($path);
            }
            return asset('LoopsBlack.png');
        }

        // Dark / Primary logo (used in dark sidebar and dark theme)
        $path = static::get('app_logo');
        if ($path && File::exists(public_path($path))) {
            return asset($path);
        }
        return asset('LoopsWhite.png');
    }

    /**
     * Check if a custom logo is currently configured.
     */
    public static function hasCustomLogo(string $type = 'dark'): bool
    {
        $key = ($type === 'light') ? 'app_logo_light' : 'app_logo';
        $path = static::get($key);
        return !empty($path) && File::exists(public_path($path));
    }

    /**
     * Get the App Icon / Favicon URL.
     */
    public static function getIconUrl(): string
    {
        $path = static::get('app_icon');
        if ($path && File::exists(public_path($path))) {
            return asset($path);
        }
        return asset('loops-icon.png');
    }

    /**
     * Check if a custom icon is currently configured.
     */
    public static function hasCustomIcon(): bool
    {
        $path = static::get('app_icon');
        return !empty($path) && File::exists(public_path($path));
    }

    /**
     * Get the logo as a base64 encoded string for PDF generation.
     */
    public static function getPdfLogoBase64(): string
    {
        // Check light logo first for print / PDF readability
        $lightPath = static::get('app_logo_light');
        if ($lightPath && File::exists(public_path($lightPath))) {
            $fullPath = public_path($lightPath);
            $mime = File::mimeType($fullPath);
            return 'data:' . $mime . ';base64,' . base64_encode(File::get($fullPath));
        }

        // Fallback to primary custom logo
        $mainPath = static::get('app_logo');
        if ($mainPath && File::exists(public_path($mainPath))) {
            $fullPath = public_path($mainPath);
            $mime = File::mimeType($fullPath);
            return 'data:' . $mime . ';base64,' . base64_encode(File::get($fullPath));
        }

        // Fallback to default black logo
        $defaultPath = public_path('LoopsBlack.png');
        if (File::exists($defaultPath)) {
            return 'data:image/png;base64,' . base64_encode(File::get($defaultPath));
        }

        return '';
    }

    /**
     * Check if Employee Compensation & Payroll section is enabled.
     */
    public static function isCompensationEnabled(): bool
    {
        $setting = static::get('show_employee_compensation');
        if ($setting !== null) {
            return filter_var($setting, FILTER_VALIDATE_BOOLEAN);
        }

        // Initially disabled by default unless explicitly enabled
        return false;
    }

    /**
     * Check if a settings module is enabled in navigation and settings hub.
     */
    public static function isSettingsModuleEnabled(string $module): bool
    {
        // Organization & System is core and always enabled
        if ($module === 'organization' || $module === 'system' || $module === 'general') {
            return true;
        }

        $setting = static::get('settings_module_' . $module);
        if ($setting !== null) {
            return filter_var($setting, FILTER_VALIDATE_BOOLEAN);
        }

        // Default to enabled (true)
        return true;
    }
}
