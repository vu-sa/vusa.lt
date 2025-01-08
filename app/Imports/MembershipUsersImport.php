<?php

namespace App\Imports;

use App\Models\Membership;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MembershipUsersImport implements WithMultipleSheets
{
    public function __construct(public Membership $membership) {}

    /**
     * @param  Collection  $collection
     */
    public function sheets(): array
    {
        return [
            'IMPORT' => new MembershipUsersImportSheet($this->membership),
        ];
    }
}
