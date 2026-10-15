<?php

namespace Database\Seeders;

use App\Actions\PairTranslatedRecord;
use App\Enums\AgendaItemType;
use App\Enums\InstitutionActivityCampaign;
use App\Enums\MeetingType;
use App\Events\MeetingFullyCreated;
use App\Models\Cadence;
use App\Models\Content;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\FieldResponse;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Institution;
use App\Models\InstitutionActivityRequest;
use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Page;
use App\Models\Pivots\AgendaItem;
use App\Models\Pivots\Dutiable;
use App\Models\Problem;
use App\Models\ProblemCategory;
use App\Models\Registration;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Tenant;
use App\Models\User;
use App\Support\MeetingTitle;
use App\Tasks\Handlers\PeriodicityGapTaskHandler;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tiptap\Editor;

/**
 * A small, hand-written world for the screenshots shown in the docs (tests/Browser README,
 * "Docs screenshots"). Faker's en_US company names and lorem text read as placeholders there.
 */
class DocsSeeder extends Seeder
{
    public const REPRESENTATIVE_EMAIL = 'ieva.petraityte@vusa.test';

    public const RESOURCE_MANAGER_EMAIL = 'tomas.kazlauskas@vusa.test';

    public const COORDINATOR_EMAIL = 'greta.urbonaite@vusa.test';

    public const CENTRAL_COORDINATOR_EMAIL = 'jonas.rimkus@vusa.test';

    public const MIXED_RESERVATION_NAME = 'Studentų dienų stendas';

    public const TIMELINE_INSTITUTION = 'VU SA parlamentas';

    public const COUNCIL_INSTITUTION = 'Chemijos ir geomokslų fakulteto taryba';

    public const UNFINISHED_AGENDA_ITEM = 'Bendrabučių vietų skirstymas';

    public const OPEN_PROBLEM = 'Bendrabučių vietos skirstomos per vėlai';

    public const RESOLVED_PROBLEM = 'Laboratorinių darbų tvarkaraštis kirtosi su paskaitomis';

    public const COMMUNICATION_COORDINATOR_EMAIL = 'rugile.navickaite@vusa.test';

    public const NEWS_TITLE = 'Prasideda registracija į pirmakursių stovyklą';

    public const PAGE_TITLE = 'Kaip tapti studentų atstovu';

    public const REGISTRATION_FORM = 'Pirmakursių stovyklos registracija';

    public function run(): void
    {
        $tenant = Tenant::query()->firstOrFail();

        $council = $this->institution($tenant, self::COUNCIL_INSTITUTION, 'Faculty of Chemistry and Geosciences Council', 'CGF taryba');
        $senate = $this->institution($tenant, 'Vilniaus universiteto Senatas', 'Vilnius University Senate', 'VU Senatas');

        $representative = User::factory()->create([
            'name' => 'Ieva Petraitytė',
            'email' => self::REPRESENTATIVE_EMAIL,
        ]);

        $committee = $this->institution($tenant, 'Chemijos studijų programos komitetas', 'Chemistry Study Programme Committee', 'Chemijos SPK');

        $representativeType = DutyType::query()->where('slug', 'studentu-atstovai')->firstOrFail();

        // A VU body type, so the institution frame shows its governance scope instead of a dash.
        $council->types()->attach(InstitutionType::query()->where('slug', 'studentu-atstovu-organas')->firstOrFail());

        // Duty inboxes show up in the notification settings frame; faker addresses read as placeholders.
        foreach (['chgf.taryba@vusa.lt' => $council, 'senatas@vusa.lt' => $senate, 'chemija.spk@vusa.lt' => $committee] as $email => $institution) {
            $duty = Duty::factory()->for($institution)->create([
                'name' => ['lt' => 'Studentų atstovė', 'en' => 'Student representative'],
                'description' => ['lt' => '', 'en' => ''],
                'email' => $email,
            ]);
            $representative->duties()->attach($duty, ['start_date' => now()->subYear()]);
            $duty->types()->attach($representativeType);
            $duty->assignRole('Studentų atstovas');
        }

        // Tasks come from the app's own subscribers, so each frame shows what a rep really gets:
        // agenda items → "Užpildyti darbotvarkės klausimų informaciją", none → "Sukurti … klausimus".
        $pastMeeting = $this->meeting($council, now()->subDays(12)->setTime(15, 0), MeetingType::InPerson, [
            ['lt' => 'Egzaminų sesijos rezultatai', 'en' => 'Exam session results'],
            ['lt' => self::UNFINISHED_AGENDA_ITEM, 'en' => 'Dormitory allocation'],
        ]);
        $this->recordVotes($pastMeeting);

        $this->meeting($council, now()->addDays(3)->setTime(15, 0), MeetingType::InPerson, [
            ['lt' => 'Studijų programų atnaujinimas', 'en' => 'Study programme renewal'],
            ['lt' => 'Stipendijų skyrimo tvarka', 'en' => 'Scholarship allocation rules'],
            ['lt' => 'Einamieji klausimai', 'en' => 'Other business'],
        ]);

        $this->meeting($senate, now()->addDays(11)->setTime(13, 0), MeetingType::Remote, []);

        app(PeriodicityGapTaskHandler::class)->findOrCreate($committee, collect([$representative]), now()->addDays(5));

        $this->reservations($tenant, $representative);
        $this->problems($tenant, $representative, $council, $committee);
        $this->dutyTimeline($tenant);
        $this->coordinators($tenant);
        $this->activityRequests($tenant, $representative, $committee);
        $this->content($tenant);
    }

