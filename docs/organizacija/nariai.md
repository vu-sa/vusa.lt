---
doc_status: reviewed
title: Nariai
area: users
models: [User]
last_reviewed: 2026-10-04
tests:
  - resources/js/Utils/__tests__/String.test.ts
  - tests/Feature/Search/ContactSearchIndexSyncTest.php
  - tests/Feature/Admin/Management/UserControllerTest.php
  - tests/Feature/Admin/UserMergeTest.php
  - tests/Feature/Auth/UserUpdateAuthorizationTest.php
  - tests/Feature/Permissions/UserSelfLockoutTest.php
  - tests/Feature/Permissions/UserDestructiveAuthorizationTest.php
  - tests/Feature/Permissions/UserIdentityAuthorizationTest.php
  - tests/Feature/Permissions/UnclaimedUserVisibilityTest.php
  - tests/Feature/Api/Admin/UserSearchApiControllerTest.php
  - resources/js/Pages/Admin/People/__tests__/ShowUser.component.test.ts
  - resources/js/Components/AdminForms/__tests__/UserForm.component.test.ts
  - resources/js/Components/AdminForms/__tests__/DuplicateUserWarning.component.test.ts
  - resources/js/Composables/__tests__/useDuplicateUserCheck.test.ts
---

# Nariai

**Nariai** – tai VU SA bendruomenės nariai, studentų atstovai ir kiti asmenys, turintys paskyras platformoje
Mano VU SA. Kiekvienas narys turi profilį, kuriame matomi jo pareigybių laikotarpiai, užduotys ir pranešimai.

Narių sąrašas pasiekiamas adresu `/mano/users`, o kiekvieno nario profilio kortelė atveriama adresu
`/mano/users/{id}`.

## Rekomendacijos {#rekomendacijos}

::: tip
- **Naudok esamą paskyrą:** prieš kurdamas narį paieškok jo sąraše. Pradėjus eiti kitas pareigas ar perėjus į kitą padalinį, esamai paskyrai priskirk naują pareigybės laikotarpį.
- **Asmeninis el. paštas:** prisijungimui rinkis paties nario naudojamą adresą, pvz., studentinį `@stud.vu.lt`. Pareigybės institucinį adresą nurodyk prie pareigybės, kad pasikeitus ją einančiam žmogui paskyros istorija liktų susieta su tuo pačiu asmeniu.
- **Užbaik laikotarpį:** baigus eiti pareigas, paprastai pakanka užbaigti [pareigybės laikotarpį](/visak/pareigybiu-laikotarpiai). Vien dėl kadencijos pabaigos paskyros trinti nereikia.
:::

## Kaip tai veikia

### Tapatybė ir prisijungimas {#tapatybe}

Nario paskyrą sudaro:

- **Vardas ir pavardė** – asmens vardas, rodomas visoje platformoje ir viešoje svetainėje.
- **Prisijungimo el. paštas** – unikalus naudotojo el. pašto adresas, pagal kurį atpažįstama tapatybė jungiantis per bendrą universiteto prisijungimą (Microsoft OAuth) arba slaptažodžiu.
- **Kontaktiniai duomenys** – telefonas, nuotrauka, Facebook nuoroda ir įvardžiai.

::: tip Studentinis el. paštas
Prisijungimo el. paštui rekomenduojama naudoti asmeninį studentinį adresą (pvz., `@stud.vu.lt`),
o ne pareigybinį `@vusa.lt`. Pareigybiniai el. paštai keičiantis kadencijoms perduodami kitiems nariams,
todėl asmeninė paskyros istorija turi likti susieta su konkrečiu žmogumi.
:::

### Teisės ir padaliniai per pareigybes {#padaliniai-ir-teises}

1. **Prieiga suteikiama per pareigybes.** Naudotojas pats savaime dažniausiai neturi jokių administravimo
   teisių (bazinė nario prieiga leidžia matyti savo užduotis, pranešimus ir teikti rezervacijas).
   Papildomos teisės paprastai gaunamos per [pareigybes](/organizacija/pareigybes) ir joms priskirtas roles;
   superadministratorius gali priskirti rolę tiesiogiai paskyrai.
2. **Padalinys nustatomas iš pareigybių.** Platformoje narys nepriklauso padaliniui tiesioginiu lauku – nario padaliniai
   nustatomi pagal jo einamas pareigybes (`User::tenants()`).
3. **Nariai be padalinio.** Jei asmuo šiuo metu neturi jokios aktyvios pareigybės, sąraše prie jo rodoma
   būsena **Be padalinio**. Tokį narį sąraše mato tik administratoriai, turintys visuotinę prieigą arba sukūrę šį narį, kol jam nepriskirta konkretaus padalinio pareigybė.

