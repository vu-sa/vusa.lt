<?php

use App\Models\Pivots\AgendaItem;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
 * Presence channel for the private real-time collaborative agenda-item notes.
 *
 * Membership is the single authorization gate for the realtime layer: only users
 * who may "update" the agenda item can join, send/receive whispered Y.js updates,
 * and appear in the live presence list. Returning an array authorizes the member.
 */
Broadcast::channel('agenda-item-notes.{agendaItemId}', function ($user, string $agendaItemId) {
    $agendaItem = AgendaItem::find($agendaItemId);

    if (! $agendaItem || ! Gate::forUser($user)->allows('update', $agendaItem)) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
        'profile_photo_path' => $user->profile_photo_path,
    ];
});