    /**
     * A communication coordinator with a news item, a page paired with its English version and a
     * filled-in registration form, so the Svetainė and Formos frames show real content.
     */
    private function content(Tenant $tenant): void
    {
        $board = Institution::query()->where('name->lt', 'VU SA padalinio valdyba')->firstOrFail();
        $duty = Duty::factory()->for($board)->create([
            'name' => ['lt' => 'Komunikacijos koordinatorė', 'en' => 'Communication coordinator'],
            'description' => ['lt' => '', 'en' => ''],
        ]);
        $duty->assignRole('Komunikacijos koordinatorius');

        User::factory()->create(['name' => 'Rugilė Navickaitė', 'email' => self::COMMUNICATION_COORDINATOR_EMAIL])
            ->duties()->attach($duty, ['start_date' => now()->subYear()]);

        News::factory()->for($tenant)->create([
            'title' => self::NEWS_TITLE,
            'permalink' => 'prasideda-registracija-i-pirmakursiu-stovykla',
            'short' => 'Trys dienos prie ežero su naujais kurso draugais: registruokis iki rugsėjo 20 d.',
            'content_id' => $this->richContent(
                '<p>Kviečiame visus pirmakursius į tradicinę stovyklą prie Asvejos ežero. Laukia pažintiniai žaidimai, susitikimas su dėstytojais ir vakaras prie laužo.</p><p>Vietų skaičius ribotas, todėl registruokis kuo greičiau.</p>'
            ),
            'image' => '/images/placeholders/foto2.jpg',
            'important' => false,
            'draft' => false,
            'publish_time' => now()->subDays(2)->setTime(10, 0),
            'lang' => 'lt',
        ]);

        $page = Page::factory()->for($tenant)->create([
            'title' => self::PAGE_TITLE,
            'permalink' => 'kaip-tapti-studentu-atstovu',
            'content_id' => $this->richContent(
                '<p>Studentų atstovai kalba studentų vardu fakulteto tarybose, studijų programų komitetuose ir Senate.</p><p>Užpildyk registracijos formą, o padalinio koordinatorius susisieks su tavimi ir papasakos apie rinkimus.</p>'
            ),
            'is_active' => true,
            'lang' => 'lt',
        ]);
        $englishPage = Page::factory()->for($tenant)->create([
            'title' => 'How to become a student representative',
            'permalink' => 'how-to-become-a-student-representative',
            'content_id' => $this->richContent(
                '<p>Student representatives speak for students in faculty councils, study programme committees and the Senate.</p><p>Fill in the registration form and the unit coordinator will tell you about the elections.</p>'
            ),
            'is_active' => true,
            'lang' => 'en',
        ]);
        PairTranslatedRecord::execute($page, $englishPage->id);

        $this->registrationForm($tenant);
    }

