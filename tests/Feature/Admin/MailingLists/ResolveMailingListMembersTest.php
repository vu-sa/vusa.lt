<?php

use App\Actions\ResolveMailingListMembers;
use App\Enums\MailingListAddressMode;
use App\Enums\MailingListSourceType;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\MailingList;
use App\Models\MailingListSource;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\ValueObjects\MailingListRecipient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->main = Tenant::main();
    $this->padalinys = Tenant::query()->where('id', '!=', $this->main->id)->first();
    $this->list = MailingList::factory()->managed()->create(['tenant_id' => $this->main->id]);
});

function mailingListSeat(Institution $institution, string $userEmail, array $pivot = [], array $duty = []): User
{
    $user = User::factory()->create(['email' => $userEmail]);
    $dutyModel = Duty::factory()->for($institution)->create(['email' => null, ...$duty]);
    $user->duties()->attach($dutyModel, ['start_date' => today()->subMonth(), ...$pivot]);

    return $user;
}

function mailingListSource(MailingList $list, MailingListSourceType $type, ?string $sourceId = null, array $attributes = []): MailingListSource
{
    return $list->sources()->create([
        'type' => $type,
        'source_id' => $sourceId,
        'address_mode' => MailingListAddressMode::Holder,
        ...$attributes,
    ]);
}

/** @return list<string> */
function mailingListEmails(MailingList $list): array
{
    return ResolveMailingListMembers::execute($list)->map(fn (MailingListRecipient $r) => $r->email)->all();
}

describe('date window', function (): void {
    test('only assignments in force today count, with the end date inclusive', function (): void {
        $institution = Institution::factory()->for($this->main)->create();
        mailingListSeat($institution, 'current@stud.vu.lt');
        mailingListSeat($institution, 'ends-today@stud.vu.lt', ['end_date' => today()]);
        mailingListSeat($institution, 'ended@stud.vu.lt', ['end_date' => today()->subDay()]);
        mailingListSeat($institution, 'future@stud.vu.lt', ['start_date' => today()->addDay()]);

        mailingListSource($this->list, MailingListSourceType::Institution, $institution->id);

        expect(mailingListEmails($this->list))->toBe(['current@stud.vu.lt', 'ends-today@stud.vu.lt']);
    });

    test('inactive duties and inactive institutions are left out', function (): void {
        $type = InstitutionType::factory()->create();
        $active = Institution::factory()->for($this->main)->create();
        $inactive = Institution::factory()->for($this->main)->create();
        $active->types()->attach($type);
        $inactive->types()->attach($type);

        mailingListSeat($active, 'kept@stud.vu.lt');
        mailingListSeat($active, 'retired-duty@stud.vu.lt', duty: ['is_active' => false]);
        mailingListSeat($inactive, 'inactive-body@stud.vu.lt');
        $inactive->update(['is_active' => false]);

        mailingListSource($this->list, MailingListSourceType::InstitutionType, (string) $type->id);

        expect(mailingListEmails($this->list))->toBe(['kept@stud.vu.lt']);
    });
});

