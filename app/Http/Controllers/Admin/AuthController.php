<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthenticateRequest;
use App\Models\Duty;
use App\Models\User;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Uri;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Microsoft\Provider as MicrosoftProvider;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * Handle Microsoft OAuth callback.
     */
    public function storeFromMicrosoft(Request $request)
    {
        $isPopup = $request->session()->pull('oauth_popup', false);

        if ($request->has('error')) {
            return $this->failOAuthError($request, $isPopup);
        }

        $microsoftProvider = Socialite::driver('microsoft');

        try {
            $microsoftUser = $microsoftProvider->user();
        } catch (InvalidStateException) {
            Log::warning('Microsoft OAuth InvalidStateException, retrying with stateless', [
                'user_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => $request->session()->getId(),
                'referer' => $request->headers->get('referer'),
            ]);

            /** @phpstan-ignore-next-line */
            $microsoftProvider = Socialite::driver('microsoft')->stateless();
            $microsoftUser = $microsoftProvider->user();
        } catch (ClientException $e) {
            // e.g. a 400 from the token exchange
            Log::error('Microsoft OAuth ClientException', [
                'message' => $e->getMessage(),
                'user_ip' => $request->ip(),
            ]);

            return $this->failLogin($isPopup, __('messages.auth.login_failed'));
        } catch (\Exception $e) {
            Log::error('Microsoft OAuth unexpected error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_ip' => $request->ip(),
            ]);

            return $this->failLogin($isPopup, __('messages.auth.login_unexpected_error'));
        }

        $this->rememberLogoutHint($request, $microsoftProvider);

        $email = $microsoftUser->getEmail();
        $user = User::withTrashed()->where('email', $email)->first();

        // A deleted account must not reinstate itself, nor fall through to the duty branch
        // and be re-created — users_email_unique would reject that anyway.
        if ($user?->trashed()) {
            return $this->failLogin($isPopup, __('auth.account_deleted'));
        }

        $account = $user ?? $this->currentDutyHolder($email, $isPopup);

        if ($account instanceof User) {
            return $this->completeLogin($request, $account, $microsoftUser->token ?? null, $isPopup);
        }

        if ($account !== null) {
            return $account;
        }

        $message = __('messages.auth.no_account_found');

        if ($isPopup) {
            return $this->handlePopupCallback(false, route('login'), $message);
        }

        return redirect()->route('login')->withErrors([
            'email' => $message,
        ]);
    }

    /**
     * Handle password-based authentication.
     */
    public function authenticate(AuthenticateRequest $request)
    {
        $credentials = $request->validated();

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => __('auth.failed'),
        ])->onlyInput('email');
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request): RedirectResponse
    {
        $this->logoutSession($request);

        // Cleanly return the user to homepage, without inertia
        return back()->with('success', __('messages.auth.logout_success'));
    }

    public function logoutFromMicrosoft(Request $request): Response
    {
        $logoutRedirect = config('services.microsoft.logout_redirect');

        if (! is_string($logoutRedirect) || $logoutRedirect === '') {
            $logoutRedirect = route('login');
        }

        /** @var MicrosoftProvider $microsoftProvider */
        $microsoftProvider = Socialite::driver('microsoft');
        $logoutUrl = Uri::of((string) $microsoftProvider->getLogoutUrl($logoutRedirect));
        $logoutHint = $request->session()->get('microsoft_logout_hint');

        if (is_string($logoutHint) && $logoutHint !== '') {
            $logoutUrl = $logoutUrl->withQuery(['logout_hint' => $logoutHint]);
        }

        $this->logoutSession($request);

        return Inertia::location((string) $logoutUrl);
    }

    private function logoutSession(Request $request): void
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();
    }

    /**
     * Answer an `error` callback, e.g. the user cancelling the Microsoft login.
     */
    private function failOAuthError(Request $request, bool $isPopup)
    {
        $error = $request->input('error');
        $errorSubcode = $request->input('error_subcode');

        Log::info('Microsoft OAuth flow cancelled or denied', [
            'error' => $error,
            'error_subcode' => $errorSubcode,
            'error_description' => $request->input('error_description'),
            'user_ip' => $request->ip(),
        ]);

        if ($error === 'access_denied' || $errorSubcode === 'cancel') {
            return $this->failLogin($isPopup, __('messages.auth.login_cancelled'), 'status');
        }

        return $this->failLogin($isPopup, __('messages.auth.login_error'));
    }

    private function rememberLogoutHint(Request $request, mixed $microsoftProvider): void
    {
        $request->session()->forget('microsoft_logout_hint');

        if (! $microsoftProvider instanceof MicrosoftProvider) {
            return;
        }

        $logoutHint = $microsoftProvider->getClaims()->login_hint ?? null;

        if (is_string($logoutHint) && $logoutHint !== '') {
            $request->session()->put('microsoft_logout_hint', $logoutHint);
        }
    }

    /**
     * The single current holder of the duty owning this email, a refusal when the
     * duty has none or several, or null when no duty uses the email.
     */
    private function currentDutyHolder(?string $email, bool $isPopup)
    {
        $duty = Duty::where('email', $email)->first();

        if (! $duty) {
            return null;
        }

        $holders = $duty->current_users()->get();

        if ($holders->count() === 1) {
            return $holders->first();
        }

        $errorMsg = $holders->isEmpty()
            ? __('messages.auth.duty_email_no_user')
            : __('messages.auth.duty_email_many_users');

        if ($isPopup) {
            return $this->handlePopupCallback(false, route('login'), $errorMsg);
        }

        return redirect()->route('home', ['subdomain' => 'www', 'lang' => app()->getLocale()])->with('error', $errorMsg);
    }

    private function completeLogin(Request $request, User $user, ?string $token, bool $isPopup)
    {
        $user->microsoft_token = $token;
        $user->save();

        Auth::login($user);
        $request->session()->regenerate();

        if ($isPopup) {
            return $this->handlePopupCallback(true, route('dashboard'));
        }

        return redirect()->intended(route('dashboard'));
    }

    private function failLogin(bool $isPopup, string $message, string $flashKey = 'error')
    {
        if ($isPopup) {
            return $this->handlePopupCallback(false, route('login'), $message);
        }

        return redirect()->route('login')->with($flashKey, $message);
    }

    /**
     * Handle popup OAuth callback by returning a page that sends postMessage to opener.
     */
    private function handlePopupCallback(bool $success, ?string $redirectUrl = null, ?string $message = null)
    {
        $data = [
            'type' => $success ? 'oauth-success' : 'oauth-error',
            'redirectUrl' => $redirectUrl ?? route('dashboard'),
            'message' => $message,
        ];

        return response()->view('auth.popup-callback', ['data' => $data]);
    }
}
