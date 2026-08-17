---
paths:
  - 'app/Http/Requests/**'
---

# Requests

## A class-level `create` check does not scope the owning FK
`HasCommonChecks::create()` only asks whether the user holds `{resource}.create.padalinys` *somewhere* — it is tenant-agnostic. So `can('create', X::class)` alone is never enough on a store endpoint that reads its owning tenant or parent from the payload.

Two helpers exist for this; use them instead of hand-rolling:
- `App\Http\Requests\Concerns\ValidatesTenantScope::tenantIdInAuthorizedScope($permission)` — for a `tenant_id` rule.
- `App\Rules\WithinAuthorizedTenantScope($parentModel, $permission)` — when the tenant is reached through a parent (e.g. `institution_id` on StoreMeetingRequest).

When the parent has no tenant_id (Meeting, Reservation), authorize the parent object in `authorize()` instead, and `return true` when it does not resolve so the `exists` rule reports it as a validation error rather than a 403.

Pick the ability by what the role actually holds: StoreTaskRequest checks `view` on the taskable, not `update`, because student reps hold `tasks.create.padalinys` with only `institutions.read.own`.
