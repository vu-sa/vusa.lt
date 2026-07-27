<?php

namespace App\Imports;

use App\Models\Membership;
use App\Models\User;
use App\Support\Spreadsheet\SpreadsheetReader;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class MembershipUsersImport
{
    public function __construct(public Membership $membership) {}

    public function import(UploadedFile $file): void
    {
        $rows = SpreadsheetReader::readSheetAsCollection($file, 'IMPORT');

        foreach ($rows as $row) {
            $start_date = Date::excelToDateTimeObject($row['narystes_pradzia']);
            $end_date = Date::excelToDateTimeObject($row['narystes_pabaiga']);

            // users_email_unique ignores deleted_at, so a scoped firstOrCreate cannot
            // see a trashed account and its INSERT violates the key, aborting the whole
            // import. Importing a membership list is an explicit re-add, so restore.
            $user = User::withTrashed()->firstOrCreate([
                'email' => $row['studentinis_el_pastas'],
            ], [
                'name' => $row['vardas_ir_pavarde'],
                'phone' => $row['tel_nr'],
            ]);

            if ($user->trashed()) {
                $user->restore();
            }

            $membership = $user->memberships()->where('membership_id', $this->membership->id)->first();

            if ($membership) {
                $user->memberships()->updateExistingPivot($membership->id, [
                    'start_date' => $start_date < $membership->pivot->start_date ? $start_date : $membership->pivot->start_date,
                    'end_date' => $end_date > $membership->pivot->end_date ? $end_date : $membership->pivot->end_date,
                    'status' => 'active',
                ]);
            } else {
                $user->memberships()->attach($this->membership->id, [
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'status' => 'active',
                ]);
            }
        }
    }
}
