<?php

namespace App\Services\Typesense;

use App\Models\Calendar;
use App\Models\Document;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Page;
use App\Models\Pivots\AgendaItem;
use App\Models\PublicInstitution;
use App\Models\PublicMeeting;
use Illuminate\Support\Facades\Config;

/**
 * Centralized configuration for all Typesense searchable collections.
 *
 * This class defines which collections are public vs admin, their permissions,
 * and provides methods to access collection metadata consistently across the app.
 *
 * Used by:
 * - TypesenseScopedKeyService (for admin key generation)
 * - TypesenseManager (for frontend config)
 * - GenerateTypesenseSearchKey (for public key generation)
 * - GenerateTypesenseAdminSearchKey (for admin key generation)
 * - SearchableModelEnum (can reference this for consistency)
 */
class TypesenseCollectionConfig
{
    /**
     * Public collections configuration
     * These are accessible with the public search-only key
     *
     * @var array<string, array{model: class-string, description: string}>
     */
    protected const PUBLIC_COLLECTIONS = [
        'news' => [
            'model' => News::class,
            'description' => 'Published news articles',
        ],
        'pages' => [
            'model' => Page::class,
            'description' => 'Published static pages',
        ],
        'documents' => [
            'model' => Document::class,
            'description' => 'Public documents with anonymous access',
        ],
        'calendar' => [
            'model' => Calendar::class,
            'description' => 'Public calendar events',
        ],
        'public_institutions' => [
            'model' => PublicInstitution::class,
            'description' => 'Public institution contacts',
        ],
        'public_meetings' => [
            'model' => PublicMeeting::class,
            'description' => 'Public meeting records for transparency',
        ],
    ];

    /**
     * Admin collections configuration
     * These require scoped API keys with tenant filtering (unless skip_tenant_filter is true)
     *
     * own_permission: When set, users with only .own permission (not .padalinys) can access
     * records where their duties have a direct relationship to the model via institution.
     * This requires the searchable array to include 'institution_ids' field.
     *
     * @var array<string, array{model: class-string, permission: string, description: string, skip_tenant_filter?: bool, own_permission?: string}>
     */
    protected const ADMIN_COLLECTIONS = [
        'meetings' => [
            'model' => Meeting::class,
            'permission' => 'meetings.read.padalinys',
            'own_permission' => 'meetings.read.own',
            'description' => 'Internal meeting records with tenant-based access',
        ],
        'agenda_items' => [
            'model' => AgendaItem::class,
            'permission' => 'meetings.read.padalinys', // Same as meetings - they're connected
            'own_permission' => 'meetings.read.own',
            'description' => 'Meeting agenda items with tenant-based access',
        ],
        'news' => [
            'model' => News::class,
            'permission' => 'news.read.padalinys',
            'description' => 'News articles with tenant-based access for editing',
        ],
        'pages' => [
            'model' => Page::class,
            'permission' => 'pages.read.padalinys',
            'description' => 'Static pages with tenant-based access for editing',
        ],
        'calendar' => [
            'model' => Calendar::class,
            'permission' => 'calendar.read.padalinys',
            'description' => 'Calendar events with tenant-based access for editing',
        ],
        'institutions' => [
            'model' => Institution::class,
            'permission' => 'institutions.read.padalinys',
            'own_permission' => 'institutions.read.own',
            'description' => 'Institutions with tenant-based or self-referential access',
        ],
        'documents' => [
            'model' => Document::class,
            'permission' => null, // No permission required - documents are publicly accessible
            'description' => 'Documents searchable without tenant filtering (public access)',
            'skip_tenant_filter' => true, // Documents are publicly accessible, no tenant scoping
        ],
    ];

    /**
     * Get all public collection names (with prefix applied)
     *
     * @return array<string>
     */
    public static function getPublicCollectionNames(): array
    {
        $prefix = Config::get('scout.prefix', '');

        return array_map(
            fn (string $collection) => $prefix.$collection,
            array_keys(self::PUBLIC_COLLECTIONS)
        );
    }

