<?php

use App\Models\Membership;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeAdminUser();
    $this->membership = Membership::factory()->for($this->tenant)->create();
});

function makeMembershipImportXlsx(array $userRows): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('IMPORT');

    $rows = array_merge(
        [['Vardas ir pavardė', 'Studentinis el. paštas', 'Tel. nr.', 'Narystės pradžia', 'Narystės pabaiga']],
        $userRows,
    );
    $sheet->fromArray($rows, null, 'A1');

    $tmp = tempnam(sys_get_temp_dir(), 'membership_import_').'.xlsx';
    (new XlsxWriter($spreadsheet))->save($tmp);

    return new UploadedFile($tmp, 'import.xlsx', null, null, true);
}

it('rejects unauthorized membership import', function () {
    $file = makeMembershipImportXlsx([
        ['Jonas Jonaitis', 'jonas@vu.lt', 'tel-1', Date::PHPToExcel(now()), Date::PHPToExcel(now()->addYear())],
    ]);

    asUser($this->user)
        ->post(route('membershipUsers.import', $this->membership), ['file' => $file])
        ->assertStatus(403);

    expect(User::where('email', 'jonas@vu.lt')->exists())->toBeFalse();
});

it('imports new users into a membership from an xlsx file', function () {
    $start = now()->startOfDay();
    $end = now()->addYear()->startOfDay();

    $file = makeMembershipImportXlsx([
        ['Jonas Jonaitis', 'jonas@vu.lt', 'tel-1', Date::PHPToExcel($start), Date::PHPToExcel($end)],
        ['Petras Petraitis', 'petras@vu.lt', 'tel-2', Date::PHPToExcel($start), Date::PHPToExcel($end)],
    ]);

    asUser($this->admin)
        ->post(route('membershipUsers.import', $this->membership), ['file' => $file])
        ->assertOk();

    $jonas = User::where('email', 'jonas@vu.lt')->first();
    $petras = User::where('email', 'petras@vu.lt')->first();

    expect($jonas)->not->toBeNull();
    expect($jonas->name)->toBe('Jonas Jonaitis');
    expect($petras)->not->toBeNull();

    expect($jonas->memberships()->where('memberships.id', $this->membership->id)->exists())->toBeTrue();
    expect($petras->memberships()->where('memberships.id', $this->membership->id)->exists())->toBeTrue();

    $pivot = $jonas->memberships()->where('memberships.id', $this->membership->id)->first()->pivot;
    expect((string) $pivot->start_date)->toBe($start->toDateTimeString());
    expect((string) $pivot->end_date)->toBe($end->toDateTimeString());
});

it('merges membership periods when the user already belongs to the membership', function () {
    $existingUser = User::factory()->create(['email' => 'jonas@vu.lt', 'name' => 'Existing Jonas']);
    $existingUser->memberships()->attach($this->membership->id, [
        'start_date' => now()->subMonth()->startOfDay(),
        'end_date' => now()->addMonth()->startOfDay(),
        'status' => 'active',
    ]);

    $earlierStart = now()->subYear()->startOfDay();
    $laterEnd = now()->addYear()->startOfDay();

    $file = makeMembershipImportXlsx([
        ['Jonas Jonaitis', 'jonas@vu.lt', 'tel-1', Date::PHPToExcel($earlierStart), Date::PHPToExcel($laterEnd)],
    ]);

    asUser($this->admin)
        ->post(route('membershipUsers.import', $this->membership), ['file' => $file])
        ->assertOk();

    expect(User::where('email', 'jonas@vu.lt')->count())->toBe(1);

    $pivot = $existingUser->memberships()->where('memberships.id', $this->membership->id)->first()->pivot;
    expect((string) $pivot->start_date)->toBe($earlierStart->toDateTimeString());
    expect((string) $pivot->end_date)->toBe($laterEnd->toDateTimeString());
});
