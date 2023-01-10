<?php

namespace App\Policies;

use App\Models\Pivots\AgendaItem;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Policies\Traits\UseUserDutiesForAuthorization;
use Illuminate\Support\Str;
use App\Enums\ModelEnum;

class AgendaItemPolicy
{
    use HandlesAuthorization, UseUserDutiesForAuthorization;

    private $pluralModelName;

    public function __construct()
    {
        $this->pluralModelName = Str::plural(ModelEnum::AGENDA_ITEM()->label);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user): bool
    {
        return $this->forUser($user)->check($this->pluralModelName . '.index.padalinys');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pivots\AgendaItem  $agendaItem
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, AgendaItem $agendaItem)
    {
        //
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pivots\AgendaItem  $agendaItem
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, AgendaItem $agendaItem)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pivots\AgendaItem  $agendaItem
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, AgendaItem $agendaItem)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pivots\AgendaItem  $agendaItem
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, AgendaItem $agendaItem)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Pivots\AgendaItem  $agendaItem
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, AgendaItem $agendaItem)
    {
        //
    }
}
