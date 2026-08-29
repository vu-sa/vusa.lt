<?php

use App\Enums\InstitutionScope;
use App\Models\Institution;
use App\Models\Type;
use App\Services\InstitutionScopeResolver;
use App\Support\MorphMap;
use Illuminate\Database\Migrations\Migration;

/**
 * Declare the governance scope of the root institution types.
 *
 * Only roots are written: everything else inherits through `types.parent_id`, so a new child of
 * `studentu-atstovu-organas` is a VU body without anyone remembering to say so.
 */
return new class extends Migration
{
    private const SCOPES = [
        'studentu-atstovu-organas' => InstitutionScope::University,
        'pkp' => InstitutionScope::Vusa,
        'padaliniai' => InstitutionScope::Vusa,
        'vu-sa-darinys' => InstitutionScope::Vusa,
        'padalinio-dienos' => InstitutionScope::Vusa,
    ];

    public function up(): void
    {
        foreach (self::SCOPES as $slug => $scope) {
            $type = Type::withTrashed()
                ->where('model_type', MorphMap::alias(Institution::class))
                ->where('slug', $slug)
                ->first();

            if ($type === null) {
                continue;
            }

            $extra = $type->extra_attributes ?? [];

            // Idempotent: never overwrite a scope somebody has already chosen by hand.
            if (array_key_exists('governance_scope', $extra)) {
                continue;
            }

            $extra['governance_scope'] = $scope->value;
            $type->extra_attributes = $extra;
            $type->saveQuietly();
        }

        // saveQuietly skips the model events that normally invalidate the scope map.
        app(InstitutionScopeResolver::class)->flush();
    }

    public function down(): void
    {
        foreach (array_keys(self::SCOPES) as $slug) {
            $type = Type::withTrashed()
                ->where('model_type', MorphMap::alias(Institution::class))
                ->where('slug', $slug)
                ->first();

            if ($type === null) {
                continue;
            }

            $extra = $type->extra_attributes ?? [];
            unset($extra['governance_scope']);
            $type->extra_attributes = $extra;
            $type->saveQuietly();
        }

        app(InstitutionScopeResolver::class)->flush();
    }
};
