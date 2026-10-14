<?php

namespace App\Support;

/**
 * Resolves the `data-surface` attribute `app.blade.php` stamps on `<html>`, which scopes the few
 * rules admin and public do not share. The public-only `<head>` gates read the same answer.
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
