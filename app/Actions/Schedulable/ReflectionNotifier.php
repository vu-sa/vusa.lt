<?php

namespace App\Actions\Schedulable;

use App\Models\Meeting;
use App\Models\User;
use App\Notifications\ReflectNotification;
use Illuminate\Support\Facades\Notification;

class ReflectionNotifier {
    public static function notifyUsers() {
        // check if user last action is not null, then send a notification to all those users
        // TODO: change the criteria in the future
        $users = User::whereNotNull('last_action')->get();
        
        Notification::send($users, new ReflectNotification());
    }
}