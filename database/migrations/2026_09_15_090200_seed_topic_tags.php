<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Aliases flagged as navigable topics (drive `/tema/{alias}` landing pages). The remaining
     * tags stay descriptive-only — most are named entities (vu-senatas, vu-sa-ark) rather than
     * subjects, and would produce empty topic pages if promoted.
     */
    private const array TOPIC_ALIASES = [
        'integracija',
        'finansine-parama-stipendijos',
        'mokymai',
        'bendruomene',
        'atstovavimas',
        'studentu-iniciatyvos',
        'akademine-informacija',
        'kuratoriu-programa',
        'aukstasis-mokslas',
        'griztamasis-rysys',
        'socialine-dimensija-negalia',
        'akademine-etika',
        'mainu-galimybes',
        'moksline-veikla',
        'psichine-emocine-sveikata',
        'studijos',
        'darnumas',
        'doktorantai',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::TOPIC_ALIASES as $index => $alias) {
            DB::table('tags')
                ->where('alias', $alias)
                ->update(['is_topic' => true, 'sort_order' => ($index + 1) * 10]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('tags')
            ->whereIn('alias', self::TOPIC_ALIASES)
            ->update(['is_topic' => false, 'sort_order' => 0]);
    }
};
