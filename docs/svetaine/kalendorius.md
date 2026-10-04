---
doc_status: reviewed
title: Kalendorius
area: calendar
models: [Calendar]
last_reviewed: 2026-10-01
tests:
  - tests/Feature/Admin/Calendar/CalendarControllerTest.php
  - tests/Feature/Public/CalendarCanonicalUrlTest.php
  - tests/Feature/Public/CalendarEventPageTest.php
  - tests/Feature/Meetings/MeetingCalendarEventTest.php
  - tests/Unit/Actions/DuplicateCalendarActionTest.php
  - resources/js/Components/AdminForms/__tests__/CalendarForm.component.test.ts
  - resources/js/Pages/Public/__tests__/CalendarEvent.component.test.ts
---

# Kalendorius

Kalendorius skirtas VU SA, padalinių ir studentų programų, klubų bei projektų (PKP) renginiams, posėdžiams, mokymams ir kitoms studentams svarbioms datoms skelbti. Renginiai rodomi VU SA pradiniame puslapyje, viešame kalendoriuje ir atskiruose kiekvieno renginio puslapiuose.

Skiltis pasiekiama adresu `/mano/calendar`.

## Rekomendacijos {#rekomendacijos}

::: tip
- **Kalendorius ar naujiena:** konkrečiu laiku vykstančią veiklą skelbk kalendoriuje, o bendro pobūdžio pranešimą – [naujienose](/svetaine/naujienos).
- **Kalbos:** jei renginys skirtas ir tarptautiniams studentams, pateik pavadinimą, vietą bei aprašymą anglų kalba.
- **Tiksli vieta:** nurodyk adresą ir auditoriją, o nuotoliniam renginiui pridėk prisijungimo nuorodą.
- **Įskaitomas viršelis:** patikrink, kaip paveikslėlis atrodo telefone. Svarbią informaciją pateik ir aprašyme, kad jos nereikėtų skaityti vien iš plakato.
:::

## Kaip tai veikia

### Viešas kalendorius ir iCal prenumerata

- **Svetainės kalendorius:** visi paskelbti renginiai pasiekiami adresu `/lt/kalendorius` (arba anglų k. `/en/calendar`), o padalinio lankytojams – atitinkamame padalinio polapyje (pvz., `mif.vusa.lt/lt/kalendorius`). Kiekvienas renginys turi kanoninį puslapį su nuoroda pavidalu `/kalendorius/{metai}/{permalink}`.
- **iCal kalendoriaus prenumerata:** adresu `/kalendorius/ics` veikia atviras iCal srautas. Studentai ir organizatoriai gali įsikelti šią nuorodą į savo asmeninius kalendorius („Google Calendar“, „Apple Calendar“, „Outlook“), ir renginiai sinchronizuosis automatiškai.
- **Pradinis puslapis:** artimiausi renginiai automatiškai atrenkami ir pateikiami pradinio puslapio bloke.

### Renginio duomenys ir ryšiai

Kiekvienas kalendoriaus įrašas susideda iš:

- **Dvikalbių tekstų:** pavadinimo, aprašymo ir vietos lietuvių bei anglų kalbomis.
- **Laiko ribų:** pradžios datos bei laiko ir neprivalomo pabaigos laiko.
- **Renginio tipo:** kategorijos (pvz., „Mokymai“, „Šventė“, „Posėdis“), leidžiančios lankytojams filtruoti kalendorių.
- **Žymų:** teminių etikečių, siejančių renginį su kitomis naujienomis ir puslapiais.
- **Vaizdų:** pagrindinio viršelio paveikslėlio ir papildomų nuotraukų galerijos.
- **Priklausomybės padaliniui:** renginį skelbiančio VU SA padalinio arba centrinio biuro.

## Veiksmai

### Renginių sąrašas ir paieška

Sąraše (`/mano/calendar`) pateikiami visi tavo pasiekiami renginiai:

- **Filtravimas:** gali filtruoti pagal padalinį, renginio tipą, datą arba ieškoti renginio pagal pavadinimą.
- **Būsenos rodikliai:** matoma, ar renginys aktyvus, kokiam padaliniui priklauso ir kokia jo data.
- **Peržiūra:** paspaudęs renginio pavadinimo iškart atidarai jo redagavimo langą.

### Naujo renginio kūrimas

Viršuje spausk **Naujas renginys** (arba eik adresu `/mano/calendar/create`):

1. **Padalinys** – jei turi teises keliuose padaliniuose, pasirink, kurio padalinio vardu skelbiamas renginys.
2. **Pavadinimas** – įrašyk renginio pavadinimą lietuviškai ir angliškai.
3. **Data ir laikas** – nurodyk renginio pradžią (data ir valanda). Jei renginys turi aiškią pabaigą, įvesk ir pabaigos laiką.
4. **Vieta** – nurodyk fizinę vietą (pvz., „VU Didžioji aula, Universiteto g. 3“) arba nuotolinio susitikimo nuorodą (pvz., „MS Teams“).
5. **Renginio tipas** – išskleidžiamajame sąraše parink atitinkamą tipą (jei reikia naujo tipo, jį sukuria centrinis biuras skiltyje [Renginių tipai](/svetaine/renginiu-tipai)).
6. **Žymos** – priskirk aktualias žymas iš bendro žymų sąrašo.
7. **Aprašymas** – naudodamasis raiškiojo teksto redaktoriumi pateik išsamią renginio programą, registracijos reikalavimus ir kitą svarbią informaciją.
8. **Viršelis ir nuotraukos** – įkelk pagrindinį plakatą / viršelį, kuris bus rodomas kalendoriaus kortelėje, bei papildomus vaizdus.
9. Spausk **Išsaugoti**.

