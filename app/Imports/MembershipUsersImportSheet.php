<?php

namespace App\Imports;

use App\Models\Membership;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class MembershipUsersImportSheet implements ToCollection, WithHeadingRow
{
    public Membership $membership;

    public function __construct(Membership $membership)
    {
        $this->membership = $membership;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $start_date = Date::excelToDateTimeObject($row['narystes_pradzia']);
            $end_date = Date::excelToDateTimeObject($row['narystes_pabaiga']);

            // check if membership for user exists already, if yes, merge membership periods
            $user = User::query()->firstOrCreate([
                'email' => $row['studentinis_el_pastas'],
            ], [
                'name' => $row['vardas_ir_pavarde'],
                'phone' => $row['tel_nr'],
            ]);

            // check if membership exists
            $membership = $user->memberships()->where('membership_id', $this->membership->id)->first();

            if ($membership) {
                // merge membership periods
                // if start_date is earlier than existing start_date, update start_date
                // if end_date is later than existing end_date, update end_date
                // start_date and end_date are in a pivot
                $user->memberships()->updateExistingPivot($membership->id, [
                    'start_date' => $start_date < $membership->pivot->start_date ? $start_date : $membership->pivot->start_date,
                    'end_date' => $end_date > $membership->pivot->end_date ? $end_date : $membership->pivot->end_date,
                    'status' => 'active',
                ]);
            } else {
                // create new membership
                $user->memberships()->attach($this->membership->id, [
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'status' => 'active',
                ]);
            }
        }
    }
}
