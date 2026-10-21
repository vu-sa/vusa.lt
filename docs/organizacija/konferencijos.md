---
doc_status: reviewed
title: Konferencijos
area: mailingLists
models: [MailingList]
last_reviewed: 2026-10-18
tests:
  - tests/Feature/Admin/MailingLists/ResolveMailingListMembersTest.php
  - tests/Feature/Admin/MailingLists/SyncMailingListTest.php
  - tests/Feature/Admin/MailingLists/MailingListControllerTest.php
  - tests/Feature/Admin/MailingLists/MailingListDeploymentTest.php
  - tests/Feature/Admin/MailingLists/FetchMailingListArchiveTest.php
  - tests/Feature/Admin/MailingLists/ImportMailingListsTest.php
  - tests/Feature/Admin/MailingLists/MailingListQueueTest.php
  - tests/Feature/Admin/MailingLists/MailingListUserMergeTest.php
  - tests/Browser/MailingListPagesTest.php
  - resources/js/Features/Admin/MailingLists/__tests__/ShowMailingList.component.test.ts
  - resources/js/Features/Admin/MailingLists/__tests__/MailingListArchive.component.test.ts
  - resources/js/Features/Admin/MailingLists/__tests__/MailingListSourceSheet.component.test.ts
---

# Konferencijos

**Konferencijos** – tai VU SA el. pašto konferencijos (Mailman sąrašai) adresu `konf.vusa.lt`,
pvz., `taryba@konf.vusa.lt` ar `akad.etika@konf.vusa.lt`. Laiškas, išsiųstas konferencijos adresu,
pasiekia jos gavėjus pagal Mailman pristatymo nustatymus.

Platformoje matyti, kas gauna kiekvienos konferencijos laiškus ir kodėl, o automatiškai valdomose
konferencijose gavėjai keičiasi kartu su pareigybėmis: pasibaigus kadencijai žmogus iš konferencijos
pašalinamas, o naujai paskirtas – pridedamas.

Skiltis pasiekiama adresu `/mano/mailingLists` (Organizacija → Konferencijos).

<ChangelogNote version="v3.7" date="2026-10-18" title="El. pašto konferencijos">

Konferencijos, jų gavėjų šaltiniai ir laiškų archyvai pasiekiami Organizacijos srityje.

</ChangelogNote>

## Susitarimai

- Automatiškai valdomos konferencijos gavėjus keičia tik platforma. Žmogus, kurio reikia, bet kurio
  nepaaiškina jokia pareigybė, pridedamas rankiniu būdu su pastaba, kodėl jis konferencijoje.
- Pakeitimai Mailman administravimo puslapyje automatiškai valdomai konferencijai neišlieka:
  kitos sinchronizacijos metu jie atstatomi pagal šaltinius.
- Konferencijos, kurių nariai nėra platformoje (alumni, veteranai, nariai, pirmakursiai), lieka
  valdomos rankiniu būdu.

## Kaip tai veikia

### Valdymo būdai {#valdymas}

| Valdymas | Kas nustato gavėjus | Ką rodo platforma |
|---|---|---|
| **Rankinis valdymas** | Mailman administratoriai | Gavėjus, statistiką ir archyvą; Mailman niekas nekeičiama |
| **Automatinis valdymas** | Konferencijos šaltiniai platformoje | Gavėjus su priežastimis, skirtumus su Mailman ir archyvą |

Iš Mailman įkeltos naujos konferencijos platformoje atsiranda kaip rankiniu būdu valdomos. Automatinis valdymas
įjungiamas kiekvienai konferencijai atskirai, peržiūrėjus, kas pasikeis.

### Šaltiniai {#saltiniai}

Automatiškai valdomos konferencijos gavėjai – visų jos šaltinių sąjunga, atėmus išimtis.
Skaičiuojami tik šiandien galiojantys pareigybių laikotarpiai (pabaigos diena dar įskaičiuojama),
o neaktyvios pareigybės ir institucijos praleidžiamos.