    private function registrationForm(Tenant $tenant): void
    {
        $form = Form::factory()->for($tenant)->create([
            'name' => ['lt' => self::REGISTRATION_FORM, 'en' => 'First-year camp registration'],
            'description' => ['lt' => '<p>Registracija į rugsėjo pabaigos stovyklą.</p>', 'en' => '<p>Registration for the camp at the end of September.</p>'],
            'path' => ['lt' => 'pirmakursiu-stovykla', 'en' => 'first-year-camp'],
            'publish_time' => now()->subWeek(),
        ]);

        $fields = collect([
            ['Vardas ir pavardė', 'Full name', 'string'],
            ['El. paštas', 'Email', 'string'],
            ['Studijų programa', 'Study programme', 'string'],
            ['Reikia nakvynės', 'Needs accommodation', 'boolean'],
        ])->map(fn (array $field, int $order): FormField => FormField::factory()->for($form)->create([
            'label' => ['lt' => $field[0], 'en' => $field[1]],
            'description' => ['lt' => '', 'en' => ''],
            'type' => $field[2],
            'is_required' => $field[2] === 'string',
            'order' => $order,
        ]));

        foreach ([
            ['Paulius Mockus', 'paulius.mockus@stud.vu.lt', 'Chemija', true],
            ['Karolina Jankauskaitė', 'karolina.jankauskaite@stud.vu.lt', 'Geologija', false],
            ['Domantas Vasiliauskas', 'domantas.vasiliauskas@stud.vu.lt', 'Biochemija', true],
        ] as $daysAgo => $answers) {
            $registration = Registration::factory()->for($form)->create(['created_at' => now()->subDays($daysAgo + 1)]);

            foreach ($fields as $index => $field) {
                FieldResponse::factory()->create([
                    'registration_id' => $registration->id,
                    'form_field_id' => $field->id,
                    'response' => ['value' => $answers[$index]],
                ]);
            }
        }
    }

    private function richContent(string $html): int
    {
        $content = Content::query()->create();
        $content->parts()->create([
            'type' => 'tiptap',
            'order' => 0,
            'json_content' => (new Editor)->setContent($html)->getDocument(),
        ]);

        return $content->id;
    }

