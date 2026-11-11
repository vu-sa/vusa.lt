<?php

namespace Database\Seeders;

use App\Settings\DutyTypeSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Mirrors staging's institution and duty types (ids, hierarchy, texts), so local data and the
 * learning-path audiences that target types by slug or id behave like the real ones.
 */
class TypeSeeder extends Seeder
{
    public const int CHAIR_DUTY_TYPE_ID = 4;

    public const int PRESIDENT_DUTY_TYPE_ID = 5;

    public const int COORDINATOR_DUTY_TYPE_ID = 6;

    public const int MEMBER_DUTY_TYPE_ID = 7;

    public const int CURATOR_DUTY_TYPE_ID = 8;

    public const int MANAGER_DUTY_TYPE_ID = 9;

    public const int STUDENT_REPRESENTATIVE_DUTY_TYPE_ID = 10;

    public function run(): void
    {
        DB::table('institution_types')->insert(array_map($this->toRow(...), $this->institutionTypes()));
        DB::table('duty_types')->insert(array_map($this->toRow(...), $this->dutyTypes()));

        $settings = app(DutyTypeSettings::class);
        $settings->coordinator_type_id ??= self::COORDINATOR_DUTY_TYPE_ID;
        $settings->curator_type_id ??= self::CURATOR_DUTY_TYPE_ID;
        $settings->save();
    }