describe('institution types', function (): void {
    beforeEach(function (): void {
        $this->parent = InstitutionType::factory()->create();
        $this->child = InstitutionType::factory()->create(['parent_id' => $this->parent->id]);
        $this->excluded = InstitutionType::factory()->create();

        $this->parentBody = Institution::factory()->for($this->main)->create();
        $this->parentBody->types()->attach($this->parent);
        $this->childBody = Institution::factory()->for($this->main)->create();
        $this->childBody->types()->attach($this->child);
        $this->excludedBody = Institution::factory()->for($this->main)->create();
        $this->excludedBody->types()->attach([$this->parent->id, $this->excluded->id]);
        $this->padalinysBody = Institution::factory()->for($this->padalinys)->create();
        $this->padalinysBody->types()->attach($this->parent);

        mailingListSeat($this->parentBody, 'parent@stud.vu.lt');
        mailingListSeat($this->childBody, 'child@stud.vu.lt');
        mailingListSeat($this->excludedBody, 'excluded@stud.vu.lt');
        mailingListSeat($this->padalinysBody, 'padalinys@stud.vu.lt');
    });

    test('subtypes are included, excluded types and other tenants are not', function (): void {
        mailingListSource($this->list, MailingListSourceType::InstitutionType, (string) $this->parent->id, ['filters' => [
            'tenant_scope' => 'main',
            'exclude_institution_type_ids' => [$this->excluded->id],
        ]]);

        expect(mailingListEmails($this->list))->toBe(['child@stud.vu.lt', 'parent@stud.vu.lt']);
    });

    test('subtypes can be left out', function (): void {
        mailingListSource($this->list, MailingListSourceType::InstitutionType, (string) $this->parent->id, ['filters' => [
            'tenant_scope' => 'main',
            'include_subtypes' => false,
            'exclude_institution_type_ids' => [$this->excluded->id],
        ]]);

        expect(mailingListEmails($this->list))->toBe(['parent@stud.vu.lt']);
    });

    test('a selected tenant scope reaches only the chosen tenants', function (): void {
        mailingListSource($this->list, MailingListSourceType::InstitutionType, (string) $this->parent->id, ['filters' => [
            'tenant_scope' => 'selected',
            'tenant_ids' => [$this->padalinys->id],
        ]]);

        expect(mailingListEmails($this->list))->toBe(['padalinys@stud.vu.lt']);
    });
});

describe('addresses', function (): void {
    test('a duty mailbox source subscribes the mailbox, falling back to holders without one', function (): void {
        $dutyType = DutyType::factory()->create();
        $institution = Institution::factory()->for($this->padalinys)->create();
        $withMailbox = mailingListSeat($institution, 'holder-a@stud.vu.lt', duty: ['email' => 'Atstovai@MIF.vusa.lt']);
        $withoutMailbox = mailingListSeat($institution, 'holder-b@stud.vu.lt');
        $withMailbox->duties()->first()->types()->attach($dutyType);
        $withoutMailbox->duties()->first()->types()->attach($dutyType);

        mailingListSource($this->list, MailingListSourceType::DutyType, (string) $dutyType->id, [
            'address_mode' => MailingListAddressMode::DutyMailbox,
        ]);

        expect(mailingListEmails($this->list))->toBe(['atstovai@mif.vusa.lt', 'holder-b@stud.vu.lt']);
    });

    test('a mailbox source follows an ex-officio seat to the mailbox of the duty it comes with', function (array $sourcePivot, array $seatPivot, string $expected): void {
        $council = Institution::factory()->for($this->main)->create();
        $chair = mailingListSeat(Institution::factory()->for($this->padalinys)->create(), 'chair@ff.stud.vu.lt', $sourcePivot, ['email' => 'pirmininkas@ff.vusa.lt']);
        $chair->duties()->attach(Duty::factory()->for($council)->create(['email' => null]), [
            'start_date' => today()->subMonth(),
            'via_dutiable_id' => Dutiable::query()->where('dutiable_id', $chair->id)->value('id'),
            ...$seatPivot,
        ]);

        mailingListSource($this->list, MailingListSourceType::Institution, $council->id, [
            'address_mode' => MailingListAddressMode::DutyMailbox,
        ]);

        expect(mailingListEmails($this->list))->toBe([$expected]);
    })->with([
        'source duty mailbox' => [[], [], 'pirmininkas@ff.vusa.lt'],
        'source assignment email' => [['additional_email' => 'chair@vusa.lt'], [], 'chair@vusa.lt'],
        'seat assignment email' => [['additional_email' => 'chair@vusa.lt'], ['additional_email' => 'seat@vusa.lt'], 'seat@vusa.lt'],
    ]);

    test('a mailbox source marks holders who fall back to their personal email', function (): void {
        $institution = Institution::factory()->for($this->main)->create();
        mailingListSeat($institution, 'member@stud.vu.lt');

        mailingListSource($this->list, MailingListSourceType::Institution, $institution->id, [
            'address_mode' => MailingListAddressMode::DutyMailbox,
        ]);

        $recipient = ResolveMailingListMembers::execute($this->list)->sole();

        expect($recipient->email)->toBe('member@stud.vu.lt')
            ->and($recipient->reasons[0])->toContain(__('mailing_lists.reasons.mailbox_fallback', ['duty' => '']));
    });

    test('a personal email source ignores the duty an ex-officio seat comes with', function (): void {
        $council = Institution::factory()->for($this->main)->create();
        $chair = mailingListSeat(Institution::factory()->for($this->padalinys)->create(), 'chair@ff.stud.vu.lt', duty: ['email' => 'pirmininkas@ff.vusa.lt']);
        $chair->duties()->attach(Duty::factory()->for($council)->create(['email' => null]), [
            'start_date' => today()->subMonth(),
            'via_dutiable_id' => Dutiable::query()->where('dutiable_id', $chair->id)->value('id'),
        ]);

        mailingListSource($this->list, MailingListSourceType::Institution, $council->id);

        expect(mailingListEmails($this->list))->toBe(['chair@ff.stud.vu.lt']);
    });

    test('a person reached through several assignments is subscribed once, with the assignment email', function (): void {
        $senate = Institution::factory()->for($this->main)->create();
        $commission = Institution::factory()->for($this->main)->create();
        $user = mailingListSeat($commission, 'povilas@tf.stud.vu.lt');
        $user->duties()->attach(
            Duty::factory()->for($senate)->create(['email' => null]),
            ['start_date' => today()->subMonth(), 'additional_email' => 'povilas@cr.vu.lt'],
        );

        mailingListSource($this->list, MailingListSourceType::Institution, $commission->id);
        mailingListSource($this->list, MailingListSourceType::Institution, $senate->id);

        $recipients = ResolveMailingListMembers::execute($this->list);

        expect($recipients)->toHaveCount(1)
            ->and($recipients->first()->email)->toBe('povilas@cr.vu.lt')
            ->and($recipients->first()->reasons)->toHaveCount(2);
    });

    test('exclusions win over every source and expired manual entries are dropped', function (): void {
        mailingListSource($this->list, MailingListSourceType::Email, attributes: ['email' => 'kept@vusa.lt']);
        mailingListSource($this->list, MailingListSourceType::Email, attributes: ['email' => 'excluded@vusa.lt']);
        mailingListSource($this->list, MailingListSourceType::Email, attributes: ['email' => 'expired@vusa.lt', 'expires_on' => today()->subDay()]);
        $this->list->exclusions()->create(['email' => 'EXCLUDED@vusa.lt']);

        expect(mailingListEmails($this->list))->toBe(['kept@vusa.lt']);
    });
});

