<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Grouping contacts by unit only means something for delegated seats; the form no longer offers it without them. */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('duties')
            ->where('contacts_grouping', 'tenant')
            ->whereNotExists(fn ($query) => $query->select(DB::raw(1))->from('duty_tenant')->whereColumn('duty_tenant.duty_id', 'duties.id'))
            ->update(['contacts_grouping' => 'none', 'updated_at' => DB::raw('updated_at')]);
    }

    public function down(): void {}
};
