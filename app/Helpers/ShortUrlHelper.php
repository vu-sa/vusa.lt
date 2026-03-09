<?php

namespace App\Helpers;

use Hashids\Hashids;

/**
 * Helper for generating short URLs using Hashids.
 *
 * Produces YouTube/bit.ly style unique, non-sequential IDs.
 * The encoding is deterministic and reversible.
 *
 * Example: ID 1 -> "k3xR7m", ID 2 -> "Qn8vYp", ID 3 -> "wJ4tLs"
 */
class ShortUrlHelper
{
    /**
     * Minimum length for encoded strings
     */
    private const MIN_LENGTH = 6;

    /**
     * Get the Hashids instance with app-specific salt
     */
    private static function getHashids(): Hashids
    {
        // Use app key as salt for consistent encoding across deployments
        $salt = config('app.key', 'vusa-short-urls');

        return new Hashids($salt, self::MIN_LENGTH);
    }

    /**
     * Encode an integer ID to a hashid string
     */
    public static function encode(int $id): string
    {
        return self::getHashids()->encode($id);
    }

    /**
     * Decode a hashid string back to an integer ID
     */
    public static function decode(string $code): ?int
    {
        $decoded = self::getHashids()->decode($code);

        if (empty($decoded)) {
            return null;
        }

        return $decoded[0];
    }

    /**
     * Generate a full shareable URL for a document
     */
    public static function documentUrl(int $id): string
    {
        $code = self::encode($id);

        return url("/d/{$code}");
    }

    /**
     * Find document ID from a short code
     */
    public static function documentIdFromCode(string $code): ?int
    {
        return self::decode($code);
    }
}
