<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Self-renamed people keep a visible " *" so admins can review their names. The star stands
     * apart from the name so duplicate matching still sees the real name tokens.
     */
    public function up(): void
    {
        $renamed = DB::table('users')->where('name_was_changed', true)->pluck('name', 'id');

        Log::info('Dropping users.name_was_changed', ['renamed_users' => $renamed->count()]);

        foreach ($renamed as $id => $name) {
            DB::table('users')->where('id', $id)->update(['name' => Str::finish((string) $name, ' *')]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name_was_changed');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('name_was_changed')->default(false);
        });

        foreach (DB::table('users')->whereLike('name', '% *')->pluck('name', 'id') as $id => $name) {
            DB::table('users')->where('id', $id)->update([
                'name_was_changed' => true,
                'name' => Str::beforeLast($name, ' *'),
            ]);
        }
    }
};
