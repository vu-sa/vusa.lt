<?php

namespace App\Services;

use App\Enums\TenantType;
use App\Models\Duty;
use App\Models\DutyName;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ResourceServices\SharepointFileService;

class DutyPresenter
{
    /** @return array{id: string, name: string|null, description: string|null, institution_id: string, order: int, email: string|null, contacts_grouping: string, places_to_occupy: int|null, is_active: bool, created_at: string, updated_at: string, deleted_at: string|null, institution: array{id: string, name: string|null, short_name: string|null, alias: string, description: string|null, address: string|null, phone: string|null, email: string|null, working_hours: string|null, website: string|null, logo_url: string|null, facebook_url: string|null, instagram_url: string|null, tenant_id: int|null, is_active: int, meeting_periodicity_days: int, created_at: string, updated_at: string, deleted_at: string|null, tenant: array{id: int, type: TenantType|null, fullname: string, shortname: string, alias: string, phone: string|null, email: string|null, address: string|null, shortname_vu: string|null, primary_institution_id: string|null, goals_enabled: bool, created_at: string|null, updated_at: string|null}|null}|array{id: string, name: string|null, short_name: string|null, alias: string, description: string|null, address: string|null, phone: string|null, email: string|null, working_hours: string|null, website: string|null, logo_url: string|null, facebook_url: string|null, instagram_url: string|null, tenant_id: int|null, is_active: int, meeting_periodicity_days: int, created_at: string, updated_at: string, deleted_at: string|null, tenant: array{id: int, type: TenantType|null, fullname: string, shortname: string, alias: string, phone: string|null, email: string|null, address: string|null, shortname_vu: string|null, primary_institution_id: string|null, goals_enabled: bool, created_at: string|null, updated_at: string|null}|null, types: list<array{id: int, parent_id: int|null, title: string|null, description: string|null, slug: string|null, extra_attributes: array<array-key, mixed>|null, created_at: string|null, updated_at: string|null, deleted_at: string|null, pivot: array{institution_id: string, institution_type_id: int}}>}|null, users: list<array{id: string, email: string|null, phone: string|null, facebook_url: string|null, name: string, pronouns: string|null, show_pronouns: bool, is_active: int, updated_at: string, created_at: string, profile_photo_path: string|null, profile_photo_focal_point: string|null, deleted_at: string|null, pivot: array{duty_id: string, dutiable_id: string, dutiable_type: string, id: string, via_dutiable_id: string|null, tenant_id: int|null, start_date: string, end_date: string|null, additional_photo: string|null, additional_photo_focal_point: string|null, additional_email: string|null, use_original_duty_name: bool, description: string|null, study_program_id: string|null, study_program_note: string|null, created_at: string, updated_at: string, tenant: array{id: int, shortname: string}|null, study_program: array{id: string, name: string}|null}}>, types: list<array{id: int, parent_id: int|null, title: string|null, description: string|null, created_at: string|null, updated_at: string|null, deleted_at: string|null, extra_attributes: array<array-key, mixed>|null, pivot: array{duty_id: string, duty_type_id: int}}>, former_names: list<array{id: string, duty_id: string, name: string, used_until: string, created_at: string|null, updated_at: string|null}>, sharepointPath: string|null, sharepointFolderUrl: string|null}|array{id: string, name: string|null, description: string|null, institution_id: string, order: int, email: string|null, contacts_grouping: string, places_to_occupy: int|null, is_active: bool, created_at: string, updated_at: string, deleted_at: string|null, institution: array{id: string, name: string|null, short_name: string|null, alias: string, description: string|null, address: string|null, phone: string|null, email: string|null, working_hours: string|null, website: string|null, logo_url: string|null, facebook_url: string|null, instagram_url: string|null, tenant_id: int|null, is_active: int, meeting_periodicity_days: int, created_at: string, updated_at: string, deleted_at: string|null, tenant: array{id: int, type: TenantType|null, fullname: string, shortname: string, alias: string, phone: string|null, email: string|null, address: string|null, shortname_vu: string|null, primary_institution_id: string|null, goals_enabled: bool, created_at: string|null, updated_at: string|null}|null}|array{id: string, name: string|null, short_name: string|null, alias: string, description: string|null, address: string|null, phone: string|null, email: string|null, working_hours: string|null, website: string|null, logo_url: string|null, facebook_url: string|null, instagram_url: string|null, tenant_id: int|null, is_active: int, meeting_periodicity_days: int, created_at: string, updated_at: string, deleted_at: string|null, tenant: array{id: int, type: TenantType|null, fullname: string, shortname: string, alias: string, phone: string|null, email: string|null, address: string|null, shortname_vu: string|null, primary_institution_id: string|null, goals_enabled: bool, created_at: string|null, updated_at: string|null}|null, types: list<array{id: int, parent_id: int|null, title: string|null, description: string|null, slug: string|null, extra_attributes: array<array-key, mixed>|null, created_at: string|null, updated_at: string|null, deleted_at: string|null, pivot: array{institution_id: string, institution_type_id: int}}>}|null, users: list<array{id: string, email: string|null, phone: string|null, facebook_url: string|null, name: string, pronouns: string|null, show_pronouns: bool, is_active: int, updated_at: string, created_at: string, profile_photo_path: string|null, profile_photo_focal_point: string|null, deleted_at: string|null, pivot: array{duty_id: string, dutiable_id: string, dutiable_type: string, id: string, via_dutiable_id: string|null, tenant_id: int|null, start_date: string, end_date: string|null, additional_photo: string|null, additional_photo_focal_point: string|null, additional_email: string|null, use_original_duty_name: bool, description: string|null, study_program_id: string|null, study_program_note: string|null, created_at: string, updated_at: string, tenant: array{id: int, shortname: string}|null, study_program: array{id: string, name: string}|null}}>, types: list<array{id: int, parent_id: int|null, title: string|null, description: string|null, created_at: string|null, updated_at: string|null, deleted_at: string|null, extra_attributes: array<array-key, mixed>|null, pivot: array{duty_id: string, duty_type_id: int}}>, former_names: list<array{id: string, duty_id: string, name: string, used_until: string, created_at: string|null, updated_at: string|null}>, sharepointPath: string|null, sharepointFolderUrl: string|null, tenants: list<array{id: int, type: TenantType|null, fullname: string, shortname: string, alias: string, phone: string|null, email: string|null, address: string|null, shortname_vu: string|null, primary_institution_id: string|null, goals_enabled: bool, created_at: string|null, updated_at: string|null, laravel_through_key: string}>} */
    public static function forShow(Duty $duty): array
    {
        $attributes = $duty->attributesToArray();
        $users = [];
        foreach ($duty->users as $user) {
            $users[] = self::holder($user);
        }
        $types = [];
        foreach ($duty->types as $type) {
            $types[] = self::type($type);
        }
        $formerNames = [];
        foreach ($duty->formerNames as $former) {
            $formerNames[] = self::formerName($former);
        }

        $payload = [
            'id' => $attributes['id'],
            'name' => $attributes['name'] === null ? null : (string) $duty->name,
            'description' => $attributes['description'] === null ? null : (string) $duty->description,
            'institution_id' => $attributes['institution_id'],
            'order' => $attributes['order'],
            'email' => $attributes['email'],
            'contacts_grouping' => $attributes['contacts_grouping'],
            'places_to_occupy' => $attributes['places_to_occupy'],
            'is_active' => $attributes['is_active'],
            'created_at' => $attributes['created_at'],
            'updated_at' => $attributes['updated_at'],
            'deleted_at' => $attributes['deleted_at'],
            'institution' => $duty->institution === null ? null : self::institution($duty->institution),
            'users' => $users,
            'types' => $types,
            'former_names' => $formerNames,
            'sharepointPath' => SharepointFileService::pathOrNull($duty),
            'sharepointFolderUrl' => SharepointFileService::folderUrlOrNull($duty),
        ];
        if ($duty->relationLoaded('tenants')) {
            $tenants = [];
            foreach ($duty->tenants as $tenant) {
                $item = self::tenant($tenant);
                $item['laravel_through_key'] = $tenant->getAttribute('laravel_through_key');
                $tenants[] = $item;
            }
            $payload['tenants'] = $tenants;
        }

        return $payload;
    }

