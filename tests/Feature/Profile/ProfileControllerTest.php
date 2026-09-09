<?php

use App\Models\News;
use App\Models\Page;
use App\Models\QuickLink;
use App\Models\Resource;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

    // Create related test data
    $this->page = Page::factory()->for($this->tenant)->create();
    $this->news = News::factory()->for($this->tenant)->create();
    $this->quickLink = QuickLink::factory()->for($this->tenant)->create();
    $this->resource = Resource::factory()->for($this->tenant)->create();
});

describe('user settings', function (): void {
    test('authenticated user can access user settings', function (): void {
        asUser($this->admin)
            ->get(route('profile'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowUserSettings')
                ->has('user')
                ->where('user.id', $this->admin->id)
            );
    });

    test('user settings include role and permission data', function (): void {
        asUser($this->admin)
            ->get(route('profile'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ShowUserSettings')
                ->has('user.roles')
                ->has('user.current_duties')
            );
    });

    test('unauthenticated user cannot access user settings', function (): void {
        $this->get(route('profile'))
            ->assertRedirect(route('login'));
    });

    test('user can update settings', function (): void {
        $validData = [
            'phone' => '+37060000000',
            'profile_photo_path' => '/path/to/photo.jpg',
            'show_pronouns' => true,
        ];

        asUser($this->admin)
            ->patch(route('profile.update'), $validData)
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'phone' => '+37060000000',
            'profile_photo_path' => '/path/to/photo.jpg',
        ]);
    });

    test('user cannot change email or password via the profile endpoint', function (): void {
        $originalEmail = $this->admin->email;
        $originalPassword = $this->admin->password;

        asUser($this->admin)
            ->patch(route('profile.update'), [
                'phone' => '+37061111111',
                'email' => 'attacker@example.com',
                'password' => 'hijacked-password',
            ])
            ->assertStatus(302);

        $this->admin->refresh();

        expect($this->admin->email)->toBe($originalEmail)
            ->and($this->admin->password)->toBe($originalPassword);

        // The legitimate field still updates.
        expect($this->admin->phone)->toBe('+37061111111');
    });

    test('user cannot change name after it was previously changed', function (): void {
        // Set name_was_changed to true
        $this->admin->name_was_changed = true;
        $this->admin->save();

        $updateData = [
            'name' => 'New Name',
            'phone' => '+37062222222',
        ];

        asUser($this->admin)
            ->patch(route('profile.update'), $updateData)
            ->assertStatus(302)
            ->assertSessionHas('success');

        // Name should remain unchanged
        $this->assertDatabaseMissing('users', [
            'id' => $this->admin->id,
            'name' => 'New Name',
        ]);

        // But the phone should be updated
        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'phone' => '+37062222222',
        ]);
    });

    test('user can update password', function (): void {
        $passwordData = [
            'current_password' => 'password', // Default password from factory
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        asUser($this->admin)
            ->patch(route('profile.updatePassword'), $passwordData)
            ->assertStatus(302)
            ->assertSessionHas('success');
    });
});