    /**
     * Parents come before their children, so the self-referencing foreign key holds on insert.
     *
     * @param  array{id: int, parent_id: int|null, slug?: string, title: array<string, string|null>, description?: array<string, string>, extra_attributes?: array<string, mixed>, deleted?: bool}  $type
     * @return array<string, mixed>
     */
    private function toRow(array $type): array
    {
        return [
            'id' => $type['id'],
            'parent_id' => $type['parent_id'],
            ...(array_key_exists('slug', $type) ? ['slug' => $type['slug']] : []),
            'title' => json_encode($type['title']),
            'description' => isset($type['description']) ? json_encode($type['description']) : null,
            'extra_attributes' => isset($type['extra_attributes']) ? json_encode($type['extra_attributes']) : null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => ($type['deleted'] ?? false) ? now() : null,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function institutionTypes(): array
    {
        return [
            ['id' => 1, 'parent_id' => null, 'slug' => 'pkp', 'title' => ['lt' => 'Programos, klubai, projektai', 'en' => 'Programs, Clubs and Projects'], 'extra_attributes' => ['governance_scope' => 'vusa']],
            ['id' => 2, 'parent_id' => null, 'slug' => 'studentu-atstovu-organas', 'title' => ['lt' => 'Studentų atstovų organas', 'en' => 'Student Representative Body'], 'extra_attributes' => ['governance_scope' => 'vu']],
            ['id' => 3, 'parent_id' => null, 'slug' => 'padaliniai', 'title' => ['lt' => 'VU SA padalinys', 'en' => 'VU SR Unit'], 'extra_attributes' => ['governance_scope' => 'vusa']],
            ['id' => 13, 'parent_id' => 2, 'slug' => 'spk', 'title' => ['lt' => 'Studijų programos komitetas', 'en' => 'Study Program Committee'], 'description' => ['lt' => '<p>Studijų programų komitetai (SPK) yra studijų programų valdymo dariniai, atsakingi už studijų programų kokybę bei jų siekinių įgyvendinimą.</p><p>SPK sudaro studijų programą įgyvendinantys dėstytojai, socialiniai partneriai, bent vienas studentų atstovas (jungtinio studijų programų komiteto atveju – bent 2).</p><p>Į studentų atstovą savo SPK gali kreiptis, jei turi pasiūlymų, kaip būtų galima pagerinti studijų programos vykdymo kokybę bei kitais studijų programos vykdymo ar individualaus studijų plano sudarymo klausimais.</p>', 'en' => '<p>Study Program Committees (referred to as SPC) are study program management bodies responsible for the quality of study programs and the achievement of their objectives.</p><p>A SPC consists of faculty members implementing the study program, social partners, and at least one student representative (in the case of a joint study program committee, at least two).</p><p>Students may contact the student representative on their SPC if they have suggestions on how to improve the quality of the study program’s implementation or regarding other issues related to the implementation of the study program or the development of an individual study plan.</p>'], 'extra_attributes' => ['meeting_periodicity_days' => 90]],
            ['id' => 14, 'parent_id' => 2, 'slug' => 'studiju-kolegija', 'title' => ['lt' => 'Studijų kolegija', 'en' => 'Study College'], 'description' => ['lt' => '<p>Studijų kolegija pataria už studijų organizavimą atsakingam VU KAP vadovo pavaduotojui studijų vykdymo ir jų kokybės gerinimo klausimais. Studijų kolegiją sudaro VU KAP veikiančių SPK pirmininkai ir VU SA deleguotas studentų atstovas.</p>', 'en' => '<p>The Study College advises the deputy director of VU CAU, who is responsible for organizing studies, on matters related to the implementation of studies and the improvement of their quality. The Study Council consists of the chairs of the active SPC within VU CAU and a student representative delegated by VU SR.</p>']],
            ['id' => 17, 'parent_id' => 2, 'slug' => 'kap-taryba', 'title' => ['lt' => 'KAP Taryba', 'en' => 'CAU Council'], 'description' => ['lt' => '<p><strong>VU KAP Taryba</strong> – kolegialus VU KAP valdymo organas, renkamas 4 metams. VU KAP taryba renka VU KAP vadovą, ir tvirtina jo pavaduotojus, </p><p>VU KAP veiklos planą, biudžetą, mokslinių tyrimų kryptis, SPK ir VU KAP komisijų sudėtis (išskyrus studentų atstovus), svarsto naujas studijų programas bei esamų studijų programų keitimus, vertina mokslinių tyrimų ir studijų rezultatus. </p><p>VU KAP Tarybą sudaro VU KAP vadovas, dėstytojai, akademinės ir neakademinės bendruomenės nariai bei socialiniai partneriai ir studentai.</p>', 'en' => '<p>The <strong>VU CAU Council</strong> is a collegial governing body of VU CAU, elected for a four-year term.</p><p>The VU CAU Council elects the head of VU CAU and approves his or her deputies, the VU CAU activity plan, budget, research priorities, and the composition of the SPC and VU CAU committees (excluding student representatives); it also reviews new study programs and changes to existing ones, and evaluates the results of research and studies.</p><p>The VU CAU Council consists of the head of VU CAU, faculty members, members of the academic and non-academic communities, social partners, and students.</p>']],
            ['id' => 18, 'parent_id' => 2, 'slug' => 'gnk', 'title' => ['lt' => 'Ginčų nagrinėjimo komisija', 'en' => 'Dispute Resolution Commission'], 'description' => ['lt' => '<p>Komisija nagrinėja pareiškimus dėl ginčų, susijusių su mokslo ir studijų veikla, tarp Padalinio vykdomus studijų dalykus (modulius) studijuojančiųjų ir kitų bendruomenės narių, dirbančių Padalinyje. </p><p>VU KAP ginčų nagrinėjimo komisija svarsto apeliacijas dėl Padalinyje vykdomų studijų dalykų pasiekimų vertinimo, tarpinių atsiskaitymų, egzaminų laikymo, baigiamojo egzamino ar baigiamojo darbo gynimo procedūros ar kitų pažeidimų, galimai turinčių įtakos galutiniam studijų dalyko pasiekimų vertinimui.</p>', 'en' => '<p>The Commission reviews complaints regarding disputes related to academic and study activities between students enrolled in courses (modules) offered by the Department and other members of the community working at the Department.</p><p>The VU CAU Dispute Resolution Commission considers appeals regarding the assessment of academic performance in courses offered by the Department, interim assessments, exam administration, procedures for the final exam or thesis defense, or other violations that may affect the final assessment of course performance.</p>']],
            ['id' => 19, 'parent_id' => 2, 'slug' => 'aek', 'title' => ['lt' => 'Akademinės etikos komisija', 'en' => 'Academic Ethics Commission'], 'description' => ['lt' => '<p>Komisija nagrinėja kreipimusis dėl akademinės bendruomenės narių, dirbančių ar studijuojančių tame Padalinyje, veiksmų, galimai pažeidžiančių akademinę etiką.</p><p>VU KAP akademinės etikos komisiją sudaro 4 VU KAP dėstytojai ir 2 studentų atstovai.</p>', 'en' => '<p>The Commission reviews complaints regarding actions by members of the academic community who work or study at that unit that may violate academic ethics.</p><p>The Academic Ethics Commission of the Vilnius University CAU consists of 4 faculty members from the CAU and 2 student representatives.</p>']],
            ['id' => 20, 'parent_id' => 2, 'slug' => 'pak', 'title' => ['lt' => 'Priėmimo-atestacijos komisija', 'en' => 'Attestation Commission'], 'description' => ['en' => null], 'extra_attributes' => ['meeting_periodicity_days' => 90]],
            ['id' => 21, 'parent_id' => 2, 'slug' => 'rinkimu-komisija', 'title' => ['lt' => 'Rinkimų komisija', 'en' => 'Election Commission'], 'description' => ['en' => null], 'extra_attributes' => ['meeting_periodicity_days' => 90]],
            ['id' => 22, 'parent_id' => 2, 'slug' => 'dekanatas-direktoratas', 'title' => ['lt' => 'Dekanatas / direktoratas', 'en' => null], 'description' => ['en' => null], 'extra_attributes' => ['meeting_periodicity_days' => 14]],
            ['id' => 27, 'parent_id' => null, 'slug' => 'vu-sa-darinys', 'title' => ['lt' => 'VU SA darinys'], 'description' => ['en' => null], 'extra_attributes' => ['governance_scope' => 'vusa']],
            ['id' => 28, 'parent_id' => 2, 'slug' => 'senatas-ir-jo-komitetai', 'title' => ['lt' => 'Senatas ir jo komitetai'], 'description' => ['en' => null]],
            ['id' => 29, 'parent_id' => 27, 'slug' => 'vu-sa-p-kvo', 'title' => ['lt' => 'VU SA P kolegialaus valdymo organas'], 'description' => ['en' => null]],
            ['id' => 30, 'parent_id' => null, 'slug' => 'padalinio-dienos', 'title' => ['lt' => 'Padalinio dienos', 'en' => null], 'description' => ['en' => null], 'extra_attributes' => ['governance_scope' => 'vusa']],
            ['id' => 31, 'parent_id' => 2, 'slug' => 'erasmus-komisija', 'title' => ['lt' => 'Erasmus+ komisija', 'en' => null], 'description' => ['en' => null]],
            ['id' => 32, 'parent_id' => 2, 'slug' => 'stipendiju-komisija', 'title' => ['lt' => 'Stipendijų komisija', 'en' => null], 'description' => ['en' => null]],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function dutyTypes(): array
    {
        return [
            ['id' => 6, 'parent_id' => null, 'title' => ['lt' => 'Koordinatorius (-ė)', 'en' => 'Coordinator'], 'description' => ['lt' => '<p>VU SA ar VU SA PKP sričių vadovai</p>']],
            ['id' => 7, 'parent_id' => null, 'title' => ['lt' => 'Narys (-ė)', 'en' => 'Member'], 'description' => ['lt' => '<p></p>']],
            ['id' => 4, 'parent_id' => 6, 'title' => ['lt' => 'Padalinio ar iniciatyvos vadovas (-ė)', 'en' => 'Tenant Chairperson'], 'description' => ['lt' => '<p>Pagrindinis padalinio ar iniciatyvos vadovas (-ė), atsakingas žmogus už visą padalinį. Dažniausiai tai yra padalinio pirmininkas, prezidentas. Nežymėti šiuo tipu atskirų VU SA darinių vadovų.</p>', 'en' => null]],
            ['id' => 5, 'parent_id' => 6, 'title' => ['lt' => 'Prezidentas (-ė)', 'en' => 'President'], 'description' => ['lt' => '<p>Kai kurių renkamų VU SA arba VU SA PKP vadovų (-ių) pareigos vadinamos „Prezidentas“.</p>'], 'deleted' => true],
            ['id' => 8, 'parent_id' => null, 'title' => ['lt' => 'Kuratorius (-ė)', 'en' => 'Kuratorius (-ė)'], 'description' => ['lt' => '<p></p>']],
            ['id' => 9, 'parent_id' => 6, 'title' => ['lt' => 'Vadovas (-ė)', 'en' => 'Manager'], 'description' => ['lt' => '<p></p>']],
            ['id' => 10, 'parent_id' => 7, 'title' => ['lt' => 'Studentų (-čių) atstovas (-ė)', 'en' => null], 'description' => ['lt' => '<p>Studentų (-čių) atstovai (-ės), kurie buvo išsirinkti studentų (-čių) konferencijų metu arba buvo deleguoti į atstovavimo darinius.</p>', 'en' => null]],
            ['id' => 26, 'parent_id' => null, 'title' => ['lt' => 'Mentorius (-ė)', 'en' => 'Mentor'], 'description' => ['en' => null]],
            ['id' => 27, 'parent_id' => 6, 'title' => ['lt' => 'Žmogiškųjų išteklių koordinatorius (-ė)', 'en' => 'Human Resources Coordinator'], 'description' => ['en' => null]],
            ['id' => 28, 'parent_id' => 6, 'title' => ['lt' => 'Administratorius (-ė)', 'en' => null], 'description' => ['en' => null]],
            ['id' => 29, 'parent_id' => 6, 'title' => ['lt' => 'Studentų (-čių) atstovų (-ių) koordinatorius (-ė)', 'en' => null], 'description' => ['en' => null]],
            ['id' => 30, 'parent_id' => 6, 'title' => ['lt' => 'Komunikacijos koordinatorius (-ė)', 'en' => null], 'description' => ['en' => null]],
        ];
    }
}