    /**
     * Open, in-progress and resolved problems, one raised in the council's dormitory item, so the
     * list, record and institution frames show every status and the meeting link.
     */
    private function problems(Tenant $tenant, User $representative, Institution $council, Institution $committee): void
    {
        $this->call(ProblemCategorySeeder::class);
        $category = fn (string $slug): int => ProblemCategory::query()->where('slug', $slug)->value('id');

        $open = Problem::query()->create([
            'title' => ['lt' => self::OPEN_PROBLEM, 'en' => 'Dormitory places are allocated too late'],
            'description' => [
                'lt' => '<p>Pirmakursiai sužino, ar gavo vietą bendrabutyje, tik likus savaitei iki mokslo metų pradžios. Kitų miestų studentams tenka skubiai ieškoti būsto.</p>',
                'en' => '<p>First-year students learn whether they have a dormitory place only a week before term starts.</p>',
            ],
            'steps_taken' => [
                'lt' => '<p>Surinkome 40 studentų atsiliepimų ir pristatėme juos fakulteto tarybai.</p>',
                'en' => '<p>Collected feedback from 40 students and presented it to the faculty council.</p>',
            ],
            'tenant_id' => $tenant->id,
            'created_by' => $representative->id,
            'responsible_user_id' => $representative->id,
            'occurred_at' => now()->subDays(40),
            'status' => 'in_progress',
        ]);
        $open->categories()->attach([$category('administrative'), $category('processes')]);
        $open->institutions()->attach($council);
        $open->agendaItems()->attach(
            AgendaItem::query()->where('title->lt', self::UNFINISHED_AGENDA_ITEM)->firstOrFail()
        );

        $resolved = Problem::query()->create([
            'title' => ['lt' => self::RESOLVED_PROBLEM, 'en' => 'Lab schedule clashed with lectures'],
            'description' => [
                'lt' => '<p>Antro kurso chemikų laboratoriniai darbai buvo suplanuoti tuo pačiu metu kaip privaloma paskaita.</p>',
                'en' => '<p>Second-year chemistry labs were scheduled at the same time as a mandatory lecture.</p>',
            ],
            'solution' => [
                'lt' => '<p>Studijų programos komitetas perkėlė laboratorinius darbus į ketvirtadienio popietę.</p>',
                'en' => '<p>The study programme committee moved the labs to Thursday afternoon.</p>',
            ],
            'tenant_id' => $tenant->id,
            'created_by' => $representative->id,
            'occurred_at' => now()->subDays(75),
            'resolved_at' => now()->subDays(50),
            'status' => 'resolved',
        ]);
        $resolved->categories()->attach($category('processes'));
        $resolved->institutions()->attach($committee);

        $newProblem = Problem::query()->create([
            'title' => ['lt' => 'Trūksta informacijos apie pakartotinius egzaminus', 'en' => 'Missing information on exam retakes'],
            'description' => [
                'lt' => '<p>Studentai neranda, kur skelbiamos pakartotinių egzaminų datos.</p>',
                'en' => '<p>Students cannot find where retake dates are announced.</p>',
            ],
            'tenant_id' => $tenant->id,
            'created_by' => $representative->id,
            'occurred_at' => now()->subDays(6),
            'status' => 'open',
        ]);
        $newProblem->categories()->attach($category('communication'));
    }

    /**
     * One item fully recorded and one still missing whether the outcome favoured students, so the
     * meeting and agenda item frames show both a finished row and the checklist's open step.
     */
    private function recordVotes(Meeting $meeting): void
    {
        [$finished, $unfinished] = $meeting->agendaItems()->orderBy('order')->get()->all();

        foreach ([[$finished, 'positive'], [$unfinished, null]] as [$item, $benefit]) {
            $item->update(['type' => AgendaItemType::Voting]);
            $item->votes()->create([
                'is_main' => true,
                'decision' => 'positive',
                'student_vote' => 'positive',
                'student_benefit' => $benefit,
            ]);
        }
    }

    /**
     * The padalinys coordinator sees the Padaliniai statistics for this padalinys only; the
     * central one also reads every padalinys' tasks, which the Užduotys frame needs.
     */
    private function coordinators(Tenant $tenant): void
    {
        $board = $this->institution($tenant, 'VU SA padalinio valdyba', 'VU SR unit board', 'Valdyba');

        foreach ([
            [self::COORDINATOR_EMAIL, 'Greta Urbonaitė', 'Studentų atstovų koordinatorė', 'Student representative coordinator', 'Studentų atstovų koordinatorius'],
            [self::CENTRAL_COORDINATOR_EMAIL, 'Jonas Rimkus', 'Centrinio biuro studentų atstovų koordinatorius', 'Central office student representative coordinator', 'Centrinio biuro studentų atstovų koordinatorius'],
        ] as [$email, $name, $dutyLt, $dutyEn, $role]) {
            $duty = Duty::factory()->for($board)->create([
                'name' => ['lt' => $dutyLt, 'en' => $dutyEn],
                'description' => ['lt' => '', 'en' => ''],
            ]);
            $duty->assignRole($role);

            User::factory()->create(['name' => $name, 'email' => $email])
                ->duties()->attach($duty, ['start_date' => now()->subYear()]);
        }
    }

