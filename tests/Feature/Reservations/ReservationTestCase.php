<?php

namespace Tests\Feature\Reservations;

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Padalinys;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversNothing;
use Tests\TestCase;

#[CoversNothing]
class ReservationTestCase extends TestCase
{
    use RefreshDatabase;

    protected $simpleUser;

    protected $reservationManagerUser;

    protected $resourcePadalinys;

    protected $resourceManagerUser;

    protected $reservation;

    protected $resources;

    public function setUp(): void
    {
        parent::setUp();

        $this->resourcePadalinys = Padalinys::inRandomOrder()->first();

        $this->resources = Resource::factory()->for($this->resourcePadalinys)->count(3)->create();
        $this->reservation = Reservation::factory()->hasAttached($this->resources)->create();

        $this->simpleUser = User::factory()->create();
        $this->reservationManagerUser = User::factory()->hasAttached($this->reservation)->create();
        $this->resourceManagerUser = User::factory()->create();

        $resourceManagerDuty = Duty::factory()->has(Institution::factory()->state(
            ['padalinys_id' => $this->resourcePadalinys->id]
        ))->hasAttached($this->resourceManagerUser, ['start_date' => now()->subDays(1), 'end_date' => now()->addDays(1)])->create();

        $resourceManagerRole = Role::factory()->create();
        $resourceManagerRole->givePermissionTo(['reservations.read.*', config('permission.resource_managership_indicating_permission')]);

        $resourceManagerDuty->assignRole($resourceManagerRole);
    }

    // Reservation users, resource admins
}