    /** @return array{id: string, email: string|null, phone: string|null, facebook_url: string|null, name: string, pronouns: string|null, show_pronouns: bool, is_active: int, updated_at: string, created_at: string, profile_photo_path: string|null, profile_photo_focal_point: string|null, deleted_at: string|null, pivot: array{duty_id: string, dutiable_id: string, dutiable_type: string, id: string, via_dutiable_id: string|null, tenant_id: int|null, start_date: string, end_date: string|null, additional_photo: string|null, additional_photo_focal_point: string|null, additional_email: string|null, use_original_duty_name: bool, description: string|null, study_program_id: string|null, study_program_note: string|null, created_at: string, updated_at: string, tenant: array{id: int, shortname: string}|null, study_program: array{id: string, name: string}|null}} */
    public static function holder(User $user): array
    {
        $userPayload = UserPresenter::user($user);

        return [
            'id' => $userPayload['id'],
            'email' => $userPayload['email'],
            'phone' => $userPayload['phone'],
            'facebook_url' => $userPayload['facebook_url'],
            'name' => $userPayload['name'],
            'pronouns' => $userPayload['pronouns'],
            'show_pronouns' => $userPayload['show_pronouns'],
            'is_active' => $userPayload['is_active'],
            'updated_at' => $userPayload['updated_at'],
            'created_at' => $userPayload['created_at'],
            'profile_photo_path' => $userPayload['profile_photo_path'],
            'profile_photo_focal_point' => $userPayload['profile_photo_focal_point'],
            'deleted_at' => $userPayload['deleted_at'],
            'pivot' => self::term($user->getRelation('pivot')),
        ];
    }

