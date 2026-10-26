---
doc_status: reviewed
title: Paskyra ir prieiga
area: profile
last_reviewed: 2026-10-26
tests:
  - resources/js/Components/Institutions/__tests__/UserActivityRequestHistory.component.test.ts
  - tests/Browser/UserActivityRequestsTest.php
  - tests/Feature/Admin/Management/UserActivityRequestsTest.php
  - tests/Feature/Profile/ProfileControllerTest.php
  - tests/Feature/Profile/PasswordTest.php
  - tests/Feature/Admin/Core/ProfileRolesTest.php
  - tests/Feature/Permissions/ScheduledDutyAuthorizationTest.php
  - resources/js/Pages/Admin/__tests__/ShowProfile.component.test.ts
  - resources/js/Pages/Admin/__tests__/ShowMyRoles.component.test.ts
  - resources/js/Components/Layouts/Shell/__tests__/ShellAccountMenu.component.test.ts
  - resources/js/Composables/__tests__/useAccessibilityPreferences.test.ts
  - tests/Browser/AdminAccessibilityTest.php
  - resources/js/Composables/__tests__/useMotionAwareAutoplay.test.ts
  - resources/js/Composables/__tests__/useMotionAwareSortable.test.ts
  - resources/js/Components/ui/carousel/__tests__/Carousel.component.test.ts
---

# Paskyra ir prieiga

Viršutinėje juostoje paspaudus savo nuotrauką arba vardą, atveriamas paskyros meniu.
Jame pasiekiami punktai **Paskyra**, **Mano rolės ir pareigybės**, **Pranešimų nustatymai**, **Mano veiklos užklausos**,
**Išvaizda** ir **Pagalba**. Šiais puslapiais gali naudotis kiekvienas prisijungęs narys;
juose rodomi tavo duomenys.

## Kaip tai veikia

Paskyros duomenys ir pareigybių suteikiama prieiga tvarkomi atskirai.
Pakeitus kontaktus nauja rolė nesuteikiama, o rolių peržiūra nekeičia pareigų datų.

## Veiksmai

### Atnaujinti savo duomenis {#profilis}

Atverk **Paskyra** (`/mano/profile`), pakeisk telefono numerį, Facebook nuorodą arba
profilio nuotrauką ir spausk **Išsaugoti**. Jei rodoma lauko klaida, pataisyk ją ir išsaugok dar kartą.
Skiltyje **Kreipinys ir įvardžiai** gali įrašyti įvardžius abiem kalbomis ir pasirinkti, ar juos rodyti viešai.

Vardas, pavardė ir prisijungimo el. paštas šioje formoje užrakinti. Jei vardas ar pavardė
neteisingi, kreipkis į administratorių. El. pašto adresų pranešimams pasirinkimas
yra atskiras nustatymas.
**Slaptažodžio keitimas** rodomas tik paskyrai, kuri jau turi vietinį slaptažodį.
Įrašyk dabartinį slaptažodį, naująjį ir jo patvirtinimą, tada spausk **Keisti slaptažodį**.
Tai nekeičia tavo „Microsoft“ paskyros slaptažodžio.

### Patikrinti savo prieigą {#prieiga}

<ChangelogNote version="v3.0" date="2026-10-02" title="Pareigos, rolės ir prieigos istorija atskirame puslapyje">

