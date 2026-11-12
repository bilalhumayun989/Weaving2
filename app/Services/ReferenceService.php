<?php
namespace App\Services;

class ReferenceService
{
    public static function make(string $prefix): string
    {
        // Stable, collision-resistant document reference; readable number sequences can be added after client sign-off.
        return $prefix . '-' . now()->format('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
    }
}
