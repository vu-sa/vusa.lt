---
doc_status: reviewed
title: Padaliniai ir pareigybės
coverage: ignore
last_reviewed: 2026-09-30
tests:
  - tests/Feature/Permissions/ScheduledDutyAuthorizationTest.php
  - tests/Feature/Permissions/BaselineAccessTest.php
  - tests/Feature/Admin/Management/DutyControllerTest.php
  - tests/Feature/CrossTenantDutyTest.php
  - tests/Feature/Responsibilities/ResponsibilityResolverTest.php
---

# Padaliniai ir pareigybės

Mano VU SA struktūros pagrindas yra padaliniai ir pareigybės. Iš jų ryšių sistema sprendžia,
kurio padalinio duomenis tu gali matyti ir tvarkyti, kas gauna pranešimus ir kas atstovauja
studentams universiteto organuose.

## Pagrindinė grandinė {#pagrindine-grandine}

Platformos struktūra sudaryta iš keturių grandžių:

1. **Padalinys** – teritorinis ar administracinis VU SA vienetas: fakultetinis padalinys
   (pvz., VU SA MIF, VU SA FilF), Centrinis biuras (VU SA CB) arba programinė veikla (pvz., PKP).
2. **Institucija** – padaliniui priklausantis organas, komitetas, taryba, darbo grupė
   arba pati VU SA struktūrinė dalis.
3. **Pareigybė** – konkreti vieta institucijoje (pvz., padalinio pirmininkas, komunikacijos
   koordinatorius, kuratorius, studentų atstovas taryboje ar studijų programos komitete). Pareigybė turi
   numatytą vietų skaičių, tipus ir jai suteiktas roles.
4. **Pareigybės laikotarpis** – konkretaus asmens paskyrimas į pareigybę tam tikram
   laiko tarpui su pradžios ir pabaigos datomis.

Kasdieniame administravimo darbe teisės sujungiamos į **roles**, o rolės priskiriamos
**pareigybėms**. Narys šias teises gauna per savo **pareigybės laikotarpius**. Be šio kelio,
yra bazinė nario prieiga ir tiesiogiai paskyrai suteiktos teisės; jas paaiškina
[Teisės ir rolės](/pagrindai/teises).

## Pareigybė ir pareigybės laikotarpis {#pareigybe-ir-laikotarpis}

- **Pareigybė** yra nuolatinis įrašas sistemoje. Kai keičiasi koordinatorius ar pirmininkas,
  nauja pareigybė nekuriama – išlieka ta pati pareigybė su savo instituciniais ryšiais, istorija ir
  instituciniu el. pašto adresu.
- **Pareigybės laikotarpis** nurodo, kas ir kada šias pareigas ėjo. Vienas žmogus tose pačiose pareigose
  gali turėti kelis laikotarpius (pvz., po pertraukos), o perrinktam nariui galima pratęsti esamą laikotarpį arba pradėti naują.
- [Pareigybių atnaujinimo vedlys](/organizacija/pareigybiu-atnaujinimas) **nesiunčia kvietimo
  prisijungti ir nesukuria slaptažodžio**. Vien paskyrimas į pareigas nėra kvietimas prisijungti.

## Datos ir narystės taisyklės {#datos}

### Abi datos įskaitomos {#iskaitomos-datos}

