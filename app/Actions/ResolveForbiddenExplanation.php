<?php

namespace App\Actions;

use App\Policies\ModelPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

/**
 * Turns a denied ability into what the 403 page tells the user: the permission they lack (U8).
 */
class ResolveForbiddenExplanation
{
    /** Policy ability → the CRUD action of the permission that gates it. */
    private const array ACTION_BY_ABILITY = [
        'viewAny' => 'read',
        'view' => 'read',
        'create' => 'create',
        'update' => 'update',
        'delete' => 'delete',
        'restore' => 'delete',
        'forceDelete' => 'forceDelete',
    ];

    /**
     * The `{resource}.{action}` a policy ability maps to, or null when the ability's policy
     * does not name a resource (closure gates, hand-written policies).
     *
     * @param  array<int, mixed>  $arguments  The Gate arguments; the first is the model or its class.
     * @return array{resource: string, action: string}|null
     */
    public static function permissionFor(string $ability, array $arguments): ?array
    {
        $action = self::ACTION_BY_ABILITY[$ability] ?? null;
        $subject = $arguments[0] ?? null;

        if ($action === null || ! ($subject instanceof Model || (is_string($subject) && class_exists($subject)))) {
            return null;
        }

        $policy = Gate::getPolicyFor($subject);
        $resource = $policy instanceof ModelPolicy ? $policy->resourceName() : null;

        return $resource ? ['resource' => $resource, 'action' => $action] : null;
    }

    /**
     * @param  array{resource: string, action: string}|null  $denied
     * @return array{
     *     permission: string|null,
     *     action: string|null,
     *     resource: string|null
     * }
     */
    public static function execute(?array $denied): array
    {
        $entityKey = $denied ? 'entities.'.Str::singular($denied['resource']).'.model' : null;

        return [
            'permission' => $denied ? "{$denied['resource']}.{$denied['action']}" : null,
            'action' => $denied ? __("forbidden.actions.{$denied['action']}") : null,
            'resource' => $entityKey && Lang::has($entityKey) ? trans_choice($entityKey, 5) : ($denied['resource'] ?? null),
        ];
    }
}