| Šaltinis | Kas įtraukiama | Papildomi nustatymai |
|---|---|---|
| **Pareigybė** | Vienos pareigybės užimantieji arba jos paštas | Kuris adresas |
| **Pareigybės tipas** | Visos to tipo pareigybės, pvz., visi pirmininkai | Kuris adresas, padaliniai, potipiai |
| **Institucija** | Visų institucijos pareigybių užimantieji | Kuris adresas, tik tam tikri pareigybių tipai |
| **Institucijos tipas** | Visų to tipo aktyvių institucijų nariai | Kuris adresas, padaliniai, potipiai, išskiriami tipai, pareigybių tipai |
| **Atsakomybė** | Visos pareigybės, turinčios [atsakomybę](/pagrindai/atsakomybes) – tiesiogiai, per tipą ar instituciją, pvz., visi studentų atstovų koordinatoriai | Kuris adresas, padaliniai |
| **Kita konferencija** | Kitos konferencijos gavėjai (pvz., `bt` = biuras + taryba) | – |
| **El. paštas** | Vienas adresas rankiniu būdu | Vardas, pastaba, galiojimo data |

Pavyzdžiui, konferenciją `centriniai` sudaro institucijos tipas *Studentų atstovų organas* su
potipiais, tik centriniame padalinyje, išskyrus tipą *VU SA darinys*. Konferenciją visiems padalinių
koordinatoriams patogiausia sudaryti iš atsakomybės *Studentų atstovų koordinavimas* šaltinio.

Atsakomybės, pagal kurią sudaroma konferencija, ištrinti negalima.

<DocScreenshot name="mailing-list-sources" alt="Konferencijos gavėjų šaltiniai ir išimtys" caption="Gavėjų šaltiniai ir išimtys tvarkomi konferencijos puslapyje." />

Kita automatiškai valdoma konferencija įtraukiama su jos išimtimis. Rankiniu būdu valdoma
konferencija įtraukiama pagal paskutinį iš Mailman įkeltą gavėjų sąrašą. Padalinio koordinatorius
gali rinktis tik jam prieinamų padalinių šaltinius; tipų šaltiniai taip pat lieka šiose ribose.

### Kuris adresas {#adresas}

- **Asmeninis paštas** – pareigų laikotarpyje nurodytas el. paštas, o jei jo nėra, nario el. paštas.
- **Pareigybės paštas** – pareigybės dėžutė (pvz., `atstovai@mif.vusa.lt`). Keičiantis žmonėms adresas
  lieka tas pats. Jei pareigybė dėžutės neturi, imamas užimančiojo asmeninis paštas.

Jei tas pats žmogus į konferenciją patenka per kelias pareigybes, jis įtraukiamas vieną kartą, o
pareigų laikotarpyje nurodytas el. paštas turi pirmenybę prieš nario el. paštą.

Sujungus narių profilius, gavėjo ryšys su nariu ir šaltinių bei išimčių autorystė perkeliami į
paliekamą profilį. Automatiškai valdomoje konferencijoje el. pašto adresai perskaičiuojami per kitą
sinchronizaciją.

### Sinchronizacija {#sinchronizacija}

Automatiškai valdomų konferencijų atnaujinimas pradedamas praėjus penkioms minutėms po pareigybės
laikotarpio sukūrimo, datų ar el. pašto pakeitimo arba ištrynimo. Gavėjai taip pat perskaičiuojami
pakeitus pareigybės paštą, aktyvumą, instituciją ar tipus, nario el. paštą ir institucijos aktyvumą
ar tipus. Keli greta atlikti pakeitimai sujungiami į vieną atnaujinimą; eilėje jis gali užtrukti ilgiau.
Kiekvieną naktį gavėjai perskaičiuojami ir be pakeitimų platformoje, kad įsigaliotų pareigų datos.
Gavėjai pridedami ir šalinami be pasisveikinimo ar atsisveikinimo laiškų.

