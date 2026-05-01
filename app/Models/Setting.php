<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    // ── Static helpers ─────────────────────────────────────────────

    /**
     * Get a setting value by key (cached forever, invalidated on update).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("setting:{$key}", function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }

    /**
     * Set a setting value and clear its cache.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting:{$key}");
    }

    /**
     * Get multiple settings as a keyed array.
     */
    public static function getMany(array $keys): array
    {
        return collect($keys)->mapWithKeys(fn($key) => [
            $key => static::get($key),
        ])->all();
    }

    /**
     * Bulk update settings and clear all their caches.
     */
    public static function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            static::set($key, $value);
        }
    }
}
