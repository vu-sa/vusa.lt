<?php

namespace App\Services;

use App\Enums\TenantType;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserMicrosoftIdentity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\AbstractUser;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Finds the personal account a Microsoft login belongs to: by linked Microsoft identity, by
 * email, or, once per person, by a VU student ↔ staff address rename. Duty mailboxes are
 * shared between holders, so they are resolved elsewhere and never linked.
 */
class MicrosoftAccountResolver
{
    /** May return a trashed user; the caller refuses it. */
    public function resolve(SocialiteUser $microsoftUser, ?string $microsoftTenantId): ?User
    {
        $email = $microsoftUser->getEmail();

        return $this->findLinkedUser($microsoftUser, $microsoftTenantId)
            ?? ($email ? User::withTrashed()->where('email', $email)->first() : null)
            ?? ($email ? $this->findRenamedAccount($microsoftUser, $email) : null);
    }

    public function link(User $user, SocialiteUser $microsoftUser, ?string $microsoftTenantId): void
    {
        $objectId = (string) $microsoftUser->getId();

        if (! $microsoftTenantId || $objectId === '') {
            return;
        }

        UserMicrosoftIdentity::query()->updateOrCreate(
            ['microsoft_tenant_id' => $microsoftTenantId, 'microsoft_object_id' => $objectId],
            ['user_id' => $user->id, 'principal_name' => (string) $microsoftUser->getEmail(), 'last_login_at' => now()],
        );
    }

    private function findLinkedUser(SocialiteUser $microsoftUser, ?string $microsoftTenantId): ?User
    {
        $objectId = (string) $microsoftUser->getId();

        if (! $microsoftTenantId || $objectId === '') {
            return null;
        }

        return UserMicrosoftIdentity::query()
            ->where('microsoft_tenant_id', $microsoftTenantId)
            ->where('microsoft_object_id', $objectId)
            ->first()
            ?->user;
    }

    /**
     * One-way: a faculty student and staff address accept each other, a cr.vu.lt login accepts
     * either at any faculty, and nothing reaches a cr.vu.lt account. The local part (with any
     * `.2` suffix) must match exactly, only one account may qualify, and the names must agree.
     */
    private function findRenamedAccount(SocialiteUser $microsoftUser, string $email): ?User
    {
        $candidateEmails = $this->renamedAddressCandidates($email);

        if ($candidateEmails === []) {
            return null;
        }

        $candidates = User::withTrashed()->whereIn('email', $candidateEmails)->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        if ($candidates->count() > 1) {
            $this->refuseRename($email, 'several_accounts');

            return null;
        }

        /** @var User $user */
        $user = $candidates->first();

        if (! $this->namesMatch($microsoftUser, $user->name)) {
            $this->refuseRename($email, 'name_mismatch', $user);

            return null;
        }

        if (! $user->trashed()) {
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->event('microsoft_login_linked')
                ->withProperties(['principal_name' => $email, 'account_email' => $user->email])
                ->log('microsoft_login_linked');
        }

        return $user;
    }

    /** @return list<string> */
    private function renamedAddressCandidates(string $email): array
    {
        $email = Str::lower($email);

        if (substr_count($email, '@') !== 1) {
            return [];
        }

        [$localPart, $domain] = explode('@', $email);
        $universityDomain = config('vusa.microsoft_login.university_domain');
        $prefixes = $this->facultyMailPrefixes();

        if ($domain === config('vusa.microsoft_login.central_domain')) {
            return collect($prefixes)
                ->flatMap(fn (string $prefix) => [
                    "{$localPart}@{$prefix}.stud.{$universityDomain}",
                    "{$localPart}@{$prefix}.{$universityDomain}",
                ])
                ->values()
                ->all();
        }

        foreach ($prefixes as $prefix) {
            if ($domain === "{$prefix}.{$universityDomain}") {
                return ["{$localPart}@{$prefix}.stud.{$universityDomain}"];
            }

            if ($domain === "{$prefix}.stud.{$universityDomain}") {
                return ["{$localPart}@{$prefix}.{$universityDomain}"];
            }
        }

        return [];
    }

    /** @return list<string> */
    private function facultyMailPrefixes(): array
    {
        $overrides = config('vusa.microsoft_login.faculty_mail_prefixes', []);

        return Tenant::query()
            ->where('type', TenantType::Padalinys)
            ->pluck('alias')
            ->map(fn (string $alias) => Str::lower($overrides[$alias] ?? $alias))
            ->unique()
            ->values()
            ->all();
    }

    private function namesMatch(SocialiteUser $microsoftUser, string $accountName): bool
    {
        $raw = $microsoftUser instanceof AbstractUser ? $microsoftUser->getRaw() : [];
        $accountWords = $this->nameWords($accountName);

        return collect([
            $microsoftUser->getName(),
            trim(($raw['givenName'] ?? '').' '.($raw['surname'] ?? '')),
        ])
            ->filter(fn (mixed $name) => is_string($name) && trim($name) !== '')
            ->contains(fn (string $name) => $this->nameWords($name) === $accountWords);
    }

    /** @return list<string> */
    private function nameWords(string $name): array
    {
        return Str::of($name)->ascii()->lower()->replaceMatches('/[^a-z]+/', ' ')->squish()
            ->explode(' ')
            ->filter()
            ->sort()
            ->values()
            ->all();
    }

    private function refuseRename(string $email, string $reason, ?User $user = null): void
    {
        Log::warning('Microsoft login: renamed VU address not matched', [
            'principal_name' => $email,
            'reason' => $reason,
            'user_id' => $user?->id,
        ]);
    }
}