Jei sinchronizacija pašalintų daugiau nei pusę konferencijos gavėjų arba nepaliktų nė vieno, ji sustoja su būsena
**Laukia peržiūros**. Paspaudus **Sinchronizuoti**, reikia atskirai patvirtinti peržiūrėtus skirtumus.
Peržiūra galioja 15 minučių; jei gavėjai pasikeičia, prieš tvirtinant ją reikia įkelti iš naujo.
Nepavykus įjungti automatinio valdymo konferencija lieka valdoma rankiniu būdu.

### Archyvas {#archyvas}

Archyvo kortelėje rodomi konferencijai išsiųsti laiškai su priedais ir nuoroda į Mailman archyvą.
Archyvas atnaujinamas kas valandą. Visa istorija įkeliama dalimis: kol ji nebaigta, rodomas
įkėlimo progresas, o metų laiškų skaičius dar nerodomas. Vienu kartu rodoma 50 laiškų;
**Rodyti daugiau** įkelia senesnius. Laiško tekstas išskleidžiamas paspaudus jo antraštę.

Archyvą mato konferenciją tvarkantys ir jos gavėjai. Gavėjai kol kas gali atverti tik tiesioginę
konferencijos nuorodą; atskiros skilties jų meniu dar nėra. Nuoroda į Mailman ar privatų priedą
gali paprašyti prisijungti prie Mailman.

## Veiksmai

### Įjungti automatinį valdymą {#ijungti}

1. Atsidaryk konferenciją ir kortelėje **Šaltiniai** pridėk šaltinius.
2. Kortelėje **Gavėjai** peržiūrėk skirtumus su Mailman. Prie žmonių, kurie būtų pašalinti, bet
   turi likti, spausk **Palikti rankiniu būdu**; prie nepageidaujamų naujų – **Neįtraukti**.
3. Spausk **Įjungti automatinį valdymą** ir patvirtink.

### Nauja konferencija {#nauja}

Naujos konferencijos į platformą įkeliamos iš Mailman. Centrinio biuro studentų atstovų
koordinatorius paspaudžia **Atnaujinti iš Mailman**. Įkeltos konferencijos iš pradžių valdomos
rankiniu būdu; automatinis valdymas įjungiamas peržiūrėjus šaltinius ir gavėjų skirtumus.

### Grąžinti rankinį valdymą {#rankinis}

Meniu ⋯ pasirink **Grąžinti rankinį valdymą**. Gavėjai lieka tokie, kokie yra, bet nebesikeičia
kartu su pareigybėmis.

## Kas ką gali {#kas-ka-gali}

| Veiksmas | „Studentų atstovų koordinatorius“ | „Centrinio biuro studentų atstovų koordinatorius“ | Konferencijos gavėjas |
|---|---|---|---|
| Matyti konferencijų sąrašą | Savo padalinio | Visas | – |
| Peržiūrėti konferenciją ir archyvą | Savo padalinio | Visas | Tik savo konferencijas |
| Keisti šaltinius ir valdymą | Savo padalinio | Visas | – |
| Atnaujinti visas konferencijas iš Mailman | – | Taip | – |

## Techninė informacija

- Leidimai: `mailingLists.{read,create,update}.{padalinys,*}`. Gavėjo prieiga tikrinama
  `MailingListPolicy::view()` pagal paskutinį žinomą gavėjų sąrašą.
- Gavėjus apskaičiuoja `App\Actions\ResolveMailingListMembers`, sinchronizuoja
  `App\Actions\SyncMailingList` per Mailman REST API (`config/services.php` → `mailman`).
- Komandos: `mailing-lists:sync` (kas naktį), `mailing-lists:import` (kas naktį),
  `mailing-lists:fetch-archives` (kas valandą). Bandomojoje aplinkoje Mailman niekas nekeičiama.
