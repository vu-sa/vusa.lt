<?php

namespace Database\Seeders;

use App\Enums\MeetingType;
use App\Events\MeetingFullyCreated;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Support\MeetingTitle;
use App\Tasks\Handlers\PeriodicityGapTaskHandler;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * A small, hand-written world for the screenshots shown in the docs (tests/Browser README,
 * "Docs screenshots"). Faker's en_US company names and lorem text read as placeholders there.
 */
class DocsSeeder extends Seeder
{
    public const REPRESENTATIVE_EMAIL = 'ieva.petraityte@vusa.test';

    public const RESOURCE_MANAGER_EMAIL = 'tomas.kazlauskas@vusa.test';

    public const MIXED_RESERVATION_NAME = 'Studentų dienų stendas';

    public function run(): void
    {
        $tenant = Tenant::query()->firstOrFail();

        $council = $this->institution($tenant, 'Chemijos ir geomokslų fakulteto taryba', 'Faculty of Chemistry and Geosciences Council', 'CGF taryba');
        $senate = $this->institution($tenant, 'Vilniaus universiteto Senatas', 'Vilnius University Senate', 'VU Senatas');

        $representative = User::factory()->create([
            'name' => 'Ieva Petraitytė',
            'email' => self::REPRESENTATIVE_EMAIL,
        ]);

        $committee = $this->institution($tenant, 'Chemijos studijų programos komitetas', 'Chemistry Study Programme Committee', 'Chemijos SPK');

        $representativeType = Type::query()->where('slug', 'studentu-atstovai')->firstOrFail();

        foreach ([$council, $senate, $committee] as $institution) {
            $duty = Duty::factory()->for($institution)->create([
                'name' => ['lt' => 'Studentų atstovė', 'en' => 'Student representative'],
                'description' => ['lt' => '', 'en' => ''],
            ]);
            $representative->duties()->attach($duty, ['start_date' => now()->subYear()]);
            $duty->types()->attach($representativeType);
            $duty->assignRole('Student Representative');
        }

        // Tasks come from the app's own subscribers, so each frame shows what a rep really gets:
        // agenda items → "Užpildyti darbotvarkės klausimų informaciją", none → "Sukurti … klausimus".
        $this->meeting($council, now()->subDays(12)->setTime(15, 0), MeetingType::InPerson, [
            ['lt' => 'Egzaminų sesijos rezultatai', 'en' => 'Exam session results'],
            ['lt' => 'Bendrabučių vietų skirstymas', 'en' => 'Dormitory allocation'],
        ]);

        $this->meeting($council, now()->addDays(3)->setTime(15, 0), MeetingType::InPerson, [
            ['lt' => 'Studijų programų atnaujinimas', 'en' => 'Study programme renewal'],
            ['lt' => 'Stipendijų skyrimo tvarka', 'en' => 'Scholarship allocation rules'],
            ['lt' => 'Einamieji klausimai', 'en' => 'Other business'],
        ]);

        $this->meeting($senate, now()->addDays(11)->setTime(13, 0), MeetingType::Remote, []);

        app(PeriodicityGapTaskHandler::class)->findOrCreate($committee, collect([$representative]), now()->addDays(5));

        $this->reservations($tenant, $representative);
    }

