---
doc_status: reviewed
title: Tipai ir kategorijos
area: types
models: [InstitutionType, DutyType, EventType, ResourceCategory, ProblemCategory]
last_reviewed: 2026-10-02
tests:
  - tests/Feature/Admin/ModelMeta/TypeControllerTest.php
  - tests/Feature/Migrations/SplitTypesTest.php
---

# Tipai ir kategorijos

## Kaip tai veikia

<ChangelogNote version="v2.36" date="2026-10-02" title="Atskiri institucijų ir pareigybių tipų sąrašai" />

Skiltyje **Sistema → Tipai ir kategorijos** pasirink vieną iš penkių sąrašų:

- **Institucijų tipai** grupuoja institucijas, nustato valdymo sritį ir institucijų ryšius.
- **Pareigybių tipai** grupuoja pareigybes; jų rolės suteikiamos susietoms pareigybėms.
- **Renginių tipai** apibūdina kalendoriaus įvykius. [Plačiau](/svetaine/renginiu-tipai)
- **Išteklių kategorijos** grupuoja rezervuojamus išteklius. [Plačiau](/rezervacijos/kategorijos)
- **Problemų kategorijos** grupuoja registruojamas problemas. [Plačiau](/visak/problemos)

Matai tik tuos sąrašus, kuriuos gali pasiekti. Bendras puslapis rodo nuorodas į sąrašus;
konkrečius tipus ir kategorijas tvarkyk pasirinktame sąraše.

## Veiksmai

Institucijos ar pareigybės tipą sukurk per **+ Sukurti**. Įrašyk lietuvišką ir anglišką
pavadinimus bei aprašymus. Tėvinį tipą pasirink iš tos pačios rūšies sąrašo; tipų hierarchija
negali sudaryti rato. Iš institucijos tipo tėvų paveldima valdymo sritis.

Atidaręs tipo įrašą, skiltyje **Susieti įrašai** tvarkyk institucijas arba pareigybes.
Viena institucija ar pareigybė gali turėti kelis tipus. Pareigybės tipo įraše atskirai tvarkyk
roles. Failus rask tipo įrašo failų skiltyje; susieti įrašai gali paveldėti savo tipų ir jų tėvų failus.

## Kas ką gali

Sistemos administratorius tvarko visus penkis sąrašus. Kitiems naudotojams sąrašai ir veiksmai
rodomi pagal suteiktas teises; teisė tvarkyti institucijas ar pareigybes savaime nesuteikia teisės
keisti bendro tipų sąrašo.

## Susitarimai

Institucijų ir pareigybių tipai yra atskiri sąrašai. Tipo rūšies redaguodamas nekeisk;
sukurk tinkamos rūšies tipą ir susiek jam reikalingus įrašus.

Ištrintą tipą gali atkurti iš šiukšlinės. Visam laikui ištrinti nepavyks, kol su juo susieti
įrašai, vaikiniai tipai arba pareigybės tipo rolės; tai galioja ir ištrintiems susietiems įrašams.

## Techninė informacija

Bendras katalogas: `/mano/types`. Institucijų tipai: `/mano/institutionTypes`;
pareigybių tipai: `/mano/dutyTypes`. Ankstesni bendri tipų kūrimo ir redagavimo maršrutai
bei viešas `/api/v1/types` endpointas pašalinti.
