<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    public const PUBLIC_APP_URL = 'public_app_url';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, ?string $value): self
    {
        return self::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }

    public static function publicAppUrl(): string
    {
        return rtrim(self::get(self::PUBLIC_APP_URL, config('app.url')), '/');
    }

    public static function setPublicAppUrl(string $url): self
    {
        return self::set(self::PUBLIC_APP_URL, rtrim($url, '/'));
    }
}
