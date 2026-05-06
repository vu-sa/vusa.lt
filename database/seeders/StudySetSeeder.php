<?php

namespace Database\Seeders;

use App\Models\LecturerReview;
use App\Models\StudySet;
use App\Models\StudySetCourse;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StudySetSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (self::studySets() as $studySetData) {
                $tenant = Tenant::query()->where('alias', $studySetData['tenant_alias'])->first();

                if (! $tenant) {
                    $this->command?->warn("Skipping study set {$studySetData['name']} because tenant {$studySetData['tenant_alias']} was not found.");

                    continue;
                }

                $studySet = self::findStudySet($tenant, $studySetData);

                $studySet->fill([
                    'name' => self::translation($studySetData['name']),
                    'description' => null,
                    'order' => $studySetData['order'],
                    'is_visible' => true,
                    'tenant_id' => $tenant->id,
                ]);
                $studySet->save();

                foreach ($studySetData['courses'] as $courseData) {
                    $course = StudySetCourse::query()
                        ->where('study_set_id', $studySet->id)
                        ->where('name->lt', $courseData['name'])
                        ->firstOrNew();

                    $course->fill([
                        'study_set_id' => $studySet->id,
                        'name' => self::translation($courseData['name']),
                        'order' => $courseData['order'],
                        'semester' => $courseData['semester'],
                        'credits' => $courseData['credits'],
                        'is_visible' => true,
                    ]);
                    $course->save();

                    foreach ($courseData['reviews'] as $reviewData) {
                        $review = LecturerReview::query()
                            ->where('study_set_course_id', $course->id)
                            ->where('lecturer->lt', $reviewData['lecturer'])
                            ->firstOrNew();

                        $review->fill([
                            'study_set_course_id' => $course->id,
                            'lecturer' => self::translation($reviewData['lecturer']),
                            'comment' => self::translation($reviewData['comment']),
                            'is_visible' => true,
                        ]);
                        $review->save();
                    }

                    $lecturers = array_column($courseData['reviews'], 'lecturer');

                    $course->reviews()
                        ->get()
                        ->reject(fn (LecturerReview $review) => in_array($review->getTranslation('lecturer', 'lt'), $lecturers, true))
                        ->each
                        ->delete();
                }

                $courseNames = array_column($studySetData['courses'], 'name');

                $studySet->courses()
                    ->get()
                    ->reject(fn (StudySetCourse $course) => in_array($course->getTranslation('name', 'lt'), $courseNames, true))
                    ->each
                    ->delete();
            }
        });
    }

    /**
     * @param  array{tenant_alias: string, name: string, order: int, courses: array<int, array{name: string}>}  $studySetData
     */
    private static function findStudySet(Tenant $tenant, array $studySetData): StudySet
    {
        $firstCourseName = $studySetData['courses'][0]['name'] ?? null;

        if ($firstCourseName) {
            $studySet = StudySet::query()
                ->where('tenant_id', $tenant->id)
                ->where('name->lt', $studySetData['name'])
                ->whereHas('courses', fn ($query) => $query->where('name->lt', $firstCourseName))
                ->first();

            if ($studySet) {
                return $studySet;
            }
        }

        return StudySet::query()
            ->where('tenant_id', $tenant->id)
            ->where('order', $studySetData['order'])
            ->firstOrNew();
    }

    /**
     * @return array{lt: string, en: null}
     */
    private static function translation(string $value): array
    {
        return ['lt' => $value, 'en' => null];
    }

    /**
     * @return array<int, array{tenant_alias: string, name: string, order: int, courses: array<int, array{name: string, order: int, semester: string, credits: int, reviews: array<int, array{lecturer: string, comment: string}>}>}>
     */
    private static function studySets(): array
    {
        return json_decode(self::DATA, true, flags: JSON_THROW_ON_ERROR);
    }

    private const DATA = <<<'JSON'
