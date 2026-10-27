---
doc_status: reviewed
title: Tipai ir kategorijos
area: types
models: [InstitutionType, DutyType, EventType, ResourceCategory, ProblemCategory]
last_reviewed: 2026-10-21
tests:
  - tests/Feature/Permissions/DutyTypeAssignmentTest.php
  - tests/Feature/Responsibilities/ProjectDutyResponsibilitiesTest.php
  - tests/Feature/Admin/ModelMeta/TypeControllerTest.php
  - tests/Feature/Migrations/SplitTypesTest.php
  - resources/js/Pages/Admin/ModelMeta/__tests__/IndexDomainTypes.test.ts
  - resources/js/Pages/Admin/ModelMeta/__tests__/ShowType.test.ts
  - tests/Browser/TypeDirectoryTest.php
  - resources/js/Components/AdminForms/TypeTree/__tests__/TypeTree.component.test.ts
---

# Tipai ir kategorijos

Tipai ir kategorijos skirti institucijoms, pareigybėms, renginiams, ištekliams ir problemoms klasifikuoti bei valdyti. Skiltis pasiekiama adresu `/mano/types`.

## Susitarimai {#susitarimai}

- Institucijų ir pareigybių tipai yra atskiri sąrašai. Tipo rūšies redaguojant rekomenduojama nekeisti; sukuriamas tinkamos rūšies tipas ir su juo susiejami reikalingi įrašai.
- Ištrintą tipą galima atkurti iš šiukšlinės. Visam laikui ištrinti nepavyksta, kol su juo susieti įrašai, vaikiniai tipai arba tipui suteiktos atsakomybės; tai galioja ir ištrintiems susietiems įrašams.

## Kaip tai veikia

<ChangelogNote date="2026-10-02" title="Atskiri institucijų ir pareigybių tipų sąrašai">

Institucijų ir pareigybių tipai tvarkomi atskirai. Renkantis sąrašą išlieka bendras katalogas, o susiejimai tvarkomi konkretaus tipo įraše.

</ChangelogNote>

Skiltyje **Sistema → Tipai ir kategorijos** pasirenkamas vienas iš penkių sąrašų:

