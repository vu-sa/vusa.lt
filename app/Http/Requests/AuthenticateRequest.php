<?php

namespace App\Http\Requests;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The login form itself — guarded by the `guest` middleware rather than by a policy.
 */
class AuthenticateRequest extends FormRequest
{
    public const int MAX_ATTEMPTS_PER_ACCOUNT = 5;

    /** Only failures count, so a whole campus behind one NAT can still sign in. */
    public const int MAX_FAILURES_PER_IP = 30;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required'],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $lockedKey = collect([
            $this->accountThrottleKey() => self::MAX_ATTEMPTS_PER_ACCOUNT,
            $this->ipThrottleKey() => self::MAX_FAILURES_PER_IP,
        ])->search(fn (int $maxAttempts, string $key): bool => RateLimiter::tooManyAttempts($key, $maxAttempts));

        if ($lockedKey === false) {
            return;
        }

        event(new Lockout($this));

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($lockedKey)]),
        ]);
    }

    public function recordFailedAttempt(): void
    {
        RateLimiter::hit($this->accountThrottleKey());
        RateLimiter::hit($this->ipThrottleKey());
    }

    public function clearAttempts(): void
    {
        RateLimiter::clear($this->accountThrottleKey());
    }

    private function accountThrottleKey(): string
    {
        return 'login:'.Str::transliterate(Str::lower($this->string('email')->toString())).'|'.$this->ip();
    }

    private function ipThrottleKey(): string
    {
        return 'login-ip:'.$this->ip();
    }
}
