<?php

use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Support\MorphMap;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
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

function fakeMicrosoftCallbackUser(string $email): void
{
    $provider = Mockery::mock(MicrosoftProvider::class);
    $provider->shouldReceive('user')->andReturn(SocialiteUser::fake([
        'id' => 'microsoft-user-id',
        'name' => 'Representative',
        'email' => $email,
        'token' => 'access-token',
    ]));
    $provider->shouldReceive('getClaims')->andReturn((object) []);
    Socialite::shouldReceive('driver')->with('microsoft')->andReturn($provider);
}

function attachCurrentDutyHolder(Duty $duty, User $user): void
{
    Dutiable::factory()->create([
        'duty_id' => $duty->id,
        'dutiable_id' => $user->id,
        'dutiable_type' => MorphMap::alias(User::class),
        'start_date' => now()->subMonth(),
        'end_date' => null,
    ]);
}

test('Microsoft login stores the token on a matched user', function (): void {
    $user = User::factory()->create(['email' => 'rep@example.com']);
    fakeMicrosoftCallbackUser('rep@example.com');

    $this->get(route('microsoft.callback'))->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->microsoft_token)->toBe('access-token');
});

test('Microsoft login cancelled by the user returns to login with a status', function (): void {
    $this->get(route('microsoft.callback', ['error' => 'access_denied']))
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', __('messages.auth.login_cancelled'));

    $this->assertGuest();
});

test('Microsoft login with another OAuth error returns to login with an error', function (): void {
    $this->get(route('microsoft.callback', ['error' => 'server_error']))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', __('messages.auth.login_error'));
});

test('Microsoft login reports a failed token exchange', function (): void {
    $provider = Mockery::mock(MicrosoftProvider::class);
    $provider->shouldReceive('user')->andThrow(new ClientException('Bad Request', new GuzzleRequest('POST', 'token'), new GuzzleResponse(400)));
    Socialite::shouldReceive('driver')->with('microsoft')->andReturn($provider);

    $this->get(route('microsoft.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', __('messages.auth.login_failed'));
});

test('Microsoft login refuses a deleted account', function (): void {
    User::factory()->create(['email' => 'rep@example.com'])->delete();
    fakeMicrosoftCallbackUser('rep@example.com');

    $this->get(route('microsoft.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', __('auth.account_deleted'));

    $this->assertGuest();
});

test('Microsoft login through a duty email signs in its single current holder', function (): void {
    $user = User::factory()->create();
    $duty = Duty::factory()->create(['email' => 'duty@example.com']);
    attachCurrentDutyHolder($duty, $user);
    fakeMicrosoftCallbackUser('duty@example.com');

    $this->get(route('microsoft.callback'))->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->microsoft_token)->toBe('access-token');
});

test('Microsoft login through a duty email with several holders is refused', function (): void {
    $duty = Duty::factory()->create(['email' => 'duty@example.com']);
    attachCurrentDutyHolder($duty, User::factory()->create());
    attachCurrentDutyHolder($duty, User::factory()->create());
    fakeMicrosoftCallbackUser('duty@example.com');

    $this->get(route('microsoft.callback'))
        ->assertRedirect(route('home', ['subdomain' => 'www', 'lang' => app()->getLocale()]))
        ->assertSessionHas('error', __('messages.auth.duty_email_many_users'));

    $this->assertGuest();
});

test('Microsoft login through a duty email without a holder is refused', function (): void {
    Duty::factory()->create(['email' => 'duty@example.com']);
    fakeMicrosoftCallbackUser('duty@example.com');

    $this->get(route('microsoft.callback'))
        ->assertRedirect(route('home', ['subdomain' => 'www', 'lang' => app()->getLocale()]))
        ->assertSessionHas('error', __('messages.auth.duty_email_no_user'));
});

test('Microsoft login with an unknown email returns an email error', function (): void {
    fakeMicrosoftCallbackUser('nobody@example.com');

    $this->get(route('microsoft.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => __('messages.auth.no_account_found')]);
});

test('Microsoft popup login answers through the popup callback page', function (): void {
    User::factory()->create(['email' => 'rep@example.com']);
    fakeMicrosoftCallbackUser('rep@example.com');

    $this->withSession(['oauth_popup' => true])
        ->get(route('microsoft.callback'))
        ->assertOk()
        ->assertViewIs('auth.popup-callback')
        ->assertViewHas('data', fn (array $data): bool => $data['type'] === 'oauth-success');
});

test('Microsoft popup login failure answers through the popup callback page', function (): void {
    fakeMicrosoftCallbackUser('nobody@example.com');

    $this->withSession(['oauth_popup' => true])
        ->get(route('microsoft.callback'))
        ->assertOk()
        ->assertViewHas('data', fn (array $data): bool => $data['type'] === 'oauth-error'
            && $data['message'] === __('messages.auth.no_account_found'));
});
