---
doc_status: reviewed
title: Atsakomybės ir leidimai
area: roles
models: [Role, Permission, ResponsibilityGrant]
last_reviewed: 2026-10-21
tests:
  - tests/Feature/Permissions/DutyTypeAssignmentTest.php
  - tests/Feature/Admin/Permissions/ResponsibilityControllerTest.php
  - tests/Feature/Admin/Permissions/PermissionControllerTest.php
  - tests/Feature/Admin/People/DutyResponsibilityControllerTest.php
  - tests/Feature/Responsibilities/ProjectDutyResponsibilitiesTest.php
  - tests/Feature/System/PermissionCatalogTest.php
  - tests/Feature/System/ModelPermissionSeederTest.php
  - tests/Feature/Console/SyncPermissionsTest.php
  - tests/Feature/Permissions/BaselineAccessTest.php
  - resources/js/Features/Admin/PermissionMatrix/__tests__/PermissionMatrix.component.test.ts
  - resources/js/Features/Admin/PermissionMatrix/__tests__/usePermissionDraft.test.ts
---

# Atsakomybės ir leidimai

Darbo srities **Sistema** skiltys **Atsakomybės** (`/mano/responsibilities`) ir **Leidimai**
(`/mano/permissions`) nusako, ką pareigybės daro ir ką dėl to gali platformoje. Kaip veikia
teisės ir bazinė nario prieiga, aprašyta [Teisėse](/pagrindai/teises).

<ChangelogNote version="v3.10" date="2026-10-19" title="Rolės tapo atsakomybėmis">

Buvusios rolės dabar vadinamos **atsakomybėmis**: jos turi lietuvišką ir anglišką pavadinimą bei
aprašymą, o teisės nustatomos viename tinklelyje.

</ChangelogNote>

## Rekomendacijos {#rekomendacijos}

::: tip
Atsakomybę pavadink pagal darbą, o ne pagal žmogų: „Komunikacija“, „Organizacijos valdymas“, o ne
„Komunikacijos koordinatorius“. Suteik tik reikalingus veiksmus ir siauriausią tinkamą apimtį.
:::

## Kaip tai veikia

- **Atsakomybė** nusako, ką pareigybė daro, ir kartu suteikia tam darbui reikalingas teises.
  Visi pareigybę einantys nariai gauna jos atsakomybių teises.