    /** @return array{duty_id: string, dutiable_id: string, dutiable_type: string, id: string, via_dutiable_id: string|null, tenant_id: int|null, start_date: string, end_date: string|null, additional_photo: string|null, additional_photo_focal_point: string|null, additional_email: string|null, use_original_duty_name: bool, description: string|null, study_program_id: string|null, study_program_note: string|null, created_at: string, updated_at: string, tenant: array{id: int, shortname: string}|null, study_program: array{id: string, name: string}|null} */
    public static function term(Dutiable $term): array
    {
        $attributes = $term->attributesToArray();

        return [
            'duty_id' => $attributes['duty_id'],
            'dutiable_id' => $attributes['dutiable_id'],
            'dutiable_type' => $attributes['dutiable_type'],
            'id' => $attributes['id'],
            'via_dutiable_id' => $attributes['via_dutiable_id'],
            'tenant_id' => $attributes['tenant_id'],
            'start_date' => $attributes['start_date'],
            'end_date' => $attributes['end_date'],
            'additional_photo' => $attributes['additional_photo'],
            'additional_photo_focal_point' => $attributes['additional_photo_focal_point'],
            'additional_email' => $attributes['additional_email'],
            'use_original_duty_name' => $attributes['use_original_duty_name'],
            'description' => $attributes['description'] === null ? null : (string) $term->description,
            'study_program_id' => $attributes['study_program_id'],
            'study_program_note' => $attributes['study_program_note'] === null ? null : (string) $term->study_program_note,
            'created_at' => $attributes['created_at'],
            'updated_at' => $attributes['updated_at'],
            'tenant' => $term->tenant === null ? null : ['id' => $term->tenant->id, 'shortname' => $term->tenant->shortname],
            'study_program' => $term->study_program === null ? null : ['id' => $term->study_program->id, 'name' => (string) $term->study_program->name],
        ];
    }

    /** @return array{id: string, name: string|null, short_name: string|null, alias: string, description: string|null, address: string|null, phone: string|null, email: string|null, working_hours: string|null, website: string|null, logo_url: string|null, facebook_url: string|null, instagram_url: string|null, tenant_id: int|null, is_active: int, meeting_periodicity_days: int, created_at: string, updated_at: string, deleted_at: string|null, tenant: array{id: int, type: TenantType|null, fullname: string, shortname: string, alias: string, phone: string|null, email: string|null, address: string|null, shortname_vu: string|null, primary_institution_id: string|null, goals_enabled: bool, created_at: string|null, updated_at: string|null}|null}|array{id: string, name: string|null, short_name: string|null, alias: string, description: string|null, address: string|null, phone: string|null, email: string|null, working_hours: string|null, website: string|null, logo_url: string|null, facebook_url: string|null, instagram_url: string|null, tenant_id: int|null, is_active: int, meeting_periodicity_days: int, created_at: string, updated_at: string, deleted_at: string|null, tenant: array{id: int, type: TenantType|null, fullname: string, shortname: string, alias: string, phone: string|null, email: string|null, address: string|null, shortname_vu: string|null, primary_institution_id: string|null, goals_enabled: bool, created_at: string|null, updated_at: string|null}|null, types: list<array{id: int, parent_id: int|null, title: string|null, description: string|null, slug: string|null, extra_attributes: array<array-key, mixed>|null, created_at: string|null, updated_at: string|null, deleted_at: string|null, pivot: array{institution_id: string, institution_type_id: int}}>} */
    public static function institution(Institution $institution): array
    {
        $attributes = $institution->attributesToArray();

        $payload = [
            'id' => $attributes['id'],
            'name' => $attributes['name'] === null ? null : (string) $institution->name,
            'short_name' => $attributes['short_name'] === null ? null : (string) $institution->short_name,
            'alias' => $attributes['alias'],
            'description' => $attributes['description'] === null ? null : (string) $institution->description,
            'address' => $attributes['address'] === null ? null : (string) $institution->address,
            'phone' => $attributes['phone'],
            'email' => $attributes['email'],
            'working_hours' => $attributes['working_hours'] === null ? null : (string) $institution->working_hours,
            'website' => $attributes['website'],
            'logo_url' => $attributes['logo_url'],
            'facebook_url' => $attributes['facebook_url'],
            'instagram_url' => $attributes['instagram_url'],
            'tenant_id' => $attributes['tenant_id'],
            'is_active' => $attributes['is_active'],
            'meeting_periodicity_days' => $attributes['meeting_periodicity_days'],
            'created_at' => $attributes['created_at'],
            'updated_at' => $attributes['updated_at'],
            'deleted_at' => $attributes['deleted_at'],
            'tenant' => $institution->tenant === null ? null : self::tenant($institution->tenant),
        ];
        if ($institution->relationLoaded('types')) {
            $types = [];
            foreach ($institution->types as $type) {
                $types[] = self::institutionType($type);
            }
            $payload['types'] = $types;
        }

        return $payload;
    }

