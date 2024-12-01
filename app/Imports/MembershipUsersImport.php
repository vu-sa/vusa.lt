<?php

namespace App\Imports;

use App\Models\Membership;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MembershipUsersImport implements WithMultipleSheets
{
    public Membership $membership;

    public function __construct(Membership $membership)
    {
        $this->membership = $membership;
    }

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
