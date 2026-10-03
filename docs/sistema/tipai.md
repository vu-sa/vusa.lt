---
doc_status: reviewed
title: Tipai ir kategorijos
area: types
models: [InstitutionType, DutyType, EventType, ResourceCategory, ProblemCategory]
last_reviewed: 2026-10-03
tests:
  - tests/Feature/Admin/ModelMeta/TypeControllerTest.php
  - tests/Feature/Migrations/SplitTypesTest.php
  - resources/js/Pages/Admin/ModelMeta/__tests__/IndexDomainTypes.test.ts
  - tests/Browser/TypeDirectoryTest.php
---

# Tipai ir kategorijos

## Kaip tai veikia

<ChangelogNote version="v3.0" date="2026-10-02" title="Atskiri institucijų ir pareigybių tipų sąrašai">

Institucijų ir pareigybių tipai tvarkomi atskirai. Renkantis sąrašą išlieka bendras katalogas, o susiejimai ir rolės tvarkomi konkretaus tipo įraše.

</ChangelogNote>

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

Pasirinktame sąraše taip pat gali spausti **Naujas institucijos tipas** arba
**Naujas pareigybės tipas**. Mygtukas rodomas tik turint teisę kurti tos rūšies tipus;
šiukšlinėje jo nėra.

Atidaręs tipo įrašą, skiltyje **Susieti įrašai** tvarkyk institucijas arba pareigybes.
Viena institucija ar pareigybė gali turėti kelis tipus. Pareigybės tipo įraše atskirai tvarkyk
roles. Failus rask tipo įrašo failų skiltyje; susieti įrašai gali paveldėti savo tipų ir jų tėvų failus.

## Kas ką gali {#teises}

Rolė **Super Admin** leidžia tvarkyti visus penkis sąrašus. Kitiems naudotojams sąrašai ir veiksmai
rodomi pagal suteiktas teises; teisė tvarkyti institucijas ar pareigybes savaime nesuteikia teisės
keisti bendro tipų sąrašo.

## Pranešimai ir automatizavimas {#pranesimai}

- **Rolių suteikimas**: priskyrus pareigybės tipą pareigybei, jai automatiškai suteikiamos su tuo tipu susietos rolės. Pašalinus tipo priskyrimą pareigybei, su tuo tipu susietos rolės nuo pareigybės atjungiamos.
- **Teisių atnaujinimas**: atnaujinus tipo roles ar susiejimus, išvaloma susijusių naudotojų prieigos ir navigacijos talpykla.
- **Vientisumo apsauga**: visam laikui ištrinti tipą, turintį vaikinių tipų, susietų įrašų ar rolių, neleidžiama. Perkėlimui į šiukšlinę šis apribojimas netaikomas.

## Susitarimai {#susitarimai}

Institucijų ir pareigybių tipai yra atskiri sąrašai. Tipo rūšies redaguodamas nekeisk;
sukurk tinkamos rūšies tipą ir susiek jam reikalingus įrašus.

Ištrintą tipą gali atkurti iš šiukšlinės. Visam laikui ištrinti nepavyks, kol su juo susieti
įrašai, vaikiniai tipai arba pareigybės tipo rolės; tai galioja ir ištrintiems susietiems įrašams.

## Techninė informacija {#technine-informacija}

Bendras katalogas: `/mano/types`. Institucijų tipai: `/mano/institutionTypes`;
pareigybių tipai: `/mano/dutyTypes`. Ankstesni bendri tipų kūrimo ir redagavimo maršrutai
bei viešas `/api/v1/types` maršrutas pašalinti.
