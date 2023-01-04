<?php

namespace App\Services;

use App\Models\Doing;
use App\Models\Task;
use App\Models\User;

class TaskCreator
{
    public static function createAutomaticTasks(Doing $doing) {
        self::createMeetingDoingTasks($doing);
    }

    // * Create tasks for users, when creating a doing with meeting type
    
    private static function createMeetingDoingTasks(Doing $doing)
    { 
        if ($doing->types->doesntContain(function ($type) {
            return $type->title === 'Posėdis';
        })) {
            return;
        }

        // check if doing is in today or in future
        // if ($doing->start_date->isToday() || $doing->start_date->isFuture()) {
        // }

        // TODO: fix for multiple matters

        $institution = $doing->matters->first()->institution;

        $users = $institution->duties->pluck('users')->flatten()->unique('id');

        $task = Task::create([
            'name' => 'Pasiruošti posėdžiui',
            'taskable_id' => $doing->id,
            'taskable_type' => Doing::class,
        ]);

        $task->users()->sync($users->pluck('id'));
    }
}