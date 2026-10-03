---
doc_status: reviewed
title: Pareigybės
area: duties
models: [Duty, Role, Tenant, Type, Institution, DutyResponsibility]
last_reviewed: 2026-10-03
tests:
  - tests/Feature/Duties/UpdateDutyTest.php
  - tests/Feature/Duties/DutyAssignmentServiceTest.php
  - tests/Feature/Admin/Management/DutyControllerTest.php
  - tests/Feature/Admin/DutyMergeTest.php
  - tests/Feature/Admin/People/DutyForceDeleteTest.php
  - tests/Feature/Admin/People/DutyResponsibilityControllerTest.php
  - tests/Feature/Permissions/DutySelfLockoutTest.php
  - tests/Feature/CrossTenantDutyTest.php
  - tests/Feature/Permissions/ScheduledDutyAuthorizationTest.php
  - tests/Feature/Api/Admin/DutyApiControllerTest.php
  - tests/Feature/Api/Admin/DutySearchApiControllerTest.php
  - tests/Unit/Services/DutyNameNormalizerTest.php
  - tests/Browser/DutyResponsibilitiesLayoutTest.php
  - resources/js/Pages/Admin/People/__tests__/ShowDuty.component.test.ts
  - resources/js/Pages/Admin/People/__tests__/ShowUser.component.test.ts
  - resources/js/Components/Duties/__tests__/DutyCurrentHoldersCard.component.test.ts
  - resources/js/Components/Duties/__tests__/DutyHolderCard.component.test.ts
  - resources/js/Components/Duties/__tests__/DutyLabel.component.test.ts
  - resources/js/Components/Duties/__tests__/DutyLineageCard.component.test.ts
  - resources/js/Components/Duties/__tests__/DutyOtherDutiesCard.component.test.ts
  - resources/js/Components/Duties/__tests__/DutySummaryCard.component.test.ts
  - resources/js/Components/Duties/__tests__/InflectedDutyName.component.test.ts
  - resources/js/Components/AdminForms/__tests__/DuplicateDutyWarning.component.test.ts
  - resources/js/Components/AdminForms/__tests__/DutyForm.component.test.ts
  - resources/js/Features/Admin/Responsibilities/__tests__/DutyResponsibilitiesSection.component.test.ts
  - resources/js/Features/Admin/Occupancy/__tests__/occupancy.test.ts
  - resources/js/Features/Admin/Occupancy/__tests__/AssignDutyUserSheet.component.test.ts
  - resources/js/Features/Admin/AdminSearch/Components/Detail/__tests__/DutyDetailPreview.component.test.ts
  - resources/js/Composables/__tests__/useDuplicateDutyCheck.test.ts
---

# Pareigybės

**Pareigybė** – tai VU SA ar universiteto institucijoje einamos pareigos (pavyzdžiui, padalinio
pirmininkas, komunikacijos koordinatorius, kuratorius, studentų atstovas taryboje ar komisijoje).

Pareigybės yra visos platformos teisių ir atsakomybių ašis: **naudotojai patys savaime dažniausiai neturi
jokių administratoriaus teisių** (tik išskirtiniais atvejais). Kasdieniame darbe administravimo prieiga suteikiama per pareigybės roles. Bazinė nario prieiga,
tiesiogiai paskirtos teisės ir super administratoriaus prieiga aprašytos [Teisėse ir rolėse](/pagrindai/teises).

Pareigybių sąrašas pasiekiamas adresu `/mano/duties`, o kiekviena pareigybė turi savo puslapį
`/mano/duties/{id}`.

## Kaip tai veikia

### Teisės ir rolės per pareigybes {#teises-ir-roles}

Kasdieniame administravimo darbe roles priskirk **pareigybei**, kad keičiantis nariams nereikėtų
iš naujo sudėlioti prieigos.