### Tapatybės ir prieigos apsauga {#apsauga}

Siekiant apsaugoti narių paskyras nuo netyčinio ar piktavališko perėmimo, sistemoje galioja griežtos taisyklės:

- **Vardo keitimas**: kito nario vardą ir pavardę narių administravime gali pakeisti tik
  Superadministratorius. Pats narys savo vardą gali pataisyti **vieną kartą** savo paskyros formoje;
  vėliau laukelis užrakinamas ir keisti reikia kreiptis į administratorių
  ([Paskyra ir prieiga](/mano/paskyra-ir-prieiga#profilis)).
- **El. pašto keitimas**: prisijungimo el. paštas savo paskyros formoje užrakintas. Jį keičia
  Superadministratorius arba atitinkamą nario administravimo prieigą turintis koordinatorius. Padalinio koordinatorius kito nario el. paštą gali pakeisti tik tuo atveju,
  jei visi šio nario padaliniai (įskaitant tuos, kuriuose jis ėjo pareigas anksčiau) įeina į koordinatoriaus administruojamus
  padalinius ir narys neturi tiesioginių administratoriaus rolių. Jei narys kada nors ėjo pareigas kitame padalinyje,
  laukelis rodomas su spyna ir paaiškinimu, kad tapatybė apsaugota.
- **Savęs užsirakinimo apsauga**: jei administratorius bando redaguoti savo paties profilį taip, kad
  prarastų kurią nors savo rolę, sistema nieko neišsaugo, parodo perspėjimą apie prieigos pasikeitimą ir
  laukia aiškaus patvirtinimo. Patvirtinus pakeitimas išsaugomas ir atveriama Pradžia. Superadministratorius
  perspėjamas tik tada, kai šalina savo Super Admin rolę.
- **Savęs trynimo blokavimas**: administratorius negali ištrinti savo paties paskyros.

## Veiksmai

### Narių sąrašas ir filtrai {#sarasas}

Narių sąraše (`/mano/users`) pateikiama visų pasiekiamų narių suvestinė:

- **Paieška**: galima ieškoti pagal vardą, pavardę, el. pašto adresą ar telefono numerį.
- **Rikiavimas**: galima rikiuoti pagal vardą (A–Z arba Z–A), paskutinį prisijungimą arba sukūrimo datą.
- **Filtrai**:
  - *Suplanuotos pareigybės* – atrenka narius, kuriems priskirti būsimi pareigybių laikotarpiai (kurių pradžios data yra ateityje).
- **Šiukšlinė**: viršuje rodo ištrintų narių skaičių ir leidžia peržiūrėti bei atkurti pašalintas paskyras.

### Nario profilio kortelė {#kortele}

Paspaudus nario sąraše atveriamas nario profilis (`/mano/users/{id}`). Viršuje pateikiama esminė informacija:
kontaktinis el. paštas, telefonas, paskutinio veiksmo data ir prisijungimo slaptažodžio būsena.

Profilis suskirstytas į skirtukus:

<DocScreenshot name="user-record" alt="Nario puslapis: el. paštas, telefonas, dabartinės ir buvusios pareigos su datomis" caption="Narys, kuris anksčiau buvo parlamento narys, o dabar eina pirmininko pareigas." />

1. **Pareigos**:
   - *Dabartinės pareigos* – aktyvūs pareigybių laikotarpiai, institucija, kadencijos pradžia ir pabaiga.
   - *Būsimos pareigos* – pareigos, kurios prasidės ateityje.
   - *Buvusios pareigos* – visa istorinė asmens veikla VU SA.
   - Mygtukas **Pridėti pareigybę** atidaro šoninį pareigybės priskyrimo langą (pasirenkama pareigybė, kadencijos datos ir studijų programa).
2. **Rolės**:
   - Rodomos tiesiogiai paskyrai priskirtos rolės (jei tokių yra). Kasdieniame darbe rolės gaunamos per pareigybes, todėl čia paprastai rodoma „Rolių nėra – prieiga suteikiama per pareigybes“. Tiesiogines roles keisti gali tik Superadministratorius.
3. **Užduotys**:
   - Visi nariui priskirti darbai, jų terminai ir atlikimo būsenos (`TaskManager`).
Pareigybių pavadinimai visose trijose grupėse pritaikomi pagal nario įvardžius, o jų nesant –
pagal atpažįstamą vardo galūnę. Laikotarpio parinktis išlaikyti originalų pavadinimą turi pirmenybę.
[Galūnių taisyklės](/organizacija/pareigybes#lytis-ir-dublikatai) taip pat taikomos paieškai ir viešiems kontaktams.

Nario įrašo pakeitimų istoriją peržiūrėk veiklos žurnalo lange; tai nėra atskiras profilio skirtukas.

Per meniu **⋯** profilio viršuje pasiekiami veiksmai: **Redaguoti**, laikotarpių tvarkyklė,
**Generuoti naują slaptažodį** (tik Superadministratoriui) ir **Ištrinti narį**.
**Pridėti pareigybę** yra pagrindinis profilio veiksmas.

### Naujo nario sukūrimas {#kurimas}

Naujas narys kuriamas paspaudus **Naujas narys (-ė)** sąrašo viršuje (`/mano/users/create`):

1. **Vardas ir pavardė** – įrašyk tikslų asmens vardą ir pavardę.
2. **El. paštas** – įrašyk asmeninį studentinį adresą. Sistema automatiškai tikrina dublikatus:
   jei duomenų bazėje jau yra narys su panašiu vardu ar el. paštu, formoje pasirodo perspėjimas
   `DuplicateUserWarning` su nuoroda į esamą profilį.
3. **Pareigybės priskyrimas** – kuriant profilį privaloma iškart priskirti bent vieną pareigybę iš savo
   administruojamų padalinių. Kitas pareigybes galėsi pridėti vėliau nario kortelėje.

::: warning Pakvietimai nesiunčiami
Naujo nario profilio sukūrimas **nesiunčia** automatinių el. laiškų ar prisijungimo pakvietimų.
Narys prie sistemos jungiasi savarankiškai per Microsoft OAuth su savo VU el. paštu.
:::

### Nario duomenų redagavimas {#redagavimas}

Atidarius formą per **Redaguoti narį** (`/mano/users/{id}/edit`):

- **Telefonas** ir **Facebook nuoroda** – kontaktinė informacija, matoma viešuose kontaktuose.
- **Profilio nuotrauka** – įkeliama asmens nuotrauka, kuriai galima nustatyti fokusavimo tašką
  (kad apvaliuose kadruose veidas nebūtų nukirptas).
- **Įvardžiai** – galima nurodyti asmens įvardžius (pvz., jis / jo, ji / jos) ir pažymėti varnelę
  **Rodyti įvardžius**, jei narys nori, kad jie būtų viešai matomi prie jo profilio.

### Narių sujungimas {#sujungimas}

Jei tas pats asmuo užsiregistravo kelis kartus (pavyzdžiui, vieną kartą su `@stud.vu.lt`, o kitą – su asmeniniu
`@gmail.com` adresu), šias paskyras galima sujungti į vieną:

1. Narių sąraše viršuje spausk **Sujungti narius** (įsijungia kelių įrašų žymėjimo režimas).
2. Pažymėk bent du narius, kuriuos nori sujungti.
3. Apačioje paspausk mygtuką **Sujungti** – atsidarys sujungimo langas.
4. Pasirink, kurį profilį **pasilikti kaip pagrindinį**.
5. Patvirtink sujungimą:
   - Visi pareigybių laikotarpiai, užduotys, komentarai ir rezervacijos iš naikinamų paskyrų perkeliami į pagrindinį profilį.
   - Prijungiamos paskyros perkeliamos į šiukšlinę.
   - Prisijungimo tapatybė lieka pagrindinio nario el. pašto adresas.

### Šiukšlinė ir galutinis trynimas {#trynimas}

- Pasirinkus **Ištrinti narį**, profilis perkeliamas į šiukšlinę. Narys nebegali prisijungti prie platformos, tačiau jo buvusi veiklos istorija išlieka.
- Ištrintą narį galima bet kada **atkurti** šiukšlinės rodinyje paspaudus **Atkurti**.
- **Trynimas visam laikui yra blokuojamas**, jei narys sistemoje yra palikęs komentarų, patikrinimų
  posėdžiuose (`check_ins`) arba pranešęs problemų. Tai apsaugo nuo susijusių duomenų praradimo
  ir duomenų bazės ryšių klaidų.

## Kas ką gali {#teises}

| Veiksmas | Narys | Komunikacijos koordinatorius | Studentų atstovų koordinatorius | Centrinio biuro koordinatoriai | Superadministratorius |
|---|---|---|---|---|---|
| Matyti savo profilį | ✓ | ✓ | ✓ | ✓ | ✓ |
| Matyti padalinio narius | – | ✓ | ✓ | ✓, visų padalinių | ✓ |
| Sukurti naują narį | – | ✓ | ✓ | ✓ | ✓ |
| Redaguoti nario kontaktus ir nuotrauką | tik savo | ✓, savo padalinio | ✓, savo padalinio | ✓ | ✓ |
| Keisti nario el. paštą | – | ✓, tik jei narys priklauso tik šiam padaliniui | ✓, tik jei narys priklauso tik šiam padaliniui | ✓ | ✓ |
| Keisti nario vardą ir pavardę | – | – | – | – | ✓ |
| Priskirti padalinio pareigybes | – | ✓ | ✓ | ✓ | ✓ |
| Sujungti narius | – | – | – | ✓ | ✓ |
| Keisti tiesiogines roles | – | – | – | – | ✓ |
| Generuoti / trinti slaptažodį | – | – | – | – | ✓ |
| Ištrinti narį į šiukšlinę | – | ✓, tik savo padaliniui priklausantį narį | ✓, tik savo padaliniui priklausantį narį | ✓ | ✓ |
| Ištrinti narį visam laikui (jei nėra blokatorių) | – | – | – | – | ✓ |

Centrinio biuro koordinatoriai lentelėje – **Centrinio biuro komunikacijos koordinatorius** ir
**Centrinio biuro studentų atstovų koordinatorius**.

::: warning Bendrų narių apsauga
Jei narys praeityje ar šiuo metu turi pareigybių keliuose padaliniuose, vieno padalinio koordinatorius
negali pakeisti jo prisijungimo el. pašto ar ištrinti paskyros. Tokiems veiksmams reikalinga visų
nario padalinių prieiga arba kreipimasis į Centrinio biuro koordinatorių / Superadministratorių.
:::

## Pranešimai ir automatizavimas {#pranesimai}

| Kada | Kas nutinka | Kas gauna |
|---|---|---|
| Paskyrai priskiriamas pareigybės laikotarpis | Narys įgyja pareigybės roles ir prieigos teises | Paskirtas narys |
| Baigiasi pareigybės laikotarpio pabaigos data | Prieiga iš šio laikotarpio nustoja galioti | Buvęs narys |
| Sukuriamas naujas narys | Įrašomi duomenys; **pranešimai ar el. laiškai nesiunčiami** | Niekas |
| Sukuriamas slaptažodis | Slaptažodis parodomas vieną kartą ekrane administratoriui; el. paštu nesiunčiamas | Administratorius |
| Nariui paskiriama užduotis | Narys gauna vidinį pranešimą ir užduotį darbų sąraše | Narys |

## Techninė informacija {#technine-informacija}

Šis skyrius skirtas tiems, kurie tvarko roles ar tikrina sistemos elgseną.

### Teisės

- Narių administravimą reglamentuoja `UserPolicy`.
- `users.read.*` leidžia matyti visus sistemos narius; `users.read.padalinys` riboja sąrašą iki
  naudotojo atstovaujamo padalinio narių (nariai, turintys bent vieną pareigybę tame padalinyje,
  arba nariai be padalinių, kuriuos sukūrė tas pats padalinys).
- Vardo keitimas tikrinamas per `UserPolicy::updateName`, leidžiamas tik turintiems `isSuperAdmin()`.
- Tapatybės (el. pašto) keitimas tikrinamas per `UserPolicy::updateIdentity`: reikalauja `users.update.*`
  arba visiško padalinių apėmimo (`tenantsContained`) be tiesioginių rolių (`!isProtected`).
- Sujungimas (`users.mergeUsers`) reikalauja `users.update.*` leidimo (`UserPolicy::merge`).
- Slaptažodžių generavimas ir šalinimas galimas tik Superadministratoriui (`GenerateUserPasswordRequest`).

### Kaip tai įgyvendinta

- Modelis `User` naudoja `HasRoles` (Spatie), `SoftDeletes`, `LogsModelActivity`, `Searchable` (Laravel Scout) ir `GuardsForceDeleteWhenReferenced`.
- Užklausų filtravimą valdo `BuildUserIndexQuery`: sujungia filtrus pagal padalinius, atsižvelgia į
  pareigybių laikotarpius ir specialųjį filtrą `future_duty`.
- Narių sujungimą atlieka `MergeUsers` veiksmas: transakcijoje perkelia `dutiables`, polimorfinius komentarus,
  užduotis, rezervacijas, o prijungiamus įrašus pažymi ištrintais (`delete()`).
- Galutinio trynimo apsauga `forceDeleteBlockedReason` tikrina išorinius ryšius: `Comment` (`user_id`),
  `InstitutionCheckIn` (`user_id`) ir `Problem` (`created_by`).
- Savęs užsirakinimo apsaugą užtikrina `AdminController::guardSelfLockout` kartu su `UserPolicy::canActDestructively`
  (draudžiama trinti save).
