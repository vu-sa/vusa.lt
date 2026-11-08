<?php

namespace App\Services;

use App\Models\User;

class UserPresenter
{
    /** @return array{id: string, name: string, profile_photo_path: string|null} */
    public static function avatar(User $user): array
    {
        $attributes = $user->attributesToArray();

        return [
            'id' => $attributes['id'],
            'name' => $attributes['name'],
            'profile_photo_path' => $attributes['profile_photo_path'],
        ];
    }

    /** @return array{id: string, email: string|null, phone: string|null, facebook_url: string|null, name: string, pronouns: string|null, show_pronouns: bool, is_active: int, updated_at: string, created_at: string, profile_photo_path: string|null, profile_photo_focal_point: string|null, deleted_at: string|null} */
    public static function user(User $user): array
    {
        $attributes = $user->attributesToArray();

        return [
            'id' => $attributes['id'],
            'email' => $attributes['email'],
            'phone' => $attributes['phone'],
            'facebook_url' => $attributes['facebook_url'],
            'name' => $attributes['name'],
            'pronouns' => $attributes['pronouns'] === null ? null : (string) $user->pronouns,
            'show_pronouns' => $attributes['show_pronouns'],
            'is_active' => $attributes['is_active'],
            'updated_at' => $attributes['updated_at'],
            'created_at' => $attributes['created_at'],
            'profile_photo_path' => $attributes['profile_photo_path'],
            'profile_photo_focal_point' => $attributes['profile_photo_focal_point'],
            'deleted_at' => $attributes['deleted_at'],
        ];
    }
}
