<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexSupportRequestRequest;
use App\Http\Requests\StoreSupportRequestRequest;
use App\Models\Role;
use App\Models\SupportRequest;
use App\Models\SupportRequestArea;
use App\Models\SupportRequestType;
use App\Models\SupportService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class MySupportRequestController extends AdminController
{
    public function index(IndexSupportRequestRequest $request, \App\Actions\BuildSupportRequestCollection $builder): Response
    {
        return $this->inertiaResponse('Admin/Dashboard/ShowSupportRequests', $builder->execute($request));
    }

    public function create()
    {
        $this->authorize('create', SupportRequest::class);

        return $this->inertiaResponse('Admin/SupportRequests/CreateSupportRequest', static::formOptions());
    }

    public function store(StoreSupportRequestRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['roles', 'images']);
        $area = SupportRequestArea::query()->findOrFail($data['support_request_area_id']);

        $supportRequest = SupportRequest::create([
            ...$data,
            'support_service_id' => $area->support_service_id,
            'created_by' => $request->user()->id,
            'locale' => app()->getLocale(),
        ]);

        if ($request->validated('visibility') === 'roles') {
            $supportRequest->roles()->sync($request->validated('roles', []));
        }

        foreach ($request->file('images', []) as $image) {
            $supportRequest->addMedia($image)->toMediaCollection('evidence');
        }

        return redirect()->route('supportRequests.show', $supportRequest)->with('success', __('messages.feedback.thanks'));
    }

    public static function formOptions(?User $user = null, ?SupportRequest $supportRequest = null): array
    {
        $user ??= auth()->user();

        $roleQuery = Role::query();

        if ($user && ! $user->hasRole(config('permission.super_admin_role_name'))) {
            $existingRoleIds = $supportRequest?->roles->pluck('id') ?? collect();

            $roleQuery->where(function ($query) use ($user, $existingRoleIds): void {
                $query->whereHas('users', fn ($q) => $q->where('users.id', $user->id))
                    ->orWhereHas('duties', fn ($q) => $q->whereIn('duties.id', $user->current_duties()->select('duties.id')));

                if ($existingRoleIds->isNotEmpty()) {
                    $query->orWhereIn('roles.id', $existingRoleIds);
                }
            });
        }

        $roles = $roleQuery
            ->with([
                'users:users.id,users.name,users.profile_photo_path',
                'currentUsersThroughDuties:users.id,users.name,users.profile_photo_path',
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'users' => $role->users
                    ->concat($role->currentUsersThroughDuties)
                    ->unique('id')
                    ->map(fn (User $u): array => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'profile_photo_path' => $u->profile_photo_path,
                    ])
                    ->values()
                    ->all(),
            ]);

        return [
            'types' => SupportRequestType::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'areas' => SupportRequestArea::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'roles' => $roles,
            'service' => SupportService::query()->where('slug', 'vusa-lt')->first(),
        ];
    }
}
