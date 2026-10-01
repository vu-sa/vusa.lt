---
doc_status: reviewed
last_reviewed: 2026-10-01
tests:
  - tests/Feature/Api/Admin/ContentEditorTest.php
  - tests/Feature/Admin/Content/NewsControllerTest.php
  - tests/Browser/RichContentFullscreenEditorTest.php
  - tests/Feature/Admin/Content/NewsTagsTest.php
title: Naujienos
area: news
models: [News]
---

# Naujienos

Naujienos skirtos organizacijos ir padalinių veiklų ataskaitoms, pranešimams spaudai, studentams svarbioms žinioms ir svarbiems įvykiams viešinti. Kiekvienas padalinys skelbia savo naujienas savo svetainės dalyje, o bendras VU SA naujienas kuria centrinis biuras. Naują naujieną galima greitai sukurti bet kurioje sistemos vietoje per viršutinį meniu **+ Sukurti → Nauja naujiena**.

Skiltis pasiekiama adresu `/mano/news`.

## Kaip tai veikia

### Naujienos duomenys ir skelbimo laikas

Nuo įprastų [puslapių](/svetaine/puslapiai) naujienos skiriasi keliais esminiais bruožais:

- **Paskelbimo laikas (`publish_time`):** kiekviena naujiena turi nustatytą datą ir valandą. Jei nurodai ateities laiką, naujiena tampa „suplanuota“ – jos tiesioginė nuoroda jau veikia, tačiau viešuose naujienų srautuose, pradiniame puslapyje ir paieškoje ji pasirodys tik sulaukus nurodyto laiko.
- **Trumpas įvadas:** įvadiniame tekste leidžiama iki 200 matomų simbolių. Šis tekstas pateikiamas naujienų kortelėse, socialinių tinklų peržiūrose ir paieškos rezultatuose.
- **Viršelis:** reprezentacinis vaizdas, rodomas sąrašuose ir straipsnio viršuje.
- **Žymos:** teminės etiketės, leidžiančios naujienai atsidurti atitinkamuose [Temos puslapiuose](/svetaine/zymos#temos-puslapis).

### Būsenos

| Būsena | Reikšmė | Matomumas |
|---|---|---|
| **Juodraštis** (`draft`) | Straipsnis ruošiamas arba koreguojamas. | Matomas tik administravimo aplinkoje redaktoriams. |
| **Paskelbta** (`published`) | Straipsnis paruoštas skaitytojams. | Pasiekiamas viešai. Jei skelbimo laikas praeityje – rodomas sraute iškart; jei ateityje – suplanuotas. |

## Turinio redagavimas {#turinio-redagavimas}

Naujienos turinio blokai redaguojami taip pat kaip [puslapiuose](/svetaine/puslapiai#turinio-redagavimas): telefone prieš įterpdamas bloką matai jo peržiūrą, o nuotraukų ir tvarkaraščio papildomi nustatymai atsidaro tame pačiame redaktoriuje.

## Išsaugojimas ir paskelbimas {#issaugojimas}

<ChangelogNote version="v2.34" date="2026-10-01" title="Patikimesnis išsaugojimas" />

**Juodraštis** matomas tik sistemoje. Pasirinkęs **Paskelbta** ir išsaugojęs, naujieną padarai pasiekiamą pagal viešą nuorodą. Jei paskelbimo laikas ateityje, paieškoje ir naujienų sąrašuose ji pasirodys nuo pasirinkto laiko; pati nuoroda jau veikia.

Įvadiniame tekste gali būti iki 200 matomų simbolių. Formatavimo žymos į šį skaičių neįtraukiamos. Senesnį ilgesnį įvadą gali palikti nepakeistą; pakeistas įvadas turi tilpti į ribą. Viso ekrano **Išsaugoti** išsaugo ir likusius naujienos laukus.

## Atkūrimo kopijos ir kalbos {#atkurimas}

Redaguojant saugomos privačios įrenginio bei serverio atkūrimo kopijos. Jos nepaskelbia naujienos. Grįžęs į formą pasirink **Atkurti kopiją**; jei išsaugota versija pasikeitė, **Peržiūrėti dabartinę versiją** leidžia pasirinkti, kurią kopiją tęsti. [Atkūrimo eiga](/svetaine/puslapiai#atkurimas) vienoda puslapiams ir naujienoms.

**Palyginti ir redaguoti** atveria abi kalbas greta, o telefone leidžia persijungti. Kiekviena versija turi savo įvadą, svarbiausius punktus, paskelbimo laiką ir išsaugojimą. **Sukurti versiją kita kalba** atveria tuščią juodraštį, į kurį gali nukopijuoti dabartinį turinį; išsaugojus versijos susiejamos. [Kalbų versijų eiga](/svetaine/puslapiai#kalbu-versijos).

## Rekomendacijos {#susitarimai}

- **Glaustas įvadas:** trumpai įvardyk svarbiausią žinią. Jei aktualu, atsakyk, kas, kur ir kada vyksta. Įvadas turi tilpti į [200 matomų simbolių ribą](#issaugojimas).
- **Įskaitomas viršelis:** rinkis aiškią nuotrauką ir patikrink jos apkarpymą. Plakatą su smulkiu tekstu geriau pateikti straipsnyje, o svarbiausią informaciją pakartoti tekstu.
- **Struktūra:** ilgą straipsnį skaidyk trumpomis pastraipomis ir prasmingomis paantraštėmis. Paryškink tik svarbiausias mintis.

## Veiksmai

### Naujienų sąrašas ir paieška

Naujienų sąraše (`/mano/news`) gali ieškoti pagal pavadinimą:

- **Greitieji filtrai:** leidžia vienu paspaudimu atsirinkti paskelbtas naujienas arba juodraščius.
- **Rūšiavimas:** pagal paskelbimo datą, atnaujinimo laiką ar pavadinimą.

### Naujos naujienos kūrimas

Pradėti naują naujieną gali dviem būdais:

1. Viršutinėje sistemos juostoje spausk **+ Sukurti → Nauja naujiena** (greitasis veiksmas iš bet kurio puslapio).
2. Naujienų sąraše (`/mano/news`) spausk **Nauja naujiena** (arba eik adresu `/mano/news/create`).

Formoje:

- Pasirink padalinį.
- Įrašyk pavadinimą ir trumpą įvadą (iki 200 simbolių).
- Nurodyk publikavimo datą ir laiką.
- Įkelk viršelio nuotrauką.
- Priskirk aktualias žymas.
- Atverk turinio redaktorių ir parašyk tekstą.
- Nustatyk būseną („Paskelbta“ arba „Juodraštis“) ir spausk **Išsaugoti**.

### Naujienos dubliavimas

Norėdamas sukurti panašią naujieną (pvz., kitos savaitės ataskaitą):

- Naujienų sąrašo veiksmų meniu pasirink **Dubliuoti** (`news.duplicate`).
- Sukuriama naujienos kopija su prierašu „(kopija)“.
- Pakeisk informaciją, datą ir išsaugok.

### Veiksmai su keliais įrašais

Pažymėjęs kelias naujienas varnelėmis, viršutinėje juostoje gali atlikti grupinius veiksmus:

- **Keisti būseną:** pakeisti pažymėtų naujienų būseną į „Paskelbta“ arba „Juodraštis“.
- **Ištrinti:** perkelti pažymėtas naujienas į šiukšlinę.

### Šalinimas ir atkūrimas

- **Perkėlimas į šiukšlinę:** veiksmų meniu pasirink **Ištrinti**. Naujiena paslepiama iš viešos svetainės.
- **Atkūrimas:** šiukšlinės rodinyje pasirink **Atkurti**.
- **Ištrinti visam laikui:** pašalina įrašą ir jo turinio blokus negrįžtamai.

## Kas ką gali {#teises}

| Veiksmas | Narys be rolės | Komunikacijos koordinatorius | Centrinio biuro komunikacijos koordinatorius |
|---|---|---|---|
| Matyti naujienų sąrašą | – | ✓ (savo padalinio) | ✓ (visus padalinius) |
| Kurti naujienas | – | ✓ (savo padaliniui) | ✓ (visiems) |
| Redaguoti, dubliuoti, skelbti | – | ✓ (savo padalinio) | ✓ (visų) |
| Susieti kalbų versijas | – | ✓ (jei gali redaguoti abu įrašus) | ✓ |
| Trinti į šiukšlinę / atkurti | – | ✓ (savo padalinio) | ✓ (visų) |
| Ištrinti visam laikui | – | ✓ (savo padalinio) | ✓ (visų) |

::: info Kalbų versijų redagavimas
Susiedamas kalbų versijas turi turėti teisę redaguoti abu susiejamus įrašus ir kitus įrašus, kurių ryšys būtų pakeistas.
:::

## Pranešimai ir automatizavimas {#pranesimai}

- **Suplanuotas paskelbimas:** suplanavus naujieną ateities datai, sistema automatiškai pradeda ją rodyti naujienų srautuose nuo nurodytos minutės be jokio papildomo rankinio veiksmo.
- **Kanoniniai nukreipimai:** pakeitus naujienos pavadinimą ar skelbimo metus, ankstesnė nuoroda lieka galioti ir automatiškai nukreipia į naująjį adresą.
- **Automatinis juodraščio saugojimas:** privačios kopijos serveryje ir naršyklėje padeda tęsti nebaigtą darbą. Jei kopijos išsaugoti nepavyksta, formoje rodomas perspėjimas.

## Techninė informacija {#technine-informacija}

Šis skyrius skirtas administratoriams ir sistemos priežiūrai.

### Teisės

- Prieigą tikrina `NewsPolicy`.
- Leidimai:
  - Padalinio teisės: `news.create.padalinys`, `news.read.padalinys`, `news.update.padalinys`, `news.delete.padalinys`.
  - Centrinio biuro teisės: `news.create.*`, `news.read.*`, `news.update.*`, `news.delete.*`.

### Kaip tai įgyvendinta

- Modelis: `App\Models\News`, naudojantis `SoftDeletes`.
- Valdiklis: `App\Http\Controllers\Admin\NewsController`.
- Dubliavimas: `App\Actions\DuplicateNewsAction` sukuria naujienos kopiją su visais blokais ir žymomis.
- Masiniai veiksmai: `NewsController::bulkUpdateStatus()` (`news.bulkStatus`) ir `bulkDestroy()` (`news.bulkDestroy`).
- Paieška: Typesense indeksas su atitinkamu padalinio filtru.
- Išsaugojimas tikrina įrašo versiją ir vienu veiksmu per `ContentEditorService` atnaujina naujieną, turinio blokus, žymas bei kalbų ryšius.