    /** @return array{id: int, parent_id: int|null, title: string|null, description: string|null, slug: string|null, extra_attributes: array<array-key, mixed>|null, created_at: string|null, updated_at: string|null, deleted_at: string|null, pivot: array{institution_id: string, institution_type_id: int}} */
    public static function institutionType(InstitutionType $type): array
    {
        $attributes = $type->attributesToArray();

        return [
            'id' => $attributes['id'],
            'parent_id' => $attributes['parent_id'],
            'title' => $attributes['title'] === null ? null : (string) $type->title,
            'description' => $attributes['description'] === null ? null : (string) $type->description,
            'slug' => $attributes['slug'],
            'extra_attributes' => $attributes['extra_attributes'],
            'created_at' => $attributes['created_at'],
            'updated_at' => $attributes['updated_at'],
            'deleted_at' => $attributes['deleted_at'],
            'pivot' => [
                'institution_id' => $type->pivot->institution_id,
                'institution_type_id' => $type->pivot->institution_type_id,
            ],
        ];
    }

    /** @return array{id: int, type: TenantType|null, fullname: string, shortname: string, alias: string, phone: string|null, email: string|null, address: string|null, shortname_vu: string|null, primary_institution_id: string|null, goals_enabled: bool, created_at: string|null, updated_at: string|null} */
    public static function tenant(Tenant $tenant): array
    {
        $attributes = $tenant->attributesToArray();

        return [
            'id' => $attributes['id'],
            'type' => $tenant->type,
            'fullname' => $attributes['fullname'],
            'shortname' => $attributes['shortname'],
            'alias' => $attributes['alias'],
            'phone' => $attributes['phone'],
            'email' => $attributes['email'],
            'address' => $attributes['address'],
            'shortname_vu' => $attributes['shortname_vu'],
            'primary_institution_id' => $attributes['primary_institution_id'],
            'goals_enabled' => $attributes['goals_enabled'],
            'created_at' => $attributes['created_at'],
            'updated_at' => $attributes['updated_at'],
        ];
    }

    /** @return array{id: int, parent_id: int|null, title: string|null, description: string|null, created_at: string|null, updated_at: string|null, deleted_at: string|null, extra_attributes: array<array-key, mixed>|null, pivot: array{duty_id: string, duty_type_id: int}} */
    public static function type(DutyType $type): array
    {
        $attributes = $type->attributesToArray();

        return [
            'id' => $attributes['id'],
            'parent_id' => $attributes['parent_id'],
            'title' => $attributes['title'] === null ? null : (string) $type->title,
            'description' => $attributes['description'] === null ? null : (string) $type->description,
            'created_at' => $attributes['created_at'],
            'updated_at' => $attributes['updated_at'],
            'deleted_at' => $attributes['deleted_at'],
            'extra_attributes' => $attributes['extra_attributes'],
            'pivot' => ['duty_id' => $type->pivot->duty_id, 'duty_type_id' => $type->pivot->duty_type_id],
        ];
    }

    /** @return array{id: string, duty_id: string, name: string, used_until: string, created_at: string|null, updated_at: string|null} */
    public static function formerName(DutyName $former): array
    {
        $attributes = $former->attributesToArray();

        return [
            'id' => $attributes['id'],
            'duty_id' => $attributes['duty_id'],
            'name' => (string) $former->name,
            'used_until' => $attributes['used_until'],
            'created_at' => $attributes['created_at'],
            'updated_at' => $attributes['updated_at'],
        ];
    }
}