test('a nested list is included once even when lists include each other or reference an uppercase ULID', function (): void {
    $other = MailingList::factory()->managed()->create(['tenant_id' => $this->main->id]);
    mailingListSource($other, MailingListSourceType::Email, attributes: ['email' => 'nested@vusa.lt']);
    mailingListSource($other, MailingListSourceType::MailingList, strtoupper($this->list->id));
    mailingListSource($this->list, MailingListSourceType::MailingList, strtoupper($other->id));
    mailingListSource($this->list, MailingListSourceType::Email, attributes: ['email' => 'own@vusa.lt']);

    DB::table('mailing_list_sources')->insert([
        'id' => (string) Str::ulid(),
        'mailing_list_id' => $this->list->id,
        'type' => MailingListSourceType::MailingList->value,
        'source_id' => strtoupper($this->list->id),
        'address_mode' => MailingListAddressMode::Holder->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(mailingListEmails($this->list))->toBe(['nested@vusa.lt', 'own@vusa.lt']);
});

test('a nested list applies its exclusions before contributing recipients', function (): void {
    $nested = MailingList::factory()->managed()->for($this->main)->create();
    mailingListSource($nested, MailingListSourceType::Email, attributes: ['email' => 'excluded@vusa.lt']);
    mailingListSource($nested, MailingListSourceType::Email, attributes: ['email' => 'kept@vusa.lt']);
    $nested->exclusions()->create(['email' => 'excluded@vusa.lt']);
    mailingListSource($this->list, MailingListSourceType::MailingList, $nested->id);

    expect(mailingListEmails($this->list))->toBe(['kept@vusa.lt']);
});

test('a manually managed nested list contributes its imported roster rather than draft sources', function (): void {
    $nested = MailingList::factory()->for($this->main)->create();
    $nested->members()->create(['email' => 'actual@vusa.lt']);
    mailingListSource($nested, MailingListSourceType::Email, attributes: ['email' => 'draft@vusa.lt']);
    mailingListSource($this->list, MailingListSourceType::MailingList, $nested->id);

    expect(mailingListEmails($this->list))->toBe(['actual@vusa.lt']);
});

test('inactive institutions are excluded for direct institution and duty sources', function (string $type): void {
    $institution = Institution::factory()->for($this->main)->create();
    $user = mailingListSeat($institution, 'inactive@vusa.lt');
    $institution->update(['is_active' => false]);
    $target = $type === 'duty' ? $user->duties()->first()->id : $institution->id;
    mailingListSource($this->list, MailingListSourceType::from($type), $target);

    expect(mailingListEmails($this->list))->toBeEmpty();
})->with(['duty', 'institution']);

test('source expiry uses the requested date inclusively', function (): void {
    $this->travelTo(today()->setDate(2026, 10, 16));
    mailingListSource($this->list, MailingListSourceType::Email, attributes: ['email' => 'dated@vusa.lt', 'expires_on' => '2026-10-10']);

    expect(ResolveMailingListMembers::execute($this->list, Carbon::parse('2026-10-10'))->pluck('email')->all())
        ->toBe(['dated@vusa.lt'])
        ->and(ResolveMailingListMembers::execute($this->list, Carbon::parse('2026-10-11'))->all())->toBeEmpty();
});

test('a stored source boundary still applies when its duty moves to another tenant', function (): void {
    $institution = Institution::factory()->for($this->main)->create();
    $user = mailingListSeat($institution, 'moved@vusa.lt');
    mailingListSource($this->list, MailingListSourceType::Duty, $user->duties()->first()->id, [
        'filters' => ['authorized_tenant_ids' => [$this->main->id]],
    ]);
    expect(mailingListEmails($this->list))->toBe(['moved@vusa.lt']);

    $institution->update(['tenant_id' => $this->padalinys->id]);

    expect(mailingListEmails($this->list))->toBeEmpty();
});

test('a deleted root duty type does not continue including its live descendants', function (): void {
    $root = DutyType::factory()->create();
    $child = DutyType::factory()->create(['parent_id' => $root->id]);
    $institution = Institution::factory()->for($this->main)->create();
    $user = mailingListSeat($institution, 'child@vusa.lt');
    $user->duties()->first()->types()->attach($child);
    mailingListSource($this->list, MailingListSourceType::DutyType, (string) $root->id);
    expect(mailingListEmails($this->list))->toBe(['child@vusa.lt']);

    $root->delete();

    expect(mailingListEmails($this->list))->toBeEmpty();
});

test('a responsibility source reaches its holders however it was granted, within the tenant scope', function (): void {
    $responsibility = Role::factory()->create();
    $type = DutyType::factory()->create();
    $direct = mailingListSeat(Institution::factory()->for($this->main)->create(), 'direct@stud.vu.lt');
    $viaType = mailingListSeat(Institution::factory()->for($this->main)->create(), 'via-type@stud.vu.lt');
    $elsewhere = mailingListSeat(Institution::factory()->for($this->padalinys)->create(), 'elsewhere@stud.vu.lt');
    mailingListSeat(Institution::factory()->for($this->main)->create(), 'ended@stud.vu.lt', ['end_date' => today()->subDay()])
        ->duties()->first()->assignRole($responsibility);

    $direct->duties()->first()->assignRole($responsibility);
    $viaType->duties()->first()->types()->attach($type);
    $type->responsibilityGrants()->create(['role_id' => $responsibility->id]);
    $elsewhere->duties()->first()->assignRole($responsibility);

    mailingListSource($this->list, MailingListSourceType::Responsibility, $responsibility->id, ['filters' => ['tenant_scope' => 'main']]);

    expect(mailingListEmails($this->list))->toBe(['direct@stud.vu.lt', 'via-type@stud.vu.lt']);
});