- **Institucijų tipai** grupuoja institucijas, nustato valdymo sritį ir institucijų ryšius.
- **Pareigybių tipai** grupuoja pareigybes; tipui suteiktas atsakomybes gauna visos to tipo ir jo potipių pareigybės. Kurie tipai rodomi kaip *Koordinatoriai*, *Kuratoriai* ir *Mentoriai* viešuose kontaktuose, nustatoma [Sistemos paskirtyse](/sistema/nustatymai#sistemos-paskirtys); tokio tipo ištrinti negalima.
- **Renginių tipai** apibūdina kalendoriaus įvykius. [Plačiau](/svetaine/renginiu-tipai)
- **Išteklių kategorijos** grupuoja rezervuojamus išteklius. [Plačiau](/rezervacijos/kategorijos)
- **Problemų kategorijos** grupuoja registruojamas problemas. [Plačiau](/visak/problemos)

Rodomi tik tie sąrašai, kuriuos naudotojas turi teisę pasiekti. Bendrame puslapyje pateikiamos nuorodos į sąrašus;
konkretūs tipai ir kategorijos tvarkomi pasirinktame sąraše.

## Veiksmai

Institucijos ar pareigybės tipą sukurk per **Veiksmai**. Įrašyk lietuvišką ir anglišką
pavadinimus bei aprašymus. Tėvinį tipą pasirink iš tos pačios rūšies sąrašo; tipų hierarchija
negali sudaryti rato. Iš institucijos tipo tėvų paveldima valdymo sritis, o pareigybės tipas
paveldi tėvinių tipų atsakomybes. Visur, kur renkamasi tipą, sąrašas rodomas medžiu: potipiai
pateikiami po savo tėviniu tipu, o paieškoje matyti visas kelias, pvz., „Koordinatorius ›
Administratorius“.

Pasirinktame sąraše taip pat gali spausti **Naujas institucijos tipas** arba
**Naujas pareigybės tipas**. Mygtukas rodomas tik turint teisę kurti tos rūšies tipus;
šiukšlinėje jo nėra.

Atidaręs tipo įrašą, skiltyje **Susieti įrašai** tvarkyk institucijas arba pareigybes.
Viena institucija ar pareigybė gali turėti kelis tipus. Institucijos tipo skirtuke **Ryšiai** tvarkyk
[tipų ryšius](/sistema/rysiai#tipu-rysio-kurimas). Failus rask tipo įrašo failų skiltyje; susieti įrašai gali paveldėti savo tipų ir jų tėvų failus.

### Pareigybės tipo atsakomybės {#pareigybes-tipo-atsakomybes}

<ChangelogNote date="2026-10-19" title="Atsakomybių suteikimas vienoje vietoje">

Tipo įraše suteikimai rodomi su aprėptimi ir nuorodomis; jie tvarkomi atsakomybės puslapyje.

</ChangelogNote>

<ChangelogNote date="2026-10-20" title="Potipiai paveldi atsakomybes">

Pareigybės potipis gauna ir tėvinių tipų atsakomybes. Pareigybių tipams negalima suteikti atsakomybių su leidimais „Visur“ ar „Trinti negrįžtamai“.

</ChangelogNote>

Tipo skirtuke **Atsakomybės** matyti visi jam galiojantys suteikimai ir jų aprėptis, taip pat
iš tėvinių tipų paveldėti suteikimai, pažymėti „Paveldėta iš tipo …“. Pavyzdžiui, jei
*Koordinatoriaus* tipui suteiktas „Problemų redaktorius“, jį gauna ir *Pirmininko* bei
*Administratoriaus* potipių pareigybės. Atvirkščiai neveikia: potipiui suteikta atsakomybė
tėviniam tipui neperduodama. Atverk
atsakomybę arba spausk **Atverti atsakomybes**, tada jos skirtuke **Kam suteikta** spausk
**Pridėti tipą**. Pasirink tipą ir, jei paskirtis to reikalauja, padalinį, institucijų tipą ar
instituciją. Suteikimai ir jų pašalinimas tvarkomi šiame viename puslapyje.

Pareigybės tipui galima suteikti tik atsakomybę be leidimų „Visur“ ir „Trinti negrįžtamai“:
vienas tipo suteikimas pasiekia daug pareigybių skirtinguose padaliniuose, todėl jo teisės lieka
kiekvienos pareigybės padalinio ribose. Tokią atsakomybę suteik pareigybėms atskirai. Atsakomybei,
kurią jau gauna tipai, šių leidimų pridėti taip pat negalima.

Jei prie esamo suteikimo rodoma, kad aprėptis nenurodyta arba netinkama, atsakomybės puslapyje
pašalink tą suteikimą ir suteik iš naujo su teisinga aprėptimi. Sistema jos nenuspėja ir pati nekeičia.

## Kas ką gali {#teises}

Rolė **Super Admin** leidžia tvarkyti visus penkis sąrašus. Kitiems naudotojams sąrašai ir veiksmai
rodomi pagal suteiktas teises; teisė tvarkyti institucijas ar pareigybes savaime nesuteikia teisės
keisti bendro tipų sąrašo.

Pareigybės tipą priskirti ar pašalinti galima tik turint jo delegavimą ir teisę redaguoti
kiekvieną keičiamą pareigybę. Atsakomybes valdantys administratoriai gali priskirti visus tinkamus
tipus, bet jiems taip pat reikia prieigos prie pareigybės. Vien teisės redaguoti tipą neužtenka.

Pareigybės tipą perkelti po kitu tėviniu tipu gali tik tas, kas galėtų suteikti visas
atsakomybes, kurias jo pareigybės dėl to gautų ar prarastų – kitiems toks pakeitimas atmetamas.
Kitų padalinių esami priskyrimai išlieka, tačiau jų keisti negalima. Pasibaigusios pareigos
delegavimo nebesuteikia; paskutinę pareigų dieną jis dar galioja.

## Pranešimai ir automatizavimas {#pranesimai}

- **Atsakomybių suteikimas**: priskyrus pareigybės tipą pareigybei, ji gauna tipui ir jo tėviniams tipams suteiktas atsakomybes. Pašalinus tipą, jos atimamos, nebent suteiktos pareigybei ir tiesiogiai. Perkėlus tipą po kitu tėviniu tipu, perkėlus jį į šiukšlinę ar atkūrus, atsakomybės perskaičiuojamos visoms jo ir jo potipių pareigybėms.
- **Teisių atnaujinimas**: atnaujinus tipo atsakomybes ar susiejimus, išvaloma susijusių naudotojų prieigos ir navigacijos talpykla.
- **Vientisumo apsauga**: visam laikui ištrinti tipą, turintį vaikinių tipų, susietų įrašų ar atsakomybių, neleidžiama. Perkėlimui į šiukšlinę šis apribojimas netaikomas.

## Techninė informacija {#technine-informacija}

Bendras katalogas: `/mano/types`. Institucijų tipai: `/mano/institutionTypes`;
pareigybių tipai: `/mano/dutyTypes`. Ankstesni bendri tipų kūrimo ir redagavimo maršrutai
bei viešas `/api/v1/types` maršrutas pašalinti.

Paveldėjimą skaičiuoja `DutyType::withDescendantIds()` / `withAncestorIds()` (tik neištrinti
tipai); `ProjectDutyResponsibilities` juo perskaičiuoja `model_has_roles`. Perkėlimo apsauga –
`DutyTypeRequest`, tipui leidžiamų teisių taisyklė – `TypeGrantablePermissions`. Tipų
pasirinkimo komponentai – `resources/js/Components/AdminForms/TypeTree/`.
