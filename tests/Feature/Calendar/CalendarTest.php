<?php

use App\Models\Calendar;
use App\Models\Padalinys;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->padalinys = Padalinys::query()->inRandomOrder()->first();

    $this->user = makeUser($this->padalinys);

    $this->admin = makeCalendarManager($this->padalinys);
});

function makeCalendarManager($padalinys): User
{
    $user = makeUser($padalinys);

    $user->duties()->first()->assignRole('Communication Coordinator');

    return $user;
}

describe('auth: simple user', function () {
    beforeEach(function () {
        asUser($this->user)->get(route('dashboard'))->assertStatus(200);
    });
    
    test('can\'t index calendar', function () {
        asUser($this->user)->get(route('calendar.index'))->assertStatus(302)->assertRedirectToRoute('dashboard');
    });

    test('can\'t access calendar event create page', function () {
        asUser($this->user)->get(route('calendar.create'))->assertStatus(302);
    });

    test('can\'t store calendar event', function () {
        asUser($this->user)->post(route('calendar.store'), [
            'title' => 'Test event',
            'description' => 'Test event description',
            // Emulate JS date picker
            'start_date' => strtotime(now()->format('Y-m-d H:i:s')) * 1000,
            'end_date' => strtotime(now()->addHour()->format('Y-m-d H:i:s')) * 1000,
        ])->assertStatus(302);
    });

    test('can\' t access the calendar event edit page', function () {
        $calendar = Calendar::query()->first();

        asUser($this->user)->get(route('calendar.edit', $calendar))->assertStatus(302);
    });

    test('simple user cant update calendar', function () {
        $calendar = Calendar::query()->first();

        asUser($this->user)->put(route('calendar.update', $calendar), [
            'title' => 'Test event updated',
            'description' => 'Test event description updated',
            // Emulate JS date picker
            'start_date' => strtotime(now()->addDay()->format('Y-m-d H:i:s')) * 1000,
            'end_date' => strtotime(now()->addDay()->addHour()->format('Y-m-d H:i:s')) * 1000,
        ])->assertStatus(302);
    });

    test('simple user cant delete calendar', function () {
        $calendar = Calendar::query()->first();

        asUser($this->user)->delete(route('calendar.destroy', $calendar))->assertStatus(302);
    });
});
