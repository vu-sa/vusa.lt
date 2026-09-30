---
doc_status: reviewed
title: Platforma
area: actionWindow
last_reviewed: 2026-09-30
tests:
  - resources/js/__tests__/designTokens.test.ts
  - resources/js/Components/Patterns/__tests__/StatusBadge.component.test.ts
  - resources/js/Components/AdminForms/__tests__/DuplicateDutyWarning.component.test.ts
  - resources/js/Components/AdminForms/__tests__/DuplicateUserWarning.component.test.ts
  - resources/js/Features/Admin/AdminSearch/Components/Select/__tests__/SearchSelectDialog.component.test.ts
  - resources/js/Features/Admin/AdminSearch/Components/__tests__/SearchHitRow.component.test.ts
  - resources/js/Features/Admin/AdminSearch/Components/__tests__/SearchSplitView.component.test.ts
  - tests/Browser/ActionWindowTest.php
  - tests/Browser/AdminDesignSurfaceTest.php
  - tests/Browser/AdminSearchPickerLayoutTest.php
  - tests/Browser/AdminAccessibilityTest.php
  - tests/Browser/AdminShellTest.php
  - tests/Browser/DefaultThemeTest.php
  - tests/Feature/Admin/SoftDeletableResourcesTest.php
  - tests/Unit/Models/TranslatableExpectationsTest.php
  - tests/Feature/System/TranslationIntegrityTest.php
---

# Platforma

Mano VU SA sudaro **darbo sritys** (Mano, ViSAK, Rezervacijos, Svetainė, Organizacija, Sistema).
Kiekviena sritis turi skiltis. Viršutinė juosta leidžia perjungti sritis, o mygtukas **+ Sukurti**
atveria langą su visais tau leidžiamais kūrimo veiksmais.

<DocScreenshot name="action-window" narrow alt="Langas „Ką norėtum padaryti?“, atidaromas mygtuku „+ Sukurti“" caption="Mygtukas „+ Sukurti“ viršutinėje juostoje atveria šį langą." />

::: info Ko nematai – to neturi
Sritys, skiltys ir veiksmai rodomi tik tada, kai turi teisę jais naudotis. Jie niekada nerodomi
išjungti. Jei kolega mato skiltį, kurios tu nematai, skiriasi jūsų teisės (žr.
[Teisės ir rolės](/pagrindai/teises)).
:::

## Mygtukai ir klaviatūra {#mygtukai-ir-klaviatura}

Pagrindiniai kūrimo veiksmai išsiskiria ryškia spalva ir didžiosiomis raidėmis. Kiti veiksmai
rašomi įprastai, kad būtų lengviau juos perskaityti. Naršant klaviatūra, aktyvi nuoroda turi
matomą apvadą.

Sąrašuose įrašo veiksmai matomi tiek eilutėse, tiek lentelėje. Vien piktograma pažymėti mygtukai
turi aiškų pavadinimą ekrano skaitytuvui, o telefone jų paspaudimo vieta yra bent 44 × 44 taškai.
Ištrintų įrašų sąraše galima juos atkurti arba, turint teisę, ištrinti visam laikui.

## Paieška ir įrašų pasirinkimas {#paieska-ir-pasirinkimas}

Kai forma prašo susieti kitą įrašą, pasirinkimo lange gali ieškoti, filtruoti ir peržiūrėti radinį prieš jį pridėdamas. Pažymėtus įrašus patvirtini lango apačioje; jei laukas neprivalomas, pasirinkimą gali išvalyti. Telefone peržiūrėjęs radinį mygtuku „Atgal į sąrašą“ grįši prie rezultatų. Nepasiekiamą išteklių gali peržiūrėti, bet negali pasirinkti.

Kuriant narį ar pareigybę, perspėjimas apie galimą dublikatą parodo sutapimus ir galimus veiksmus. Tai patarimas: sutampantys vardai savaime neuždraudžia išsaugoti įrašo. Atverti ar sujungti kitą įrašą siūloma tik tada, kai turi teisę jį tvarkyti.

## Bendrosios platformos galimybės {#bendrosios-galimybes}

Platforma turi bendrų mechanizmų, kurie vienodai veikia visose darbo srityse ir moduliuose:

### Šiukšlinė ir atkūrimas {#siuksline}

Turinio ir konfigūracijos įrašai (naujienos, puslapiai, baneriai, kalendoriaus renginiai, failai, ištekliai) palaiko laikiną ištrynimą:
- Ištrintas įrašas nepašalinamas iš duomenų bazės iš karto – jis perkeliamas į **šiukšlinę**.
- Naudotojai, turintys įrašo trynimo teisę, gali jį **atkurti** mygtuku „Atkurti“.
- Visiškas pašalinimas („Ištrinti visam laikui“) reikalauja super administratoriaus teisės, apsaugančios nuo netyčinio duomenų praradimo.
- **Operaciniai įrašai** (pvz., **rezervacijos**) į šiukšlinę nekeliami: juos ištrynus, įrašas iš karto negrįžtamai pašalinamas kartu su susijusiais tarpiniais duomenimis.

### Daugiakalbiškumas ir vertimai {#vertimai}

VU SA platforma yra dvikalbė (lietuvių ir anglų k.):
- **Formose** verčiami laukai pateikiami su kalbų pasirinkimu (LT ir EN skirtukais). Lietuvių kalbos tekstas yra privalomas, o anglų kalbos – rekomenduojamas.
- **Sąrašuose ir viešojoje svetainėje** rodoma dabartinė vartotojo pasirinkta kalba. Jei angliško vertimo nėra, sistema automatiškai rodo lietuvišką tekstą (atsarginį variantą).

