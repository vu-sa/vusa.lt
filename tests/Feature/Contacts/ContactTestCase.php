<?php

namespace Tests\Feature\Contacts;

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Padalinys;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversNothing;
use Tests\TestCase;

#[CoversNothing]
class ContactTestCase extends TestCase
{
    use RefreshDatabase;

    protected $simpleUser;

    protected $contactManagerUser;

    protected $padalinys;

    protected $contactManagementDuty;

    public function setUp(): void
    {
        parent::setUp();

        $this->padalinys = Padalinys::inRandomOrder()->first();

        $this->simpleUser = User::factory()->hasAttached(Duty::factory()->for(Institution::factory()->for($this->padalinys)),
            ['start_date' => now()->subDay()]
        )->create();

        $this->contactManagerUser = User::factory()->hasAttached(Duty::factory()->for(Institution::factory()->for($this->padalinys)),
            ['start_date' => now()->subDay()]
        )->create();

        $this->contactManagementDuty = $this->contactManagerUser->duties()->first();

        $this->contactManagementDuty->assignRole('Student Representative Coordinator');
    }
}