Pradžios ir pabaigos dienos įskaitomos: pabaigos data yra **paskutinė aktyvi pareigų diena**.
Užbaigus pareigas šiandien, narys lieka aktyvus iki šiandienos pabaigos. Išsamias taisykles ir
pavyzdžius rasi [pareigybių laikotarpių datų aprašyme](/organizacija/pareigybes#laikotarpio-datos).

### Dabartinė narystė ir prieiga prieš pareigų pradžią {#naryste-ir-autorizacija}

**Dabartinis narys** – žmogus, kurio pareigų pradžios diena jau atėjo, o paskutinė pareigų diena dar
nepasibaigė. Pagal dabartinę narystę sudaromi pareigas einančių narių sąrašai.

Būsimas paskyrimas gali suteikti pareigybės rolėse numatytą prieigą **dar prieš pareigų pradžią**.
Tačiau iki pradžios dienos žmogus nelaikomas dabartiniu pareigų vykdytoju.

## Dažnai painiojamos sąvokos {#savoku-skirtumai}

Kad administravimas būtų tikslus, atskirk šias sąvokas:

| Sąvoka | Kas tai yra | Kur naudojama |
|---|---|---|
| **Pareigybės laikotarpis** | Konkretaus žmogaus paskyrimas į pareigas su pradžios ir pabaigos datomis | Nustato, ar žmogus dabar eina pareigas ir kokias teises turi |
| **Institucijos kadencija** | Visos institucijos veiklos ciklas (dažniausiai liepos 1 d. – birželio 30 d.) | Naudojama posėdžiams ir protokolams priskirti veiklos laikotarpiui bei pareigybių datoms derinti |
| **Rolė** | Teisių rinkinys (pvz., *Komunikacijos koordinatorius*), priskiriamas pareigybei | Nurodo, kokius veiksmus asmuo gali atlikti platformoje |
| **Koordinavimo atsakomybė** | Atsakomybė už organą, tipą ar padalinį (pvz., studentų atstovų koordinavimas) | Nurodo, kam atstovai siunčia klausimus ir kas gauna atstovavimo registracijas |
| **Sekretorius** | Asmuo, paskirtas konkrečios institucijos kadencijai tvarkyti posėdžius | Gauna posėdžių protokolavimo ir darbotvarkės užduotis |
| **Koordinatorius** | Žmogus, einantis pareigas su koordinavimo atsakomybe | Padeda atstovams; vien koordinavimo atsakomybė nereiškia sekretoriaus paskyrimo |

::: warning Koordinavimo atsakomybė nėra leidimų rolė
Atsakomybės priskyrimas nurodo atstovavimo pagalbą, bet **nesuteikia** padalinio puslapių, naujienų ar
pareigybių valdymo teisių. Šią prieigą kasdieniame darbe suteikia pareigybei priskirtos atitinkamos rolės.
:::

## Kur tai tvarkoma {#nuorodos}

- **Padalinių duomenys**: peržiūrėk ir tvarkyk juos [Organizacija → Padaliniai](/organizacija/padaliniai).
- **Padalinių atstovavimo rodikliai**: peržiūrėk juos [ViSAK → Padaliniai](/visak/padaliniai).
- **Pareigybės**: kurk ir redaguok pareigybes [Organizacija → Pareigybės](/organizacija/pareigybes).
- **Metinis narių keitimas**: naudok [Pareigybių atnaujinimo vedlį](/organizacija/pareigybiu-atnaujinimas).
- **Masinis datų derinimas**: naudok [ViSAK → Laikotarpių tvarkyklę](/visak/pareigybiu-laikotarpiai).
- **Teisės ir rolės**: išsamus prieigos modelio paaiškinimas pateiktas [Pagrindai → Teisės ir rolės](/pagrindai/teises).
- **Atsakomybės**: koordinavimo taisykles rasi [Pagrindai → Atsakomybės](/pagrindai/atsakomybes).
- **Kadencijos**: institucijų ciklus tvarkyk [Sistema → Nustatymai (Kadencijos)](/sistema/nustatymai#kadencijos).

## Techninė informacija {#technine-informacija}

- Padalinius aprašo `Tenant` modelis, institucijas – `Institution`, pareigybes – `Duty`.
- Asmens paskyrimas saugomas ryšio lentelėje `dutiables` (`Dutiable` modelis) su `start_date`, `end_date`, `study_program_id`, `additional_email`, `additional_photo`.
- `User::current_duties()` tikrina, kad `start_date <= today` ir `(end_date is null or end_date >= today)`.
- `User::authorization_duties()` tikrina `(end_date is null or end_date >= today)` – tai apima ir būsimus paskyrimus (`upcoming_duties()`). Šią atskirtį tikrina `ScheduledDutyAuthorizationTest`.
- Bazinę prisijungusio nario prieigą be rolių užtikrina `BaselineAccessTest`.
- Koordinatorių parinkimą pagal organo hierarchiją atlieka `ResponsibilityResolver`, tikrinamas `ResponsibilityResolverTest`.
