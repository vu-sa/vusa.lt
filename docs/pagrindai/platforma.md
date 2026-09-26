---
title: Platforma
area: actionWindow
last_reviewed: 2026-09-27
tests:
  - resources/js/__tests__/designTokens.test.ts
  - resources/js/Components/Patterns/__tests__/StatusBadge.component.test.ts
  - tests/Browser/ActionWindowTest.php
  - tests/Browser/AdminDesignSurfaceTest.php
  - tests/Browser/AdminAccessibilityTest.php
  - tests/Browser/AdminShellTest.php
  - tests/Browser/DefaultThemeTest.php
---

# Platforma

Mano VU SA sudaro **darbo sritys** (Mano, ViSAK, Rezervacijos, Svetainė, Organizacija, Sistema).
Kiekviena sritis turi skiltis. Viršutinė juosta leidžia perjungti sritis, o mygtukas **+ Sukurti**
atveria langą su visais tau leidžiamais kūrimo veiksmais.

::: warning Rašoma
Šis puslapis dar rašomas. Kai bus baigtas, jame bus skyriai **Kaip tai veikia**, **Veiksmai**,
**Kas ką gali** ir **Pranešimai ir automatizavimas**, kaip [Rezervacijų](/rezervacijos/rezervacijos) skyriuje.
:::

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

::: details Kuriantiems platformą
Spalvos aprašytos kaip kintamieji `resources/css/theme/design-tokens.css` ir
`resources/css/theme/base-tokens.css`: `--brand` / `--brand-fill` – VU SA spalva, `--status-*` –
šešios būsenos, `--cat-1…8` – kategorijos. Taisyklės, kada kurį naudoti, – `.ai/rules/css.md` ir
`.ai/rules/js-pages-admin.md`; kontrastą tikrina Storybook istorija `Patterns/ColourSystem`.
Būseną visada rodyk per `StatusBadge`, o ne savo spalvomis.
:::

## Techninė informacija {#technine-informacija}

- Spalvų kintamieji abiem temoms ir kategorijų atspalvių atstumai tikrinami `designTokens.test.ts`.
- `StatusBadge` visada rodo žodį ir piktogramą, o spalvą ima iš būsenos vaidmens.
- Stačiakampius kampus, šriftą ir fokuso apvadą naršyklėje tikrina `AdminDesignSurfaceTest`, šviesią
  temą pagal nutylėjimą – `DefaultThemeTest`, prieinamumo nustatymus – `AdminAccessibilityTest`,
  telefono juostą ir sparčiuosius klavišus – `AdminShellTest`, langą **+ Sukurti** – `ActionWindowTest`.
