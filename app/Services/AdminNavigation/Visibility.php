<?php

namespace App\Services\AdminNavigation;

use App\Models\User;
use App\Services\ModelAuthorizer;
use Closure;
use Illuminate\Support\Facades\Gate;

/**
 * The gating vocabulary a workspace, section or create action is checked against.
 *
 * Every constructor resolves through the same authorization surfaces the controllers already
 * use (`$user->can()`, `Gate::forUser()`, `ModelAuthorizer::allows()`), so a catalog entry can
 * never grant more than the route it points at already allows — it can only additionally hide.
 */
final readonly class Visibility
{
    private function __construct(private Closure $check) {}

    /**
     * Always visible to an authenticated user (e.g. Pradžia's own sections).
     */
    public static function always(): self
    {
        return new self(fn (User $user): bool => true);
    }

    /**
     * The normal case: a policy ability on a model class, e.g. `Visibility::can('viewAny',
     * Meeting::class)`.
     *
     * @param  class-string  $model
     */
    public static function can(string $ability, string $model): self
    {
        return new self(fn (User $user): bool => $user->can($ability, $model));
    }

    /**
     * A `Gate::define`d ability with no model, e.g. `manage-settings`.
     */
    public static function gate(string $ability): self
    {
        return new self(fn (User $user): bool => Gate::forUser($user)->allows($ability));
    }

    /**
     * A raw permission string, for models with no policy of their own — e.g. `File`, which
     * `ModelEnum`'s docblock notes "is not a model" and is excluded from every policy map.
     */
    public static function permission(string $permission): self
    {
        return new self(fn (User $user): bool => app(ModelAuthorizer::class)->allows($user, $permission));
    }

    public function allows(User $user): bool
    {
        return ($this->check)($user);
    }
}
