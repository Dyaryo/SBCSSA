<?php

use Illuminate\Support\Facades\File;

if (! function_exists('asset_v')) {
    /**
     * URL for a public asset with the file's modification time appended, so a
     * changed stylesheet or script is refetched instead of served from cache.
     *
     *   {{ asset_v('css/app.css') }}  ->  /css/app.css?v=1763265000
     *
     * Cache-busting only applies to files that exist; a missing file falls back
     * to the plain URL so nothing breaks if something is renamed later.
     */
    function asset_v(string $path): string
    {
        $path = ltrim($path, '/');
        $full = public_path($path);

        if (! File::exists($full)) {
            return asset($path);
        }

        return asset($path).'?v='.File::lastModified($full);
    }
}
