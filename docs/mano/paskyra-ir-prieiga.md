---
doc_status: reviewed
title: Paskyra ir prieiga
area: profile
last_reviewed: 2026-10-02
tests:
  - tests/Feature/Profile/ProfileControllerTest.php
  - tests/Feature/Profile/PasswordTest.php
  - tests/Feature/Admin/Core/ProfileRolesTest.php
  - tests/Feature/Permissions/ScheduledDutyAuthorizationTest.php
  - resources/js/Pages/Admin/__tests__/ShowProfile.component.test.ts
  - resources/js/Pages/Admin/__tests__/ShowMyRoles.component.test.ts
  - resources/js/Components/Layouts/Shell/__tests__/ShellAccountMenu.component.test.ts
---

# Paskyra ir prieiga

Paspausk savo nuotrauką arba vardą viršutinėje juostoje. Paskyros meniu atskirai pasieksi
**Paskyra**, **Mano rolės ir pareigybės**, **Pranešimų nustatymai**, **Išvaizda** ir **Pagalba**.
Šiais puslapiais gali naudotis kiekvienas prisijungęs narys; juose matai savo duomenis.

## Kaip tai veikia

Paskyros duomenys ir pareigybių suteikiama prieiga tvarkomi atskirai.
Pakeitęs kontaktus naujos rolės negausi, o peržiūrėdamas roles nepakeisi savo pareigų datų.

## Veiksmai

### Atnaujinti savo duomenis {#profilis}

Atverk **Paskyra** (`/mano/profile`), pakeisk telefono numerį, Facebook nuorodą arba
profilio nuotrauką ir spausk **Išsaugoti**. Jei rodoma lauko klaida, pataisyk ją ir išsaugok dar kartą.
Skiltyje **Kreipinys ir įvardžiai** gali įrašyti įvardžius abiem kalbomis ir pasirinkti, ar juos rodyti viešai.

Savo vardą ir pavardę gali pataisyti **tik vieną kartą** – formoje tai primenama prie laukelio.
Po to laukelis užrakinamas; jei vardą reikia keisti dar kartą, kreipkis į administratorių.
Prisijungimo el. paštas šioje formoje užrakintas visada. El. pašto adresų pranešimams pasirinkimas
yra atskiras nustatymas.
**Slaptažodžio keitimas** rodomas tik paskyrai, kuri jau turi vietinį slaptažodį.
Įrašyk dabartinį slaptažodį, naująjį ir jo patvirtinimą, tada spausk **Keisti slaptažodį**.
Tai nekeičia tavo „Microsoft“ paskyros slaptažodžio.

### Patikrinti savo prieigą {#prieiga}

<ChangelogNote version="v3.0" date="2026-10-02" title="Pareigos, rolės ir prieigos istorija atskirame puslapyje">

Kai neberandi skilties arba pasikeitė pareigos, pirmiausia tikrink **Mano rolės ir pareigybės**.
Čia matai savo pareigas ir skiltis, kurias gali atverti.

</ChangelogNote>

Atverk **Mano rolės ir pareigybės** (`/mano/profile/roles`). Čia rasi:

- dabartines, būsimas ir buvusias pareigas su institucijomis bei datomis;
- per pareigybes gautas ir tiesiogiai paskyrai priskirtas roles;
- prieinamas darbo sritis ir jų skiltis su nuorodomis;
- datuotą pareigų pradžios ir pabaigos istoriją;
- žymas apie iš kitos pareigybės kylančias (*ex-officio*) ar kito padalinio atstovavimo pareigas.

<DocScreenshot name="my-roles" alt="Mano rolės ir pareigybės: dabartinės pareigybės su datomis ir rolėmis, žemiau – kokias darbo sritis ir skiltis gali atverti" caption="Atstovė mato savo tris pareigybes ir skiltis, kurias jos jai atveria." href="/mano/profile/roles" />

Jei tavo pareigos prasidės ateityje, prieigą gali gauti jau dabar, kad spėtum pasiruošti.
Narių sąrašuose vis tiek būsi rodomas tik nuo pareigų pradžios. Pabaigos diena dar įskaitoma. Tikslias taisykles skaityk
[Datos ir narystės taisyklės](/pagrindai/padaliniai-ir-pareigybes#datos),
o ką gali be papildomos rolės – [Teisės ir rolės](/pagrindai/teises#bazine-prieiga).

Jei skilties trūksta, patikrink savo pareigų datas ir roles, tada kreipkis į savo administratorių.
Pats šiame puslapyje rolių ar pareigų nepriskiri.

### Nustatymai ir pagalba {#pagalba}

- **Pranešimų nustatymai**: rinkis laiškų ir įrenginio pranešimų parinktis. Jų taisyklės
  aprašytos [Pranešimų pagrinduose](/pagrindai/pranesimai).
- **Išvaizda**: perjunk temą, kalbą arba atverk prieinamumo valdiklius
  (plačiau – [Platforma](/pagrindai/platforma#spalvos-zenklai-ir-formos)).
- **Pagalba**: atverk gidą, **Parodyk, kaip veikia** turą, **Mano užklausos** arba **Pranešti problemą**.
  Problemos pranešimui pridedamas dabartinis puslapio adresas ir naršyklės kontekstas.
  Užklausų matomumas bei sprendimo eiga aprašyti [Pagalbos užklausose](/sistema/pagalbos-uzklausos).

### Atsijungti {#atsijungimas}

**Atsijungti** užbaigia Mano VU SA sesiją. **Atsijungti nuo Microsoft** papildomai pradeda
„Microsoft“ atsijungimą. Bendrame kompiuteryje rinkis ir „Microsoft“ atsijungimą;
vien naršyklės užvėrimas nėra patikimas atsijungimo veiksmas.

## Kas ką gali {#teises}

Kiekvienas prisijungęs narys peržiūri savo paskyrą bei prieigą ir keičia savo leidžiamus
kontaktinius duomenis. Kitų narių pareigas ar roles tvarkyk per tam skirtus
[Organizacijos puslapius](/organizacija/), turėdamas atitinkamą rolę.

## Techninė informacija {#technine-informacija}

- `ProfileController` visada naudoja prisijungusio naudotojo ID; prieigos suvestinę sudaro `GetUserAccessSummary`.
- Profilio laukai tikrinami `UpdateUserSettingsRequest`, slaptažodis – `UpdatePasswordRequest`.
- Profilio el. paštas ir slaptažodis nepriimami per bendrą profilio atnaujinimą.
- Vardą `ProfileController::updateUserSettings` priima tik kol `name_was_changed` netiesa ir tada jį
  nustato; vėlesni vardo pakeitimai tyliai praleidžiami. `ShowProfile.vue` tada užrakina vardo lauką,
  el. pašto lauką – visada.
- `ProfileRolesTest` tikrina pareigų būsenas, paskutinės dienos įskaitymą, roles ir kitų
  narių duomenų neįtraukimą. Sąsajos testai tikrina laukus, prieigos vaizdą ir meniu veiksmus.
