<?php

namespace App\Policies;

use App\Enums\ModelEnum;
use App\Models\MailingList;
use App\Models\User;
use App\Services\ModelAuthorizer;
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
    public function viewArchive(User $user, MailingList $mailingList): bool
    {
        return $this->view($user, $mailingList) || $this->isSubscribed($user, $mailingList);
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
