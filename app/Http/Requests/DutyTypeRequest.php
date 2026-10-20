<?php

namespace App\Http\Requests;

use App\Actions\Responsibilities\GrantableResponsibilities;
use App\Models\DutyType;
use App\Models\ResponsibilityGrant;
use App\Models\Role;
use App\Support\MorphMap;
use Closure;
use Illuminate\Validation\Validator;

/**
 * A subtype inherits its ancestors' responsibilities, so moving a type is granting: whoever moves
 * it must be able to grant every responsibility its duties gain or lose.
 */
class DutyTypeRequest extends TypeAttributesRequest
{
    protected function typeClass(): string
    {
        return DutyType::class;
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $type = $this->route('type');

            if (! $type instanceof DutyType || ! $this->has('parent_id')) {
                return;
            }

            $newParentId = $this->filled('parent_id') ? (int) $this->input('parent_id') : null;

            if ($newParentId === $type->parent_id) {
                return;
            }

            $before = $this->inheritedRoleIds($type->parent_id);
            $after = $this->inheritedRoleIds($newParentId);
            $changed = array_values(array_unique([...array_diff($before, $after), ...array_diff($after, $before)]));

            $ungrantable = Role::query()->whereKey($changed)->get()
                ->reject(fn (Role $role): bool => GrantableResponsibilities::allows($this->user(), $role));

            if ($ungrantable->isNotEmpty()) {
                $validator->errors()->add('parent_id', __('types.parent_changes_responsibilities', [
                    'responsibilities' => $ungrantable->map->displayTitle()->implode(', '),
                ]));
            }
        }];
    }

    /**
     * @return list<string>
     */
    private function inheritedRoleIds(?int $parentId): array
    {
        if ($parentId === null) {
            return [];
        }

        return ResponsibilityGrant::query()
            ->where('grantee_type', MorphMap::alias(DutyType::class))
            ->whereIn('grantee_id', array_map(strval(...), DutyType::withAncestorIds([$parentId])))
            ->distinct()
            ->pluck('role_id')
            ->map(strval(...))
            ->all();
    }
}