1. Kai naudotojas paskiriamas į pareigybę (sukuriant [pareigybės laikotarpį](/visak/pareigybiu-laikotarpiai#laikotarpis)),
   jis automatiškai įgyja visas pareigybei suteiktas roles ir teises.
2. Jei pareigybę eina keli žmonės (pvz., keli kuratoriai ar kolegialaus organo nariai), visi jie
   naudojasi tomis pačiomis pareigybės teisėmis.
3. Pasibaigus paskutinei pareigų dienai, prieiga iš šio laikotarpio nustoja galioti.
   Kitos pareigybės ir bazinė nario prieiga dėl to neišnyksta.

::: tip Pareigos pasikeitė – pakeisk narį
Kai keičiasi padalinio pirmininkas ar koordinatorius, **nereikia kurti naujos pareigybės**.
Pakanka senajam nariui nustatyti kadencijos pabaigos datą ir prie tos pačios pareigybės priskirti
naująjį narį. Taip išsaugoma visa pareigybės istorija, instituciniai ryšiai ir el. pašto adresas.
Masiniam narių keitimui naudok [Pareigybių atnaujinimo vedlį](/organizacija/pareigybiu-atnaujinimas).
:::

### Tipai ir automatinis rolių suteikimas {#tipai}

Kiekvienai pareigybei galima priskirti vieną ar kelis **tipus** (pvz., *Pirmininkas*,
*Koordinatoriai*, *Kuratoriai*, *Studentų atstovai*).

Tipai atlieka dvi svarbias funkcijas:
- **Viešas grupavimas ir atvaizdavimas**: pagal tipą viešoje svetainėje kontaktai atvaizduojami
  tinkamose skiltyse (pvz., padalinio kontaktų bloke ar studentų atstovų sąrašuose).
- **Automatinis tam tikrų rolių susiejimas**: sistemoje kai kurie tipai yra susieti su bazinėmis rolėmis:
  - Priskyrus tipą **Studentų atstovai**, pareigybė automatiškai gauna rolę **Studentų atstovas** (leidžiančią institucijoje fiksuoti posėdžius ir darbotvarkės klausimus).
  - Priskyrus tipą **Koordinatoriai**, pareigybė automatiškai gauna rolę **Problemų redaktorius** (leidžiančią kelti ir tvarkyti padalinio problemas).
  - **Svarbu:** tipas *Koordinatoriai* **nesuteikia** padalinio valdymo teisių (pvz., naujienų, puslapių, pareigybių ar išteklių). Šios srities rolės (pvz., *Komunikacijos koordinatorius*, *Išteklių administratorius*) pareigybei turi būti **priskirtos rankiniu būdu** pareigybės formos skiltyje *Rolės ir teisės*.
  - Nuėmus tipą, su juo susieta automatinė rolė iš pareigybės pašalinama.

::: warning Kas gali priskirti tipą
Tipą pareigybei gali priskirti tik tas administratorius, kurio paties rolė turi teisę tą tipą priskirti, arba super administratorius. Pavyzdžiui, Komunikacijos koordinatorius gali priskirti tipus *Koordinatoriai*, *Kuratoriai*, *Pirmininkas*, o Studentų atstovų koordinatorius – *Studentų atstovai*.
:::

### Vietų skaičius ir užimtumas {#uzimtumas}

Kiekviena pareigybė turi numatytą **vietų skaičių**:
- Jei pareigybei numatyta 1 vieta, o ją eina 1 narys – pareigybė pilnai užimta.
- Jei pareigų šiuo metu niekas neina – ji laikoma **neužimta**.
- Neužimtos pareigybės sąrašuose žymimos perspėjimo ženklu, o pareigybės puslapyje rodomas ryškus
  perspėjimas **Pareigos neužimtos**.
- Kolegialiems valdymo organams (taryboms, komisijoms) vietų skaičius nurodo, kiek studentų atstovų
  turi būti deleguota į šį organą.

### Atsakomybės {#atsakomybes}

Pareigybės puslapyje yra skirtukas **Atsakomybės**. Čia nustatoma, už kokį padalinį, institucijos
tipą ar konkretų organą ši pareigybė atsako (pavyzdžiui, *Studentų atstovų koordinavimas*).

Atsakomybė nurodo sistemai, kas gauna atstovų registracijos anketas, posėdžių klausimus ir veiklos
ataskaitas. Plačiau apie atsakomybių mechanizmą skaityk skyriuje [Atsakomybės](/pagrindai/atsakomybes).

### Bendros pareigybės tarp padalinių (kvotos) {#bendros-pareigybes}

Kai kurios institucijos (pvz., VU SA Parlamentas) priklauso bendram dariniui, tačiau jų nariai renkami iš visų fakultetinių
padalinių.

Tokioms pareigybėms naudojamas **padalinių kvotų mechanizmas**:
- Pareigybę sukūręs padalinys nustato, kurie kiti padaliniai gali skirti savo atstovus ir kokia
  jiems skirta vieta (kvota).
- Fakulteto koordinatorius mato šią instituciją ir pareigybę savo sąraše kaip išorinę ir gali
  paskirti savo fakulteto studentą, neviršydamas skirtos kvotos.
- Fakulteto administratorius negali keisti pačios pareigybės pavadinimo ar kitų padalinių atstovų,
  tačiau gali valdyti savo deleguotus narius.

### Ex officio pareigos {#ex-officio}

Kai kurios pareigos pagal įstatus priklauso automatiškai (*ex officio*), pavyzdžiui, padalinio
pirmininkas pagal pareigas yra ir padalinio tarybos narys.

- Prie pirmininko pareigybės nurodomos tikslinės pareigybės.
- Paskyrus naują pirmininką arba pakeitus jo kadencijos datas, sistema automatiškai sukuria arba
  atnaujina susietus tarybos nario laikotarpius.
- Rankiniu būdu keisti *ex officio* laikotarpio datų negalima – jos visada seka pagrindinių
  pareigų datas.

### Pareigų galūnių giminizavimas, linksniavimas ir dublikatų prevencija {#lytis-ir-dublikatai}

Lietuvių kalboje pareigybių pavadinimai kinta pagal einančio asmens įvardį arba vardo giminę (*Pirmininkas* /
*Pirmininkė*, *Koordinatorius* / *Koordinatorė*).

Kad duomenų bazėje nesidaugintų dubliuotos pareigybės:
- Pareigybės pavadinimas kuriamas viena bendra forma.
- Sistema automatiškai moka linksniuoti ir pritaikyti lyties galūnę pagal pareigas einančio asmens
  profilio duomenis.
- Kuriant naują pareigybę, sistema tikrina normalizuotą pavadinimą: jei
  institucijoje jau yra „Komunikacijos koordinatorius“, bandant sukurti „Komunikacijos koordinatorė“
  arba „Komunikacijos koordinatorius (-ė)“, formoje iškart pasirodo perspėjimas **Aptiktas pareigybės dublikatas**
  su nuoroda į jau esantį įrašą.

## Veiksmai

### Pareigybių sąrašas ir filtrai {#sarasas}

Pareigybių sąraše (`/mano/duties`) pateikiamos visos pasiekiamos pareigybės:

- **Paieška**: galima ieškoti pagal pareigybės pavadinimą, el. paštą arba instituciją.
- **Greitasis filtras „Neužimtos“**: vienu paspaudimu atrenka pareigybes, kurios šiuo metu neturi
  narių.
- **Duomenų kokybės filtrai** šoninėje juostoje:
  - *Neužimtos* – pareigybės be aktyvių narių.
  - *Trūksta EN pavadinimo* – pareigybės, neturinčios angliško vertimo.
  - *Trūksta LT pavadinimo* – pareigybės be lietuviško pavadinimo.
  - *Pasikartojantys nariai* – pareigybės, kuriose tas pats žmogus turi kelis persidengiančius
    laikotarpius.
- **Šiukšliadėžė** viršuje leidžia peržiūrėti ištrintas pareigybes ir jas atkurti.

### Pareigybės puslapis {#puslapis}

Atidarius pareigybę (`/mano/duties/{id}`), viršuje matai instituciją, užimtų ir visų vietų
skaičių (pvz., 3 / 5), pareigybės el. paštą ir kategorijas, jei jos priskirtos. Puslapis
suskirstytas į skirtukus:

<DocScreenshot name="duty-record" alt="Pareigybės puslapis: institucija, užimtos vietos, el. paštas, dabartiniai nariai su veiksmais Redaguoti ir Užbaigti pareigas bei laikotarpių istorija" caption="Parlamento nario pareigybė: trys iš penkių vietų užimtos." />

1. **Nariai**:
   - *Dabartiniai nariai*: kas šiuo metu eina pareigas ir nuo kada. Žyma **Ex-officio** rodo, kad
     pareigos kyla iš kitos pareigybės, o **Deleguota** – kad narys atstovauja kitam padaliniui.
   - *Būsimi nariai*: nariai, kurių pareigos prasidės ateityje (rodoma, tik jei tokių yra).
   - *Laikotarpių istorija*: pasibaigę laikotarpiai – kas ir kada šias pareigas ėjo anksčiau.
   - Mygtukas **Priskirti narį** atidaro nario priskyrimo formą. Prie kiekvieno nario rasi
     **Redaguoti**, o prie dabartinių – ir **Užbaigti pareigas**.
2. **Apie pareigybę**:
   - Pareigybės aprašymas.
   - Kitos tos pačios institucijos pareigybės ir kiek narių jas eina.
3. **Atsakomybės**:
   - Padaliniai, institucijų tipai ar konkretūs organai, kuriuos ši pareigybė koordinuoja.
   - Mygtukas **Pridėti atsakomybę** leidžia priskirti naują koordinavimo sritį.
   - Greta rodomos pareigybės rolės – jos lemia, ką pareigybė gali daryti platformoje.
4. **Failai** (rodoma, jei pareigybė turi SharePoint aplanką ar kategoriją):
   - Su pareigybe susieti dokumentai ir ataskaitos.

### Laikotarpio datos {#laikotarpio-datos}

**Abi datos įskaitomos:** laikotarpis nuo 2025-05-30 iki 2025-06-01 apima gegužės 30, 31 ir birželio 1 d.
Pradžia negali būti vėlesnė už pabaigą. Pabaigos data yra paskutinė aktyvi diena. **Užbaigti pareigas šiandien** įrašo šiandieną;
narys lieka dabartinių narių sąraše iki dienos pabaigos ir tampa buvusiu kitą dieną.
Istorinis įrašas išlieka. Jei paskutinė pareigų diena buvo vakar, įrašyk vakarykštę datą.

<ChangelogNote version="v3.0" date="2026-10-02" title="Paskutinė pareigų diena">

Dabartinių ir buvusių narių sąrašai dabar vienodai įskaito pabaigos dieną.
Užbaigimo patvirtinimas aiškiai paaiškina, kad šiandien pareigos dar galioja.

</ChangelogNote>

### Nario priskyrimas ir šalinimas {#priskirti-nari}

Paspaudus **Priskirti narį** pareigybės puslapyje:
1. Pasirenkamas narys (iš esamų narių sąrašo arba įvedant el. paštą).
2. Nurodoma **pradžios data** (pagal nutylėjimą – šiandien).
3. Nurodoma **pabaigos data** (jei kadencija terminuota).
4. Jei reikia, nurodoma studijų programa (aktualu kuratoriams), papildomas
   el. paštas ar nuotrauka.

Norint užbaigti nario pareigas:
- Narių sąraše spausk **Redaguoti** ir nustatyk pabaigos datą arba pasirinkite **Užbaigti pareigas šiandien**.
- **Ištrinti priskyrimą** naudok tik tuo atveju, jei narys buvo priskirtas per klaidą. Teisingai
  baigtos kadencijos turi turėti pabaigos datą, kad išliktų veiklos istorijoje.

### Kūrimas ir redagavimas {#kurimas}

Nauja pareigybė kuriama per **+ Sukurti → Pareigybė** arba mygtuku **Nauja pareigybė** sąrašo
viršuje (`/mano/duties/create`):
- **Pavadinimas** (LT ir EN) – rašomas pagrindine forma (pvz., *Finansų koordinatorius*).
- **Institucija** – institucija, kuriai pareigybė priklauso.
- **Institucinis el. paštas** – dažniausiai `@vusa.lt` dėžutė. Šis el. paštas naudojamas ir prisijungimui
  prie Office 365.
- **Vietų skaičius** – numatytas narių skaičius (pagal nutylėjimą 1).
- **Tipai ir rolės** – pažymimi tinkami tipai ir, jei reikia, specifinės administratoriaus rolės.
- **Kontaktų grupavimas** – kaip nariai rodomi kontaktuose (be grupavimo, pagal studijų programą ar
  pagal padalinį).

Redagavimas pasiekiamas pareigybės puslapyje per **⋯ → Redaguoti pareigybę** (`/mano/duties/{id}/edit`).

### Pareigybių sujungimas {#sujungimas}

Jei istorijoje buvo sukurtos kelios tos pačios pareigybės versijos (pavyzdžiui, „Atstovas taryboje“
ir „Atstovė taryboje“, arba dublikatas dėl rašybos klaidos), jas galima **sujungti į vieną**:

1. Pareigybių sąraše viršuje spausk **Sujungti pareigybes** (įsijungia pasirinkimo režimas).
2. Pažymėk bent dvi pareigybes, kurias norite sujungti.
3. Apačioje paspausk mygtuką **Sujungti** – atsidarys sujungimo langas.
4. Pasirink, kurią pareigybę **pasilikti kaip pagrindinę**.
5. Patvirtink sujungimą:
   - Visi narių laikotarpiai iš naikinamų pareigybių perkeliami į pagrindinę.
   - Jei tas pats žmogus abiejose pareigybėse turėjo persidengiančius laikotarpius, jie sujungiami
     į vieną ištisinį laikotarpį.
   - Visi tipai, rolės ir padalinių kvotos perkeliami į pagrindinę pareigybę (be dubliavimo).
   - Panaikintos pareigybės pervedamos į šiukšliadėžę (minkštasis trynimas).

### Šiukšlinė {#trynimas}

- Pareigybės ištrynimas per **⋯ → Ištrinti pareigybę** perkelia ją į šiukšliadėžę.
- Ištrintą pareigybę galima bet kada **atkurti** iš šiukšliadėžės sąrašo.
- **Galutinis ištrynimas visam laikui yra blokuojamas**, jei pareigybė kada nors
  turėjo bent vieną narį. Taip apsaugomas duomenų vientisumas ir studentų atstovavimo istorija.
  Jei pareigybė nebenaudojama, tiesiog palik ją ištrintą šiukšliadėžėje.

## Kas ką gali {#teises}

| Veiksmas | Studentų atstovas | Komunikacijos koordinatorius | Studentų atstovų koordinatorius | Centrinio biuro koordinatoriai | Super administratorius |
|---|---|---|---|---|---|
| Matyti savo pareigybę | ✓ | ✓ | ✓ | ✓ | ✓ |
| Matyti padalinio pareigybes | – | ✓ | ✓ | ✓, visų padalinių | ✓ |
| Sukurti ir redaguoti padalinio pareigybę | – | ✓ | ✓ | ✓, visų padalinių | ✓ |
| Priskirti narius savo padalinyje | – | ✓ | ✓ | ✓, visų padalinių | ✓ |
| Priskirti narius pagal savo padalinio kvotą bendrose pareigybėse | – | ✓ | ✓ | ✓ | ✓ |
| Sujungti pareigybes | – | ✓ | ✓ | ✓ | ✓ |
| Pridėti pareigybės atsakomybes | – | ✓ | ✓ | ✓ | ✓ |
| Ištrinti pareigybę į šiukšlinę | – | ✓ | ✓ | ✓ | ✓ |
| Ištrinti pareigybę visam laikui (tik be istorijos) | – | – | – | – | ✓ |

„Centrinio biuro koordinatoriai“ lentelėje – **Centrinio biuro komunikacijos koordinatorius**
ir **Centrinio biuro studentų atstovų koordinatorius**. Apimtis ribojama administruojamais padaliniais;
sujungimui reikia galėti redaguoti paliekamą ir ištrinti sujungiamas pareigybes.

::: warning Savęs užsirakinimo apsauga
Jei administratorius bando iš savo paties pareigybės pašalinti administracines teises suteikiančią
rolę arba užbaigti savo paties narystę, sistema operaciją sustabdo ir parodo įspėjimą
apie prieigos pokytį. Tai apsaugo administratorių nuo netyčinio prieigos praradimo. Pakeitimas
įrašomas tik tada, kai vartotojas aiškiai patvirtina veiksmą.
:::

## Pranešimai ir automatizavimas {#pranesimai}

| Kada | Kas nutinka | Kas gauna |
|---|---|---|
| Sukuriamas nario laikotarpis su pradžios data šiandien | Iškart suteikiamos pareigybės teisės ir rolės | Paskirtas narys |
| Baigiasi paskutinė pareigybės laikotarpio diena | Prieiga iš šio laikotarpio nustoja galioti | Buvęs narys |
| Paskirtas narys būsimai datai (ateityje) | Sukuriamas būsimas laikotarpis; dabartinių narių sąraše jis atsiras pradžios dieną | Būsimas narys |
| Pareigybei priskiriamas tipas su susietomis rolėmis (pvz., „Studentų atstovai“) | Pareigybei automatiškai suteikiamos su tipu susietos rolės | Pareigybės nariai |
| Pareigybei priskiriama atsakomybė | Asmuo pradedamas laikyti srities koordinatoriumi | Koordinatorius |
| Sukuriama nauja studentų atstovų registracija | Pranešimą gauna pareigybė, turinti to organo atsakomybę | Koordinatorius |


::: details Dažni klausimai
**Ką daryti, jei koordinatorius atsistatydino viduryje kadencijos?**
Nario kortelėje spausk **Redaguoti** ir nustatyk šiandienos datą kaip pabaigos datą. Tada
prie tos pačios pareigybės priskirkite naują laikinąjį ar išrinktą narį.

**Ar galima pareigybei priskirti kelis el. pašto adresus?**
Ne, pareigybė turi vieną pagrindinį institucinį el. paštą. Tačiau kiekvienas narys savo narystės
laikotarpyje gali turėti nurodytą papildomą kontaktinį el. paštą ar asmeninį paštą.
:::

## Techninė informacija {#technine-informacija}

Šis skyrius skirtas tiems, kurie tvarko roles ar tikrina sistemos elgseną.

### Teisės

- Pareigybių administravimą reglamentuoja `DutyPolicy`.
- `duties.read.*` leidžia matyti visas sistemos pareigybes; `duties.read.padalinys` riboja matomumą
  iki vartotojo atstovaujamo padalinio institucijų.
- Kiekvienas naudotojas, kuris bent kartą ėjo pareigybę, turi teisę peržiūrėti tos pareigybės įrašą
  (`DutyPolicy::view`).
- `DutyPolicy::managePeople` tikrina, ar vartotojas gali priskirti ir šalinti narius: savame
  padalinyje pakanka `duties.update.padalinys`, o bendrose pareigybėse tikrinama, ar vartotojo
  padalinys yra `assignableTenants` sąraše ir ar deleguojamas studentas priklauso tam padaliniui.
- Trynimas visam laikui (`duties.forceDelete.padalinys`) tikrina `GuardsForceDelete` sutartį:
  jei `dutiables()->exists()`, trynimas nutraukiamas su paaiškinimu, apsaugant nuo duomenų bazės
  išorinių raktų klaidų.

### Kaip tai įgyvendinta

- Pareigybės modelis `Duty` naudoja `HasRoles` (Spatie), `HasTranslations` (daugiakalbystei),
  `LogsModelActivity`, `LogsRelationshipChanges` ir `SoftDeletes`.
- Narių priskyrimo ryšys yra polimorfinis `dutiables` (`Dutiable` pivotas), palaikantis `start_date`,
  `end_date`, `study_program_id`, `additional_email`, `additional_photo`.
- Tipų susiejimą stebi `TypeableObserver`: kai `Typeable` įrašas išsaugomas, `GetAttachableTypesForDuty`
  patikrina, ar veikiantis naudotojas turi teisę priskirti šį tipą (`role_can_attach_types`), ir
  automatiškai priskiria su tipu susietas roles (`$typeable->typeable->roles()->syncWithoutDetaching($roles)`),
  pavyzdžiui, `Studentų atstovas` tipui `studentu-atstovai` arba `Problemų redaktorius` tipui `koordinatoriai`.
- Pavadinimų unikalumą ir normalizavimą užtikrina `DutyNameNormalizer` (sulygina vyr./mot. galūnes,
  skliaustus `(-ė)` ir linksnius).
- Savęs užsirakinimo apsaugą užtikrina `AdminController::guardSelfLockout` kartu su
  `DutySelfLockoutChecker`.
- `current_duties` tikrina ir pradžią, ir imtinę pabaigą. `authorization_duties` apima ir
  dar neprasidėjusius laikotarpius; tai tikrina `ScheduledDutyAuthorizationTest`.
  Būsimas laikotarpis dėl to netampa dabartiniu.
