<?php

namespace App\Notifications\Concerns;

/**
 * A meeting notice sent to someone only because they follow the institution. They asked to hear
 * about it, so it pushes (though the notice itself is only worth knowing), unless they turned
 * "Sekamos institucijos" push off. Overseers get the same notice without the push.
 */
trait ReachesFollowers
{
    protected bool $viaFollow = false;

    public function viaFollow(): static
    {
        $this->viaFollow = true;

        return $this;
    }

    #[\Override]
    public function sendsPush(): bool
    {
        return $this->viaFollow || parent::sendsPush();
    }

    #[\Override]
    protected function wantsPush(object $notifiable): bool
    {
        if (! $this->viaFollow) {
            return parent::wantsPush($notifiable);
        }

        return ! method_exists($notifiable, 'wantsFollowedInstitutionPush') || $notifiable->wantsFollowedInstitutionPush();
    }
}
