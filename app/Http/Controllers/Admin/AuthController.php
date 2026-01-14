<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Duty;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

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
                $message = app()->getLocale() === 'en'
                    ? 'Login was cancelled. Please try again if you wish to sign in.'
                    : 'Prisijungimas buvo atšauktas. Bandykite dar kartą, jei norite prisijungti.';

                if ($isPopup) {
                    return $this->handlePopupCallback(false, route('login'), $message);
                }

                return redirect()->route('login')->with('status', $message);
            }

            // Other OAuth errors
            $message = app()->getLocale() === 'en'
                ? 'An error occurred during login. Please try again.'
                : 'Prisijungimo metu įvyko klaida. Bandykite dar kartą.';

            if ($isPopup) {
                return $this->handlePopupCallback(false, route('login'), $message);
            }

            return redirect()->route('login')->with('error', $message);
        }

        try {
            $microsoftUser = Socialite::driver('microsoft')->user();
        } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
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
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // Handle Guzzle HTTP errors (e.g., 400 Bad Request from token exchange)
            \Log::error('Microsoft OAuth ClientException', [
                'message' => $e->getMessage(),
                'user_ip' => $request->ip(),
            ]);

            $message = app()->getLocale() === 'en'
                ? 'Login failed. Please try again.'
                : 'Prisijungimas nepavyko. Bandykite dar kartą.';

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

            $message = app()->getLocale() === 'en'
                ? 'An unexpected error occurred. Please try again.'
                : 'Įvyko netikėta klaida. Bandykite dar kartą.';

            if ($isPopup) {
                return $this->handlePopupCallback(false, route('login'), $message);
            }

            return redirect()->route('login')->with('error', $message);
        }

        // pirmiausia ieškome per vartotoją, per paštą
        $user = User::where('email', $microsoftUser->getEmail())->first();

        if ($user) {
            // jei randama per vartotojo paštą, prijungiam

            // if user role is null, add role
            $user->microsoft_token = $microsoftUser->token ?? null;

            $user->save();

            Auth::login($user);
            $request->session()->regenerate();

            if ($isPopup) {
                return $this->handlePopupCallback(true, RouteServiceProvider::HOME);
            }

            return redirect()->intended(RouteServiceProvider::HOME);
        }

        $duty = Duty::where('email', $microsoftUser->getEmail())->first();

        if ($duty) {
            // # TEST: if only current users from duty are allowed to login

            // get count of current users
            $count = $duty->current_users()->count();

            if ($count > 1) {
                $errorMsg = 'Nepavyko prisijungti su pareigybiniu paštu, nes pareigybinis paštas turi daugiau nei vieną aktyvų vartotoją. Susisiekite su administratoriumi.';
                if ($isPopup) {
                    return $this->handlePopupCallback(false, route('login'), $errorMsg);
                }

                return redirect()->route('home', ['subdomain' => 'www', 'lang' => app()->getLocale()])->with('error', $errorMsg);
            }

            $user = $duty->current_users()->first();

            if (! $user) {
                $errorMsg = 'Nepavyko prisijungti su pareigybiniu paštu, nes pareigybinis paštas neturi aktyvaus vartotojo. Bandykite ištrinti slapukus arba naudoti naršyklės privatų rėžimą.';
                if ($isPopup) {
                    return $this->handlePopupCallback(false, route('login'), $errorMsg);
                }

                return redirect()->route('home', ['subdomain' => 'www', 'lang' => app()->getLocale()])->with('error', $errorMsg);
            }

            /** @var \App\Models\User $user */
            $user->microsoft_token = $microsoftUser->token ?? null;

            $user->save();

            Auth::login($user);

            $request->session()->regenerate();

            if ($isPopup) {
                return $this->handlePopupCallback(true, RouteServiceProvider::HOME);
            }

            return redirect()->intended(RouteServiceProvider::HOME);
        }

        // No user or duty found with this email - redirect to login with error
        $message = app()->getLocale() === 'en'
            ? 'No account or duty was found with this email address. Please contact a VU SR student representative coordinator or administrator to get access.'
            : 'Su šiuo el. pašto adresu nerastas nei vartotojas, nei pareigybė. Susisiekite su VU SA padalinio studentų atstovų koordinatoriumi ar administratoriumi, kad gautumėte prieigą.';

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
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->intended(RouteServiceProvider::HOME);
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
        return back()->with('success', 'Sėkmingai atsijungta!');
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