Pasikeitus pareigoms arba neberandant skilties, pirmiausia verta patikrinti **Mano rolės ir pareigybės**.
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
Narių sąrašuose narys vis tiek rodomas tik nuo pareigų pradžios. Pabaigos diena dar įskaitoma. Tikslios taisyklės aprašytos
skyriuje [Datos ir narystės taisyklės](/pagrindai/padaliniai-ir-pareigybes#datos),
o bazinės galimybės be papildomos rolės – [Teisės ir rolės](/pagrindai/teises#bazine-prieiga).

Jei skilties trūksta, verta patikrinti pareigų datas bei roles ir kreiptis į administratorių.
Šiame puslapyje rolės ar pareigos nepriskiriamos.

### Nustatymai ir pagalba {#pagalba}

- **Pranešimų nustatymai**: čia pasirenkamos laiškų ir įrenginio pranešimų parinktys. Jų taisyklės
  aprašytos [Pranešimų pagrinduose](/pagrindai/pranesimai).
- **Išvaizda**: galima perjungti temą, kalbą arba atverti prieinamumo valdiklius
  (plačiau – [Platforma](/pagrindai/platforma#spalvos-zenklai-ir-formos)).
- **Pagalba**: čia galima atverti gidą, **Parodyk, kaip veikia** turą, **Mano užklausos** arba **Pranešti problemą**.
  Problemos pranešimui pridedamas dabartinis puslapio adresas ir naršyklės kontekstas.
  Užklausų matomumas bei sprendimo eiga aprašyti [Pagalbos užklausose](/sistema/pagalbos-uzklausos).

### Prieinamumo nustatymai {#prieinamumas}

<ChangelogNote version="v3.3" date="2026-10-18" title="Bendri prieinamumo ir judesio nustatymai">

Paskyros skiltyje **Prieinamumas** atverk **Valdyti prieinamumo nustatymus**. Tie patys valdikliai
pasiekiami paskyros meniu per **Išvaizda → Prieinamumas** ir viešos svetainės prieinamumo meniu.

</ChangelogNote>

Galima pakeisti teksto dydį, padidinti kontrastą, pabraukti nuorodas ir pasirinkti **Sumažinti judesį**.
Pasirinkimai išsaugomi šioje naršyklėje ir galioja Mano VU SA bei viešoje svetainėje tame pačiame
svetainės domene. Jie neperkeliami į kitą įrenginį. Jei naršyklė neleidžia jų išsaugoti, nustatymai
veikia iki puslapio įkėlimo iš naujo.

Sumažinus judesį, išjungiami puslapių perėjimai, trumpinamos sąsajos animacijos ir nebekaitaliojamos
pareigybių pavadinimų galūnės. Karuselių skaidrės, kortelių krūvos ir prisijungimo nuotraukos
nebesikeičia automatiškai. Mygtukais ir klaviatūra skaidrės pakeičiamos iš karto. Skaičiai rodomi
be laipsniško skaičiavimo, o turinio nuorodos, formos ir vadovai slenka be animacijos. Grafų,
žemėlapių ir rikiuojamų sąrašų valdikliai veikia be papildomų judesio efektų.

Operacinės sistemos sumažinto judesio nuostata taip pat taikoma: jos negalima atšaukti
išjungus svetainės žymimąjį laukelį ar paspaudus **Atstatyti**. Kai ji įjungta, nustatymų lange rodomas paaiškinimas.

**Atstatyti** grąžina svetainės nustatymus į pradines reikšmes. Paspaudus **Escape**, užveriamas
nustatymų langas ir klaviatūros žymeklis grįžta į jį atvėrusį mygtuką. Viešos svetainės mobiliajame
meniu pirmas **Escape** užveria atvertą kalbos ar prieinamumo langelį, o kitas – patį meniu.
Atvertas mobilusis meniu sustabdo puslapio slinkimą; padidinus langą iki kompiuterio rodinio jis
užveriamas, išskyrus XL teksto dydį, kai paliekama mobilioji navigacija.

<DocScreenshot name="accessibility-settings" alt="Prieinamumo nustatymai: teksto dydis, kontrastas, nuorodų pabraukimas ir sumažintas judesys" caption="Tie patys prieinamumo nustatymai pasiekiami ir paskyroje, ir viešoje svetainėje." />

### Atsijungti {#atsijungimas}

**Atsijungti** užbaigia Mano VU SA sesiją. **Atsijungti nuo Microsoft** papildomai pradeda
„Microsoft“ atsijungimą. Bendrame kompiuteryje rekomenduojama rinktis ir „Microsoft“ atsijungimą;
vien naršyklės užvėrimas nėra patikimas atsijungimo veiksmas.

### Mano veiklos užklausos {#veiklos-uzklausos}

<ChangelogNote version="v3.24" date="2026-10-26" title="Veiklos užklausos paskyros meniu ir Pradžioje">
Gautas veiklos užklausas ir jų būsenas galima peržiūrėti paskyros meniu, o laukiančias atsakymo – Pradžioje.
</ChangelogNote>

Paskyros meniu **Mano veiklos užklausos** (`/mano/profile/activity-requests`) rodo tau gautas užklausas apie institucijų veiklą ir posėdžių įrašų papildymą. Matoma institucija, laikotarpis, siuntėjas arba automatinis šaltinis, žinutė ir būsena: **Laukiame atsakymo**, **Atsakyta**, **Veikla patvirtinta** arba **Galiojimas baigėsi**. Pirmos rodomos neatsakytos galiojančios užklausos, tada naujausia istorija; **Rodyti daugiau** įkelia kitus įrašus.

Prie galiojančios neatsakytos užklausos **Atsakyti** atveria tą patį atsakymo puslapį kaip laiško nuoroda. Atsakymas išsaugomas tik jį patvirtinus. Užklausų istorija išlieka ir pasibaigus pareigoms; papildomų administravimo teisių jai nereikia. Kolegų atsakymai gali užbaigti užklausą – rodoma užbaigimo priežastis ir užfiksuoti rezultatai.

## Kas ką gali {#teises}

Kiekvienas prisijungęs narys peržiūri savo paskyrą bei prieigą ir keičia savo leidžiamus
kontaktinius duomenis. Kitų narių pareigos ir rolės tvarkomos per tam skirtus
[Organizacijos puslapius](/organizacija/), turint atitinkamą rolę.

## Techninė informacija {#technine-informacija}

- `ProfileController` visada naudoja prisijungusio naudotojo ID; prieigos suvestinę sudaro `GetUserAccessSummary`.
- Profilio laukai tikrinami `UpdateUserSettingsRequest`, slaptažodis – `UpdatePasswordRequest`.
- Profilio vardas, el. paštas ir slaptažodis nepriimami per bendrą profilio atnaujinimą;
  `ShowProfile.vue` vardo ir el. pašto laukus rodo užrakintus.
- `ProfileRolesTest` tikrina pareigų būsenas, paskutinės dienos įskaitymą, roles ir kitų
  narių duomenų neįtraukimą. Sąsajos testai tikrina laukus, prieigos vaizdą ir meniu veiksmus.
