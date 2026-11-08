<?php

use App\Enums\TenantType;
use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserMicrosoftIdentity;
use App\Services\Typesense\TypesenseScopedKeyService;
use App\Support\MorphMap;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use SocialiteProviders\Microsoft\Provider as MicrosoftProvider;
use Spatie\Activitylog\Models\Activity;

pest()->use(RefreshDatabase::class);

test('local logout ends only the application session', function (): void {
    $user = User::factory()->create();
    $cacheKey = TypesenseScopedKeyService::getCacheKey($user->id);
    cache()->put($cacheKey, ['collections' => []], 900);

    $this->from(route('dashboard'))
        ->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('dashboard'));

    $this->assertGuest();
    expect(cache()->has($cacheKey))->toBeFalse();
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
    $cacheKey = TypesenseScopedKeyService::getCacheKey($user->id);
    cache()->put($cacheKey, ['collections' => []], 900);

    $response = $this->actingAs($user)
        ->withSession(['microsoft_logout_hint' => 'opaque-login-hint'])
        ->withHeader('X-Inertia', 'true')
        ->post(route('logout.microsoft'));

    $response->assertConflict()->assertHeader('X-Inertia-Location');
    $this->assertGuest();
    expect(cache()->has($cacheKey))->toBeFalse();

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

test('Microsoft login reports a failed stateless retry, e.g. a callback without a code', function (): void {
    $statelessProvider = Mockery::mock(MicrosoftProvider::class);
    $statelessProvider->shouldReceive('user')->andThrow(new ClientException('Bad Request', new GuzzleRequest('POST', 'token'), new GuzzleResponse(400)));
    $provider = Mockery::mock(MicrosoftProvider::class);
    $provider->shouldReceive('user')->andThrow(new InvalidStateException);
    $provider->shouldReceive('stateless')->andReturn($statelessProvider);
    Socialite::shouldReceive('driver')->with('microsoft')->andReturn($provider);

    $this->get(route('microsoft.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', __('messages.auth.login_failed'));

    $this->assertGuest();
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

const VU_MICROSOFT_TENANT = '11111111-2222-3333-4444-555555555555';

/** @param array<string, string> $attributes */
function fakeVuMicrosoftLogin(string $email, string $name = 'Vardenis Pavardenis', string $objectId = 'object-1', array $attributes = []): void
{
    $provider = Mockery::mock(MicrosoftProvider::class);
    $provider->shouldReceive('user')->andReturn(SocialiteUser::fake([
        'id' => $objectId,
        'name' => $name,
        'email' => $email,
        'token' => 'access-token',
        ...$attributes,
    ]));
    $provider->shouldReceive('getClaims')->andReturn((object) ['tid' => VU_MICROSOFT_TENANT]);
    Socialite::shouldReceive('driver')->with('microsoft')->andReturn($provider);
}

function ensureFacultyTenant(string $alias): void
{
    Tenant::query()->where('alias', $alias)->exists()
        || Tenant::factory()->create(['alias' => $alias, 'type' => TenantType::Padalinys]);
}

describe('Microsoft identity linking', function (): void {
    beforeEach(function (): void {
        ensureFacultyTenant('gmc');
        ensureFacultyTenant('mif');
        ensureFacultyTenant('filf');
    });

    test('an email login links the Microsoft identity', function (): void {
        $user = User::factory()->create(['email' => 'vardenis.pavardenis@gmc.stud.vu.lt', 'name' => 'Someone Else']);
        fakeVuMicrosoftLogin('vardenis.pavardenis@gmc.stud.vu.lt');

        $this->get(route('microsoft.callback'))->assertRedirect(route('dashboard'));

        expect($user->microsoftIdentities()->sole())
            ->microsoft_tenant_id->toBe(VU_MICROSOFT_TENANT)
            ->microsoft_object_id->toBe('object-1');
    });

    test('a renamed account keeps signing in through its linked identity', function (): void {
        $user = User::factory()->create(['email' => 'vardenis.pavardenis@gmc.stud.vu.lt', 'name' => 'Someone Else']);
        UserMicrosoftIdentity::factory()->for($user)->create([
            'microsoft_tenant_id' => VU_MICROSOFT_TENANT,
            'microsoft_object_id' => 'object-1',
            'principal_name' => $user->email,
        ]);
        fakeVuMicrosoftLogin('vp.renamed@cr.vu.lt');

        $this->get(route('microsoft.callback'))->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        expect($user->fresh()->email)->toBe('vardenis.pavardenis@gmc.stud.vu.lt')
            ->and(UserMicrosoftIdentity::query()->sole()->principal_name)->toBe('vp.renamed@cr.vu.lt');
    });

    test('a linked identity of a deleted account is refused', function (): void {
        $user = User::factory()->create();
        UserMicrosoftIdentity::factory()->for($user)->create([
            'microsoft_tenant_id' => VU_MICROSOFT_TENANT,
            'microsoft_object_id' => 'object-1',
        ]);
        $user->delete();
        fakeVuMicrosoftLogin('new.address@cr.vu.lt');

        $this->get(route('microsoft.callback'))->assertSessionHas('error', __('auth.account_deleted'));

        $this->assertGuest();
    });

    test('a login through a duty email links no identity', function (): void {
        $user = User::factory()->create();
        attachCurrentDutyHolder(Duty::factory()->create(['email' => 'duty@vusa.lt']), $user);
        fakeVuMicrosoftLogin('duty@vusa.lt');

        $this->get(route('microsoft.callback'))->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        expect(UserMicrosoftIdentity::query()->count())->toBe(0);
    });

    test('a renamed VU address signs in the account stored under its counterpart', function (string $stored, string $login): void {
        $user = User::factory()->create(['email' => $stored, 'name' => 'Vardenis Pavardenis']);
        fakeVuMicrosoftLogin($login, 'Pavardenis Vardenis');

        $this->get(route('microsoft.callback'))->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        expect($user->fresh()->email)->toBe($stored)
            ->and($user->microsoftIdentities()->sole()->principal_name)->toBe($login)
            ->and(Activity::query()->where('event', 'microsoft_login_linked')->where('subject_id', $user->id)->exists())->toBeTrue();
    })->with([
        'student to faculty staff' => ['vardenis.pavardenis@gmc.stud.vu.lt', 'vardenis.pavardenis@gmc.vu.lt'],
        'student to central admin' => ['vardenis.pavardenis@gmc.stud.vu.lt', 'vardenis.pavardenis@cr.vu.lt'],
        'faculty staff to student' => ['vardenis.pavardenis@gmc.vu.lt', 'vardenis.pavardenis@gmc.stud.vu.lt'],
        'faculty staff to central admin' => ['vardenis.pavardenis@gmc.vu.lt', 'vardenis.pavardenis@cr.vu.lt'],
        'mail prefix differs from the alias' => ['vardenis.pavardenis@flf.stud.vu.lt', 'vardenis.pavardenis@flf.vu.lt'],
    ]);

    test('the given name and surname also satisfy the name check', function (): void {
        $user = User::factory()->create(['email' => 'vardenis.pavardenis@gmc.stud.vu.lt', 'name' => 'Vardenis Pavardenis']);
        fakeVuMicrosoftLogin('vardenis.pavardenis@gmc.vu.lt', 'Pavardenis, Vardenis (GMC)', attributes: ['givenName' => 'Vardenis', 'surname' => 'Pavardenis']);

        $this->get(route('microsoft.callback'))->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    });

    test('a renamed VU address is refused outside the allowed renames', function (string $stored, string $login, string $name = 'Vardenis Pavardenis'): void {
        User::factory()->create(['email' => $stored, 'name' => 'Vardenis Pavardenis']);
        fakeVuMicrosoftLogin($login, $name);

        $this->get(route('microsoft.callback'))
            ->assertSessionHasErrors(['email' => __('messages.auth.no_account_found')]);

        $this->assertGuest();
        expect(UserMicrosoftIdentity::query()->count())->toBe(0);
    })->with([
        'central admin account' => ['vardenis.pavardenis@cr.vu.lt', 'vardenis.pavardenis@gmc.vu.lt'],
        'another faculty' => ['vardenis.pavardenis@gmc.stud.vu.lt', 'vardenis.pavardenis@mif.vu.lt'],
        'a different name' => ['vardenis.pavardenis@gmc.stud.vu.lt', 'vardenis.pavardenis@gmc.vu.lt', 'Kitas Žmogus'],
        'a numbered namesake' => ['vardenis.pavardenis@gmc.stud.vu.lt', 'vardenis.pavardenis.2@gmc.vu.lt'],
        'a non-VU domain' => ['vardenis.pavardenis@gmc.stud.vu.lt', 'vardenis.pavardenis@gmc.vusa.lt'],
    ]);

    test('a central admin address matching accounts in two faculties is refused', function (): void {
        User::factory()->create(['email' => 'vardenis.pavardenis@gmc.stud.vu.lt', 'name' => 'Vardenis Pavardenis']);
        User::factory()->create(['email' => 'vardenis.pavardenis@mif.stud.vu.lt', 'name' => 'Vardenis Pavardenis']);
        fakeVuMicrosoftLogin('vardenis.pavardenis@cr.vu.lt');

        $this->get(route('microsoft.callback'))
            ->assertSessionHasErrors(['email' => __('messages.auth.no_account_found')]);

        $this->assertGuest();
    });

    test('a renamed VU address of a deleted account is refused', function (): void {
        User::factory()->create(['email' => 'vardenis.pavardenis@gmc.stud.vu.lt', 'name' => 'Vardenis Pavardenis'])->delete();
        fakeVuMicrosoftLogin('vardenis.pavardenis@gmc.vu.lt');

        $this->get(route('microsoft.callback'))->assertSessionHas('error', __('auth.account_deleted'));

        $this->assertGuest();
    });
});