- **Leidimas** yra viena teisė, pvz., redaguoti padalinio naujienas. Leidimų sąrašą sudaro sistema.
- **Sistemos paskirtis**: kelios atsakomybės (studentų atstovavimas, studentų atstovų koordinavimas,
  narių registracija) dar ir nurodo sistemai, ką rasti – kam siųsti užduotis, registracijas ar
  pranešimus. Kuri atsakomybė atlieka kurią paskirtį, nustatoma
  [Sistemos paskirtyse](/sistema/nustatymai#sistemos-paskirtys). Dauguma atsakomybių paskirties
  neturi ir sąraše žymimos **Prieiga**.
- **Kam suteikiama**: atsakomybė suteikiama
  1. pareigybei – dažniausias atvejis;
  2. [pareigybės tipui](/sistema/tipai#pareigybes-tipo-atsakomybes) – ją gauna kiekviena to tipo
     ir jo potipių pareigybė; tokia atsakomybė negali turėti leidimų „Visur“ ir „Trinti negrįžtamai“;
  3. institucijai – retas atvejis, kai ją turi gauti kiekviena institucijos pareigybė (pvz., visi
     Centrinio biuro nariai).

<span id="roles-tipu-leidimai"></span>

::: info Atsakomybės bendros, teisės – su apimtimis
Atsakomybės bendros visai platformai, bet kiekvienai teisei nustatoma apimtis: **Savo** (tik su
tavimi susiję įrašai), **Padalinyje** arba **Visur**. Fakulteto komunikacijos pareigybė gali
redaguoti tik savo padalinio naujienas, o Centrinio biuro – visų padalinių.
:::

## Veiksmai

### Atsakomybių sąrašas (`/mano/responsibilities`)

Sąraše matyti kiekvienos atsakomybės aprašymas, sistemos paskirtis, teisių, pareigybių ir dabar ją
turinčių žmonių skaičius. Pagal nutylėjimą pirmiau rodomos atsakomybės su paskirtimi.

### Naujos atsakomybės kūrimas

1. Sąrašo viršuje paspausk **Nauja atsakomybė**.
2. Įrašyk pavadinimą lietuviškai (angliškas nebūtinas) ir trumpą aprašymą.
3. Išsaugok. Atsivėrusiame atsakomybės puslapyje nustatyk teises ir kam ji suteikiama.

### Teisių nustatymas (skirtukas „Teisės“)

Tinklelyje sritys sugrupuotos pagal darbo sritis (Pradžia, ViSAK, Rezervacijos, Svetainė,
Organizacija, Sistema) ir išdėstytos tokia pat tvarka kaip meniu. Kiekvienam veiksmui – matyti,
kurti, redaguoti, perkelti į šiukšlinę, trinti negrįžtamai – pasirink apimtį. Ištekliams be
šiukšlinės veiksmas „Trinti negrįžtamai“ pažymėtas pastaba, kad ištrynimas iškart galutinis.
Plačiame ekrane slenkant lentelę stulpelių antraštės lieka matomos po pagrindiniu meniu.
Užraktu **Visi nariai** pažymėtus veiksmus pagal
[bazinę prieigą](/pagrindai/teises#bazine-prieiga) jau turi kiekvienas narys; prie išteklių
parašyta, ką kiekvienas narys gali be jokios atsakomybės.

Apimtis **Savo** siūloma tik ten, kur ji ką nors reiškia: pavyzdžiui, posėdžiams (savo institucijų
posėdžiai) ir pareigybėms. Užduotims, dokumentams, naudotojams ir kitiems ištekliams jos nėra –
savo užduotis ir save matai be jokios atsakomybės.

Pakeitimai kaupiami ir apačioje rodoma, kiek teisių pridėsi ir atimsi. Visi pakeitimai įrašomi
vienu **Išsaugoti** paspaudimu; **Atšaukti** grąžina išsaugotą būseną.

### Atsakomybės suteikimas (skirtukas „Kam suteikta“) {#atsakomybes-suteikimas}

Skirtuke matyti pareigybės, pareigybių tipai ir institucijos, kurioms atsakomybė suteikta, su narių
skaičiumi. Paspausk **Suteikti**, pasirink, kam, ir surask pareigybę, tipą ar instituciją.
Atsakomybei su paskirtimi dar nurodoma, už ką pareigybė atsakinga – padalinį, institucijų tipą ar
instituciją.

Pareigybės tipui atsakomybės su leidimais „Visur“ ar „Trinti negrįžtamai“ suteikti negalima –
lange rodomas paaiškinimas, kurie leidimai tam trukdo. Tokia atsakomybė suteikiama pareigybėms
atskirai. Atsakomybei, kurią jau gauna tipai, šių leidimų pridėti taip pat negalima.

Atsakomybę galima suteikti ir pareigybės puslapio skirtuke **Atsakomybės**. Ten matyti ir tos,
kurias pareigybė gauna per tipą ar instituciją.

### Žmonės ir delegavimas

- **Žmonės** rodo, kas dabar eina pareigybes su šia atsakomybe.
- **Delegavimas** nustato, kokius pareigybių tipus šios atsakomybės nariai gali priskirti
  ar pašalinti pareigybės formoje ir tipo puslapyje. Reikia ir teisės redaguoti konkrečią
  pareigybę; vien tipo redagavimo teisės neužtenka. Delegavimas galioja per šiuo metu einamas
  pareigas. Koordinatorius neprivalo pats turėti tipui suteikiamų atsakomybių – pavyzdžiui,
  studentų atstovų koordinatorius gali priskirti studentų atstovo tipą.

Tipo puslapyje suteikimai tik rodomi, su nuorodomis į jų atsakomybes. Tipui suteikti atsakomybę
ar ją pašalinti galima atsakomybės puslapio skirtuke **Kam suteikta**, nurodant paskirčiai
reikalingą aprėptį. Esami suteikimai be tinkamos aprėpties pažymimi; jų duomenys automatiškai nekeičiami.

### Atsakomybės trynimas

Veiksmų meniu pasirink **Šalinti**. Atsakomybė atimama iš visų pareigybių. Ištrinti negalima
atsakomybės, kuri atlieka sistemos paskirtį arba pagal kurią sudaroma
[konferencija](/organizacija/konferencijos).

### Leidimų sąrašas (`/mano/permissions`)

Skiltis informacinė: leidimai generuojami sistemos kode ir rankiniu būdu nekuriami.

## Kas ką gali {#teises}

| Veiksmas | Pareigybę redaguojantis koordinatorius | Atsakomybes valdantis administratorius | Superadministratorius |
|---|---|---|---|
| Matyti atsakomybių ir leidimų skiltis | – | ✓ | ✓ |
| Kurti, redaguoti ir trinti atsakomybes | – | ✓ | ✓ |
| Keisti atsakomybės teises | – | ✓ | ✓ |
| Suteikti atsakomybę pareigybei, tipui ar institucijai | Tik turimas pats, savo padalinio pareigybei | ✓ | ✓ |
| Nustatyti sistemos paskirtis | – | Jei gali keisti nustatymus | ✓ |

Pareigybę redaguojantis koordinatorius (pvz., „Komunikacijos koordinatorius“) pareigybės puslapyje
gali suteikti tik tas atsakomybes, kurias turi pats. Taip niekas negali suteikti daugiau teisių,
nei turi.

::: warning Superadministratoriaus apsauga
**Super Admin** keisti, trinti ar suteikti pareigybei negalima. Superadministratorius turi visą
prieigą nepriklausomai nuo teisių tinklelio.
:::

## Pranešimai ir automatizavimas {#pranesimai}

- **Prieiga atnaujinama iš karto**: suteikus ar atėmus atsakomybę, pakeitus jos teises ar pareigybės
  tipą, atnaujinama nariams prieiga ir meniu.
- **Pagal tipą ir instituciją**: pareigybei gavus tipą (ar jo potipį) ar perėjus į instituciją,
  kuriai atsakomybė suteikta, ji gauna atsakomybę; netekus – jos netenka. Ištrinta pareigybė per tipą ar instituciją
  atsakomybių nebegauna.
- **Teisių trūkumai**: jei atsakomybę su paskirtimi turinčiai pareigybei trūksta tam darbui
  reikalingų teisių, atsakomybės ir pareigybės puslapiuose rodomas įspėjimas.

## Techninė informacija {#technine-informacija}

- Atsakomybės saugomos Spatie `roles` lentelėje (`App\Models\Role`): `name` – nekintantis raktas,
  `title` ir `description` – verčiami laukai, `purpose` – `App\Enums\ResponsibilityPurpose`.
- Suteikimai – `responsibility_grants` (`ResponsibilityGrant`: `grantee` = `duty` | `duty_type` |
  `institution`, nebūtinas `scope`). `model_has_roles` eilutės pareigybėms yra jų projekcija, kurią
  perskaičiuoja `ProjectDutyResponsibilities`; `responsibilities:project --check` praneša apie
  neatitikimus. `Duty::assignRole()` / `removeRole()` / `syncRoles()` rašo tiesioginius suteikimus.
- Leidimų katalogas – `App\Support\Permissions\PermissionCatalog` (`ModelEnum` × `CRUDEnum` ×
  leidžiamos apimtys, be bazinės prieigos ir be nieko neduodančių derinių, plius `settings.read.*` ir
  `settings.update.*`). `permissions:audit` praneša apie trūkstamus ir nebenaudojamus leidimus,
  o `permissions:sync` (leidžiamas kiekvieno diegimo metu) sukuria trūkstamus ir pašalina nebenaudojamus,
  išsaugodamas esamų leidimų ID ir turėtojus. Taisyklės: `.ai/rules/permissions.md`.
- Maršrutai: `responsibilities.*`, `responsibilities.permissions.update`,
  `responsibilities.grants.store|destroy`, `responsibilities.attachableTypes.update`,
  `duties.responsibilities.store|destroy`. Seni `/mano/roles…` adresai nukreipiami.
- Suteikti gali `GrantableResponsibilities` (turintys `roles.update.*` – bet kurią, kiti – tik
  turimas). Tipui leidžiamas teises tikrina `TypeGrantablePermissions` (jokių `*` ir `forceDelete`);
  `permissions:audit` praneša apie esamus jos neatitinkančius tipų suteikimus. Teisių trūkumus skaičiuoja `ResponsibilityPermissionGaps`.
