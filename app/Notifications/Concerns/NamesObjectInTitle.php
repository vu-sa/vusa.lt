<?php

namespace App\Notifications\Concerns;

use Illuminate\Support\Str;

trait NamesObjectInTitle
{
    #[\Override]
    public function digestLine(object $notifiable): string
    {
        return Str::limit($this->title($notifiable).' — '.strip_tags($this->body($notifiable)), 200);
    }

    #[\Override]
    public function digestGroupTitle(object $notifiable): string
    {
        return __($this->type()->labelKey());
    }
}