## Spalvos, ženklai ir formos {#spalvos-zenklai-ir-formos}

Mano VU SA turi atrodyti ramiai ir aiškiai: popieriaus spalvos fonas, tamsus tekstas, VU SA raudona
(tamsioje temoje – gintarinė), kampai stačiakampiai, o sritis skiria plonos linijos, ne rėmeliai ir
šešėliai. Kiekvienas ekranas pirmiausia atsako, *kas laukia tavęs*, ir tik tada – *kur gali eiti*.

Spalva platformoje visada ką nors reiškia, o ką – nusako **forma**:

| Ką matai | Ką tai reiškia | Pavyzdys |
|---|---|---|
| **Vientisas raudonas (gintarinis) mygtukas** | Vienas pagrindinis veiksmas toje ekrano dalyje arba „tu esi čia“ | **+ SUKURTI**, **IŠSAUGOTI** |
| **Spalvotas ženkliukas su žodžiu ir piktograma** | Būsena | „Vėluoja“, „Patvirtinta“ |
| **Mažas spalvotas taškas ar brūkšnelis** | Kategorija: įrašo tipas, renginio tipas, padalinys, grafiko serija | Posėdžio tipo žymė kalendoriuje |

Raudona (VU SA spalva) niekada nereiškia būsenos, o būsenos spalva niekada nėra vienintelis
ženklas – šalia visada yra žodis ir piktograma.

### Būsenos {#busenos}

| Spalva | Kada naudojama | Pavyzdžiai |
|---|---|---|
| Pilka | Juodraštis, atšaukta, archyvuota | juodraštis, atšaukta |
| Mėlyna | Pateikta arba artėja | pateikta, artėjantis posėdis |
| Violetinė | Vyksta | vykdoma |
| Gintarinė | Reikia tavo veiksmo arba trūksta informacijos | laukia sprendimo, neišsaugoti pakeitimai |
| Žalia | Atlikta, patvirtinta | atlikta, patvirtinta |
| Raudona | Vėluoja, atmesta, nepavyko | vėluoja, atmesta |

Kai viskas gerai, ženkliuko dažniausiai nėra – rodoma tik tai, į ką verta atkreipti dėmesį.

### Didžiosios raidės ir kampai

- **Didžiosiomis raidėmis** rašomi tik pagrindiniai kūrimo mygtukai, skilčių pavadinimai, formų laukų
  pavadinimai, virš antraščių esantys trumpi užrašai (pvz., „VISAK · POSĖDŽIAI“) ir skirtukai. Visi
  kiti mygtukai, filtrai ir būsenos rašomi įprastai.
- **Kampai visada statūs.** Rėmelis aplink ką nors reiškia objektą, su kuriuo gali ką nors daryti;
  kortelių kortelėse nebūna.
- **Grafikuose** (posėdžių ir pareigybių laiko juostose) tamsios juostos – tikri duomenys, pilkos –
  praeitis, gintarinės – tai, kas dar neišsaugota arba reikalauja dėmesio.

### Tema, dydis ir įrenginiai

- Platforma pagal nutylėjimą atsidaro **šviesia tema**, net jei tavo įrenginys nustatytas tamsiai.
  Tamsią temą įjungsi paspaudęs savo vardą → **Išvaizda → Tamsi tema**; pasirinkimas išsaugomas.
- **Išvaizda → Prieinamumas** leidžia padidinti tekstą, paryškinti linijas ir pabraukti nuorodas.
- Telefone ir planšetėje galima atlikti tą patį, ką kompiuteryje: telefone mygtukas **+ Sukurti** ir
  pranešimai persikelia į apatinę juostą, o niekas nepasiekiama tik užvedus pelę.
- Klaviatūra: **?** parodo sparčiųjų klavišų sąrašą, **/** perkelia į sąrašo paiešką.



## Techninė informacija {#technine-informacija}

::: details Kuriantiems platformą
Spalvos aprašytos kaip kintamieji `resources/css/theme/design-tokens.css` ir
`resources/css/theme/base-tokens.css`: `--brand` / `--brand-fill` – VU SA spalva, `--status-*` –
šešios būsenos, `--cat-1…8` – kategorijos. Taisyklės, kada kurį naudoti, – `.ai/rules/css.md` ir
`.ai/rules/js-pages-admin.md`; kontrastą tikrina Storybook istorija `Patterns/ColourSystem`.
Būseną visada rodyk per `StatusBadge`, o ne savo spalvomis.
:::

- Spalvų kintamieji abiem temoms ir kategorijų atspalvių atstumai tikrinami `designTokens.test.ts`.
- `StatusBadge` visada rodo žodį ir piktogramą, o spalvą ima iš būsenos vaidmens.
- Stačiakampius kampus, šriftą ir fokuso apvadą naršyklėje tikrina `AdminDesignSurfaceTest`, šviesią
  temą pagal nutylėjimą – `DefaultThemeTest`, prieinamumo nustatymus – `AdminAccessibilityTest`,
  telefono juostą ir sparčiuosius klavišus – `AdminShellTest`, langą **+ Sukurti** – `ActionWindowTest`.
- Laikiną ištrynimą (`SoftDeletes`) ir jo elgseną sąrašuose tikrina `SoftDeletableResourcesTest`; visiškas ištrynimas reikalauja `*.forceDelete` teisės, kurią turi tik super administratorius.
- Daugiakalbių laukų elgseną (Spatie `HasTranslations`, `toFullArray()` administravimo formose ir `toArray()` lokalizuotai peržiūrai) bei vertimų vientisumą užtikrina `TranslatableExpectationsTest` ir `TranslationIntegrityTest`.
