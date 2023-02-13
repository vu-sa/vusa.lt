<?php

namespace App\Actions;

use App\Models\Duty;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail {
        
        public static function execute() {

            $users = User::withWhereHas('duties', function ($query) {
                $query->with('institution')->whereHas('types', function ($query) {
                    $query->where('slug', 'studentu-atstovai');
                });
            })->get();

            foreach ($users as $user) {
                
                // check if valid user mail
                if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                Mail::to($user->email)->send(new \App\Mail\WelcomeEmail($user));
            }
        }
}