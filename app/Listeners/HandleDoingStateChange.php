<?php

namespace App\Listeners;

use App\Models\Doing;
use App\Notifications\StateChangeNotification;
use App\States\Doing\Approved;
use App\States\Doing\Cancelled;
use App\States\Doing\Completed;
use App\States\Doing\Draft;
use App\States\Doing\PendingChanges;
use App\States\Doing\PendingCompletion;
use App\States\Doing\PendingFinalApproval;
use App\States\Doing\PendingPadalinysApproval;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Spatie\ModelStates\Events\StateChanged;
use Illuminate\Support\Str;

class HandleDoingStateChange
{
    public function handle(StateChanged $event)
    {
        $doing = $event->model;

        // check if doing is instance of Doing
        if (! $doing instanceof Doing) {
            return;
        }

        $user = Auth::user();

        $subject = [
            'modelClass' => class_basename(get_class($user)),
            'name' => $user->name,
            'image' => $user->profile_photo_path,
        ];

        $routeName = Str::of(class_basename(get_class($doing)))->lcfirst()->plural() . '.show';

        $object = [
            'modelClass' => class_basename(get_class($doing)),
            'name' => optional($doing)->name ?: optional($doing)->title ?: null,
            'url' => route($routeName, $doing->id),
        ];

        // ? maybe do it through transitions, idk

        // To PendingPadalinysApproval
        if (in_array($event->initialState::class, [Draft::class, PendingChanges::class]) && $event->finalState::class === PendingPadalinysApproval::class) {
            
            $text = "<p><strong>{$user->name}</strong> pateikė tvirtinimui: {$doing->title}</p>";
            Notification::send($doing->users, new StateChangeNotification($text, $object, $subject));
        }

        // To PendingFinalApproval
        if ($event->initialState::class === PendingPadalinysApproval::class && $event->finalState::class === PendingFinalApproval::class) {
            $text = "<p><strong>{$user->name}</strong> pateikė galutiniam tvirtinimui: {$doing->title}</p>";
            Notification::send($doing->users, new StateChangeNotification($text, $object, $subject));
        }

        // To Approved
        if ($event->initialState::class === PendingFinalApproval::class && $event->finalState::class === Approved::class) {
            $text = "<p><strong>{$user->name}</strong> patvirtino: {$doing->title}</p>";
            Notification::send($doing->users, new StateChangeNotification($text, $object, $subject));
        }

        // To PendingCompletion
        if ($event->initialState::class === Approved::class && $event->finalState::class === PendingCompletion::class) {
            $text = "<p><strong>{$user->name}</strong> nori užbaigti: {$doing->title}</p>";
            Notification::send($doing->users, new StateChangeNotification($text, $object, $subject));
        }

        // To Completed
        if ($event->initialState::class === PendingCompletion::class && $event->finalState::class === Completed::class) {
            $text = "<p><strong>{$user->name}</strong> patvirtino užbaigimą: {$doing->title}</p>";
            Notification::send($doing->users, new StateChangeNotification($text, $object, $subject));
        }

        // To PendingChanges
        if ($event->finalState::class === PendingChanges::class) {
            $text = "<p><strong>{$user->name}</strong> pateikė papildomoms pastaboms: {$doing->title}</p>";
            Notification::send($doing->users, new StateChangeNotification($text, $object, $subject));
        }

        // if final state is to cancelled
        if ($event->finalState::class === Cancelled::class) {
            $text = "<p><strong>{$user->name}</strong> atšaukė: {$doing->title}</p>";
            Notification::send($doing->users, new StateChangeNotification($text, $object, $subject));
        }
    }
}