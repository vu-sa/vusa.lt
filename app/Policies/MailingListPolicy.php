<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\MailingList;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MailingListPolicy extends ModelPolicy
{
    #[\Override]
    protected bool $hasManyTenants = false;

    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::MAILING_LIST->label());
    }

    /**
     * Like Mailman's private archive: list managers, plus anyone subscribed through one of
     * their own addresses (main email, an assignment's email or a duty mailbox they hold now).
     */
    #[\Override]
    public function view(User $user, Model $mailingList): bool
    {
        return $this->commonChecker($user, $mailingList, CRUDEnum::READ->label(), $this->pluralModelName, false)
            || ($mailingList instanceof MailingList && $this->isSubscribed($user, $mailingList));
    }

    #[\Override]
    public function update(User $user, Model $mailingList): bool
    {
        return $this->commonChecker($user, $mailingList, CRUDEnum::UPDATE->label(), $this->pluralModelName, false);
    }

    #[\Override]
    public function delete(User $user, Model $mailingList): bool
    {
        return $this->commonChecker($user, $mailingList, CRUDEnum::DELETE->label(), $this->pluralModelName, false);
    }

    private function isSubscribed(User $user, MailingList $mailingList): bool
    {
        return $mailingList->members()->whereIn('email', $this->addressesOf($user))->exists();
    }

    /**
     * @return list<string>
     */
    private function addressesOf(User $user): array
    {
        $user->loadMissing('current_duties');

        return collect([$user->email])
            ->merge($user->current_duties->pluck('email'))
            ->merge($user->current_duties->pluck('pivot.additional_email'))
            ->filter()
            ->map(fn (string $email) => strtolower(trim($email)))
            ->unique()
            ->values()
            ->all();
    }
}
