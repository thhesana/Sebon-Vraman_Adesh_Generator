<?php

namespace App\Support;

/**
 * Cache-busted URLs for the plain files under public/ (there is no build step).
 * Asset::v('css/theme.css') -> .../css/theme.css?v=1763372123, so browsers fetch a file again as
 * soon as it changes on the server instead of showing a stale cached copy.
 */
class Asset
{
    public static function v(string $path): string
    {
        $file = public_path($path);

        return asset($path).(is_file($file) ? '?v='.filemtime($file) : '');
    }
}