    /**
     * An open activity request from the coordinator to the representative for the committee,
     * providing realistic data for the activity request tab and documentation frames.
     */
    private function activityRequests(Tenant $tenant, User $representative, Institution $committee): void
    {
        $coordinator = User::query()->where('email', self::COORDINATOR_EMAIL)->firstOrFail();

        $request = InstitutionActivityRequest::query()
            ->where('institution_id', $committee->id)
            ->where('recipient_id', $representative->id)
            ->first();

        if ($request !== null) {
            $request->update([
                'requested_by_id' => $coordinator->id,
                'note' => 'Ar per pastarąjį mėnesį vyko Chemijos SPK posėdis? Laukiame informacijos apie priimtus sprendimus.',
                'locale' => 'lt',
            ]);
        } else {
            InstitutionActivityRequest::factory()->create([
                'institution_id' => $committee->id,
                'recipient_id' => $representative->id,
                'requested_by_id' => $coordinator->id,
                'campaign_type' => InstitutionActivityCampaign::ActivityConfirmation,
                'period_start' => now()->subDays(30),
                'period_end' => today(),
                'note' => 'Ar per pastarąjį mėnesį vyko Chemijos SPK posėdis? Laukiame informacijos apie priimtus sprendimus.',
                'locale' => 'lt',
            ]);
        }
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

    /**
     * Three terms of a parliament: ended seats, current ones, a re-election and one date off
     * the month grid, so the Pareigybių laikotarpiai frame shows every mark the guide explains.
     */
    private function dutyTimeline(Tenant $tenant): void
    {
        $parliament = $this->institution($tenant, self::TIMELINE_INSTITUTION, 'VU SR Parliament', 'Parlamentas');

        // Terms run July to June; `$current` is the start year of the one in progress.
        $current = now()->month >= 7 ? now()->year : now()->year - 1;

        foreach ([$current - 2, $current - 1, $current] as $year) {
            Cadence::factory()->create([
                'institution_id' => null,
                'start_date' => sprintf('%d-07-01', $year),
                'end_date' => sprintf('%d-06-30', $year + 1),
            ]);
        }

        $chair = Duty::factory()->for($parliament)->create([
            'name' => ['lt' => 'Pirmininkas (-ė)', 'en' => 'Chair'],
            'description' => ['lt' => '', 'en' => ''],
            'email' => 'parlamentas@vusa.lt',
            'places_to_occupy' => 1,
        ]);
        $member = Duty::factory()->for($parliament)->create([
            'name' => ['lt' => 'Parlamento narys (-ė)', 'en' => 'Member of Parliament'],
            'description' => ['lt' => '', 'en' => ''],
            'email' => 'parlamento.nariai@vusa.lt',
            'places_to_occupy' => 5,
        ]);

        $term = fn (int $year): array => [sprintf('%d-07-01', $year), sprintf('%d-06-30', $year + 1)];

        $seats = [
            [$chair, 'Rūta Vaitkutė', $term($current - 2)],
            [$chair, 'Mantas Jonaitis', [$term($current - 1)[0], null]],
            [$member, 'Mantas Jonaitis', $term($current - 2)],
            [$member, 'Gabija Stankevičiūtė', [$term($current - 2)[0], $term($current - 1)[1]]],
            [$member, 'Lukas Žukauskas', $term($current - 1)],
            [$member, 'Austėja Kazlauskaitė', [sprintf('%d-09-15', $current - 1), null]],
            [$member, 'Dovydas Paulauskas', [$term($current)[0], null]],
            [$member, 'Emilija Petrauskaitė', [$term($current)[0], null]],
        ];

        $people = [];

        foreach ($seats as [$duty, $name, [$start, $end]]) {
            $people[$name] ??= User::factory()->create([
                'name' => $name,
                'email' => Str::slug($name, '.').'@vusa.test',
                'phone' => sprintf('+370 6%02d %05d', count($people) + 10, 12345 + count($people)),
            ]);

            Dutiable::factory()->create([
                'duty_id' => $duty->id,
                'dutiable_id' => $people[$name]->id,
                'study_program_id' => null,
                'additional_email' => null,
                'additional_photo' => null,
                'additional_photo_focal_point' => null,
                'description' => null,
                'use_original_duty_name' => false,
                'start_date' => $start,
                'end_date' => $end,
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