    /**
     * Get all public collection base names (without prefix)
     *
     * @return array<string>
     */
    public static function getPublicCollectionBaseNames(): array
    {
        return array_keys(self::PUBLIC_COLLECTIONS);
    }

    /**
     * Get all admin collection names (with prefix applied)
     *
     * @return array<string>
     */
    public static function getAdminCollectionNames(): array
    {
        $prefix = Config::get('scout.prefix', '');

        return array_map(
            fn (string $collection) => $prefix.$collection,
            array_keys(self::ADMIN_COLLECTIONS)
        );
    }

    /**
     * Get all admin collection base names (without prefix)
     *
     * @return array<string>
     */
    public static function getAdminCollectionBaseNames(): array
    {
        return array_keys(self::ADMIN_COLLECTIONS);
    }

    /**
     * Get the permission required for an admin collection
     */
    public static function getPermissionForCollection(string $collection): ?string
    {
        return self::ADMIN_COLLECTIONS[$collection]['permission'] ?? null;
    }

    /**
     * Check if a collection should skip tenant filtering
     * Some collections (like documents) are publicly accessible and don't need tenant scoping
     */
    public static function shouldSkipTenantFilter(string $collection): bool
    {
        return self::ADMIN_COLLECTIONS[$collection]['skip_tenant_filter'] ?? false;
    }

    /**
     * Get the own permission for a collection (for .own scope filtering by institution)
     */
    public static function getOwnPermissionForCollection(string $collection): ?string
    {
        return self::ADMIN_COLLECTIONS[$collection]['own_permission'] ?? null;
    }

    /**
     * Get full admin collection config
     *
     * @return array<string, array{model: class-string, permission: string, description: string, skip_tenant_filter?: bool}>
     */
    public static function getAdminCollections(): array
    {
        return self::ADMIN_COLLECTIONS;
    }

    /**
     * Get full public collection config
     *
     * @return array<string, array{model: class-string, description: string}>
     */
    public static function getPublicCollections(): array
    {
        return self::PUBLIC_COLLECTIONS;
    }

    /**
     * Get model class for a collection
     */
    public static function getModelForCollection(string $collection): ?string
    {
        return self::PUBLIC_COLLECTIONS[$collection]['model']
            ?? self::ADMIN_COLLECTIONS[$collection]['model']
            ?? null;
    }

    /**
     * Check if a collection is public
     */
    public static function isPublicCollection(string $collection): bool
    {
        return isset(self::PUBLIC_COLLECTIONS[$collection]);
    }

    /**
     * Check if a collection is admin-only
     */
    public static function isAdminCollection(string $collection): bool
    {
        return isset(self::ADMIN_COLLECTIONS[$collection]);
    }

    /**
     * Get all collection names (both public and admin, with prefix)
     *
     * @return array<string>
     */
    public static function getAllCollectionNames(): array
    {
        return array_merge(
            self::getPublicCollectionNames(),
            self::getAdminCollectionNames()
        );
    }

    /**
     * Get all model classes that are searchable
     *
     * @return array<class-string>
     */
    public static function getAllModelClasses(): array
    {
        $publicModels = array_column(self::PUBLIC_COLLECTIONS, 'model');
        $adminModels = array_column(self::ADMIN_COLLECTIONS, 'model');

        return array_merge($publicModels, $adminModels);
    }

    /**
     * Get collection info for display/debugging
     *
     * @return array<string, array{type: string, model: class-string, permission?: string, description: string, prefixed_name: string}>
     */
    public static function getCollectionInfo(): array
    {
        $prefix = Config::get('scout.prefix', '');
        $info = [];

        foreach (self::PUBLIC_COLLECTIONS as $name => $config) {
            $info[$name] = [
                'type' => 'public',
                'model' => $config['model'],
                'description' => $config['description'],
                'prefixed_name' => $prefix.$name,
            ];
        }

        foreach (self::ADMIN_COLLECTIONS as $name => $config) {
            $info[$name] = [
                'type' => 'admin',
                'model' => $config['model'],
                'permission' => $config['permission'],
                'description' => $config['description'],
                'prefixed_name' => $prefix.$name,
            ];
        }

        return $info;
    }
}