    /**
     * The padalinys' equipment, a manager with requests in every stage, and the representative's
     * cart, so the Rezervacijos frames show a working queue rather than empty states.
     */
    private function reservations(Tenant $tenant, User $representative): void
    {
        $office = $this->institution($tenant, 'VU SA padalinio biuras', 'VU SR unit office', 'Biuras');
        $managerDuty = Duty::factory()->for($office)->create([
            'name' => ['lt' => 'Administratorius', 'en' => 'Administrator'],
            'description' => ['lt' => '', 'en' => ''],
        ]);
        $managerDuty->assignRole(RoleResourceManagerSeeder::NAME);

        $manager = User::factory()->create(['name' => 'Tomas Kazlauskas', 'email' => self::RESOURCE_MANAGER_EMAIL]);
        $manager->duties()->attach($managerDuty, ['start_date' => now()->subYear()]);

        $speaker = $this->resource($tenant, 'Garso kolonėlė JBL PartyBox', 'JBL PartyBox speaker', 2);
        $projector = $this->resource($tenant, 'Projektorius Epson', 'Epson projector', 1);
        $tent = $this->resource($tenant, 'Renginių palapinė 3×3 m', 'Event tent 3×3 m', 4);
        $flag = $this->resource($tenant, 'VU SA vėliava', 'VU SR flag', 5);

        $this->reservation($representative, 'Pirmakursių stovyklos įranga', now()->addDays(6), 3, [[$speaker, 1, 'created'], [$tent, 2, 'created']]);
        $this->reservation($representative, 'Mokymai naujiems atstovams', now()->addDays(2), 1, [[$projector, 1, 'reserved']]);
        $this->reservation($manager, 'Padalinio visuotinis susirinkimas', now()->subDays(4), 2, [[$flag, 2, 'lent']]);
        // One of each next step, so the record page shows Tvirtinti, Išduoti and Grąžinti together.
        $this->reservation($representative, self::MIXED_RESERVATION_NAME, now()->subDay(), 3, [[$tent, 1, 'lent'], [$speaker, 1, 'reserved'], [$flag, 1, 'created']]);

        $cart = $representative->reservationDraft()->create([
            'name' => 'Karjeros dienos stendas',
            'description' => 'Stendui fakulteto fojė: atsiimsime išvakarėse, grąžinsime kitą dieną.',
            'start_time' => now()->addDays(14)->setTime(9, 0),
            'end_time' => now()->addDays(15)->setTime(18, 0),
        ]);
        $cart->items()->create(['resource_id' => $tent->id, 'quantity' => 1]);
        $cart->items()->create(['resource_id' => $flag->id, 'quantity' => 2]);
    }

    private function resource(Tenant $tenant, string $nameLt, string $nameEn, int $capacity): Resource
    {
        return Resource::factory()->for($tenant)->create([
            'name' => ['lt' => $nameLt, 'en' => $nameEn],
            'description' => ['lt' => '', 'en' => ''],
            'location' => 'Padalinio biuras',
            'capacity' => $capacity,
            'is_reservable' => true,
        ]);
    }

    /**
     * Attaching through the pivot fires ReservationResourceCreated, so pending items raise the
     * managers' approval tasks exactly as a submitted cart does.
     *
     * @param  list<array{0: resource, 1: int, 2: string}>  $items
     */
    private function reservation(User $owner, string $name, Carbon $start, int $days, array $items): void
    {
        $reservation = Reservation::create([
            'name' => $name,
            'description' => '',
            'start_time' => $start->copy()->setTime(10, 0),
            'end_time' => $start->copy()->addDays($days)->setTime(16, 0),
        ]);
        $reservation->users()->attach($owner);

        foreach ($items as [$resource, $quantity, $state]) {
            $reservation->resources()->attach($resource->id, [
                'quantity' => $quantity,
                'start_time' => $reservation->start_time,
                'end_time' => $reservation->end_time,
                'state' => $state,
            ]);
        }
    }

    private function institution(Tenant $tenant, string $nameLt, string $nameEn, string $shortName): Institution
    {
        return Institution::factory()->for($tenant)->create([
            'name' => ['lt' => $nameLt, 'en' => $nameEn],
            'short_name' => ['lt' => $shortName, 'en' => $shortName],
            'description' => ['lt' => '', 'en' => ''],
            'image_url' => null,
        ]);
    }

    /**
     * Mirrors MeetingController::store(): generated title, agenda items, then MeetingFullyCreated.
     *
     * @param  array<int, array{lt: string, en: string}>  $agendaTitles
     */
    private function meeting(Institution $institution, Carbon $startTime, MeetingType $type, array $agendaTitles): Meeting
    {
        $meeting = Meeting::create([
            'title' => MeetingTitle::build($startTime, $type, 'lt'),
            'start_time' => $startTime,
            'end_time' => $startTime->copy()->addHours(2),
            'type' => $type,
        ]);
        $meeting->institutions()->attach($institution);

        foreach ($agendaTitles as $order => $title) {
            AgendaItem::factory()->for($meeting)->create([
                'title' => $title,
                'description' => null,
                'order' => $order + 1,
            ]);
        }

        event(new MeetingFullyCreated($meeting));

        return $meeting;
    }
}
