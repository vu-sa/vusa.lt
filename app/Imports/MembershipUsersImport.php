<?php

namespace App\Imports;

use App\Models\Membership;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MembershipUsersImport implements WithMultipleSheets
{
    public function __construct(public Membership $membership) {}

    /**
     * @return array<string, MembershipUsersImportSheet>
     */
    public function sheets(): array
    {
        return [
            'IMPORT' => new MembershipUsersImportSheet($this->membership),
        ];
    }
}
