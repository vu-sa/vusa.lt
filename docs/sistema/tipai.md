---
doc_status: reviewed
title: Tipai ir kategorijos
area: types
models: [InstitutionType, DutyType, EventType, ResourceCategory, ProblemCategory]
last_reviewed: 2026-10-20
tests:
  - tests/Feature/Permissions/DutyTypeAssignmentTest.php
  - tests/Feature/Admin/ModelMeta/TypeControllerTest.php
  - tests/Feature/Migrations/SplitTypesTest.php
  - resources/js/Pages/Admin/ModelMeta/__tests__/IndexDomainTypes.test.ts
  - resources/js/Pages/Admin/ModelMeta/__tests__/ShowType.test.ts
  - tests/Browser/TypeDirectoryTest.php
---

# Tipai ir kategorijos

Tipai ir kategorijos skirti institucijoms, pareigybėms, renginiams, ištekliams ir problemoms klasifikuoti bei valdyti. Skiltis pasiekiama adresu `/mano/types`.

## Susitarimai {#susitarimai}

- Institucijų ir pareigybių tipai yra atskiri sąrašai. Tipo rūšies redaguojant rekomenduojama nekeisti; sukuriamas tinkamos rūšies tipas ir su juo susiejami reikalingi įrašai.
- Ištrintą tipą galima atkurti iš šiukšlinės. Visam laikui ištrinti nepavyksta, kol su juo susieti įrašai, vaikiniai tipai arba tipui suteiktos atsakomybės; tai galioja ir ištrintiems susietiems įrašams.

## Kaip tai veikia

<ChangelogNote version="v3.0" date="2026-10-02" title="Atskiri institucijų ir pareigybių tipų sąrašai">

Institucijų ir pareigybių tipai tvarkomi atskirai. Renkantis sąrašą išlieka bendras katalogas, o susiejimai tvarkomi konkretaus tipo įraše.

</ChangelogNote>

Skiltyje **Sistema → Tipai ir kategorijos** pasirenkamas vienas iš penkių sąrašų:

- **Institucijų tipai** grupuoja institucijas, nustato valdymo sritį ir institucijų ryšius.
- **Pareigybių tipai** grupuoja pareigybes; tipui suteiktas atsakomybes gauna visos to tipo pareigybės. Kurie tipai rodomi kaip *Koordinatoriai*, *Kuratoriai* ir *Mentoriai* viešuose kontaktuose, nustatoma [Sistemos paskirtyse](/sistema/nustatymai#sistemos-paskirtys); tokio tipo ištrinti negalima.
- **Renginių tipai** apibūdina kalendoriaus įvykius. [Plačiau](/svetaine/renginiu-tipai)
- **Išteklių kategorijos** grupuoja rezervuojamus išteklius. [Plačiau](/rezervacijos/kategorijos)
- **Problemų kategorijos** grupuoja registruojamas problemas. [Plačiau](/visak/problemos)

Rodomi tik tie sąrašai, kuriuos naudotojas turi teisę pasiekti. Bendrame puslapyje pateikiamos nuorodos į sąrašus;
konkretūs tipai ir kategorijos tvarkomi pasirinktame sąraše.

## Veiksmai

Institucijos ar pareigybės tipą sukurk per **Veiksmai**. Įrašyk lietuvišką ir anglišką
pavadinimus bei aprašymus. Tėvinį tipą pasirink iš tos pačios rūšies sąrašo; tipų hierarchija
negali sudaryti rato. Iš institucijos tipo tėvų paveldima valdymo sritis.

Pasirinktame sąraše taip pat gali spausti **Naujas institucijos tipas** arba
**Naujas pareigybės tipas**. Mygtukas rodomas tik turint teisę kurti tos rūšies tipus;
šiukšlinėje jo nėra.

Atidaręs tipo įrašą, skiltyje **Susieti įrašai** tvarkyk institucijas arba pareigybes.
Viena institucija ar pareigybė gali turėti kelis tipus. Institucijos tipo skirtuke **Ryšiai** tvarkyk
[tipų ryšius](/sistema/rysiai#tipu-rysio-kurimas). Failus rask tipo įrašo failų skiltyje; susieti įrašai gali paveldėti savo tipų ir jų tėvų failus.

### Pareigybės tipo atsakomybės {#pareigybes-tipo-atsakomybes}

<ChangelogNote version="v3.10" date="2026-10-19" title="Atsakomybių suteikimas vienoje vietoje">

Tipo įraše suteikimai rodomi su aprėptimi ir nuorodomis; jie tvarkomi atsakomybės puslapyje.

</ChangelogNote>

Tipo skirtuke **Atsakomybės** matyti visi jam galiojantys suteikimai ir jų aprėptis. Atverk
atsakomybę arba spausk **Atverti atsakomybes**, tada jos skirtuke **Kam suteikta** spausk
**Pridėti tipą**. Pasirink tipą ir, jei paskirtis to reikalauja, padalinį, institucijų tipą ar
instituciją. Suteikimai ir jų pašalinimas tvarkomi šiame viename puslapyje.

Jei prie esamo suteikimo rodoma, kad aprėptis nenurodyta arba netinkama, atsakomybės puslapyje
pašalink tą suteikimą ir suteik iš naujo su teisinga aprėptimi. Sistema jos nenuspėja ir pati nekeičia.

## Kas ką gali {#teises}

Rolė **Super Admin** leidžia tvarkyti visus penkis sąrašus. Kitiems naudotojams sąrašai ir veiksmai
rodomi pagal suteiktas teises; teisė tvarkyti institucijas ar pareigybes savaime nesuteikia teisės
keisti bendro tipų sąrašo.

Pareigybės tipą priskirti ar pašalinti galima tik turint jo delegavimą ir teisę redaguoti
kiekvieną keičiamą pareigybę. Atsakomybes valdantys administratoriai gali priskirti visus tinkamus
tipus, bet jiems taip pat reikia prieigos prie pareigybės. Vien teisės redaguoti tipą neužtenka.
Kitų padalinių esami priskyrimai išlieka, tačiau jų keisti negalima. Pasibaigusios pareigos
delegavimo nebesuteikia; paskutinę pareigų dieną jis dar galioja.

## Pranešimai ir automatizavimas {#pranesimai}

- **Atsakomybių suteikimas**: priskyrus pareigybės tipą pareigybei, ji gauna tipui suteiktas atsakomybes. Pašalinus tipą, jos atimamos, nebent suteiktos pareigybei ir tiesiogiai.
- **Teisių atnaujinimas**: atnaujinus tipo atsakomybes ar susiejimus, išvaloma susijusių naudotojų prieigos ir navigacijos talpykla.
- **Vientisumo apsauga**: visam laikui ištrinti tipą, turintį vaikinių tipų, susietų įrašų ar atsakomybių, neleidžiama. Perkėlimui į šiukšlinę šis apribojimas netaikomas.

## Techninė informacija {#technine-informacija}

Bendras katalogas: `/mano/types`. Institucijų tipai: `/mano/institutionTypes`;
pareigybių tipai: `/mano/dutyTypes`. Ankstesni bendri tipų kūrimo ir redagavimo maršrutai
bei viešas `/api/v1/types` maršrutas pašalinti.
