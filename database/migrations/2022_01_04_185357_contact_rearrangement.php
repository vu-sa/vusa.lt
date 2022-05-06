<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ContactRearrangement extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Contact group 'padaliniai' rearrangement

        Schema::table('padaliniai', function (Blueprint $table) {
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('shortname_vu');
        });

        $padaliniaiFromContacts = DB::table('contacts')
            ->where('groupname', '=', 'padaliniai')
            ->select('phone', 'email', 'name_short', 'name_full', 'address', 'webpage')
            ->get();

        DB::table('contacts')->where('groupname', '=', 'padaliniai')->delete();

        foreach ($padaliniaiFromContacts as $key => $value) {
            DB::table('padaliniai')->where('shortname', '=', $value->name_short)->update([
                'phone' => $value->phone,
                'email' => trim($value->email),
                'address' => $value->address,
            ]);
        }

        $padaliniai = DB::table('padaliniai')->select('id', 'shortname', 'alias')->get();

        foreach ($padaliniai as $key => $value) {

            if ($value->shortname != "VU SA") {
                $shortname_vu = "VU" . explode('SA', $value->shortname)[1];
            } else {
                $shortname_vu = "VU";
            }

            DB::table('padaliniai')->where('id', '=', $value->id)->update([
                'shortname_vu' => $shortname_vu,
                'alias' => substr($value->alias, 4)
            ]);
        }

        DB::table('padaliniai')->where('shortname', '=', 'VU SA FilF')->update([
            'shortname_vu' => 'VU FlF',
        ]);

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('address');
            $table->dropColumn('webpage');
            $table->dropColumn('lang');
        });

        // Contact group 'aprasymas, aprasymas-padalinys' rearrangement

        DB::table('duties_institutions')->insert([
            'name' => 'Vilniaus universiteto Studentų atstovybė',
            'short_name' => 'VU SA',
            'alias' => 'vusa',
            'description' => 'Studentų organizacija',
        ]);

        $aprasymas = DB::table('contacts')
            ->where('groupname', 'like', 'aprasymas%')
            ->select('infoText', 'image', 'groupname', 'grouptitle', 'contactGroup')
            ->get();

        foreach ($aprasymas as $key => $value) {

            $short_name = DB::table('roles')->where('id', '=', $value->contactGroup)->select('description')->first();

            if ($value->grouptitle == "central-office") {

                $cb_institution_id = DB::table('duties_institutions')->where('alias', '=', 'centrinis-biuras')->select('id')->first()->id;

                $en = new \stdClass;
                $en->description = $value->infoText;
                $en->alias = $value->grouptitle;

                DB::table('duties_institutions')->where('id', '=', $cb_institution_id)->update([
                    'attributes' => json_encode(['en' => $en]),
                ]);
            } else {

                DB::table('duties_institutions')->insert([
                    'pid' => 1,
                    'short_name' => is_null($short_name) ? null : $short_name->description,
                    'description' => $value->infoText,
                    'image_url' => $value->image,
                    'alias' => $value->grouptitle == 'padalinio-biuras' ? $value->grouptitle . '-' . $value->contactGroup : $value->grouptitle,
                    'role_id' => $value->contactGroup
                ]);
            }
        }

        DB::table('contacts')->where('groupname', 'like', 'aprasymas%')->delete();

        // Contact group 'centrinis-biuras' rearrangement

        DB::table('duties_types')->insert([[
            'name' => 'Pirmininkas',
            'alias' => 'pirmininkas',
            'description' => 'Pirmininkas',
        ], [
            'name' => 'Prezidentas',
            'alias' => 'prezidentas',
            'description' => 'Prezidentas',
        ], [
            'name' => 'Koordinatorius',
            'alias' => 'koordinatorius',
            'description' => 'Koordinatorius',
        ]]);

        $centrinisBiuras = DB::table('contacts')->where('groupname', '=', 'centrinis-biuras')
            ->select('name', 'duties', 'phone', 'email', 'infoText', 'image', 'groupname')->get();

        foreach ($centrinisBiuras as $key => $value) {
            $duties_institution_id = DB::table('duties_institutions')
                ->where('alias', 'like', $value->groupname . '%')
                ->select('id')
                ->first();

            $user_id = DB::table('users')->insertGetId([
                'name' => $value->name,
                'email' => trim($value->email),
                'phone' => $value->phone,
                'profile_photo_path' => $value->image,
            ]);

            $duty_id = DB::table('duties')->insertGetId([
                'name' => $value->duties,
                'description' => $value->infoText,
                'institution_id' => $duties_institution_id->id,
                'type_id' => in_array($value->duties, ['Prezidentas', 'Prezidentė']) ? 2 : 3,
                'email' => trim($value->email),
            ]);

            DB::table('duties_users')->insert([
                'duty_id' => $duty_id,
                'user_id' => $user_id
            ]);
        }

        DB::table('contacts')->where('groupname', 'like', 'centrinis-biuras%')->delete();

        // central-office

        $centrinisBiurasEn = DB::table('contacts')->where('groupname', '=', 'central-office')
            ->select('duties', 'email', 'infoText', 'groupname')->get();

        foreach ($centrinisBiurasEn as $key => $value) {
            $duties_institution_id = DB::table('duties_institutions')
                ->where('alias', 'like', $value->groupname . '%')
                ->select('id')
                ->first();

            $duty_id = DB::table('duties')->where('email', '=', trim($value->email))->select('id')->first()->id;

            $en = new \stdClass;

            $en->name = $value->duties;
            $en->description = $value->infoText;

            DB::table('duties')->where('id', '=', $duty_id)->update([
                'attributes' => json_encode(['en' => $en]),
            ]);
        }

        DB::table('contacts')->where('groupname', 'like', 'central-office')->delete();

        // Contact group 'revizija' rearrangement

        $revizija_institution_id = DB::table('duties_institutions')
            ->insertGetId([
                'name' => 'Revizijos komisija',
                'alias' => 'revizija',
                'description' => 'Revizijos komisija',
                'pid' => 1,
            ]);

        $narys_type_id = DB::table('duties_types')
            ->insertGetId(['name' => "Narys", 'alias' => 'narys', 'description' => 'Narys']);

        $revizija_duty_id = DB::table('duties')->insertGetId(
            [
                'name' => 'Revizijos komisijos narys',
                'description' => 'Revizijos komisijos narys',
                'institution_id' => $revizija_institution_id,
                'type_id' => $narys_type_id,
            ]
        );

        $revizija = DB::table('contacts')->where('groupname', '=', 'revizija')
            ->select('name', 'email', 'groupname')->get();

        // $revizija->select('groupname')->distinct()->get();

        foreach ($revizija as $key => $value) {

            $user_id = DB::table('users')->insertGetId([
                'name' => $value->name,
                'email' => trim($value->email),
            ]);

            DB::table('duties_users')->insert([
                'duty_id' => $revizija_duty_id,
                'user_id' => $user_id
            ]);
        }

        DB::table('contacts')->where('groupname', '=', 'revizija')->delete();
        DB::table('contacts')->where('groupname', '=', 'parl-pirm')->delete();

        // Contact group 'padalinio-biuras' rearrangement

        $padalinioBiuras = DB::table('contacts')->where('groupname', '=', 'padalinio-biuras')
            ->select('name', 'duties', 'phone', 'email', 'infoText', 'image', 'groupname', 'contactGroup')->get();

        foreach ($padalinioBiuras as $key => $value) {
            $duties_institution_id = DB::table('duties_institutions')
                ->where('role_id', '=', $value->contactGroup)
                ->select('id')
                ->first();

            $user_id = DB::table('users')->insertGetId([
                'name' => $value->name,
                'email' => trim($value->email),
                'phone' => $value->phone,
                'profile_photo_path' => $value->image,
            ]);

            $duty_id = DB::table('duties')->insertGetId([
                'name' => $value->duties,
                'description' => $value->infoText,
                'institution_id' => $duties_institution_id->id,
                'type_id' => in_array($value->duties, ['Pirmininkas', 'Pirmininkė']) ? 1 : 3,
                'email' => trim($value->email),
            ]);

            DB::table('duties_users')->insert([
                'duty_id' => $duty_id,
                'user_id' => $user_id
            ]);
        }

        DB::table('contacts')->where('groupname', '=', 'padalinio-biuras')->delete();

        // Contact group 'taryba' rearrangement

        $taryba_institution_id = DB::table('duties_institutions')->insertGetId([
            'pid' => 1,
            'name' => 'VU SA taryba',
            'short_name' => 'Taryba',
            'alias' => 'taryba',
            'description' => 'Kolegialus valdymo organas',
        ]);

        $taryba = DB::table('contacts')
            ->where('groupname', '=', 'taryba')
            ->select('name', 'phone', 'email', 'groupname', 'name_short')
            ->get();

        $taryba_duty_id = DB::table('duties')->insertGetId([
            'name' => 'Tarybos narys',
            'description' => 'Tarybos narys',
            'institution_id' => $taryba_institution_id,
            'type_id' => 4
        ]);

        foreach ($taryba as $key => $value) {

            $user_id = DB::table('users')->where('email', '=', trim($value->email))->select('id')->first();

            DB::table('duties_users')->insert([
                'duty_id' => $taryba_duty_id,
                'user_id' => $user_id->id
            ]);
        }

        DB::table('contacts')->where('groupname', '=', 'taryba')->delete();

        // Contact group 'stiprinimas' rearrangement

        $stiprinimas_institution_id = DB::table('duties_institutions')->insertGetId([
            'pid' => 1,
            'name' => 'Institucinio stiprinimo fondas',
            'short_name' => 'ISF',
            'alias' => 'isf',
            'description' => 'Kolegialus organas',
        ]);

        $stiprinimas = DB::table('contacts')
            ->where('groupname', '=', 'stiprinimas')
            ->select('name', 'phone', 'email', 'groupname', 'members')
            ->get();

        $stiprinimas_duty_id = DB::table('duties')->insertGetId([
            'name' => 'ISF narys',
            'description' => 'ISF narys',
            'institution_id' => $stiprinimas_institution_id,
            'type_id' => 4
        ]);

        foreach ($stiprinimas as $key => $value) {

            $user_id = DB::table('users')->insertGetId([
                'name' => $value->name,
                'email' => trim($value->email),
                'phone' => $value->phone,
            ]);

            DB::table('duties_users')->insert([
                'duty_id' => $stiprinimas_duty_id,
                'user_id' => $user_id,
                'attributes' => json_encode(['assigned_institutions' => explode(', ', $value->members)])
            ]);
        }

        DB::table('contacts')->where('groupname', '=', 'stiprinimas')->delete();

        // Contact group 'padalinio-kuratoriai' rearrangement

        $padalinioKuratoriai_type_id = DB::table('duties_types')->insertGetId([
            'name' => 'Kuratorius',
            'alias' => 'kuratorius',
            'description' => 'Kuratorius',
        ]);

        $kuratoriai = DB::table('contacts')
            ->where('groupname', '=', 'padalinio-kuratoriai')
            ->select('name', 'phone', 'email', 'groupname', 'contactGroup', 'image', 'infoText', 'duties')
            ->get();

        $distinct_kuratoriai_group = DB::table('contacts')
            ->where('groupname', '=', 'padalinio-kuratoriai')
            ->select('contactGroup')
            ->distinct()
            ->get();

        foreach ($distinct_kuratoriai_group as $key => $value) {

            $institutions = DB::table('duties_institutions')
                ->where('role_id', '=', $value->contactGroup)
                ->select('id', 'short_name')
                ->first();

            DB::table('duties')->insert([
                'name' =>  $institutions->short_name . ' kuratorius',
                'description' => 'Kuratorius',
                'institution_id' => $institutions->id,
                'type_id' => $padalinioKuratoriai_type_id
            ]);
        }

        foreach ($kuratoriai as $key => $value) {

            if (is_null($value->name)) {
                continue;
            }

            $role_id = $value->contactGroup;

            $institution_id = DB::table('duties_institutions')
                ->where('role_id', '=', $role_id)
                ->select('id')
                ->first();

            $duty_id = DB::table('duties')
                ->where([['institution_id', '=', $institution_id->id],['description', '=', 'Kuratorius']])
                ->select('id')
                ->first();

            // check if user with email exists
            $user_id = DB::table('users')->where('email', '=', trim($value->email))->select('id')->first();

            if (is_null($user_id)) {
                $user_id = DB::table('users')->insertGetId([
                    'name' => $value->name,
                    'email' => trim($value->email),
                    'phone' => $value->phone,
                    'profile_photo_path' => $value->image,
                ]);

                DB::table('duties_users')->insert([
                    'duty_id' => $duty_id->id,
                    'user_id' => $user_id,
                    'attributes' => json_encode(['info_text' => $value->infoText, 'study_program' => $value->duties])

                ]);
            } else {
                DB::table('duties_users')->insert([
                    'duty_id' => $duty_id->id,
                    'user_id' => $user_id->id,
                    'attributes' => json_encode(['info_text' => $value->infoText, 'study_program' => $value->duties])
                ]);
            }
        }

        DB::table('contacts')->where('groupname', '=', 'padalinio-kuratoriai')->delete();

        // Contact group 'programos-klubai-projektai' rearrangement

        $programosKlubaiProjektai = DB::table('contacts')->where('groupname', '=', 'programos-klubai-projektai')->select('id', 'name', 'phone', 'email', 'groupname', 'name_full')->get();

        DB::table('duties_types')->insert([
            'name' => 'Vadovas',
            'alias' => 'vadovas',
            'description' => 'Vadovas',
        ]);

        DB::table('duties_institutions_types')->insert([
            'name' => "Programos, klubai, projektai",
            'alias' => "pkp",
        ]);

        foreach ($programosKlubaiProjektai as $key => $value) {

            // check if user with email exists
            $user_id = DB::table('users')->where('email', '=', trim($value->email))->select('id')->first();

            $institution_id = DB::table('duties_institutions')->insertGetId([
                'name' => $value->name_full,
                'alias' => 'pkp-' . $value->id,
                'type_id' => 1
            ]);

            $duty_id = DB::table('duties')->insertGetId([
                'name' => $value->name_full . ' ' . 'vadovas',
                'institution_id' => $institution_id,
                'email' => trim($value->email),
                'type_id' => 6,
            ]);

            if (is_null($user_id)) {

                $user_id = DB::table('users')->insertGetId([
                    'name' => $value->name,
                    'email' => trim($value->email),
                    'phone' => $value->phone,
                    'profile_photo_path' => $value->name_full
                ]);

                DB::table('duties_users')->insert([
                    'duty_id' => $duty_id,
                    'user_id' => $user_id,
                ]);
            } else {
                DB::table('duties_users')->insert([
                    'duty_id' => $duty_id,
                    'user_id' => $user_id->id,
                ]);
            }
        }

        DB::table('contacts')->where('groupname', '=', 'programos-klubai-projektai')->delete();

        // Contact group 'padalinio-biuras-en' rearrangement

        $padalinioBiurasEn = DB::table('contacts')->where('groupname', '=', 'padalinio-biuras-en')->select('id', 'duties', 'phone', 'email', 'groupname', 'infoText', 'contactGroup')->get();

        foreach ($padalinioBiurasEn as $key => $value) {

            $en = new \stdClass;

            $en->name = $value->duties;
            $en->infoText = $value->infoText;

            $duty_id = DB::table('duties')->where('email', '=', trim($value->email))->update(['attributes' => json_encode(['en' => $en])]);
        }

        DB::table('contacts')->where('groupname', '=', 'padalinio-biuras-en')->delete();

        // Contact group 'padalinio-studentu-atstovai' rearrangement

        $padalinioStudentuAtstovai = DB::table('contacts')->where('groupname', '=', 'padalinio-studentu-atstovai')->select('name', 'duties', 'email', 'phone', 'contactGroup')->get();

        $padalinioStAtsInstitutionTypeId = DB::table('duties_institutions_types')->insertGetId([
            'name' => "Studentų atstovų organas",
            'alias' => "studentu-atstovu-organas",
            'description' => 'Būtina sutvarkyti.'
        ]);

        $padalinioStAtsTypeId = DB::table('duties_types')->insertGetId([
            'name' => "Studentų atstovas",
            'alias' => "studentu-atstovas",
            'description' => 'Studentų atstovas'
        ]);

        foreach ($padalinioStudentuAtstovai as $key => $value) {

            $institution_id = DB::table('duties_institutions')->insertGetId([
                'name' => $value->duties,
                'alias' => 'padalinio-studentu-atstovai-' . $value->contactGroup,
                'type_id' => $padalinioStAtsInstitutionTypeId,
                'role_id' => $value->contactGroup
            ]);

            $duty_id = DB::table('duties')
                ->insertGetId([
                    'name' => $value->duties . " studentų atstovas",
                    'type_id' => $padalinioStAtsTypeId,
                    'institution_id' => $institution_id,
                ]);

            $user_id = DB::table('users')->where('email', '=', trim($value->email))->select('id')->first();

            // Tikslas: pakeisti studento emailą į studentinį, 'users' lentelėje. 
            // jei neranda vartotojo pagal emailą:

            if (is_null($user_id)) {

                // jei randa vartotoją pagal vardą, tai pakeičiam jo emailą
                if (DB::table('users')->where('name', '=', $value->name)->exists()) {

                    try {
                        DB::table('users')->where('name', '=', $value->name)->update(['email' => trim($value->email)]);
                    } catch (\Exception $e) {
                        echo $e->getMessage();
                    }

                    $user_id = DB::table('users')->where('name', '=', $value->name)->select('id')->first()->id;
                }

                // jei visiškai nieko neranda, sukuriame paskyrą
                else {
                    $user_id = DB::table('users')->insertGetId([
                        'name' => $value->name,
                        'email' => trim($value->email),
                        'phone' => $value->phone,
                    ]);
                }

                // pridedame prie duties_users
                DB::table('duties_users')->insert([
                    'duty_id' => $duty_id,
                    'user_id' => $user_id,
                ]);
            }

            // jei randa:
            else {
                DB::table('duties_users')->insert([
                    'duty_id' => $duty_id,
                    'user_id' => $user_id->id,
                ]);
            }

            DB::table('contacts')->where('groupname', 'like', 'padalinio%')->delete();
        }

        // Contact group 'parlamentas' rearrangement

        $parlamentas = DB::table('contacts')->where('groupname', '=', 'parlamentas')->get();

        $parlamentas_duty_id = DB::table('duties')->insertGetId([
            'name' => 'Parlamento narys',
            'institution_id' => 3,
            'description' => 'Parlamento narys',
            'type_id' => 4,
            'places_to_occupy' => 47
        ]);

        foreach ($parlamentas as $key => $value) {

            $member_array = explode(', ', $value->members);

            foreach ($member_array as $key => $member) {

                $user_id = DB::table('users')->where('name', '=', $member)->select('id')->first();

                if (is_null($user_id)) {
                    $user_id = DB::table('users')->insertGetId([
                        'name' => $member,
                        'email' => $member
                    ]);

                    DB::table('duties_users')->insert([
                        'duty_id' => $parlamentas_duty_id,
                        'user_id' => $user_id,
                    ]);
                } else {
                    DB::table('duties_users')->insert([
                        'duty_id' => $parlamentas_duty_id,
                        'user_id' => $user_id->id,
                    ]);
                }
            }
        }

        DB::table('contacts')->where('groupname', 'like', 'parlament%')->delete();

        // Contact group 'studentu-atstovai' rearrangement

        $studentuAtstovai = DB::table('contacts')->where('groupname', '=', 'studentu-atstovai')->get();

        foreach ($studentuAtstovai as $key => $value) {
            $institution_id = DB::table('duties_institutions')->insertGetId([
                'name' => $value->grouptitle,
                'alias' => 'studentu-atstovai',
                'type_id' => $padalinioStAtsInstitutionTypeId,
                'role_id' => 1
            ]);

            $duty_id = DB::table('duties')
                ->insertGetId([
                    'name' => $value->grouptitle . " studentų atstovas",
                    'type_id' => $padalinioStAtsTypeId,
                    'institution_id' => $institution_id,
                ]);

            $user_id = DB::table('users')->where('email', '=', trim($value->email))->select('id')->first();

            // Tikslas: pakeisti studento emailą į studentinį, 'users' lentelėje.
            // jei neranda vartotojo pagal emailą:

            if (is_null($user_id)) {

                // jei randa vartotoją pagal vardą, tai pakeičiam jo emailą
                if (DB::table('users')->where('name', '=', $value->name)->exists()) {

                    try {
                        DB::table('users')->where('name', '=', $value->name)->update(['email' => trim($value->email)]);
                    } catch (\Exception $e) {
                        echo $e->getMessage();
                    }

                    $user_id = DB::table('users')->where('name', '=', $value->name)->select('id')->first()->id;
                }

                // jei visiškai nieko neranda, sukuriame paskyrą
                else {
                    $user_id = DB::table('users')->insertGetId([
                        'name' => $value->name,
                        'email' => trim($value->email),
                        'phone' => $value->phone,
                    ]);

                    DB::table('duties_users')->insert([
                        'duty_id' => $duty_id,
                        'user_id' => $user_id,
                    ]);
                }

                // pridedame prie duties_users
                DB::table('duties_users')->insert([
                    'duty_id' => $duty_id,
                    'user_id' => $user_id,
                ]);
            }

            // jei randa:
            else {
                DB::table('duties_users')->insert([
                    'duty_id' => $duty_id,
                    'user_id' => $user_id->id,
                ]);
            }
        }

        DB::table('contacts')->where('groupname', 'like', 'studentu-atstovai%')->delete();
        Schema::dropIfExists('contacts');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
