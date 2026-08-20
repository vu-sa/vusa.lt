<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthenticateRequest;
use App\Models\Duty;
use App\Models\User;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class AuthController extends Controller
{
    /**
     * Handle Microsoft OAuth callback.
     */
    public function storeFromMicrosoft(Request $request)
    {
        // Check if this is a popup OAuth flow
        $isPopup = $request->session()->pull('oauth_popup', false);

        // Handle OAuth errors (e.g., user cancelled login)
        if ($request->has('error')) {
            $error = $request->get('error');
            $errorSubcode = $request->get('error_subcode');

            \Log::info('Microsoft OAuth flow cancelled or denied', [
                'error' => $error,
                'error_subcode' => $errorSubcode,
                'error_description' => $request->get('error_description'),
                'user_ip' => $request->ip(),
            ]);

            // User cancelled the login - redirect gracefully
            if ($error === 'access_denied' || $errorSubcode === 'cancel') {
                $message = __('messages.auth.login_cancelled');

                if ($isPopup) {
                    return $this->handlePopupCallback(false, route('login'), $message);
                }

                return redirect()->route('login')->with('status', $message);
            }

            // Other OAuth errors
            $message = __('messages.auth.login_error');

            if ($isPopup) {
                return $this->handlePopupCallback(false, route('login'), $message);
            }

            return redirect()->route('login')->with('error', $message);
        }

        try {
            $microsoftUser = Socialite::driver('microsoft')->user();
        } catch (InvalidStateException) {
            // Log the error for debugging
            \Log::warning('Microsoft OAuth InvalidStateException, retrying with stateless', [
                'user_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => $request->session()->getId(),
                'referer' => $request->headers->get('referer'),
            ]);

            // Retry with stateless method
            /** @phpstan-ignore-next-line */
            $microsoftUser = Socialite::driver('microsoft')->stateless()->user();
        } catch (ClientException $e) {
            // Handle Guzzle HTTP errors (e.g., 400 Bad Request from token exchange)
            \Log::error('Microsoft OAuth ClientException', [
                'message' => $e->getMessage(),
                'user_ip' => $request->ip(),
            ]);

            $message = __('messages.auth.login_failed');

            if ($isPopup) {
                return $this->handlePopupCallback(false, route('login'), $message);
            }

            return redirect()->route('login')->with('error', $message);
        } catch (\Exception $e) {
            // Catch any other unexpected exceptions
            \Log::error('Microsoft OAuth unexpected error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_ip' => $request->ip(),
            ]);

            $message = __('messages.auth.login_unexpected_error');

            if ($isPopup) {
                return $this->handlePopupCallback(false, route('login'), $message);
            }

            return redirect()->route('login')->with('error', $message);
        }

        // pirmiausia ieškome per vartotoją, per paštą
        $user = User::withTrashed()->where('email', $microsoftUser->getEmail())->first();

        // A deleted account must not be able to reinstate itself by logging back in,
        // and it must not silently fall through to the duty branch and be re-created
        // either — users_email_unique would reject that anyway.
        if ($user?->trashed()) {
            $message = __('auth.account_deleted');

            if ($isPopup) {
                return $this->handlePopupCallback(false, route('login'), $message);
            }

            return redirect()->route('login')->with('error', $message);
        }

        if ($user) {
            // jei randama per vartotojo paštą, prijungiam

            // if user role is null, add role
            $user->microsoft_token = $microsoftUser->token ?? null;

            $user->save();

            Auth::login($user);
            $request->session()->regenerate();

            if ($isPopup) {
                return $this->handlePopupCallback(true, route('dashboard'));
            }

            return redirect()->intended(route('dashboard'));
        }

        $duty = Duty::where('email', $microsoftUser->getEmail())->first();

        if ($duty) {
            // # TEST: if only current users from duty are allowed to login

            // get count of current users
            $count = $duty->current_users()->count();

            if ($count > 1) {
                $errorMsg = __('messages.auth.duty_email_many_users');
                if ($isPopup) {
                    return $this->handlePopupCallback(false, route('login'), $errorMsg);
                }

                return redirect()->route('home', ['subdomain' => 'www', 'lang' => app()->getLocale()])->with('error', $errorMsg);
            }

            $user = $duty->current_users()->first();

            if (! $user) {
                $errorMsg = __('messages.auth.duty_email_no_user');
                if ($isPopup) {
                    return $this->handlePopupCallback(false, route('login'), $errorMsg);
                }

                return redirect()->route('home', ['subdomain' => 'www', 'lang' => app()->getLocale()])->with('error', $errorMsg);
            }

            /** @var User $user */
            $user->microsoft_token = $microsoftUser->token ?? null;

            $user->save();

            Auth::login($user);

            $request->session()->regenerate();

            if ($isPopup) {
                return $this->handlePopupCallback(true, route('dashboard'));
            }

            return redirect()->intended(route('dashboard'));
        }

        // No user or duty found with this email - redirect to login with error
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
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // Cleanly return the user to homepage, without inertia
        return back()->with('success', __('messages.auth.logout_success'));
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