### Panašūs ir pasikartojantys renginiai

Kiekvienam pasikartojančio renginio kartui sukurk atskirą įrašą su jo data ir laiku.
Dabartinėje formoje nėra periodinių renginių tvarkyklės ar dubliavimo veiksmo.
Naudok ankstesnio renginio informaciją kaip pavyzdį, bet prieš skelbdamas patikrink vietą,
registracijos nuorodą ir kalbų versijas.

### Posėdžių susiejimas su kalendoriaus renginiais

Posėdžių skiltyje (`/mano/meetings/{id}`) organizatoriai gali vienu mygtuko paspaudimu sukurti viešą kalendoriaus renginį. Sistema automatiškai perkelia posėdžio pavadinimą, laiką, vietą ir darbotvarkę į kalendoriaus įrašą. Ištrynus kalendoriaus įrašą iš posėdžio puslapio, pašalinamas ir susietas kalendoriaus renginys.

### Šalinimas ir atkūrimas

- **Perkėlimas į šiukšlinę:** veiksmų meniu pasirink **Ištrinti**. Renginys pašalinamas iš viešo kalendoriaus ir perkeliamas į šiukšlinę.
- **Atkūrimas:** šiukšlinės rodinyje pasirink **Atkurti**.
- **Galutinis ištrynimas:** šiukšlinėje pasirink **Ištrinti visam laikui**. Kartu pašalinami visi susiję nuotraukų failai ir nuorodos.

## Kas ką gali {#teises}

| Veiksmas | Narys be rolės | Komunikacijos koordinatorius | Centrinio biuro komunikacijos koordinatorius |
|---|---|---|---|
| Matyti kalendoriaus sąrašą | – | ✓ (savo padalinio) | ✓ (visus padalinius) |
| Sukurti naują renginį | – | ✓ (savo padaliniui) | ✓ (visai organizacijai) |
| Redaguoti ir dubliuoti renginį | – | ✓ (savo padalinio) | ✓ (visų) |
| Kurti renginį iš posėdžio puslapio | – | ✓ (jei administruoja posėdį) | ✓ |
| Trinti į šiukšlinę / atkurti | – | ✓ (savo padalinio) | ✓ (visų) |
| Ištrinti visam laikui | – | ✓ (savo padalinio) | ✓ (visų) |

## Pranešimai ir automatizavimas {#pranesimai}

- **iCal srauto sinchronizavimas:** visi pakeitimai (datos korekcija, vietos pasikeitimas) akimirksniu atsinaujina viešame prenumeratos sraute `/kalendorius/ics`.
- **Automatinis filtravimas:** renginiui pasibaigus, jis automatiškai paslepiamas iš pagrindinio puslapio „Artimiausi renginiai“ sąrašo per 10 minučių.
- **Nuorodų istorija:** pakeitus renginio datą ar pavadinimą, senas viešas adresas išsaugomas `public_urls` lentelėje, todėl lankytojai su sena nuoroda automatiškai nukreipiami į naująjį adresą.

## Techninė informacija {#technine-informacija}

Šis skyrius skirtas administratoriams ir sistemos priežiūrai.

### Teisės

- Teises valdo `CalendarPolicy`.
- Leidimų išteklius vadinasi daugiskaita: `calendars` (ne `calendar`).
- Padalinio komunikacijos koordinatoriaus rolė apima `calendars.create.padalinys`, `calendars.read.padalinys`, `calendars.update.padalinys`, `calendars.delete.padalinys`.
- Centrinio biuro komunikacijos koordinatoriaus rolė apima `calendars.create.*`, `calendars.read.*`, `calendars.update.*`, `calendars.delete.*`.

### Kaip tai įgyvendinta

- Modelis: `App\Models\Calendar`, naudojantis Spatie translatable savybes (`title`, `description`, `location`), Spatie MediaLibrary nuotraukoms (`main_image`, `images`) ir `SoftDeletes`.
- Užklausų formavimas: `App\Actions\BuildCalendarIndexQuery` apdoroja filtrus, padalinių apribojimus ir TanStack lentelės parametrus.
- Dubliavimas: `App\Actions\DuplicateCalendarAction` nukopijuoja renginio duomenis, prideda žymas ir sukuria naują juodraštį.
- Posėdžių integracija: `App\Http\Controllers\Admin\MeetingCalendarController` sukuria kalendoriaus įrašą tiesiai iš `Meeting` modelio duomenų.
- Kanoninės nuorodos ir peradresavimai tikrinami per `App\Support\LocalizedRouteSlugs` ir `PublicPageController::calendarCanonical`.
