<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use SocialiteProviders\Microsoft\Provider as MicrosoftProvider;

pest()->use(RefreshDatabase::class);

test('local logout ends only the application session', function (): void {
    $user = User::factory()->create();

    $this->from(route('dashboard'))
        ->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('dashboard'));

    $this->assertGuest();
});

test('Microsoft logout ends the application session and starts federated logout', function (): void {
    config([
        'services.microsoft.client_id' => 'client-id',
        'services.microsoft.client_secret' => 'client-secret',
        'services.microsoft.redirect' => 'https://www.vusa.lt/auth/microsoft/callback',
        'services.microsoft.logout_redirect' => 'https://www.vusa.lt/login',
        'services.microsoft.tenant' => 'common',
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['microsoft_logout_hint' => 'opaque-login-hint'])
        ->withHeader('X-Inertia', 'true')
        ->post(route('logout.microsoft'));

    $response->assertConflict()->assertHeader('X-Inertia-Location');
    $this->assertGuest();

    $location = $response->headers->get('X-Inertia-Location');
    expect($location)->toBeString()->toStartWith('https://login.microsoftonline.com/common/oauth2/v2.0/logout?');

    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
    expect($query)->toBe([
        'post_logout_redirect_uri' => 'https://www.vusa.lt/login',
        'logout_hint' => 'opaque-login-hint',
    ]);
});

test('Microsoft logout requires authentication', function (): void {
    $this->post(route('logout.microsoft'))->assertRedirect(route('login'));
});

test('Microsoft login retains the provider logout hint for the current session', function (): void {
    $user = User::factory()->create(['email' => 'representative@example.com']);
    $microsoftUser = SocialiteUser::fake([
        'id' => 'microsoft-user-id',
        'name' => $user->name,
        'email' => $user->email,
        'token' => 'access-token',
    ]);
    $provider = Mockery::mock(MicrosoftProvider::class);
    $provider->shouldReceive('user')->once()->andReturn($microsoftUser);
    $provider->shouldReceive('getClaims')->once()->andReturn((object) [
        'login_hint' => 'opaque-login-hint',
    ]);
    Socialite::shouldReceive('driver')->once()->with('microsoft')->andReturn($provider);

    $this->get(route('microsoft.callback'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('microsoft_logout_hint', 'opaque-login-hint');

    $this->assertAuthenticatedAs($user);
});