[
  {
    "tenant_alias": "chgf",
    "name": "Bendros kompetencijos chemijoje",
    "order": 1,
    "courses": [
      {
        "name": "Cheminių elementų pasaulis",
        "order": 1,
        "semester": "ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Akademinis raštingumas",
        "order": 2,
        "semester": "pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Programavimo ir duomenų analizės įvadas",
        "order": 3,
        "semester": "ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Chemijos istorija",
        "order": 4,
        "semester": "pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Žmogus, buitis ir chemija",
        "order": 5,
        "semester": "pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Vadyba chemijos pramonės įmonėse",
        "order": 6,
        "semester": "pavasris",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "chgf",
    "name": "Ekologija chemijoje ir geografijoje",
    "order": 2,
    "courses": [
      {
        "name": "Klimato ir ekosferos kaita",
        "order": 1,
        "semester": "pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Aplinkosaugos seminaras",
        "order": 2,
        "semester": "ruduo",
        "credits": 10,
        "reviews": []
      },
      {
        "name": "Žalioji chemija",
        "order": 3,
        "semester": "pavasaris",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "chgf",
    "name": "meteorologija",
    "order": 3,
    "courses": [
      {
        "name": "Meteorologijos pagrindai",
        "order": 1,
        "semester": "ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Klimato ir ekosferos kaita",
        "order": 2,
        "semester": "pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "sinoptinės meteorologijos pagrindai",
        "order": 3,
        "semester": "ruduo",
        "credits": 10,
        "reviews": []
      },
      {
        "name": "erdvės komponentų analizė taikant GIS",
        "order": 4,
        "semester": "ruduo",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "chgf",
    "name": "Medicininė farmacija (en)",
    "order": 4,
    "courses": [
      {
        "name": "Biocheminės analizės metodai farmacijoje",
        "order": 1,
        "semester": "ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Polimerai farmacinėse technologijose",
        "order": 2,
        "semester": "pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "sintetinių vaistų kūrimo principai",
        "order": 3,
        "semester": "ruduo",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "evaf",
    "name": "Verslas",
    "order": 1,
    "courses": [
      {
        "name": "Verslo teisė",
        "order": 1,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Verslo mokesčių apskaita",
        "order": 2,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Verslas-verslui rinkodara ir asmeninis pardavimas",
        "order": 3,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Verslo modeliai ir operacijos",
        "order": 4,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Verslo derybos ir psichologija",
        "order": 5,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Verslo rizikos valdymas",
        "order": 6,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "evaf",
    "name": "Duomenys ir jų analizė",
    "order": 2,
    "courses": [
      {
        "name": "Duomenų analizės įvadas",
        "order": 1,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Duomenų analizė ir interpretavimas",
        "order": 2,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Didžiųjų duomenų analizė",
        "order": 3,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Duomenų bazių technologijos",
        "order": 4,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Duomenų analizė naudojant E-views",
        "order": 5,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "evaf",
    "name": "Informacinės sistemos",
    "order": 3,
    "courses": [
      {
        "name": "Informacinės sistemos",
        "order": 1,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Informacinių sistemų projektavimo metodai",
        "order": 2,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Informacinės technologijos",
        "order": 3,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Informacinių sistemų ir vartotojų sąveika",
        "order": 4,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Kompiuterinis modeliavimas ir duomenų analizė",
        "order": 5,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "evaf",
    "name": "Rinkodara",
    "order": 4,
    "courses": [
      {
        "name": "Rinkodaros pagrindai",
        "order": 1,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Skaitmeninė rinkodara",
        "order": 2,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Rinkodaros tyrimų pagrindai",
        "order": 3,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Rinkodaros analitika",
        "order": 4,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Rinkodaros valdymas",
        "order": 5,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "evaf",
    "name": "Vadyba ir organizacijos",
    "order": 5,
    "courses": [
      {
        "name": "Organizacijų vadyba",
        "order": 1,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Vadyba",
        "order": 2,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Organizacinė elgsena",
        "order": 3,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Žinių vadyba",
        "order": 4,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "fsf",
    "name": "Saviugda (psichologinė ir filosofinė pakraipa)",
    "order": 1,
    "courses": [
      {
        "name": "Emocijos ir motyvacija",
        "order": 1,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Asmenybė ir jos tapsmas",
        "order": 2,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Socialinių įgūdžių lavinimas",
        "order": 3,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "A. Kairytė",
            "comment": "Šiuo metu dalyką dėstome dviese – psichologės Agnietė Kairytė ir Miglė Vainalavičiūtė. Kartais šio dalyko dėstytojai keičiasi, tačiau jais dažniausiai būna Psichologijos instituto darbuotojai. Šis dalykas yra svarbus, nes jame per nuolatinę refleksiją ir \"aš-kitas\" santykio nagrinėjimą, ugdome bazinius socialinius įgūdžius, kurie yra reikalingi bet kurioje profesinėje srityje, kurioje yra bent du žmonės. Dalykas siūlomas studentams, kurie nori ugdyti savo socialinius įgūdžius, nepriklausomai nuo studijuojamos disciplinos.\nSemestro metu reikės parengti pristatymą ir pateikti savirefleksiją, o paskaitų lankomumas yra privalomas, vyksta viena paskaita per savaitę."
          }
        ]
      },
      {
        "name": "Sprendimų priėmimo psichologija",
        "order": 4,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Klasikinė etika ir modernioji lyderystė",
        "order": 5,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "fsf",
    "name": "Sociologiniai dalykai",
    "order": 2,
    "courses": [
      {
        "name": "Švietimas, nelygybė ir galia",
        "order": 1,
        "semester": "Pavasaris / ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Agnė Girkontaitė",
            "comment": "Švietimas, nelygybė ir galia kurse nagrinėjame švietimą iš kritinės sociologijos perspektyvos ir keliame klausimus apie nelygybę švietime, ideologijas, simbolinę galią, aukštojo mokslo raidą ir kitus. Kursas labiausiai praverstų tiems, ką domina darbas švietimo srityje, tačiau tema išties aktuali visiems, nes visi vienaip ar kitaip susiduriame su mokyklos individualiomis ir visuomeninėmis pasekmėmis. Kurse skiriamas dėmesys ne tik teorinių idėjų ir sąvokų aptarimui, bet ir diskusijoms apie švietimo būklę ir aktualijas bei mokymuisi rašyti rašto darbus, pagrįstus tyrimais. Atsiskaitymas susideda iš dviejų pagrindinių elementų – teorinio empirinio rašto darbo grupėje ir egzamino, į rašto darbą integruojami ir kiti mažesni atsiskaitymai – referatas ir statistinių duomenų apžvalga."
          }
        ]
      },
      {
        "name": "Ekonominė sociologija",
        "order": 2,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Sveikatos sociologija",
        "order": 3,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Subkultūrų studijos",
        "order": 4,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "dr. L. Kraniauskas",
            "comment": "„Subkultūrų studijos“ jau daugiau kaip du dešimtmečius yra profesinis hobis, kuris leidžia pasidalinti sociologinėmis įžvalgomis apie alternatyvias ir muzikines tapatybes su studentais bei profesionaliais kultūros tyrėjais įvairiuose tarptautiniuose renginiuose.\n\nAlternatyvumas, kuris išreiškiamas kaip subkultūrinė tapatybė ir stilius, yra viena iš paklausiausių prekių kultūros rinkoje, idėja puikiai išnaudojama šiuolaikinėje madoje ir rinkodaroje, siūlo autentišką patirtį ir dažnai suvokiamas kaip individualus pasirinkimas. Subkultūrų studijos bando atskleisti socialines tokių tapatybių ištakas, susieti jas su platesniais visuomenės procesais ir permainomis, kas leidžia aiškiau perprasti kultūros ekonomikos veikimą ir jos ideologinį poveikį mūsų pasirinkimams.\n\nStudentai užsiėmimuose atlieka svarbų vaidmenį, nes jie suteikia galimybę susipažinti su naujai atsirandančiomis subkultūromis, kurios dar nėra papuolusios į akademinio domėjimosi akiratį. Atsiskaitymui nedidelės studentų grupelės turi pristatyti tiriamojo pobūdžio darbą apie pasirinktą subkultūrą, kuriame būtų įveiklintos teorinės sąvokos, empirinė medžiaga ir kritinis mąstymas."
          }
        ]
      },
      {
        "name": "Lyčių reprezentacijų sociologinė analizė",
        "order": 5,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Asistentė dr. G. Martinkėnė",
            "comment": "„Lyčių reprezentacijų sociologinės analizės“ dalyką dėsto socialinių mokslų daktarė, sociologė Gintė Martinkėnė Sociologijos katedroje. Šeimos ir lyčių tematikos kontekste dėstytoja jau yra seniai ir dėstytojai svarbu sudominti studentus šiomis temomis bei pažadinti arba sustiprinti jų kritinį mąstymą gyvenant šiuolaikinėje visuomenėje ir stebint, kaip lytys yra pateikiamos viešojoje erdvėje.\n\nŠis dalykas yra svarbus, nes lytys yra visur aplink mus ir mes visada esame apsupti žmonių bei žmonių sąveikų. Ir visada yra pasirinkimas eiti savo gyvenimo keliu nesusimąstant, kas vyksta aplinkui, arba sustojant ir pasidairant aplinkui bei keliant klausimus, kodėl lyčių kontekste žmonės save pateikia vienaip arba kitaip arba kodėl kitų žmonių jie yra pateikiami tam tikru būdu, nuo ko tai priklauso, kaip tai galėtų būti analizuojama sociologiškai bei kokią lyčių reprezentacijų paveikslo dinamiką galime pastebėti laiko tėkmėje.\n\nŠio kurso kontekste teoriniai dalykai yra integruojami į praktinių veiklų įgyvendinimą. Šio kurso metu nėra egzamino, tačiau yra svarbus dalyvavimas paskaitose ir seminaruose bei dviejų užduočių atlikimas: lyčių reprezentacijos viešojoje erdvėje analizė ir tinklalaidės sukūrimas."
          }
        ]
      }
    ]
  },
  {
    "tenant_alias": "fsf",
    "name": "Ugdymas (švietimo lauke)",
    "order": 3,
    "courses": [
      {
        "name": "Lytiškumo ugdymas",
        "order": 1,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Akvilė Giniotaitė",
            "comment": "Kas pirmiausia šauna į galvą išgirdus „lytiškumo ugdymas“? O kas – kitiems? Šis kursas kviečia stabtelėti ir permąstyti sritį, apie kurią mokykloje dažnai buvo nutylima, bet kuri iš tiesų nuolat veikia mūsų kasdienybę.\n\nPadeda geriau suprasti, kaip formuojasi lytiškoji savimonė, kaip mokyklos aplinka veikė ir veikia mūsų tapatybę, savivertę, tarpasmeninius santykius ir jų modelius bei platesnius visuomenės procesus. Jis ypač vertingas pedagogikos studentams ir studentėms, nes lytiškumo klausimai klasėje kyla nuolat ir reikalauja jautraus, informuoto bei atsakingo požiūrio. Tačiau kursas aktualus ir kitų krypčių studentijai. Kurso metu daug dėmesio skiriama diskusijoms ir asmeninei refleksijai: analizuojamos turėtos patirtys, aptariamas visuomenėje kylantis susipriešinimas, nagrinėjami popkultūros pavyzdžiai, socialinių tinklų reiškiniai ir jų sąsajos su lytimi bei tapatybe. Tai erdvė ne tik įgyti žinių, bet ir saugiai kelti klausimus, dalintis įžvalgomis bei gilinti savo supratimą apie šiandienos visuomenę lyčių klausimais. Atsiskaitymą sudaro: komunikacijos plano parengimas, debatai pasirinkta tema, esė apie pasirinktą LU temą, dalyvavimas paskaitų ir seminarų metu."
          }
        ]
      },
      {
        "name": "Įtraukiojo ugdymo problemos ir praktiniai sprendimai",
        "order": 2,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Viktorija Vaidogaitė",
            "comment": "Dėstau kokybinių tyrimų ir įtraukiojo ugdymo temas, ypatingą dėmesį skirdama praktiniam problemų analizavimui ir sprendimų paieškai. Šiame dalyke deriname teoriją su realiomis mokyklų situacijomis ir tyrimų atlikimu. Įtraukusis ugdymas yra viena svarbiausių šiuolaikinio švietimo krypčių, tačiau praktikoje jis kelia daug iššūkių. Šis dalykas padeda suprasti šias problemas ir išmokti jas analizuoti bei spręsti remiantis tyrimais. Dalykas skirtas studentams, kuriems įdomu švietimas, socialinė įtrauktis ir realios praktinės problemos. Taip pat tiems, kurie nori išmokti atlikti kokybinius tyrimus ir giliau analizuoti socialinius reiškinius. Įtraukaus ugdymo problemos ir praktikos, kokybiniai tyrimai (interviu, focus grupės), duomenų analizė (kodavimas, teminė analizė), tyrimo etika ir rezultatų interpretavimas. Studentai dirba grupėse arba individualiai: atlieka nedidelį kokybinį tyrimą (interviu arba focus grupę), analizuoja duomenis ir pristato rezultatus. Vertinamas tiek tyrimo procesas, tiek gebėjimas reflektuoti savo, kaip tyrėjo, patirtį."
          }
        ]
      },
      {
        "name": "Ugdymo institucijos vadyba",
        "order": 3,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Simona Boguckienė",
            "comment": "Esu praktikė - mokyklos vadovė, todėl Ugdymo institucijos vadybos paskaituose remiuosi realia patirtimi. Mano tikslas - padėti studentams tapti ne tik metodus išmanančiais, bet ir plačiau mąstančiais švietimo profesionalais. Vadyba vyksta ne tik mokyklos administracijoje - ji kasdien vyksta klasėje. Mokytojas nuolat planuoja, organizuoja, motyvuoja, sprendžia konfliktus ir bendrauja su tėvais, todėl iš esmės atlieka vadybines funkcijas. Jų neišmanymas apsunkina darbą, o jų supratimas leidžia dirbti užtikrintai, kurti stiprius santykius ir veiksmingai valdyti ugdymo procesą. Tai yra būtina sąlyga norint būti sėkmingu, profesionaliu ir vertinamu darbuotoju šiuolaikinėje mokykloje. Šį dalyką turėtų rinktis kiekvienas būsimas mokytojas, kuriam rūpi švietimas ir vaikai, kuris nori suprasti, kaip veikia mokykla, ir pasiruošti sėkmingam startui joje. Tiek kurso temos, tiek atsiskaitymas orientuoti į tai, ką galėsite pritaikyti savo būsimame darbe. Grupinės ir individualios praktinės užduotys paskaitose ir egzaminas, orientuotas į realias situacijas, su kuriomis susidursite dirbdami mokykloje."
          }
        ]
      },
      {
        "name": "Dirbtinis intelektas ugdyme: teorija, praktika, etika",
        "order": 4,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Jogaila Vaitekaitis",
            "comment": "Esu dr. Jogaila Vaitekaitis, edukologijos tyrėjas. Šiame kurse DI ne tik aptariame teoriškai — patys testuojame įrankius, vertiname jų ribas ir ieškome, kur jie realiai padeda mokyme ir mokymesi. DI įrankiai jau naudojami mokymesi, vertinime ir mokytojo darbe. Gebėjimas juos taikyti atsakingai — suprasti etinius, teisinius (pvz., ES DI aktas) ir kokybės klausimus (pvz., „haliucinacijos\") — tampa kasdiene kompetencija, ne tik technine. IT ar programavimo žinių nereikia. Kursas tinka būsimiems pedagogams, edukologams ir visiems, kurie nori sąmoningai naudoti DI savo mokymesi ir darbe. Reikia tik smalsumo ir noro eksperimentuoti. Tradicinių egzaminų nėra. Pagrindinis atsiskaitymas (50 % galutinio pažymio) — studentų pačių vedamas praktinis seminaras (dirbtuvės), kuriame parodote, kaip konkretus DI įrankis sprendžia realią edukacinę problemą. Likusi dalis — aktyvus darbas ir praktinės užduotys seminarų metu."
          }
        ]
      },
      {
        "name": "Mokymasis neformaliojo ugdymo kontekstuose",
        "order": 5,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Justina Garbauskaite",
            "comment": "I am a facilitator of learning with 10+ years of experience in the non-formal education field. Falling in love with learning in non-formal education contexts led me to study educational sciences at the university and shaped my main field of research.\tThe role of the non-formal education field is becoming increasingly relevant. Many people choose to run workshops and share their ideas with those interested in them. The challenge, however, is a lack of understanding of how to balance depth of learning with the fun of the learning process. Facilitator of learning enters. This course is meant for those aspiring to become educators (teachers, trainers, facilitators) and train them to plan, implement and evaluate non-formal learning processes. Evaluation strategy: group learning needs assessment (case analysis) – 20%, planning, implementing and evaluating a workshop based on non-formal education principles – 30%, peflective journal – 30%, active participation (in lectures, workshops, seminars, group discussions, completing learning assignments) – 20%. Important note: in order to complete the assignments – you’ll need to be present in class and interact with other learners."
          }
        ]
      },
      {
        "name": "Mokyklos pasaulyje",
        "order": 6,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Rūta Bružienė Monika Orechova",
            "comment": "The course was primarily created to provide more knowledge and understanding about various education systems in different countries around the world. Although this course is often chosen by pedagogy students, we also welcome students from psychology, political science, and language study programs. According to student feedback, the course helps to better understand the education systems of various countries, reflect on one’s own system, and view the education system not from a personal, but from a systemic perspective, which is why it can be interesting to students from various study programs. Since there are no prerequisites (except for English proficiency at B2 level), students from all study programs are welcome in the course.\n\nSince the course is taught in English, a considerable number of international students, who have chosen Vilnius University for part of their studies, gather each year. Such an intercultural group allows the main goal of the course to be effectively realized – to combine personal education and teaching experiences, which everyone has, with more structured knowledge about how education takes place in different countries and cultures.\n\nDuring the course, we address questions about the impact of globalization on education systems, their recognizable common features, as well as how the experiences and cultural norms of specific countries influence the education system and how education is experienced within these systems by participants – pupils and students.\n\nThe study and assessment requirements of the course are based on active work in the classroom; in the sessions we apply active learning methods, students work in groups, discuss share experiences and reflections. The structure of the cumulative assessment consists of project work carried out during the sessions, and students who participate less in seminars are given the opportunity to complete an individual written assignment."
          }
        ]
      }
    ]
  },
  {
    "tenant_alias": "fsf",
    "name": "Azijos kultūra",
    "order": 4,
    "courses": [
      {
        "name": "Azijos muzikos tradicijos",
        "order": 1,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Azijos kinas",
        "order": 2,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Performatyvios tradicijos Pietų Azijoje",
        "order": 3,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Įvadas į feminizmo teorijas ir lyčių studijas Azijoje",
        "order": 4,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Pietų Azijos mitologija",
        "order": 5,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Kinų estetika",
        "order": 6,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "fsf",
    "name": "Kriminologija",
    "order": 5,
    "courses": [
      {
        "name": "Policijos veikla",
        "order": 1,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Lietuvos kriminologijos istorija",
        "order": 2,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Socialiniai tyrimas kriminologijoje",
        "order": 3,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Kriminologiniai diskursai",
        "order": 4,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Įvadas į baudžiamąją politiką",
        "order": 5,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Kriminologijos teorijos",
        "order": 6,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "filf",
    "name": "Literatūra (anglų kalba)",
    "order": 1,
    "courses": [
      {
        "name": "Lyčių reprezentacijos šiuolaikinėje lietuvių literatūroje ir kultūroje",
        "order": 1,
        "semester": "pavasaris",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. D. Kuzminskaitė",
            "comment": "Šį dalyką dėsto dvi dėstytojos – lyčių studijų specialistė dr. Ieva Bisigirskaitė ir literatūrologė doc. dr. Dovilė Kuzminskaitė. Ieva veda teorines paskaitas ir supažindina su lyčių studijų pagrindais, o Dovilė veda seminarus, kuriuose gilinamasi į literatūros tekstus.\n\nKurso tikslas – pristatyti lyčių studijas ne kaip atskirą discipliną, o kaip įrankį kritiškai apmąstyti visuomeninį, politinį ir kultūrinį Lietuvos kontekstą. Kursas kviečia pažvelgti į Lietuvos kultūros lauką iš lyčių studijų perspektyvos, siejant teorines įžvalgas su aktualiais procesais – nuo literatūrinio kanono pervertinimo iki šiuolaikinio meno, kino ir aktyvizmo. Turinys grindžiamas vykdomais tyrimais, įtraukiant studentus į aktualų mokslinį procesą, o tarptautinė studentų grupė skatina kultūrinį dialogą. Programoje taip pat dalyvauja kviestiniai tyrėjai, pristatantys naujausias tyrimų kryptis ir metodologijas.\n\nDalykas atviras visų studijų krypčių studentams, tiek jau susipažinusiems su lyčių studijomis, tiek pradedantiesiems. Skatinamas aktyvus dalyvavimas, diskusijos ir kritinis mąstymas. Studijų turinys vystomas nuo teorijos prie taikymo: nagrinėjamos lyties ir tautos sąsajos, motinystė, aktyvizmas, kalbos politika, queer bendruomenės problematika Baltijos šalyse ir feminizmo raiška Lietuvos mene. Teorinės žinios įtvirtinamos praktinėse užduotyse.\n\nVertinimas kaupiamasis: 50% sudaro rašto darbas, 30% – pristatymas, 20% – darbas seminaruose."
          }
        ]
      },
      {
        "name": "Posovietinė lietuvių literatūra",
        "order": 2,
        "semester": "pavasaris",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. D. Litvinskaitė",
            "comment": "Asistentė dr. Daiva Litvinskaitė (Lituanistinių studijų katedra) dėsto kursą „Posovietinė lietuvių literatūra“, skirtą supažindinti su pagrindinėmis nepriklausomybę atkūrusios Lietuvos literatūros raidos kryptimis ir pokyčiais. Nagrinėjami kūriniai nuo XX a. 9-ojo dešimtmečio iki šių dienų, siekiant suprasti, kaip po Sovietų Sąjungos žlugimo keitėsi literatūrinis laukas.\n\nKursas padeda suvokti, kaip literatūra reaguoja į istorinius pokyčius, analizuojant temas kaip tapatybė, atmintis, trauma, laisvė, ideologija ir cenzūra, taip pat moterų literatūra. Jis ypač tinka studentams, besidomintiems literatūra, istorija ir kultūros studijomis bei norintiems geriau suprasti XX–XXI a. Lietuvos ir Rytų Europos kultūrinius procesus.\n\nVertinimas grindžiamas aktyviu dalyvavimu seminaruose, prezentacija ir semestro pabaigos egzaminu."
          }
        ]
      },
      {
        "name": "XX a. pasaulio literatūra. Trumpa literatūrinių idėjų istorija",
        "order": 3,
        "semester": "pavasaris",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. O. Ivancu",
            "comment": "I’m Ovidiu Ivancu, Associate Professor at the Institute of Foreign Languages, Faculty of Philology, Vilnius University, with over 20 years of teaching experience in Romania, India, and Moldova. In the course *World Literature of the 20th Century: A Brief History of Literary Ideas*, we explore how writers like Kafka, Camus, García Márquez, and Kundera responded to major historical shifts through literature. The course invites students to question, analyze, and connect ideas, focusing on movements such as existentialism, absurdism, magical realism, feminism, and dystopian literature.\n\nThe 20th century was a time of literary innovation, raising questions about human existence in a fragmented world. This course helps students understand literature as a way to interpret modern life, developing critical thinking, cultural literacy, and empathy through themes like identity, alienation, and societal change.\n\nIt is ideal for curious students who enjoy discussion and analysis, especially those interested in literature, education, or the intersection of art and politics. The course covers key movements and texts, always linking them to historical context and contemporary relevance.\n\nAssessment is based on active participation (20%), a midterm exam (30%), and a final exam (50%). At least 80% attendance is required to take the final exam."
          }
        ]
      },
      {
        "name": "Literatūra, kultūra, teorija",
        "order": 4,
        "semester": "ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. R. Šlapkauskaitė",
            "comment": "Kursas „Literatūra, kultūra, teorija“ pristato susistemintą Vakarų teorinio mąstymo apie literatūrą ir meną apžvalgą, daugiausia dėmesio skiriant pagrindinėms sąvokoms, istorinėms ir šiuolaikinėms problemoms bei meninių pavyzdžių teikiamoms įžvalgoms. Aptariami įvairūs požiūriai į tekstų ir kultūros tyrimus, pabrėžiant tarpdisciplininio dialogo svarbą ir skatinant permąstyti kultūrinių reiškinių vertę socialinių medijų ir dirbtinio intelekto kontekste.\n\nKursas dėstomas anglų kalba ir skirtas žingeidiems studentams, siekiantiems ugdyti kritinį mąstymą, atidų skaitymą ir kultūrinį jautrumą. Tematika apima literatūrą ir estetiką, formą ir struktūrą, žanrą, tekstą ir patirtį, tapatumą ir reprezentaciją, recepciją bei afektą, taip pat literatūros ir aplinkos santykį.\n\nStudijas sudaro paskaitos ir seminarai per 16 savaičių. Atsiskaitymas – kolokviumas semestro viduryje ir baigiamasis egzaminas, tikrinantys tiek žinias, tiek analitinio ir kritinio mąstymo gebėjimus."
          }
        ]
      },
      {
        "name": "Šiuolaikinės lietuvių literatūros įvadas",
        "order": 5,
        "semester": "ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. D. Litvinskaitė",
            "comment": "Asistentė dr. Daiva Litvinskaitė (Lituanistinių studijų katedra) dėsto kursą „Įvadas į moderniąją lietuvių literatūrą“, skirtą supažindinti su lietuvių literatūros raida nuo XIX a. pabaigos iki XX a. vidurio. Kursas ne tik pristato pagrindinius laikotarpio reiškinius, bet ir ugdo gebėjimą analizuoti bei interpretuoti kūrinius platesniame Europos literatūros kontekste.\n\nJo metu nagrinėjamas moderniosios lietuvių literatūros formavimasis, modernizmo estetika, Europos literatūrinių krypčių įtaka, karo ir pokario temos bei lietuvių literatūra diasporoje. Literatūra aptariama kaip svarbi visuomenės, tapatybės ir kultūrinių pokyčių atspindžio forma.\n\nKursas skirtas studentams, besidomintiems modernizmu ir norintiems giliau suprasti jo raišką Lietuvoje, taip pat stiprina kritinio mąstymo ir interpretacijos įgūdžius. Vertinimas grindžiamas aktyviu dalyvavimu seminaruose, prezentacija ir semestro pabaigos egzaminu."
          }
        ]
      },
      {
        "name": "Monstro įvaizdis posovietinėje literatūroje",
        "order": 6,
        "semester": "ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. T. Čenys",
            "comment": "Kursas „Monstro įvaizdis postsovietinėje literatūroje“ supažindina su monstro įvaizdžiu šiuolaikinėje kultūroje ir literatūroje bei jo kilme, remiantis įvairiomis teorijomis (Dž. Kempbelo, Dž. J. Coheno, V. Proppo ir kt.). Kursą sudaro trys dalys: monstrai mitologijoje ir folklore, gotikiniame romane ir postsovietinėje literatūroje.\n\nMonstro įvaizdis analizuojamas kaip svarbus kultūrinis fenomenas, atskleidžiantis visuomenės baimes, norus ir sociokultūrinius procesus. Kursas skirtas tiek besidomintiems literatūra, tiek populiariąja kultūra ar postsovietinių visuomenių reiškiniais.\n\nNagrinėjamos temos apima monstrų vaidmenį mitologijoje ir pasakose, jų ryšį su romantizmu ir gotikine literatūra, sovietinės traumos refleksijas, propagandos diskursus, seksualumo ir moteriškojo monstriškumo aspektus. Vertinimas grindžiamas pristatymu, aktyviu dalyvavimu seminaruose ir baigiamuoju rašto darbu."
          }
        ]
      }
    ]
  },
  {
    "tenant_alias": "filf",
    "name": "Gilinimasis į kultūrą (anglų kalba)",
    "order": 2,
    "courses": [
      {
        "name": "Baltų kultūra per kalbos prizmę",
        "order": 1,
        "semester": "ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "G. Junčytė",
            "comment": "The course Baltic Culture through Language introduces students to Baltic culture through the development of Baltic languages from their Indo-European roots to modern Lithuanian, Latvian, and Latgalian. It explores historical periods, sociocultural changes, and the mutual relationship between language and culture.\n\nThe course is important for building a deep understanding of Baltic culture from prehistoric times to the present, combining insights from linguistics with archaeology, history, ethnography, literature, and art history.\n\nIt is suitable for students interested in Baltic cultures and languages (including extinct ones like Prussian), as well as those curious about how language reveals historical and cultural developments.\n\nTopics include the formation and religion of ancient Balts, language-based insights into culture, Christianization, early texts and literary language formation, and cultural development through the 19th century, Soviet period, and restored independence.\n\nThe course consists of 8 lectures and 16 seminars. Assessment includes short seminar presentations (30%), one major presentation (30%), and a final test (40%)."
          }
        ]
      },
      {
        "name": "Kultūra ir daugiakalbystė",
        "order": 2,
        "semester": "pavasaris",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. R. Kriaučiūnienė",
            "comment": "Esu Roma Kriaučiūnienė, Filologijos fakulteto Užsienio kalbų instituto direktorė ir profesorė. Dėstau kursą „Kultūra ir daugiakalbystė“, kurio tikslas – ugdyti studentų kalbinę komunikacinę kompetenciją ir plėtoti tarpkultūrinį bendravimą, kritinį bei empatinį mąstymą, bendradarbiavimo įgūdžius ir moralinę-demokratinę laikyseną. Tai tarpdisciplininis kursas, jungiantis kalbos, kultūros ir visuomenės pažinimą.\n\nKurso metu analizuojama kultūros samprata ir daugiakalbystės vaidmuo globaliame pasaulyje. Daugiakalbystė suprantama ne tik kaip kelių kalbų mokėjimas, bet ir gebėjimas veiksmingai bendrauti su skirtingų kultūrų atstovais. Aptariami globalizacijos, migracijos, kultūrinių skirtumų suvokimo ir empatiško jų priėmimo klausimai.\n\nKursas atviras visų studijų krypčių studentams, besidomintiems kalbų ir kultūrų įvairove bei tarptautine aplinka. Nagrinėjamos temos apima tarpkultūrinę komunikaciją, etinius jos aspektus, daugiakalbystės raidą, migraciją, kultūrinę tapatybę ir švietimo klausimus.\n\nVertinimas kaupiamasis su baigiamuoju egzaminu: studentai rašo esė apie migracijos poveikį tapatumui ir atlieka komandinį projektą, nagrinėjantį aktualias daugiakalbystės temas."
          }
        ]
      },
      {
        "name": "Didžiosios Britanijos kultūros istorija",
        "order": 3,
        "semester": "ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "nebėra šito dalyko",
            "comment": "IŠIMTI ŠITĄ DALYKĄ"
          }
        ]
      },
      {
        "name": "JAV kultūros istorija",
        "order": 4,
        "semester": "ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. S. Postic",
            "comment": "Hello, my name is Svetozar Postic, and I teach the course *Cultural History of the United States*. The course covers key aspects of American culture, including politics, religion, and popular culture.\n\nIt is important for students to understand the United States, its values, and identity, as it has been a leading global power for the past 80 years, while also examining the reasons behind its recent decline in influence.\n\nThe course is especially useful for English Philology students and those studying English as a foreign language, as well as anyone interested in history, culture, sociology, politics, religion, literature, philosophy, and the arts.\n\nTopics include African Americans, Native Americans, literature and philosophy, Hollywood, education, and material culture from historical and sociological perspectives.\n\nAssessment consists of a presentation (40%), attendance and participation (10%), and a final exam (50%), which emphasizes critical thinking and the ability to connect ideas rather than memorization."
          }
        ]
      },
      {
        "name": "Kalba populiariojoje kultūroje",
        "order": 5,
        "semester": "ruduo",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "filf",
    "name": "Gilinimasis į kultūrą (lietuvių kalba)",
    "order": 3,
    "courses": [
      {
        "name": "Lytis kultūroje",
        "order": 1,
        "semester": "pavasaris",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. E. Kačkutė-Hagan",
            "comment": "Dr. E. Kačkutė-Hagan dėsto modulį „Lytis kultūroje“, kurį veda 4 dėstytojų kolektyvas. Jo metu aptariami svarbiausi feministinės kritikos etapai, metodai ir jų taikymas analizuojant literatūrą, kiną, istoriją bei kitus kultūrinius reiškinius.\n\nKursas pabrėžia, kad lyčių struktūros formuojasi ne tik per įstatymus, bet ir kultūroje bei mene. Feministinė kritika nagrinėja lyties raišką iš feministinės ir maskulinistinės perspektyvų, atskleisdama jos poveikį įvairiuose meno ir mokslo laukuose.\n\nModulis skirtas studentams, besidomintiems lyčių klausimais ir norintiems plėsti kritinio mąstymo įgūdžius. Jis apima kino, vizualiojo meno, literatūros ir istorijos disciplinas, pristato klasikines, queer, intersekcionalaus feminizmo ir maskulinizmo teorijas bei naujausius tyrimus.\n\nVertinimas apima dalyvavimą Emancipacijos dienos renginiuose ir refleksiją, sąvokų testą bei baigiamąją grupinę kūrybinę užduotį – tinklalaidės kūrimą (atskirai vertinamas jos planas ir bibliografija)."
          }
        ]
      },
      {
        "name": "Kultūros ir medijų teorijos",
        "order": 2,
        "semester": "pavasaris",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. P. Jevsejevas",
            "comment": "Šį dalyką dėsto Paulius Jevsejevas ir Dmitrij Gluščevskij iš Filologijos fakulteto Literatūros, kultūros ir vertimo tyrimų instituto, A. J. Greimo semiotikos ir literatūros teorijos centro nariai. Jų veikla apima modernias kultūros teorijas – semiotiką, hermeneutiką, psichoanalizę, intermedialumą ir kt.\n\nKursas „Kultūros ir medijų teorijos“ skirtas apmąstyti bendrąsias kultūros ir medijų problemas, pabrėžiant teoriją kaip darbą su sąvokomis, o ne kaip taisyklių ar informacijos rinkinį. Siekiama skatinti kritinį, nuoseklų mąstymą ir gebėjimą analizuoti kultūros reiškinius platesniame kontekste.\n\nKursas naudingas studentams, norintiems ugdyti gebėjimą aiškiai ir argumentuotai kalbėti apie kultūros ir medijų problemas, bei tiems, kurie domisi šių reiškinių įvairove ir nori geriau suprasti jų vaidmenį visuomenėje.\n\nTemos apima kultūrą kapitalistinėje visuomenėje, antropologinį kultūros supratimą, medijų kaitą, popkultūrą, vizualines ir skaitmenines medijas, medijų estetiką. Turinys gali kisti, įtraukiami kviestiniai lektoriai ir lankomi renginiai.\n\nAtsiskaitymas vyksta per aktyvų dalyvavimą diskusijose, pranešimus, egzamino rašto darbą ir jo gynimą žodžiu."
          }
        ]
      },
      {
        "name": "Miestas antikinėje kultūroje",
        "order": 3,
        "semester": "ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. T. Riklius",
            "comment": "Dr. Tomas Riklius (VU Filologijos fakultetas) dėsto kursą „Miestas antikinėje kultūroje“, kuriame nagrinėjamas graikų ir romėnų miestas kaip politinis, religinis, kultūrinis ir ekonominis centras. Daugiausia dėmesio skiriama Atėnams ir Romai, analizuojant jų architektūrą, viešąsias erdves, institucijas ir kasdienį gyvenimą bei jų įtaką Europos kultūrai.\n\nKursas padeda suprasti, kaip formuojasi bendruomenės, kaip architektūra atspindi politines idėjas ir kaip miestas tampa kultūrinės tapatybės erdve. Antikinių miestų pažinimas leidžia geriau suvokti ir šiuolaikinių miestų veikimą. Dalykas tinka studentams, besidomintiems istorija, kultūra, menu, architektūra ar politinių idėjų raida, ir nereikalauja išankstinių žinių. Kurse pirmiausia analizuojami Atėnai (politika, Akropolis, religija, teatras, ekonomika), vėliau Roma (įkūrimo mitai, forumas, institucijos, kasdienis gyvenimas, imperijos modelis). Vertinimas kaupiamasis: 30% sudaro rašto darbas, 30% – tarpinis atsiskaitymas, 40% – baigiamasis egzaminas."
          }
        ]
      },
      {
        "name": "Litvakų kultūra",
        "order": 4,
        "semester": "ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. M. Kvietkauskas",
            "comment": "Kursą „Litvakų kultūra“ veda dr. M. Kvietkauskas kartu su istorike prof. Jurgita Verbickiene. Jo metu atsiveria platus Lietuvos žydų bendruomenės pasaulis – nuo religinių ir kalbinių tapatybės pagrindų iki ryškiausių literatūros ir meno kūrėjų. Daug dėmesio skiriama sudėtingiems lietuvių ir žydų santykiams, Holokausto patirčiai bei šiandieninėms atminties formoms.\n\nŠis kursas leidžia geriau pažinti kultūrą, kuri ilgą laiką buvo nepakankamai matoma ir dažnai suvokiama per stereotipus. Gilinimasis į litvakų paveldą padeda suvokti jo svarbą Lietuvos ir platesniame Europos kontekste, kartu ugdo jautresnį ir atviresnį požiūrį į istoriją bei kitų patirtis.\n\nJis ypač patrauklus tiems, kuriems įdomūs istorijos, kultūros ir atminties klausimai, kurie vertina kritinį mąstymą ir dialogą tarp skirtingų tapatybių. Nors kursas yra humanitarinis, jis praturtina platesnį kultūrinį suvokimą ir pilietinį sąmoningumą.\n\nSeminaruose nagrinėjamos temos apima litvakų kilmę, religiją, istorinius procesus, Vilniaus reikšmę jų tekstuose, lietuvių ir žydų santykių raidą, moderniąją kūrybą bei Holokausto atminties problemas. Vertinimas remiasi aktyviu dalyvavimu, rašto darbu ir baigiamuoju egzaminu."
          }
        ]
      },
      {
        "name": "Senovės Graikijos kultūra ir menas",
        "order": 5,
        "semester": "ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. Š. Šavėla",
            "comment": "Kursą apie graikų kultūrą dėsto Šarūnas Šavėla ir Gražina Kelmelytė. Jo metu keliaujama tarp labai senų, šiandien jau nutolusių dalykų ir to, kas vis dar gyva mūsų kasdienybėje, nes graikų kultūra ir mąstymas stipriai formavo dabartinį pasaulį.\n\nGraikų pasaulis pristatomas kaip vienas svarbiausių Europos kultūros pamatų. Jį nagrinėjant atsiveria galimybė geriau suprasti šiuolaikinį meną, politiką ir visuomenę, o kartu išmokti nuosekliai mąstyti apie kultūros raidą.\n\nKursas labiausiai patiks smalsiems studentams, kuriems įdomu, kaip praeitis susijusi su dabartimi, kuo esame panašūs į senovės žmones ir kuo skiriamės. Taip pat tiems, kurie nori susipažinti su svarbiausiais graikų meno ir kultūros pavyzdžiais bei nebijo sudėtingų, ne visada vienareikšmių klausimų.\n\nAtsiskaitymas susideda iš seminaro pranešimo arba rašto darbo ir egzamino, kuriame tikrinamas gebėjimas atpažinti pagrindinius senovės Graikijos kultūros bruožus bei svarbiausius meno kūrinius."
          }
        ]
      }
    ]
  },
  {
    "tenant_alias": "filf",
    "name": "Gilinimasis į kalbą (anglų kalba)",
    "order": 4,
    "courses": [
      {
        "name": "Lietuvos sociolingvistinė situacija ir kalbų politika",
        "order": 1,
        "semester": "pavasaris",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. I. Daraskiene",
            "comment": "Dr. I. Daraskienės kursas „Sociolingvistinė situacija ir kalbų politika Lietuvoje“ orientuotas į kalbą kaip socialinį reiškinį – kaip žmonės Lietuvoje kalba, kokias kalbas renkasi ir ką jos jiems reiškia.\n\nKurse nagrinėjama Lietuvos visuomenės kalbinė įvairovė: kokios etninės grupės gyvena šalyje, kokias kalbas ir tarmes jos vartoja, kaip šios kalbos vertinamos, saugomos ar puoselėjamos. Taip pat analizuojama valstybės kalbų politika ir jos poveikis kalbų vartojimui.\n\nTai geras pasirinkimas studentams, kuriems įdomi lietuvių kalbos istorija, kalbinė įvairovė, visuomenės požiūris į kalbas ir tai, kaip kalba susijusi su tapatybe bei politika.\n\nStudijos vyksta per paskaitas ir seminarus. Vertinimas susideda iš aktyvaus dalyvavimo, pasirinktos temos pristatymo ir baigiamojo egzamino raštu."
          }
        ]
      },
      {
        "name": "Anglų kalbos istorija",
        "order": 2,
        "semester": "pavasaris",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "R. Šileikytė-Zukienė",
            "comment": "I am R. Šileikytė-Zukienė, and I teach the course *History of the English Language*. In this course, we explore how English has developed from its earliest stages to the present day, combining historical context with linguistic analysis to understand changes in phonology, grammar, and vocabulary.\n\nThis subject helps explain why and how language changes over time, giving a deeper understanding of present-day English. It also connects linguistic developments with historical and cultural contexts, encouraging a more critical perspective on language.\n\nThe course is best suited for students interested in language, history, and analytical thinking, especially those who are curious about the deeper structure of English and willing to engage with theoretical concepts and historical texts.\n\nWe cover the main stages of English – Old, Middle, and Early Modern – focusing on changes in phonology, morphology, syntax, and lexicon. Students are also introduced to diachronic research methods and work with historical texts, including small research projects.\n\nAssessment is cumulative: attendance and active participation make up 20% of the final grade, while a written exam accounts for 80%. Students are expected to stay engaged and keep up with coursework throughout the semester."
          }
        ]
      },
      {
        "name": "Etnolingvistika",
        "order": 3,
        "semester": "ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. J. Kirejeva",
            "comment": "My name is Jelena Kirejeva. I am an assistant professor, doctor at the Institute of Applied Linguistics, the Center for Multilingual Studies, the Faculty of Philology. The course on ethnolinguistics I designed three years ago stemmed from my obsession with both Cognitive Linguistics and Cultural Linguistics. Since then it has been popular among students of different cultural and linguistic backgrounds.\n\nThe course focuses on the interrelation between a language and the cultural behaviour of those who speak it and explores the intersection between language, cognition and cultural conceptualisations. The course was designed to help students to aquire metacultural competence by exposing them to the sociocultural reality and the ethnolinguistic diversity of the present-day world.\tAnyone who seeks to achieve success in intercultural communication can benefit from delving into the lingvo-cultural intricacies of the present-day world.\n\nThe course invites students both to explore how features of human languages encode culturally constructed conceptualisations of the whole range of human experience (e.g., the conceptualisation of emotions in the Persian language, the conceptualisation of Land in Australian Aboriginal culture, etc.) and to raise learners‘ awareness of the fact that cultural conceptualisations provide a basis for constructing, interpreting, and negotiating intercultural meanings, which, in its turn, is the key to harmonious and successful intercultural communication.\n\nThe course can be passed through continuous assessment, which is complemented by two synthesis tests (a midterm test – 40 % and a final test – 60 %), whose marks comprise the cumulative examination mark"
          }
        ]
      },
      {
        "name": "Kognityvinės lingvistikos įvadas",
        "order": 4,
        "semester": "ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. I. Šeškauskienė",
            "comment": "KL įvadas yra apie lingvistikos mokyklą, kurios pagrindinė idėja yra kalbos ir žmogaus pažinimo ir mąstymo sąsajos. Kurse dėstomos gana įvairios temos. Vienos iš jų bendresnės, pavyzdžiui, kaip žmonės skirsto į kategorijas įvairius juos supančio pasaulio dalykus ir reiškinius, kas yra prototipai, kas yra kognityvioji  metafora, kuri, kaip paaiškėja, yra mąstymo, ne tik kalbos reiškinys, kaip įvairių kultūrų žmonės suvokia erdvę (artumą, tolumą) ir laiką ir kaip apie juos kalba, kitos - specifiškesnės, pavyzdžiui, žodžių polisemija, arba daugiareikšmiškumas.\n\nKodėl dalykas yra svarbus? Neturiu vieno atsakymo. Galėtų būti svarbus žmogaus išsilavinimui, nes praplečia supratimą apie lingvistiką, apie žmogaus mąstymo sąsajas su kalba, apie įvairiose kalbose besiskiriantį kultūrinį turinį, kuris visgi netrukdo mums susikalbėti.\n\nStudentų į kursą ateina įvairių. Bet laukčiau smalsių, besidominčių kalbomis, žmogaus pažinimu. Sakyčiau, verta būtų rinktis, jei įdomu sužinoti, kas slypi už kalbos apskritai ar ką per skirtingas kalbas sužinome apie kultūrą ir mąstymą. Tai pasakytina ne tik apie kitas kalbas, bet ir apie gimtąją.\n\nAtsiskaitymai: Du rašto darbai, rašomi auditorijoje, grindžiami jau aptartomis temomis; semestro vidurio atsiskaitymas ir galutinis atsiskaitymas.  Kelis taškus galima \"uždirbti\" ir lankant seminarus ir aktyviai diskutuojant grupėje. Viskas nurodyta kurso apraše."
          }
        ]
      },
      {
        "name": "Psicholingvistikos įvadas",
        "order": 5,
        "semester": "ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. J. Korostenskienė",
            "comment": "Esu Vilniaus universiteto Filologijos fakulteto dėstytoja, tiriu kalbą ir komunikaciją tiek teoriniu, tiek diskurso lygmeniu. Mano dėstomas kursas „Psicholingvistikos įvadas“ kviečia pažvelgti į tai, kaip kalba veikia žmogaus mąstyme – nuo pavienių garsų iki ištisų tekstų. Jo metu aiškinamės, kaip realiu laiku suprantame, apdorojame ir kuriame kalbą, kodėl kartais pamirštame ar nesuprantame net pažįstamų dalykų, ir kaip šių procesų supratimas gali padėti efektyviau mokytis.\n\nKalba čia nagrinėjama ne tik kaip komunikacijos priemonė, bet ir kaip svarbi mąstymo dalis. Analizuodami kalbinius procesus geriau suvokiame, kaip formuojasi reikšmės, kaip apdorojama informacija ir kodėl kartais nepavyksta susikalbėti. Šios žinios naudingos tiek tolimesnėms kalbos, medijų ar komunikacijos studijoms, tiek platesniam žmogaus pažinimo supratimui.\n\nKursas skirtas smalsiems studentams, norintiems suprasti, kas vyksta „už kalbos“ – mąstymo ir pažinimo lygmenyje, kai kalbame, klausomės ar skaitome. Jis ypač tiks tiems, kurie nori gilintis į vidinius kalbos mechanizmus ir geriau suprasti savo pačių komunikaciją.\n\nStudijų metu nagrinėjame kalbos suvokimą ir produkciją, atminties vaidmenį, kalbos ir mąstymo santykį, atliekame eksperimentus, analizuojame realius pavyzdžius ir kuriame savus.\n\nVertinimas grindžiamas aktyviu dalyvavimu, trumpomis užduotimis, grupiniu projektu su pristatymu ir baigiamuoju rašto darbu. Daug dėmesio skiriama ne tik teorijai, bet ir gebėjimui ją pritaikyti praktikoje."
          }
        ]
      }
    ]
  },
  {
    "tenant_alias": "filf",
    "name": "Gilinimasis į kalbą (lietuvių kalba)",
    "order": 5,
    "courses": [
      {
        "name": "Lietuvių kalbos etimologija",
        "order": 1,
        "semester": "pavasaris",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. B. Kabašinskaitė",
            "comment": "Šio kurso metu gilinamasi į lietuvių kalbos žodžių kilmę, aiškinamasi, kiek ši leksika yra ištirta ir kaip etimologinius tyrimus atlieka tiek Lietuvos, tiek užsienio kalbininkai. Studentai mokosi skaityti ir suprasti etimologinius aiškinimus, trumpai ir tiksliai nusakyti žodžio kilmę bei atpažinti jo atsiradimo motyvus.\n\nKursas leidžia pamatyti, kaip kalbos istorija siejasi su platesniu pasaulio pažinimu – nuo lingvistinių tyrimų iki kultūrinių ir net filosofinių įžvalgų. Jis padeda geriau suprasti lietuvių kalbos ryšius su kitomis kalbomis, o taip pat susipažinti su archajiškais ar tarminiais žodžiais, praturtinančiais kalbos suvokimą.\n\nŠis dalykas tiks tiems, kuriems įdomi kalbos istorija, žodžių kilmės „detektyvai“, garsų kitimo dėsniai ir žodžių daryba, taip pat nebijantiems susidurti su kitomis kalbomis – tiek senosiomis, tiek šiuolaikinėmis.\n\nKurso metu aptariami etimologijos ryšiai su fonetika, morfologija ir semantika, nagrinėjami žodžių atsiradimo būdai, skolinių keliai ir jų funkcionavimas lietuvių kalboje, taip pat trumpai apžvelgiama etimologijos mokslo raida.\n\nVertinimas grindžiamas praktine užduotimi – analizuoti kelių etimologų tyrimus apie vieno žodžio kilmę, įvertinti jų argumentus, privalumus ir trūkumus, taip pat atsižvelgiama į aktyvumą seminaruose."
          }
        ]
      },
      {
        "name": "Baltų filologijos įvadas",
        "order": 2,
        "semester": "pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Bendrinė lietuvių kalba: nuostatos ir vartosena",
        "order": 3,
        "semester": "ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. I. Smetonienė",
            "comment": "Tai kompleksinis dalykas, apimantis kalbos politiką ir pagrindinius jos dokumentus, kalbos reglamentavimą Lietuvoje ir Europoje, bendrinių kalbų formavimosi istoriją, normos teorijas ir praktinę veiklą, per kurią analizuojami nukrypimai nuo normos. Kiekvienam labai svarbu pažinti savo kalbos istoriją, kalbos politiką, norminimo istoriją ir norminamąją veiklą, pažinti kalbos sistemą ir asisteminius dalykus. Kursas toks, kad jį gali rinkti bet kurios specialybės studentas, nereikia specifinių bazinių žinių – panašiai, kaip renkamasi Retorika.\n\nPagrindinės studijų dalyko tematikos: Standartinė kalba ir kitos su kodifikacija susijusios sąvokos; Bendrinių kalbų formavimosi istorija; Kalbos reglamentavimas Lietuvoje ir kitose šalyse;  normos teorijos ir jų pasireiškimas lietuvių kalboje; Leksikos, morfologijos ir sintaksės normų neatitikimas bei galimi taisymo būdai.\n\nKaupiamasis balas: teorinės dalies patikra, leksikos patikra, morfologijos patikra, sintaksės patikra. Atsiskaitymas per VMA."
          }
        ]
      },
      {
        "name": "Etnolingvistika",
        "order": 4,
        "semester": "ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Lietuvių kalbos dialektologija",
        "order": 5,
        "semester": "ruduo",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "gmc",
    "name": "Biotechnologijos",
    "order": 1,
    "courses": [
      {
        "name": "Mikrobiologija ir biotechnologija",
        "order": 1,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. R. Gudiukaitė",
            "comment": "Prof. dr. Renata Gudiukaitė dirba pramoninės mikrobiologijos ir biotechnologijos srityje, jungdama fundamentines žinias su praktiniais sprendimais. Jos dėstomas kursas „Mikrobiologija ir biotechnologija“ suteikia nuoseklų supratimą apie mikroorganizmų pasaulį ir jų taikymą šiuolaikinėse technologijose. Daug dėmesio skiriama ne tik teorijai, bet ir gebėjimui kritiškai vertinti mokslinius tyrimus bei argumentuotai pristatyti idėjas.\n\nKurso metu nagrinėjama mikroorganizmų sandara, augimas, metabolizmas, genetika, virusologijos pagrindai ir biotechnologinių procesų principai. Taip pat aptariama genetinės informacijos pernaša, baltymų sintezė, fermentacijos procesai ir mikroorganizmų taikymas pramonėje. Teorinės žinios nuolat siejamos su praktika – laboratorijose studentai mokosi dirbti su mikroorganizmais, taikyti molekulinius metodus, analizuoti DNR ir vertinti rekombinantinių baltymų sintezę.\n\nŠis kursas ypač naudingas tiems, kurie domisi mikrobiologija, genetika ar biotechnologija ir planuoja karjerą gyvybės mokslų srityje. Jis padeda suprasti, kaip mikroorganizmai veikia gamtoje ir kaip jų savybės gali būti pritaikytos medicinoje, farmacijoje, aplinkosaugoje ar pramonėje.\n\nVertinimas susideda iš teorinės ir praktinės dalių.\nTeorinė dalis (70 % galutinio balo):\n•\tKeturių tarpinių apklausų vidurkis (mikrobiologijos ir biotechnologijos dalys).\n•\tBūtina teigiamai išlaikyti visus atsiskaitymus.\n•\tJei rezultatai netenkina arba neišlaikytos bent dvi apklausos, laikomas egzaminas iš visų kurso temų.\nLaboratoriniai darbai (30 % galutinio balo):\n•\tMikrobiologijos dalies laboratoriniai darbai (10 %).\n•\tBiotechnologijos dalies laboratoriniai darbai ir ataskaita (20 %).\n•\tLaboratorinių darbų lankymas privalomas; neatlikus jų, galutinis įvertinimas nėra formuojamas.\nGalutinis balas skaičiuojamas tik teigiamai įvykdžius visus teorinius ir praktinius atsiskaitymus."
          }
        ]
      },
      {
        "name": "Pramoninė mikrobiologija",
        "order": 2,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. R. Gudiukaitė",
            "comment": "Prof. dr. Renata Gudiukaitė – mikrobiologė, savo darbe jungianti fundamentines žinias su praktiniais biotechnologiniais sprendimais. Kurse „Pramoninė mikrobiologija“ ji atskleidžia, kaip mikroorganizmai tampa svarbia šiuolaikinės pramonės ir inovacijų dalimi – nuo fermentacijos procesų iki pažangių bioproduktų kūrimo.\n\nŠio dalyko metu nagrinėjama, kaip mikroorganizmų metabolizmas pritaikomas kuriant antibiotikus, fermentus, biopolimerus ar kitus didelės vertės produktus. Taip pat aptariamos kamienų gerinimo strategijos, baltymų inžinerija, mikroorganizmų taikymas nano- ir geotechnologijose bei jų vaidmuo tvarioje, aplinkai draugiškoje pramonėje. Daug dėmesio skiriama naujausiems moksliniams tyrimams ir jų kritinei analizei.\n\nKursas skirtas studentams, kurie domisi biotechnologijų taikymu praktikoje, planuoja karjerą mokslo ar pramonės srityse ir nori gebėti kurti bei vertinti inovatyvius sprendimus. Ypač tinka tiems, kurie jau turi mikrobiologijos, biochemijos ar genų inžinerijos pagrindus.\n\nVertinimą sudaro:\n•\tDvi tarpinės apklausos (po 35 %, iš viso 70 % galutinio balo). Būtina teigiamai išlaikyti abi.\n•\tSeminarų pristatymas (20 %) – 15–20 min. trukmės aktualios pramoninės mikrobiologijos temos ar projekto idėjos pristatymas.\n•\tSeminarų lankymas ir aktyvumas (10 %) – lankymas privalomas (leidžiami 2 praleidimai), vertinamas aktyvus dalyvavimas diskusijose.\nJeigu tarpinių apklausų rezultatai netenkina, laikomas egzaminas iš visų kurso temų. Galutinis balas formuojamas tik surinkus teigiamus įvertinimus ir pasiekus nustatytą minimalų slenkstį."
          }
        ]
      },
      {
        "name": "Grybų, augalų ir dumblių biotechnologijos",
        "order": 3,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. R. Išėnaitė",
            "comment": "Dr. R. Išėnaitė dirba Gamtos tyrimų centro Mikologijos laboratorijoje ir tyrinėja makromicetus – jų taksonomiją, ekologiją, paplitimą bei panaudojimą. Dėstydama ji siekia ne tik perteikti žinias, bet ir skatinti studentus kritiškai mąstyti, diskutuoti ir gilintis į praktinius klausimus. Jos kursas supažindina su pagrindiniais grybų biotechnologijos principais ir platesnėmis jų taikymo galimybėmis.\n\nŽmonės grybus naudoja nuo seniausių laikų – tiek maistui, tiek medicinai. Iš jų gaunami produktai, pavyzdžiui, antibiotikai, iš esmės pakeitė mediciną. Grybų fermentacijos produktai mus supa kasdienėje aplinkoje, nors dažnai to net nepastebime. Šiandien vis labiau domimasi jų panaudojimu kaip funkciniu maistu, žemės ūkyje kenkėjų kontrolei, tvarių statybinių medžiagų kūrimui ir sudėtingų polimerinių junginių skaidymui.\n\nStudijos pradedamos nuo pagrindinių grybų biologijos ir fiziologijos aspektų, vėliau pereinama prie jų išteklių – valgomųjų rūšių, toksinų, pigmentų ir kitų metabolitų. Aptariami endofitiniai grybai kaip biologiškai aktyvių medžiagų šaltinis, mikoriziniai grybai – kaip dirvožemio gerinimo priemonė, o saprotrofiniai – kaip sudėtingų medžiagų skaidytojai ir biovalymo įrankiai. Taip pat nagrinėjamas grybų vaidmuo kaip bioindikatorių.\n\nKursas skirtas studentams, turintiems pagrindines mikologijos žinias ir norintiems suprasti praktinį grybų pritaikymą. Vertinimas apima seminaro pristatymą pasirinkta tema ir koliokviumą."
          }
        ]
      },
      {
        "name": "Bioenergetika",
        "order": 4,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. G. Valiulienė",
            "comment": "Esu dr. G. Valiulienė, ir, kaip ir dauguma, nemėgstu nuobodžių ar neįkvepiančių paskaitų. Todėl su studentais stengiuosi kalbėti apie tai, kas mane pačią domina ir žavi – naujausius ląstelės energetikos tyrimus, temas, turinčias praktinę reikšmę ir ryšį su kasdieniu gyvenimu. Mano tikslas – ne tik perteikti žinias, bet ir paskatinti studentus savarankiškai gilintis į juos dominančius klausimus.\n\nBioenergetika yra esminė gyvybės dalis – be energijos srautų ir virsmų gyvybė apskritai negalėtų egzistuoti. Šiame kurse siekiu parodyti, kaip energetiniai procesai susiję su gyvybės atsiradimu ir jos veikimu, bei padėti suprasti šių procesų svarbą platesniame kontekste.\n\nKursas labiausiai tiks smalsiems studentams, besižavintiems gyvybės sudėtingumu ir norintiems suprasti, kaip ji veikia molekuliniu lygmeniu.\n\nPaskaitų ir seminarų metu nagrinėjame bioenergetinę evoliuciją, gyvybės atsiradimo prielaidas, biologinių sistemų termodinamiką, oksidacijos–redukcijos reakcijas. Aptariame mitochondrijas, jų funkcijas ir reguliavimo mechanizmus, susipažįstame su tyrimų metodais, tokiais kaip Seahorse analizė. Taip pat kalbame apie taikomąsias temas – psichoaktyvių medžiagų poveikį ląstelės energetikai, mitochondrijų transplantaciją ir kt.\n\nVertinimas yra kaupiamasis: jį sudaro žodinis pranešimas, mažasis mokslinis projektas, testinis atsiskaitymas ir seminarų užduočių įvertinimas."
          }
        ]
      }
    ]
  },
  {
    "tenant_alias": "gmc",
    "name": "Medicininė kryptis",
    "order": 2,
    "courses": [
      {
        "name": "Virusologija",
        "order": 1,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "???",
            "comment": "Virusologija – tai pasirenkamasis teorinis kursas, suteikiantis išsamų supratimą apie virusus: jų sandarą, dauginimąsi, evoliuciją ir vaidmenį tiek gyvybės moksluose, tiek visuomenės sveikatoje. Studijos grindžiamos naujausia moksline literatūra, skatinamas kritinis mąstymas ir gebėjimas diskutuoti aktualiomis temomis.\n\nŠis dalykas padeda suprasti, kaip virusai sąveikauja su organizmais, kaip plinta ligos ir kaip veikia vakcinos bei antivirusiniai preparatai. Jis ypač svarbus tiems, kurie nori geriau orientuotis šiuolaikinėse sveikatos, biomedicinos ar biotechnologijų problemose ir gebėti vertinti mokslinę informaciją.\n\nKursas tiks studentams, besidomintiems virusų biologija, planuojantiems karjerą gyvybės mokslų srityse arba norintiems gilinti analitinius įgūdžius ir suprasti epidemijų, vakcinų bei gydymo principus.\n\nStudijų metu nagrinėjama virusologijos raida, virusų klasifikacija ir evoliucija, jų sandara, patekimo į ląstelę ir dauginimosi mechanizmai, skirtingos virusų grupės (gyvūnų, augalų, bakteriofagai), jų poveikis ekosistemoms, taip pat virusinės ligos, patogenezė, vakcinos ir antivirusiniai vaistai. Aptariami ir subvirusiniai veiksniai, tokie kaip prionai ar viroidai.\n\nVertinimas yra kaupiamasis: semestro metu laikomi trys koliokviumai, taip pat rengiamas pranešimas pasirinkta tema, pristatomas seminare ir aptariamas diskusijose. Galutinis pažymys sudaromas iš visų šių dalių, ypatingą dėmesį skiriant savarankiškam darbui ir gebėjimui argumentuotai analizuoti informaciją."
          }
        ]
      },
      {
        "name": "Patogeninių mikroorganizmų biologija",
        "order": 2,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. R. Baranauskienė",
            "comment": "Dr. R. Baranauskienė dėsto kursą „Patogeninių mikroorganizmų biologija“, kuris priklauso medicininės mikrobiologijos sričiai ir nagrinėja, kaip mikroorganizmai sąveikauja su žmogaus organizmu infekcijų metu. Kurse derinamos mikrobiologijos ir imunologijos žinios su klinikiniu požiūriu, siekiant suprasti, kaip vystosi infekciniai procesai ir kaip organizmas į juos reaguoja.\n\nStudijų metu aiškinamasi, kaip veikia patogenai, kokias strategijas jie naudoja, kad sukeltų ligą, kaip vystosi atsparumas antibiotikams ir kaip organizmo imuninė sistema reaguoja į infekciją. Taip pat daug dėmesio skiriama laboratorinei diagnostikai – kaip nustatomi sukėlėjai ir vertinamas imuninės sistemos atsakas.\n\nŠis dalykas ypač aktualus, nes infekcinės ligos išlieka svarbia visuomenės sveikatos problema, o naujų patogenų atsiradimas ir antibiotikų atsparumas kelia vis daugiau iššūkių. Kursas skirtas studentams, turintiems mikrobiologijos ir imunologijos pagrindus ir norintiems gilinti žinias medicininės mikrobiologijos srityje.\n\nTemos apima patogenų molekulinę biologiją ir evoliuciją, bakterijų patogenezę, virulentiškumo veiksnius, atsparumo antibiotikams mechanizmus, mikroorganizmų ir žmogaus sąveiką, imuninį atsaką bei laboratorinės diagnostikos principus.\n\nVertinimas sudarytas iš dviejų koliokviumų (70 % galutinio balo) ir seminarų, kuriuose analizuojami klinikiniai atvejai (30 %; lankymas privalomas). Neišlaikius koliokviumų, laikomas egzaminas sesijos metu."
          }
        ]
      },
      {
        "name": "Imunologija",
        "order": 3,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Žmogaus ir gyvūnų fiziologija",
        "order": 4,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. (HP) O. Rukšėnas",
            "comment": "Dr. (HP) O. Rukšėnas šį kursą dėsto jau daugiau nei 30 metų, tačiau jis vis dar išlieka gyvas ir nuolat atsinaujinantis, nes mokslas sparčiai juda į priekį. Kursas yra platus ir dinamiškas – apima daug medžiagos, bet kartu leidžia pamatyti, kaip naujos technologijos atveria vis daugiau galimybių suprasti žmogaus organizmą. Jis taip pat turi aiškų praktinį aspektą, nes padeda geriau suvokti savo kūno veikimo principus.\n\nŠis dalykas leidžia sujungti žinias iš skirtingų sričių – biochemijos, genetikos, molekulinės biologijos ir anatomijos – į vieną visumą ir geriau suprasti, kaip veikia žmogaus organizmas. Kursas ypač vertingas tiems, kurie nori sąmoningiau rūpintis savo sveikata ir suprasti vykstančius procesus.\n\nJis labiausiai tiks smalsiems ir atkakliems studentams, pasiruošusiems dirbti su dideliu informacijos kiekiu ir siekiantiems giliau suprasti biologinius procesus.\n\nKurso metu nagrinėjamos pagrindinės žmogaus organizmo sistemos: nervų, jutimo, širdies ir kraujagyslių, raumenų, endokrininė, kvėpavimo, virškinimo, šalinimo ir lytinė.\n\nPriklauso nuo pasirinkimo tipo, nes galima rinktis su ir be laboratorinių darbų, bet abiem atvejais galioja kaupiamasis balas:\n•\tSu laboratoriniais darbais: reikia atlikti ir apginti laboratorinius darbus (už tai daugiausia 2 balai), išlaikyti 4 koliokviumus (už tai daugiausia 8 balai), jei koliokviumų vidurkis 5 ir daugiau - egzamino galima nelaikyti, jei vidurkis mažiau nei 5 arba daugiau, bet netenkina studento (-ės) - laikomas egzaminas - raštu, 3 atviri klausimai\n•\tBe laboratorinių darbų: išlaikyti 4 koliokviumus, jei koliokviumų vidurkis 5 ir daugiau - egzamino galima nelaikyti, jei vidurkis mažiau nei 5 arba daugiau, bet netenkina studento (-ės) - laikomas egzaminas - raštu, 3 atviri klausimai"
          }
        ]
      }
    ]
  },
  {
    "tenant_alias": "gmc",
    "name": "Biologinė kryptis (augalai)",
    "order": 3,
    "courses": [
      {
        "name": "Fitopatologija",
        "order": 1,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. A. Kačergius",
            "comment": "Augalų patologijos srityje dirbu jau daugiau nei 30 metų – nuo studijų laikų Vilniaus universitete iki doktorantūros ir stažuočių užsienyje, kur susipažinau su moderniais molekuliniais tyrimo metodais ir dalyvavau tarptautiniuose projektuose. Per šį laiką sukaupiau nemažai mokslinės ir praktinės patirties, kuria siekiu pasidalinti su studentais, kurie ateityje galėtų tęsti darbus šioje srityje. Mane visada domino gyvoji gamta, o ypač organizmų tarpusavio santykiai – augalų, mikroorganizmų ir aplinkos sąveika.\n\nAugalai yra gyvybiškai svarbūs žmogui – jie aprūpina deguonimi, maistu, žaliavomis ir net vaistais. Tačiau jie taip pat serga, o ligos gali sukelti didžiulius nuostolius tiek žemės ūkyje, tiek natūraliose ekosistemose. Todėl svarbu suprasti ligų kilmę, patogenų biologiją ir plitimo dėsningumus, kad būtų galima kurti veiksmingas apsaugos priemones. Būtent šiam tikslui skirtas šis kursas, kuris apjungia anksčiau fragmentiškai dėstytas žinias į vieną sistemingą dalyką.\n\nKursas tiks smalsiems studentams, besidomintiems gyvąja gamta, ypač augalais, ir tiems, kurie planuoja dirbti biologijos ar su ja susijusiose srityse.\n\nStudijų metu nagrinėjamos pagrindinės temos: augalų ligų tipai ir simptomai, infekcinės ir neinfekcinės ligos, patogeninių mikroorganizmų savybės, ligų plitimo dinamika, augalų atsparumas, taip pat ligų prognozavimo ir kontrolės metodai.\n\nVertinimas apima du tarpinius atsiskaitymus, baigiamąjį egzaminą ir aktyvų dalyvavimą seminaruose, kuriuose skatinamas diskusinis mąstymas ir nagrinėjami naujausi moksliniai tyrimai."
          }
        ]
      },
      {
        "name": "Aplinkos bioįvairovė",
        "order": 2,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Augalų ir grybų ekologija",
        "order": 3,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. (HP) E. Kutorga",
            "comment": "Esame Vilniaus universiteto Gyvybės mokslų centro mokslininkai – Ernestas Kutorga ir Sigitas Juzėnas, savo veiklą siejantys su augalų ir grybų įvairovės bei ekologijos tyrimais. Sigitas daugiausia dirba su augalų populiacijų ekologija ir jų dinamika, o Ernestas – su grybų biologine įvairove ir jų nykimo priežastimis. Ši patirčių sintezė leidžia kurso metu ne tik perteikti žinias, bet ir ugdyti analitinį požiūrį į gyvosios gamtos procesus.\n\nKurse „Augalų ir grybų ekologija“ siekiame parodyti, kaip šie organizmai veikia ekosistemose – kaip jie reguliuoja energijos srautus, reaguoja į aplinkos pokyčius, palaiko sudėtingus tarpusavio ryšius ir lemia buveinių stabilumą. Tai nėra vien rūšių pažinimas – tai bandymas suprasti procesus nuo individo lygmens iki globalių ekologinių pokyčių.\n\nŠis dalykas svarbus, nes augalai ir grybai yra esminės grandys ekosistemose: augalai kuria pirminę produkciją, o grybai užtikrina medžiagų apykaitą ir simbiozinius ryšius. Jų ekologijos supratimas leidžia priimti pagrįstus sprendimus gamtosaugoje, žemės ūkyje ir aplinkos valdyme.\n\nKursas skirtas studentams, kurie nori gilintis ne tik į rūšių atpažinimą, bet ir į ekologinius dėsningumus, planuoja dirbti biomokslų srityje ir siekia ugdyti kritinį bei analitinį mąstymą.\n\nStudijos susideda iš dviejų dalių. Augalų ekologijoje nagrinėjami streso ir prisitaikymo mechanizmai, populiacijų dinamika, nišų teorija, bendrijų kaita ir bioindikacija. Grybų ekologijoje aptariamos jų funkcijos ekosistemose, gyvenimo strategijos, simbiozės, plitimas, klimato kaitos poveikis bei apsauga.\n\nStudijų procesas remiasi kaupiamojo balo sistema, skatinančia nuolatinį įsitraukimą: 40 % galutinio balo sudaro studento (-ės) pasiekimai dviejuose seminaruose bei 60 % galutinio balo sudaro dviejų kolokviumų rezultatai."
          }
        ]
      },
      {
        "name": "Populiacijų ekologija",
        "order": 4,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "gmc",
    "name": "Genetikos sritis",
    "order": 4,
    "courses": [
      {
        "name": "Populiacijų ir ekologinė genetika",
        "order": 1,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. J. Butkuvienė",
            "comment": "Save apibūdinčiau kaip pakankamai reiklią dėstytoją, kuriai svarbiausia ne faktų iškalimas ar atkartojimas, o gebėjimas suprasti situacijas, jas atpažinti ir įvertinti natūraliai gamtinėse populiacijose vykstančius procesus. Labai vertinu discipliną ir akademinį smalsumą.\n\nMano dėstomas dalykas – tai populiacijų ir ekologinės genetikos evoliucinių procesų analizė. Didžiąja dalimi kursas orientuotas į augalus ir gyvūnus, jų populiacijas ir jose vykstančius procesus; kurse nemaža dalis yra skiriama skaičiavimams ir modeliams.\n\nPopuliacijų genetika yra labai svarbi disciplina siekiant kokybiško biologinio išsilavinimo. Šis dalykas yra kritinis, nes leidžia suprasti, kaip natūralioji atranka, genų dreifas ir mutacijos keičia individus ir populiacijas. Be genetinės analizės neįmanoma išsaugoti retų ar nykstančių rūšių (pvz., vertinant inbrydingo riziką). Taikomoji populiacijų ir ekologinė genetika apima sritis nuo žemės ūkio (veislių kūrimas) iki medicinos (atsparumo antibiotikams plitimas ar ligų genetinė epidemiologija).\n\nŠį dalyką turėtų pasirinkti studentai (-ės), kurie (-ios) nori suprasti natūraliose gamtinėse populiacijose vykstančius genetinius procesus. Kuris (-i) mėgsta analizuoti konkrečius atvejus, nebijo skaičiavimų ir loginių modelių (nereikia būti matematikos genijumi, bet loginis mąstymas – pageidautinas). Kuris (-i) domisi evoliucija, ekologija ir nori pamatyti, kaip molekuliniai metodai pritaikomi tiriant laukinę gamtą ir joje egzistuojančias populiacijas.\n\nVertinimo sistema yra kompleksinė: Kurso metu studentai (-ės) rašo tris koliokviumus iš pagrindinių temų, kur kiekvieno balas sudaro 25 proc. kaupiamojo balo. Likusius 25 procentus studentas (-ė) gauna pristatęs pranešimą pasirinkta tema seminarų metu. Seminarų lankymas yra privalomas. Jeigu sukauptas pažymys netenkina, tuomet studentas (-ė) ateina į egzaminą."
          }
        ]
      },
      {
        "name": "Genetikos pagrindai",
        "order": 2,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. M. Babonaitė",
            "comment": "Esame trijų dėstytojų komanda – dr. Milda Babonaitė, dokt. Vėjūnė Pukenytė ir dokt. Vilius Mensonas. Genetiką pristatome iš skirtingų perspektyvų – nuo klasikinių Mendelio dėsnių iki šiuolaikinės molekulinės ir populiacijų genetikos. Mūsų tikslas nėra vien perteikti sąvokas – siekiame, kad studentai išmoktų mąstyti „genetiškai“: kelti hipotezes, spręsti uždavinius, analizuoti schemas ir kritiškai vertinti informaciją.\n\nKursas „Genetikos pagrindai“ suteikia esminį supratimą apie tai, kaip genetinė informacija yra užkoduota DNR, kaip ji realizuojama organizme ir kaip yra paveldima bei kinta. Tai vienas svarbiausių bazinių kursų, padedantis suprasti biologiją nuo molekulinio iki organizminio ir populiacinio lygmens.\n\nŠis dalykas skirtas studentams, norintiems tvirto pagrindo tolimesnėms gyvybės mokslų studijoms, taip pat tiems, kurie nori suprasti ryšį tarp genotipo ir fenotipo. Jis tiks ir tiems, kurie jaučia, kad genetika sudėtinga – kursas padeda viską išmokti nuosekliai, su daug praktikos ir pavyzdžių.\n\nAtsiskaitymą sudaro:\n• Tarpiniai koliokviumai (po vieną iš kiekvieno dėstytojo dalies),\n• Atsiskaitymas už pratybas / laboratorinius darbus,\n• Egzaminas sesijos metu.\n\nMinimalūs reikalavimai:\n• Galutinis kurso įvertinimas - kaupiamasis. Jeigu išlaikote visus tris tarpinius atsiskaitymus (surenkama bent 50 % bendro balo), į egzaminą sesijos metu eiti nereikia.\n• Kad kursas būtų išlaikytas privaloma lankyti ir teigiamu įvertinimu atsiskaityti už pratybas / laboratorinius darbus."
          }
        ]
      },
      {
        "name": "Vystymosi biologija",
        "order": 3,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Molekulinė evoliucija",
        "order": 4,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. J. Turčinavičienė",
            "comment": "Dėstau tik dalį šio dalyko, be manęs dar dėsto dr. Gytis Dudas ir dr. Remigijus Skirgaila. Biologinę evoliucija sudaro dvi didelės dalys: tai mikroevoliucija, kuri tiria kaip vyksta evoliucija čia ir dabar. Kita dalis - makroevoliucija, tai istorinis procesas  laiko skalėje, kurios rezultatas yra gyvybės medis, sujungiantis visus gyvus organimus Žemėje.\n\nEvoliucijos mokslas skiriasi nuo kitų dalykų, nes jis aiškina, kodėl gyvybė yra būtent tokia. Dauguma gyvybės mokslų tiria organizmų struktūrą ir veikimo mechanizmus, pritaiko fizikos ar chemijos mokslų dėsningumus, o evoliucijos teorija gali atsakyti, kodėl organizmai yra tokie, kodėl jie nėra tobuli arba kodėl jie nuostabiai prisitaikę.  Sakyčiau, kad šį kursą turėtų išklausyti visi biologijos ar molekulinės biologijos krypties studentai (-ės), nes ši teorija sujungia, apibendrina ir pritaiko  anksčiau įgytas žinias.\n\nKuriam (-iai) įdomu, kodėl pasaulis yra toks, kodėl organizmai nėra tobuli, kodėl yra tokia  didelė biologinė įvairovė.\n\nTrumpai - tai nuo evoliucinių kitimų populiacijose, organizmų filogenijos iki labai pritaikomų dalykų, tokių kaip baltymų evoliucijos in vitro.\n\nTrumpai - dalis pažymio priklauso nuo darbo seminarų metu, kita dalis - kiekvienos dalies teorinės medžiagos atsiskaitymai."
          }
        ]
      },
      {
        "name": "Žmogaus genetika",
        "order": 5,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. E. Siavrienė",
            "comment": "Esu žmogaus genetikos ir genomikos srityje dirbanti dėstytoja ir mokslininkė, aktyviai dalyvaujanti moksliniuose tyrimuose bei klinikinės genetikos tematikose. Savo dėstomą dalyką apibūdinčiau kaip šiuolaikišką, integruojantį fundamentines genetikos žinias su praktiniais medicininės genomikos aspektais. Paskaitose siekiu ne tik perteikti teorines žinias, bet ir ugdyti studentų gebėjimą kritiškai vertinti genetinius duomenis, suprasti diagnostikos principus bei aktualias bioetikos problemas.\n\nDalyko tikslas – suteikti pagrindines teorines žinias apie žmogaus genomo sandarą ir funkcionavimą, šiuolaikines žmogaus genomikos tyrimų kryptis bei taikomus tyrimo metodus. Taip pat nagrinėjama žmogaus genomikos reikšmė medicininėje praktikoje ir aktualios šios srities problemos. Žmogaus genetika šiandien yra viena sparčiausiai besivystančių biomedicinos sričių. Genominiai tyrimai tampa neatsiejama diagnostikos, ligų prevencijos ir individualizuotos medicinos dalimi. Šis dalykas padeda studentams (-ėms) gerai orientuotis žmogaus genetikos mokslo ir diagnostikos naujovėse, suprasti genetinių tyrimų interpretavimo principus bei sėkmingai pritaikyti teorines žinias praktikoje ir mokslo tiriamuosiuose darbuose.\n\nDalyką turėtų rinktis studentai (-ės), kurie (-ios) savo ateitį sieja su žmogaus genetikos kryptimi, svarsto apie medicininės genetikos magistro studijas ar planuoja karjerą biomedicinos, molekulinės diagnostikos ar mokslinių tyrimų srityse. Taip pat dalykas skirtas tiems, kurie domisi žmogaus genetika ir siekia pagilinti savo žinias apie genomo tyrimus, paveldėjimo mechanizmus bei genetinių ligų pagrindus.\n\nAtsiskaitymas yra kaupiamasis:\n• 35 % – pirmasis kolokviumas\n• 35 % – antrasis kolokviumas\n• 30 % – seminarų vertinimas"
          }
        ]
      }
    ]
  },
  {
    "tenant_alias": "gmc",
    "name": "Molekulinė biologija",
    "order": 5,
    "courses": [
      {
        "name": "Baltymų sąveikos su ligandais termodinamika",
        "order": 1,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. D. Matulis",
            "comment": "Įgijau patirtį studijuodamas doktorantūroje JAV, o taip pat dirbdamas Johnson & Johnson bendrovėje ties vaistų kūrimu. Kurse perteikiu sukauptas žinias apie baltymus, kurie atlieka daugumą funkcijų mūsų kūne, kaip baltymai sąveikauja tarpusavyje ir su mažomis molekulėmis, kaip vaistai, bei kaip jie vyniojasi, koks baltymų stabilumas, ką atlieka vanduo šiuose molekuliniuose procesuose.\n\nDalykas yra vienas kertinių molekulinių biochemijos-biofiziko mokslų. Sąveikų struktūrų bei energijų supratimas įgalina plačiau pažvelgti ir suprasti gyvybės chemiją.\n\nNorėčiau, kad paskaitose studentai (-ės) dalyvautų aktyviai, kad jie (-os) domėtųsi, kad kiekvieną savaitę skaitytų vadovėlį bei aktyviai užduotų klausimus, stengtųsi dalyką gerai suprasti, o ne iškalti.\tPagrindinės temos yra apie baltymus, jų sąvybės, ir svarbiausia, silpnosios sąveikos su kitomis molekulėmis, kaip vaistai. Stengiuosi, kad studentai (-ės) suprastų sudėtingas termodinamikos sąvokas paprastai ir galėtų tomis temomis skaityti mokslinius straipsnius.\n\nAtsiskaitymui pusę balų galima surinkti atliekant visas pratybas ir perskaičius knygos skyrių kiekvieną savaitę atsiskaityti trumpai papasakojant apie diskutuojamą klausimą. Egzaminai yra loginio sprendimo, o ne iškalimo pagrindu, todėl jie yra atvirais asmeniniais ranka rašytais užrašais."
          }
        ]
      },
      {
        "name": "Baltymų išskyrimas ir gryninimas",
        "order": 2,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. N. Urbelienė",
            "comment": "Praktikoje gana dažnu atveju studentai (-ės) susiduria su  baltymų genų raiškos ir gryninimo proceso iššūkiais. Esu sukaupusi nemažai žinių dirbant su fermentais tiek komercinėse įmonėse, tiek mokslinėje laboratorijoje.\n\nTai kursas paremtas daugiausia mano praktikoje gautomis žiniomis, jas pagrindžiant publikuotais moksliniais tyrimais.  Duomenys yra nuolat atnaujinami sekant rinkoje pasirodžius naujus baltymų gryninimui skirtus produktus.\n\nDalyko tikslas – supažindinti studentus su baltymų išskyrimo ir gryninimo metodais, skiriant ypatingą dėmesį sudėtingesnių baltymų gryninimui t.y. susiduriant su baltymų sintezės, tirpumo, teisingo baltymų klostymosi problemomis, sunkumais siekiant gauti pageidaujamo grynumo baltymų preparatus. Paskaitų metu sužinosite kaip susiplanuoti eksperimentą, apie praktikoje baltymų gryninimui naudojamus sorbentus, jų savybes, gaminančias kompanijas ir.t.t\n\nSkirtas studentams (-ėms), kurie (-ios) nori/planuoja ateityje dirbti su fermentais. Reikalingos bazinės žinios apie genų inžineriją ir baltymų chromatografiją. Paskaitų ciklas apima visą baltymo/fermento kelią – pradedant nuo geno raiškos, baltymo biosintezės pasirinktame organizme,  išskyrimo ir gryninimo procesą, bei galiausiai preparato kokybės nustatymą ir saugojimo sąlygų parinkimą.\n\nAtsiskaitymai: 2 tarpinės apklausos (70% bendro balo). Savarankiškas darbas – pristatyti mokslinės publikacijos analizę  pasirinkta tema apie fermento gryninimą (30% bendro balo). Pagal pageidavimą galima laikyti egzaminą."
          }
        ]
      },
      {
        "name": "Kiekybinė fluorescencinė mikroskopija",
        "order": 3,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. M. Tutkus",
            "comment": "Esu doc. dr. Marijonas Tutkus, mokslininkas, kurio pagrindinė tyrimų sritis – fluorescencinės mikroskopijos metodų vystymas ir jų taikymas tiriant biomolekulių struktūrą bei dinamiką pavienių molekulių lygyje. Mano vadovaujama laboratorija („Tutkus Lab“) aktyviai kuria inovatyvias mikroskopijos sistemas, tokias kaip „miEye“ bei įvairius tyrimų metodus (assays)susijusius su optine mikroskopija.\n\nMano dėstomas dalykas „Kiekybinė fluorescencinė mikroskopija“ (angl. Quantitative fluorescence microscopy) yra skirtas ne tik supažindinti su optinės mikroskopijos pagrindais, bet ir suteikti praktinių įgūdžių, kaip iš mikroskopinių vaizdų išgauti tikslius, kiekybinius duomenis apie biologines sistemas. Tai kursas, jungiantis teorines fizikos žinias, praktinį darbą laboratorijoje ir modernią duomenų analize.\n\nŠiuolaikinėje biologijoje neužtenka tik „pamatyti“ vaizdą – būtina gebėti jį išmatuoti ir interpretuoti skaičiais. Šis kursas yra itin svarbus, nes: Suteikia fundamentalias žinias apie difrakcijos ribas, super-rezoliuciją (STORM, PALM) ir pavienių molekulių sekimą. Išmoko studentus (-es) dirbti su sudėtinga įranga, tokia kaip TIRF (visiško vidinio atspindžio) ir konfokaliniai mikroskopai. Supažindina su metodais (pvz., FRET), leidžiančiais stebėti molekulių sąveikas realiu laiku.\n\nDalykas yra pasirenkamasis ir labiausiai tinka biologijos, chemijos, fizikos, nanoinžinerijos bei medicinos krypčių bakalauro studijų studentams. Šį kursą turėtų rinktis studentai (-ės), kurie (-ios):\nDomisi bio-nanotechnologijomis ir nori suprasti, kaip veikia moderniausi vaizdinimo įrankiai.\nNori išmokti praktinio duomenų apdorojimo ir analizės (pvz., intensyvumo integravimo, taškų aptikimo).\nSiekia ateityje vykdyti eksperimentinius tyrimus gyvybės mokslų srityje, kur reikalingas didelis tikslumas.\n\nGalutinį įvertinimą sudaro trys pagrindinės dalys:\n1. Laboratoriniai darbai ir jų gynimas (2,5 balo): Tai apima praktinių užduočių atlikimą, laboratorinių darbų ataskaitas bei jų žodinį gynimą.\n2. Seminarai : Seminarų pristatymai vertinami iki 2,5 balo.\n3. Egzaminas (50 %): Egzaminą sudaro 25 klausimų testas iš teorinės dalies."
          }
        ]
      }
    ]
  },
  {
    "tenant_alias": "if",
    "name": "Be temos",
    "order": 1,
    "courses": [
      {
        "name": "Baltų tyrimai: Baltijos regiono archeologija (Baltų tyrimai: Baltijos regiono archeologija)/Exploring Balts: Archaeology of the Baltic Region",
        "order": 1,
        "semester": "Ruduo/pavasaris",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. K. Minkevičius",
            "comment": "In this course, I introduce students to the archaeology of the Baltic region, covering both prehistoric and historical periods. We explore archaeological heritage, the methods used to study the past, and the ways this knowledge is interpreted and presented today. Through lectures, seminars, and field trips, students learn how material evidence helps us understand past societies and their connections to the wider European context.\n\nThe course highlights the importance of archaeological heritage in both research and contemporary society. It also encourages students to think critically about how knowledge about the past is produced and communicated. Along the way, students develop analytical, communication, and teamwork skills that are valuable in many fields.\n\nThis course is best suited for students interested in history, archaeology, and cultural heritage, especially those curious about the Baltic region. It will also appeal to those who enjoy discussion, fieldwork, and reflecting on how the past is represented in modern society.\n\nTopics include what archaeology is and how it works, the archaeological heritage of the Baltic region, the role of archaeology today, and how it is communicated to the public, including through media.\n\nAssessment consists of an oral presentation (20%), a PowerPoint presentation of a research proposal (20%), and a written essay (60%)."
          }
        ]
      },
      {
        "name": "Totalitariniai režimai (1922-1953): nacizmas ir komunizmas Europoje/Totalitarian regimes (1922-1953): Nazism and Communism in Europe",
        "order": 2,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "A. Miller",
            "comment": "I am A. Miller, a junior researcher at the (Post)Authoritarian Landscapes Research Centre at the Faculty of History. My work focuses on social history, collective memory, and power dynamics under authoritarian regimes. In my course *Totalitarian Regimes (1922–1953): Nazism and Communism in Europe*, I examine the core ideas of totalitarian ideology, how such systems functioned in practice, and the similarities and differences between major regimes of the early 20th century. The course also looks at the broader societal and cultural transformations in Europe during this period.\n\nThe course provides both a theoretical framework for understanding totalitarianism and a factual foundation for analyzing 20th-century history. It helps students critically engage with questions of power, ideology, and the impact of authoritarian systems on individuals and societies.\n\nIt is open to students from various disciplines – from psychology to political science – who are interested in these topics.\n\nWe explore themes such as the relationship between modernity and totalitarianism, the rise of fascism and Nazism, the formation of the “Soviet man,” everyday life under different regimes, propaganda, totalitarian crimes like the Great Terror and the Holocaust, and how these experiences are reflected in literature and cinema. We also discuss the Nuremberg Trials and their complexities.\n\nAssessment is based on active participation (40%), including attendance and engagement in discussions, and two seminar presentations (60%) on selected topics."
          }
        ]
      },
      {
        "name": "Krikščioniškosios vienuolystės istorija",
        "order": 3,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. L. Jovaiša",
            "comment": "Krikščioniškosios vienuolystės istorija“ skirta susipažinti su turtinga krikščioniško vienuolinio gyvenimo įvairove – bendru tokio gyvenimo pagrindu ir ant jo išsiskleidusiomis skirtingomis tradicijomis bei formomis, pirmiausia – „klasikinėmis“ (iki Apšvietos epochos ir moderniojo pasaulio).\n\nŠis dalykas pravartus norint įgyti ‚old-schoolinį‘ išsilavinimą – erudiciją, leidžiančią orientuotis vis platesniame kultūrinio ir dvasinio gyvenimo areale. Pažintis su krikščioniškosios vienuolystės istorija – tai ir savotiškas įvadas į krikščionišką dvasingumą, ir langas į Europos civilizacijos istoriją. Dalykas itin svarbus ir norint pažinti senąjį Vilniaus architektūros ir dailės paveldą – beveik visos senosios miesto bažnyčios yra pastatytos ar bent kurį laiką administruotos įvairių vienuolijų. Tad šį dalyką itin paranku studijuoti ir už auditorijos ribų – Vilniaus senamiestyje ar išvykstant vienos dienos mokomajai kelionei...\n\nBet kas, kuriam tema įdomi. Kurso klausytojai gali būti ir tokie, kuriems artima krikščionybė ir kurie nori pagilinti jau turimas žinias, ir visiškai svetimi krikščionybei, kuriems tokia tema skamba egzotiškai. Turbūt lengviau ir natūraliau kursą klausytų humanitarai ar studijuojantieji socialinius mokslus, tačiau dalykas tikrai neturėtų būti neįkandamas ir gamtos ar tiksliųjų mokslų studentams.\n\n• Kaupiamasis vertinimas (80%)\nTrys tarpiniai atsiskaitymai raštu (už 1, 2 ir 4 modulio temas) ir pristatymas žodžiu (už 3 modulio temą)\n• Tiriamasis rašto darbas (20%)\nStudentai renkasi iš pasiūlytų visose temose."
          }
        ]
      },
      {
        "name": "Nuo imperijų iki tautinių valstybių: pasaulio tvarkos istorija ir idėjos/From Empires to Nation States: History and Ideas of World Order",
        "order": 4,
        "semester": "Ruduo/pavasaris",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. T. Žukas",
            "comment": "I am Dr. T. Žukas. I study international history and work in policy analysis at a think tank, with experience in government. My research focuses on key moments in the development of international relations, and in this course I aim to share that perspective with students.\n\nIn this course, we explore how different societies have understood and attempted to create order in international politics. We ask fundamental questions: what is international order, why conflicts persist, and how ideas and historical events have shaped relations between states—from ancient civilizations to the modern world.\n\nThe course is especially relevant today, as global politics is undergoing significant change. Rather than offering simple answers, it provides a framework for understanding how international order evolves and why it remains fragile.\n\nThis course is suitable for students who are interested in history, curious about political and cultural ideas, and want to understand how societies across time have approached the problem of order in the world.\n\nWe examine topics such as concepts of order in Ancient China and Greece, the emergence of sovereignty after the Peace of Westphalia, the balance of power after the Congress of Vienna, the impact of the First World War, the role of nuclear weapons, and contemporary challenges to global order, from 9/11 to Russia’s war against Ukraine.\n\n• Active participation at the seminars (30%)\n• Essay (70%)"
          }
        ]
      },
      {
        "name": "Lietuva ir kaimynai: istorinė patirtis ir šiuolaikinė savivoka",
        "order": 5,
        "semester": "Ruduo/pavasaris",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. D. Bukelevičiūtė",
            "comment": "Dalyko turinys apima Lietuvos ir jos kaimyninių šalių santykių raidą nuo XIX a. pabaigos iki XX a. pabaigos, nagrinėjant juos platesniame geopolitiniame kontekste. Daugiausia dėmesio skiriama ryšiams su Rusija (SSRS), Vokietija, Lenkija, Latvija, Baltarusija, taip pat aptariamos jungtys su Baltijos šalimis, Skandinavija ir Ukraina. Kursą dėsto Vilniaus universiteto Istorijos fakulteto dėstytojai – doc. dr. Dalia Bukelevičiūtė, dr. Ryšard Gaidis ir doc. dr. Ramojus Kraujelis, turintys ilgametę dėstymo ir tyrimų patirtį.\n\nŠis kursas skirtas studentams, norintiems geriau suprasti, kaip formavosi Lietuvos valstybingumas, kokiame geopolitiniame kontekste jis vystėsi ir kaip kito santykiai su kaimyninėmis šalimis. Analizuojami politiniai, ekonominiai, kultūriniai ir kariniai ryšiai tarp valstybių, jų įtaka visuomenės raidai, taip pat lyginami istoriniai procesai su dabartine situacija po 1990 metų.\n\nKursas tiks tiems, kurie domisi ne tik Lietuvos, bet ir Europos, Rusijos bei Ukrainos istorija, ir nori suvokti, kaip tarptautiniai santykiai veikia valstybių likimus.\n\nStudijų metu nagrinėjamos temos apima modernių tautų formavimąsi, valstybingumo raidą tarpukariu, totalitarinių režimų poveikį Antrojo pasaulinio karo ir stalinizmo laikotarpiu, komunistinio bloko šalių patirtis bei Lietuvos ir kaimyninių valstybių santykius po Šaltojo karo.\n\n• Egzaminas raštu (50%)\nDu  atviri probleminio tipo klausimai.\n• Savarankiškai parengto pranešimo pristatymas seminaro metu (25%)\n• Aktyvus dalyvavimas seminaruose (25%)"
          }
        ]
      },
      {
        "name": "Lietuvos Didžioji Kunigaikštystė ir Europa",
        "order": 6,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. M. Jakulis",
            "comment": "Esu Vilniaus universiteto Istorijos fakulteto docentas, dėstau nuo 2013 m. Mano moksliniai interesai – socialinė Lietuvos Didžiosios Kunigaikštystės ir ypač Vilniaus istorija XVI–XVIII a., taip pat medicinos ir vienuolystės istorija. Užsiimdamas savo moksliniais tyrimais, nuolat neišvengiamai susiduriu su tuo, ką galima pavadinti „europiniu kontekstu“. Jis svarbus tiek siekiant suprasti, kaip tam tikri to meto – visų pirma Vakarų – Europoje įprasti kultūriniai, politiniai, socialiniai ar religiniai fenomenai būdavo „perkeliami“ į Lietuvos Didžiąją Kunigaikštystę, tiek kaip visa tai būdavo perkuriama ir pritaikoma vietiniam (Europos periferijos) kontekstui.\n\nŠiame dalyke ir siekiame atskleisti, kaip mezgėsi Lietuvos Didžiosios Kunigaikštystės ir kitų Europos regionų santykiai pačiose įvairiausiose srityse, pradedant karyba ir baigiant prekyba.\n\nMano galva, dalykas svarbus tuo, kad suteikia galimybę pažvelgti į Lietuvos Didžiosios Kunigaikštystės istoriją platesniame kontekste, suvokti, kokios vis dėlto gilios yra europietiškumo šaknys. Kita vertus, reikšminga tai, kad į istoriją žiūrime iš labai skirtingų perspektyvų – ne tik politinės, bet ir socialinės, materialinės, ekonominės, kultūrinės. Taigi viliamės, kad kursą klausantys studentai susidaro kur kas kompleksiškesnį Lietuvos, o kartu ir Europos istorijos vėlyvaisiais viduramžiais ir ankstyvaisiais naujaisiais laikais vaizdinį.\n\n• Egzaminas raštu (80%) Probleminiai klausimai ir testas.\n• Dalyvavimas seminaruose (20%)"
          }
        ]
      }
    ]
  },
  {
    "tenant_alias": "if",
    "name": "Be temos",
    "order": 2,
    "courses": [
      {
        "name": "Jogailaičių Europa ir Lietuvos Didžioji Kunigaikštystė nuo XV a. pradžios iki XVI a. vidurio/The Jagiellonian Europe and the Grand Duchy of Lithuania from the Early 15th to the Mid-16th Century",
        "order": 1,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. R. Trimonienė",
            "comment": "My research follows the Jagiellonians across Europe – how they ruled, negotiated power, and built connections between medieval Poland, Bohemia, Hungary, and the Grand Duchy of Lithuania.\n\nIn this course, we step into that world: how did medieval unions actually work? Who held power? And how did ideas and people move across borders? Together, we will read sources, compare political systems, and uncover what made Jagiellonian Europe unique.\n\nThis course equips you with key skills: analysing historical problems, working with sources, and clearly communicating your ideas. By the end, you will be able to independently analyse complex political processes and present topics in early modern Central and Eastern European history.\n\nNo prerequisites required.\n\nEvaluation strategy:\n• Active participation in the seminars (30 %)\n• Written assignment and its presentation (15 min.) (20 %)\n• Exam (50 %)"
          }
        ]
      },
      {
        "name": "Nedominuojančių grupių migracija: žmonės, vietos, pasakojimai",
        "order": 2,
        "semester": "Ruduo/pavasaris",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. D. Troskovaitė",
            "comment": "Esu Dovilė Troskovaitė, Vilniaus universiteto Istorijos fakulteto docentė. Kursas „Nedominuojančių grupių migracija: žmonės, vietos, pasakojimai“ gimė iš mano ilgalaikių tyrimų apie karaimus, totorius, žydus ir kitas nedominuojančias bendruomenes. Kartu su kolegėmis dr. Akvile Naudžiūniene ir dr. Dovile Čypaite–Gile išplėtėme šį lauką, įtraukdamos ir romus bei lietuvių diasporas. Kurse siekiame ne tik papasakoti šių bendruomenių istorijas, bet ir kritiškai įvertinti mitus, stereotipus bei egzotizavimo tendencijas.\n\nMigracija yra kasdienybės dalis – ji formuoja visuomenes, santykius ir tapatybes. Todėl svarbu suprasti, kaip ir kodėl atsiranda nedominuojančios grupės, kaip jos sąveikauja su dominuojančia visuomene ir kaip kuriami pasakojimai apie migraciją. Kursas padeda atskirti realius migracijos procesus nuo viešojoje erdvėje paplitusių mitų.\n\nDalykas atviras visų studijų krypčių studentams, nereikalauja išankstinių žinių. Jis ypač tiks tiems, kurie domisi šiuolaikinėmis visuomenėmis, migracija, kultūrine įvairove ar informacijos kūrimu ir nori geriau suprasti, kaip formuojasi kolektyvinės tapatybės.\n\nKurso turinys suskirstytas į tris dalis. Pirmojoje nagrinėjamos viduramžių migracijos ir tokių bendruomenių kaip žydai, totoriai ar karaimai atsiradimas Lietuvoje. Antrojoje aptariama XIX a. pabaigos ir XX a. pradžios migracija bei jos įtaka visuomenių raidai. Trečiojoje analizuojamos šiuolaikinės migracijos formos, pabėgėlių klausimai ir pasakojimai, kuriuos kuria pačios bendruomenės.\n\nVertinimas:\n• Egzaminas (testas ir užduotys (VMA)) (70%)\nTestą sudaro 50 uždarų skirtingo sudėtingumo klausimų ir užduočių, kuriais tikrinamas: su modulio problematika susijusių definicijų ir reiškinių turinio žinojimas, dabartinės ir istorinės nepakantos formų ir jų raiškos išmanymas, reiškinių lyginimas ir/ar jų dinamikos supratimas, mąstymo kritiškumas ir konstruktyvus nepakantos formų identifikavimas ir vertinimas.\n• Darbas seminarų metu (30%)\nSeminarai vyksta srautais. Dalyvavimas seminaruose ir seminarų užduočių atlikimas yra privalomas ir vertinamas balais kiekvieno seminaro metu. Studentas/ė atsiskaito už visus (100 proc.) seminarų, 6  negalintiems juose dalyvauti dėl pateisinamų priežasčių semestro metu yra skiriamas papildomas iš anksto numatytas laikas (semestro pabaigoje, prieš egzaminą) individualiems ar grupiniams atsiskaitymams (priklausomai nuo užduočių neatlikusių studentų/čių skaičiaus). Seminaruose vertinamas įsigilinimas į temą, atliktų užduočių kokybė, motyvuotos nuomonės išsakymas, kritinis ir konstruktyvus aptariamų reiškinių vertinimas, aktyvumas seminare."
          }
        ]
      },
      {
        "name": "Asmeninės patirtys kaip šaltiniai istoriniuose ir antropologiniuose tyrimuose",
        "order": 3,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "Dr. A. Naudžiūnienė",
            "comment": "Kartu su kolegėmis Dovile Čypaite-Gile ir Raminta Jakucevičiene šiame kurse siekiame parodyti, kad istoriją galima tyrinėti ne tik per institucijų ar „didžiųjų“ naratyvų prizmę, bet ir per individualias patirtis. Mums svarbu atkreipti dėmesį į asmeninius pasirinkimus ir jų kuriamus bendruomeninius ryšius, todėl dirbame su įvairiais egodokumentiniais šaltiniais – dienoraščiais, laiškais, memuarais, sakytine istorija, interviu.\n\nKurso idėja kilo iš augančio susidomėjimo tokiais šaltiniais tiek akademiniuose tyrimuose, tiek studentų darbuose. Norint juos tinkamai analizuoti, būtina suprasti jų istorinį kontekstą, skirtingų laikotarpių rašymo ypatumus ir metodologines prieigas. Todėl kurso metu ne tik aptariame teoriją, bet ir praktiškai mokomės analizuoti bei kartais patys rinkti tokius duomenis.\n\nKursas skirtas studentams, besidomintiems XIX–XX a. Lietuvos ir Europos kasdienybės bei kultūros istorija, taip pat tiems, kurie nori kritiškai pažvelgti į tradicinius istorinius pasakojimus. Ypač laukiami aktyvūs studentai, pasirengę diskutuoti, dirbti grupėse ir dalytis savo įžvalgomis.\n\nStudijų metu keliame klausimus apie asmenines patirtis istorinių lūžių metu, moterų pasakojimų vietą istorijoje, fotografijų kaip pasakojimų analizę, sakytinės istorijos patikimumą, interviu rinkimą ir duomenų interpretavimą.\n\nVertinimas grindžiamas tiriamuoju rašto darbu (70 %), kuriame analizuojamas pasirinktas egodokumentinis šaltinis, ir darbu seminaruose (30 %), vertinant užduočių atlikimą, pristatymus bei dalyvavimą diskusijose.\n\nVertinimas:\n• Tiriamasis rašto darbas (70%)\nAsmeninio pasakojimo šaltinio ar šaltinių bloko istorinė analizė.\n• Darbas seminarų metu (30%)\nSeminarams paskirtų užduočių atlikimas, jų pristatymas auditorijoje, įsitraukimas į seminaro metu vedamą diskusiją."
          }
        ]
      },
      {
        "name": "Skurdas ankstyvųjų naujųjų laikų Europos mieste/Poverty in an Early Modern European City",
        "order": 4,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": [
          {
            "lecturer": "R. Miškinytė",
            "comment": "•\tActive participation in the seminars (30%)\n•\tWritten assignment (30%)\n•\tExam (40%)"
          }
        ]
      }
    ]
  },
  {
    "tenant_alias": "mf",
    "name": "Sveikos gyvensenos veiksniai ir ligų prevencija",
    "order": 1,
    "courses": [
      {
        "name": "Darbas ir sveikata",
        "order": 1,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Ergonomika ir universali aplinka",
        "order": 2,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Fizinis aktyvumas ir ligų prevencija",
        "order": 3,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Fiziškai aktyvių asmenų mitybos ypatumai",
        "order": 4,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Seksualine sveikata",
        "order": 5,
        "semester": "Pavasaris Ir Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Mityba ir fizinis aktyvumas",
        "order": 6,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "mf",
    "name": "Psichikos sveikata ir elgesio samprata",
    "order": 2,
    "courses": [
      {
        "name": "Savižudybių psichosocialinės problemos ir prevencijos galimybės",
        "order": 1,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Psichinės ir fizinės sveikatos vienovė",
        "order": 2,
        "semester": "Pavasaris Ir Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Psichoaktyviosios medžiagos: poveikis, priklausomybė ir prevencija",
        "order": 3,
        "semester": "Pavasaris Ir Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Su sveikata susijusio elgesio psichologija",
        "order": 4,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Tranzicijos ir psichikos sveikatos raštingumas",
        "order": 5,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Emocinio intelekto lavinimas",
        "order": 6,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "mf",
    "name": "Pacientų priežiūros ir sentstančios visuomenės ypatumai",
    "order": 3,
    "courses": [
      {
        "name": "Operacinės slaugos pagrindai",
        "order": 1,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Slaugos teorijos ir modeliai",
        "order": 2,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Inovacijos gerontologijoje ir geriatrinėje slaugoje",
        "order": 3,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Sveiko senėjimo ir ilgaamžiškumo molekulinis pagrindas",
        "order": 4,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "mf",
    "name": "Vaikų sveikata ir socialiniai iššūkiai",
    "order": 4,
    "courses": [
      {
        "name": "Vaikų sveikatai palanki aplinka",
        "order": 1,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Žaidimo reikšmė vaiko gyvenime",
        "order": 2,
        "semester": "Pavasaris ir Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Ką apie vakcinas turime žinoti kiekvienas",
        "order": 3,
        "semester": "Pavasaris ir Ruduo",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "mf",
    "name": "Sveikatos politika ir socialiniai iššūkiai",
    "order": 5,
    "courses": [
      {
        "name": "Įvadas į medicinos humanitarinius mokslus",
        "order": 1,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Ką apie vakcinas turime žinoti kiekvienas",
        "order": 2,
        "semester": "Pavasaris ir ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Lyderystė ir bendradarbiavimas sveikatos sistemoje: Efektyvių tarpdisciplininių komandų stiprinimas",
        "order": 3,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Sveikatos politika",
        "order": 4,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Elgesio keitimas ir elgsenos ekonomika",
        "order": 5,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Šiuolaikiniai sveikatos iššūkiai – nuo užkrečiamųjų ligų iki narkotikų",
        "order": 6,
        "semester": "Pavasaris Ir Ruduo",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "mif",
    "name": "Valdymo pagrindai IT sferoje",
    "order": 1,
    "courses": [
      {
        "name": "Projektų valdymas",
        "order": 1,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Lyderystė",
        "order": 2,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "IT paslaugų vadyba",
        "order": 3,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Rinkodara informatikams",
        "order": 4,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "mif",
    "name": "Kibernetinės saugos pagrindai",
    "order": 2,
    "courses": [
      {
        "name": "Informatikos teisė",
        "order": 1,
        "semester": "Ruduo, Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Tinklų saugumas",
        "order": 2,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Kriptografija ir informacijos sauga",
        "order": 3,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Kibernetinis saugumas ir duomenų apsauga (Kauno fakultete)",
        "order": 4,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "mif",
    "name": "Kompiuterinė grafika",
    "order": 3,
    "courses": [
      {
        "name": "Kompiuterinės grafikos algoritmai ir technologijos",
        "order": 1,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Audiovizualinės technologijos ŠA",
        "order": 2,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Dvimatė kompiuterinė grafika ir animacija ŠA",
        "order": 3,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Trimatė kompiuterinė grafika ir animacija ŠA",
        "order": 4,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "mif",
    "name": "Programavimo tipai",
    "order": 4,
    "courses": [
      {
        "name": "Loginis programavimas",
        "order": 1,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Procedūrinis programavimas",
        "order": 2,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Lygiagretusis programavimas",
        "order": 3,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Funkcinis programavimas",
        "order": 4,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Objektinis programavimas C++",
        "order": 5,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Tipais grįstas programavimas",
        "order": 6,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "mif",
    "name": "Dirbtinio intelekto pagrindai",
    "order": 5,
    "courses": [
      {
        "name": "Dirbtinio intelekto pagrindai",
        "order": 1,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Mašininis mokymasis mokslui ir inžinerijos studijoms",
        "order": 2,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Giliojo mokymosi metodai",
        "order": 3,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Didieji kalbų modeliai",
        "order": 4,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "mif",
    "name": "Tvarumas",
    "order": 6,
    "courses": [
      {
        "name": "Klimato ir ekosferos kaita CHGF",
        "order": 1,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Darnus vystymasis VM",
        "order": 2,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "ES žaliasis kursas ir tvarumas TF",
        "order": 3,
        "semester": "Pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Žmogaus ekologija GMC",
        "order": 4,
        "semester": "Ruduo",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "tf",
    "name": "Konstitucija, Konstitucinės teisės ypatybės.",
    "order": 1,
    "courses": [
      {
        "name": "Konstitucinė priežiūra",
        "order": 1,
        "semester": "rudens",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Lietuvos konstitucionalizmo istorija",
        "order": 2,
        "semester": "rudens",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Lyginamoji konstitucinė teisė",
        "order": 3,
        "semester": "pavasaris",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "tf",
    "name": "Žmogaus teisių svarba, jų apsauga",
    "order": 2,
    "courses": [
      {
        "name": "Žmogaus teisės Europoje",
        "order": 1,
        "semester": "rudens",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Įvairovė ir įtrauktis:iššūkiai ir sprendimai",
        "order": 2,
        "semester": "rudens",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Tarptautinė taika, saugumas ir žmogaus teisių apsauga",
        "order": 3,
        "semester": "pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Žmogaus teisės ir darni plėtra",
        "order": 4,
        "semester": "pavasaris",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "tf",
    "name": "Institucijos teisėje",
    "order": 3,
    "courses": [
      {
        "name": "Diplomatinė ir konsulinė teisė",
        "order": 1,
        "semester": "ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Teisinių institucijų veiklos ir jos teisinio reguliavimo problemos",
        "order": 2,
        "semester": "ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Teisė ir skaitmeninės technologijos",
        "order": 3,
        "semester": "pavasaris",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "tf",
    "name": "Tarptautinė teisės ypatybės",
    "order": 4,
    "courses": [
      {
        "name": "Šiuolaikinių hibridinių konfliktų teisiniai iššūkiai",
        "order": 1,
        "semester": "rudens",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Tarptautinė viešoji teisė",
        "order": 2,
        "semester": "rudens",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Tarptautinių santykių istorija",
        "order": 3,
        "semester": "pavasario",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Tarptautinė taika, saugumas ir žmogaus teisių apsauga",
        "order": 4,
        "semester": "pavasario",
        "credits": 5,
        "reviews": []
      }
    ]
  },
  {
    "tenant_alias": "tf",
    "name": "Argumentavimas teisėje",
    "order": 5,
    "courses": [
      {
        "name": "retorika ir teisinė kalba",
        "order": 1,
        "semester": "pavasaris",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "Teisinis argumentavimas teismo procese",
        "order": 2,
        "semester": "ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "tarptautinių ginčų sprendimas: teismo (arbitražo) proceso inscenizavimas",
        "order": 3,
        "semester": "ruduo",
        "credits": 5,
        "reviews": []
      },
      {
        "name": "teisinės gynybos priemonės pagal ES teisę",
        "order": 4,
        "semester": "pavasaris",
        "credits": 5,
        "reviews": []
      }
    ]
  }
]
JSON;
}
