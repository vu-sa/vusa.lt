<?php

namespace App\Support;

/**
 * Resolves the `data-surface` token scope `app.blade.php` stamps on `<html>`
 * (`resources/css/public/surface.css`, `resources/css/admin/surface.css`).
 *
 * Three places in `app.blade.php` branch on the same question (the `<html>` attribute, the
 * `<body>` classes, and — for `public` — the `@head`/Umami gates), so this is a helper rather
 * than a repeated blade expression.
 */
final class DesignSurface
{
    /**
     * @return 'public'|'admin'|null null for pages that belong to neither surface (errors, mail previews).
     */
    public static function for(?string $component): ?string
    {
        if ($component !== null && str_starts_with($component, 'Public/')) {
            return 'public';
        }

        if ($component !== null && str_starts_with($component, 'Admin/')) {
            return 'admin';
        }

        return null;
    }
}
