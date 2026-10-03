---
doc_status: reviewed
title: Laikotarpių tvarkyklė
area: dutiableTimeline
models: [Dutiable, Cadence]
last_reviewed: 2026-10-03
tests:
  - tests/Feature/Admin/People/DutiableTimelineControllerTest.php
  - tests/Feature/Admin/People/DutiableDiagnosticsTest.php
  - tests/Feature/Admin/Management/DutiableControllerTest.php
  - tests/Feature/Api/Admin/DutiableTimelineApiControllerTest.php
  - tests/Feature/Api/Admin/DutiableTimelinePreviewTest.php
  - tests/Feature/CrossTenantDutyTest.php
  - tests/Feature/Admin/People/CadenceResolutionTest.php
  - tests/Feature/Admin/People/ResolveCadenceForInstitutionTest.php
  - tests/Feature/ExOfficioSyncTest.php
  - resources/js/Features/Admin/Occupancy/__tests__/AssignDutyUserSheet.component.test.ts
  - resources/js/Pages/Admin/People/__tests__/DutiableTimeline.component.test.ts
  - resources/js/Features/Admin/DutiableTimeline/__tests__/DutiableTimelineEditor.component.test.ts
  - resources/js/Features/Admin/DutiableTimeline/__tests__/DutiableTimelineToolbar.component.test.ts
  - resources/js/Features/Admin/DutiableTimeline/__tests__/DutiableTimelineDirtyBar.component.test.ts
  - resources/js/Features/Admin/DutiableTimeline/__tests__/DutiableTimelineSelectionPanel.component.test.ts
  - resources/js/Features/Admin/DutiableTimeline/__tests__/DutiableTimelineSuggestions.component.test.ts
  - resources/js/Features/Admin/DutiableTimeline/__tests__/barDragMath.test.ts
  - resources/js/Features/Admin/DutiableTimeline/__tests__/barFill.test.ts
  - resources/js/Features/Admin/DutiableTimeline/__tests__/cadencePools.test.ts
  - resources/js/Features/Admin/DutiableTimeline/__tests__/duration.test.ts
  - resources/js/Features/Admin/DutiableTimeline/__tests__/timelineDates.test.ts
  - resources/js/Features/Admin/DutiableTimeline/__tests__/timelineRenderers.test.ts
  - resources/js/Features/Admin/DutiableTimeline/__tests__/useDutiableLayout.test.ts
  - resources/js/Features/Admin/DutiableTimeline/__tests__/useDutiableStaging.test.ts
  - resources/js/Features/Admin/DutiableTimeline/__tests__/useDutiableDiagnostics.test.ts
  - resources/js/Features/Admin/DutiableTimeline/__tests__/DutiableExtrasBadge.component.test.ts
  - resources/js/Components/Patterns/__tests__/FocusModeFrame.component.test.ts
  - tests/Browser/DutiableTimelineTest.php
---

# Laikotarpių tvarkyklė

**Laikotarpių tvarkyklė** – įrankis, kuriuo vienoje laiko juostoje matai ir sutvarkai **visus
vienos institucijos pareigybių laikotarpius**: kas, kada ir kiek laiko ėjo pareigas. Ji skirta
darbui su daug įrašų iš karto – pavyzdžiui, kadencijos pabaigoje ar tvarkant senus, netiksliai
suvestus duomenis. Vieną laikotarpį patogiau pakeisti jo formoje (žr. žemiau).

Tvarkyklę rasi:

- ViSAK → **Laikotarpių tvarkyklė** (`/mano/dutiables/timeline`);
- per **+ Sukurti → Laikotarpių tvarkyklė**;
- pareigybės ar nario puslapyje per **⋯ → Tvarkyti laikotarpius** – tada ji atsidaro lange ir rodo
  tik tos pareigybės ar to nario laikotarpius.

## Pareigybės laikotarpis {#laikotarpis}

**Pareigybės laikotarpis** – vieno žmogaus pareigos vienoje pareigybėje: nuo kada ir (jei jau
baigė) iki kada jis jas ėjo. Pagal laikotarpį sistema sprendžia, ar žmogus šiuo metu eina pareigas,
todėl nuo jo priklauso jo teisės, gaunami pranešimai ir tai, kas rodoma viešai.

Vienas žmogus toje pačioje pareigybėje gali turėti kelis laikotarpius (pvz., pertrauka tarp
kadencijų), o perrinktas narys dažniausiai turi vieną laikotarpį per kelias kadencijas.

### Vieno laikotarpio forma

Pareigybės puslapio narių sąraše prie žmogaus spausk **Redaguoti** – atsidarys forma **Redaguoti
pareigybės laikotarpį** (telefone – iš apačios). Joje:

- **Pradžios data** ir **Pabaigos data** (neprivaloma – be jos laikotarpis neterminuotas);
- **Papildomas el. paštas** – rodomas prie šio nario kontaktų;
- **Papildoma nuotrauka** – rodoma vietoj nario profilio nuotraukos;
- **Studijų programa** ir jos pastaba (pvz., „1 kursas“) – rodomos skliaustuose po pareigybės
  pavadinimu;
- **Viešas aprašymas** ir ar viešai naudoti originalų pareigybės pavadinimą;
- **Užbaigti pareigas šiandien** – nustato šiandienos pabaigos datą, kuri dar įskaitoma
  (žr. [laikotarpio datas](/organizacija/pareigybes#laikotarpio-datos)); **Ištrinti priskyrimą** –
  pašalina laikotarpį visai (tai daryk tik klaidingai sukurtam įrašui).

<DocScreenshot name="dutiable-sheet" narrow alt="Forma „Redaguoti pareigybės laikotarpį“: pareigybė, narys, pradžios ir pabaigos datos, papildomas el. paštas ir nuotrauka" caption="Vieno pareigybės laikotarpio forma pareigybės puslapyje." />

*Ex officio* laikotarpio (žr. [Ex officio pareigos](#ex-officio)) datų formoje keisti negalima, o
užbaigti ar ištrinti jo nesiūloma.

## Kaip tai veikia

<DocScreenshot name="dutiable-timeline" alt="Laikotarpių tvarkyklė: kairėje pareigybės ir nariai, per vidurį laikotarpių juostos ant kadencijų fono, dešinėje pažymėtas įrašas ir pasiūlymai" caption="Tamsios juostos – dabartinės pareigos, pilkos – pasibaigusios, fone – kadencijos." href="/mano/dutiables/timeline" />

### Institucija

Tvarkyklė visada rodo vieną instituciją – jos pavadinimas yra puslapio antraštė. Paspaudęs jį
pasirinksi kitą: pirmiausia siūlomos institucijos, kuriose pats eini pareigas (daugiausia pareigų
turinčios – viršuje), o **Ieškoti tarp visų institucijų…** leidžia rasti bet kurią, kurią gali
matyti. Rodyklė šalia pavadinimo atveria institucijos puslapį.

- Pirmą kartą atidarius rodoma institucija, kurioje eini daugiausia pareigų.
- Vėliau atsidaro ta, kurią žiūrėjai paskutinę, nebent adrese nurodyta kita
  (`?institution=…`). Tokią nuorodą gali nusiųsti kolegai.

### Kaip skaityti juostas {#juostos}

Kairėje – pareigybės, po kiekviena – ją ėję ar einantys žmonės ir kiek laiko jie ėjo pareigas.
Kiekviena juosta – vienas laikotarpis. Prie nario rodoma žyma, jei priskyrimas turi papildomų
duomenų (kontaktinę nuotrauką, el. paštą, studijų programą ar viešą aprašymą).

| Juosta | Reikšmė |
|---|---|
| **Tamsi** | Dabartinės pareigos: pabaigos nėra arba ji dar neatėjo. |
| **Pilka** | Pasibaigusios pareigos. |
| **Blyški su punktyriniu kraštu** | *Ex officio* pareigos – jos sekamos iš kitos pareigybės. |
| **Gintarinė** | Neišsaugotas pakeitimas. Blyškesnė gintarinė – *ex officio* laikotarpis, kuris pasikeis kartu su tavo keičiamu. |
| **Su violetiniu kraštu** | Žmogus atstovauja kitam padaliniui. |
| **Su rodykle dešinėje** | Laikotarpis neterminuotas. |
| **Su įpjova ir punktyru** | Pradžia ar pabaiga ne mėnesio pradžioje / pabaigoje – data, pvz., 18 d., nurodyta sąmoningai. |

Tie patys žymėjimai ir tempimo taisyklės surašyti prie **ⓘ** mygtuko įrankių juostoje. Siaurame
ekrane vardų stulpelis rodo tik vardus; jį galima praplatinti tempiant jo kraštą.

### Kadencijos

Fone matomos **kadencijos** – laikotarpiai, kuriems renkami nariai (dažniausiai nuo liepos 1 d. iki
birželio 30 d.). Jos nustatomos [Nustatymuose → Kadencijos](/sistema/nustatymai#kadencijos):

- jei institucija turi **savų kadencijų**, rodomos tik jos;
- jei neturi – rodomos **bendros VU SA kadencijos**.

Pagal kadencijas pritraukiamos tempiamos juostos, siūlomi taisymai ir veikia mygtukas **Lygiuoti**.

### Ex officio pareigos {#ex-officio}

Kai kurios pareigybės suteikia kitas pareigas automatiškai (*ex officio*), pvz., pirmininkas kartu
tampa kito organo nariu. Tokio laikotarpio datos visada sutampa su šaltinio laikotarpiu: pakeitus
šaltinį, po kelių akimirkų pasikeičia ir *ex officio* laikotarpis. Pats jo tempti ar keisti negali –
pažymėjęs jį, šoniniame skydelyje rasi mygtuką šaltinio įrašui pažymėti.

### Filtrai ir rodinys

- **Kadencija** – rodo tik pasirinktų kadencijų laikotarpius. Kelias kadencijas apimantis laikotarpis
  rodomas prie kiekvienos jų. Tame pačiame meniu pasirenkama, ar rodyti **pasibaigusius**
  laikotarpius; kai jie paslėpti, prie filtro matyti ženklas.
- **Padalinys** – atsiranda tik tada, kai yra kitiems padaliniams atstovaujančių žmonių.
- Mygtuku virš vardų suskleisi ar išskleisi visas pareigybes. Suskleista pareigybė rodo, kiek
  laikotarpių joje yra ir kiek laiko ji buvo užimta.
- Kur nurodytos studijų programos, įrašus gali surikiuoti pagal jas.
- Mastelis ir „rodyti pasibaigusius“ išsaugomi ir kitą kartą atsidaro tokie patys.

### Išdėstymas ir visas ekranas {#visas-ekranas}

<ChangelogNote version="v3.0" date="2026-10-02" title="Naujas išdėstymas ir visas ekranas">

Iki v3.0 pasirinkimas, pasiūlymai ir išsaugojimas buvo po grafiku ir užėmė nemažą ekrano dalį.
Dabar išsaugojimas yra įrankių juostoje, o pasirinkimas ir pasiūlymai – šoniniame skydelyje.

</ChangelogNote>

Puslapis užima visą ekrano aukštį: ilgas grafikas slenka savo viduje, todėl įrankių juosta su
**Išsaugoti** visada matoma. Dešinėje – šoninis skydelis su pažymėtu įrašu ir siūlomais taisymais.
Telefone ir planšetėje skydelis atsidaro iš apačios, kai pažymi juostą, arba mygtuku **Pažymėta ir
pasiūlymai**.

Mygtukas **⛶** išplečia grafiką per visą ekraną. Iš jo atidaryti langai ir meniu rodomi virš grafiko.
Grįžti gali tuo pačiu mygtuku arba **Esc** (jei atidarytas langas ar meniu, pirmas **Esc** uždaro jį).

## Veiksmai

Visi pakeitimai pirmiausia tik **pažymimi** (juosta tampa gintarinė), o į sistemą įrašomi tik
paspaudus **Išsaugoti**.

### Tempimas

- **Visa juosta** slenka mėnesiais, o **mėnesio diena išlieka**: gegužės 18 d. pradžia, patempta dviem
  mėnesiais, taps liepos 18 d., ne liepos 1 d. Pažymėta juosta po tempimo lieka pažymėta.
- **Juostos kraštas** keičia tik pradžią arba pabaigą ir pritraukiamas prie artimiausios ribos:
  kadencijos, mėnesio pradžios (1 d.), šiandienos arba gretimo to paties žmogaus laikotarpio.
  Neterminuoto laikotarpio pabaigą rasi prie rodyklės.
- **Alt** – be pritraukimo, tikslia diena. **Ctrl (⌘)** – tempti visus pažymėtus kartu.
- **Esc** – atšaukti tempimą.

### Pažymėtas įrašas

Pažymėk juostą grafike arba varnelę prie vardo (su **Ctrl / ⌘** – kelias). Šoniniame skydelyje:

- **Pradžia** ir **Pabaiga** – tikslios datos; **Palikti neterminuotą** išvalo pabaigą;
- **Lygiuoti** – pradžią ir pabaigą perkelia į jų kadencijų ribas. Data, nutolusi nuo ribos daugiau
  nei 45 dienas, laikoma sąmoninga ir nekeičiama;
- **Užbaigti** – nustato pabaigos datą neterminuotam laikotarpiui (galima pasirinkti datą arba
  greitąją parinktį „Vakar“);
- **↗** – atveria [vieno laikotarpio formą](#laikotarpis); **šiukšlinė** – pašalina laikotarpį
  (atkurti nebus galima);
- pažymėjus kelis: **Taikyti datas** visiems iš karto, o to paties žmogaus tos pačios pareigybės
  laikotarpius – **Sujungti** į vieną (nuo ankstyviausios pradžios iki vėliausios pabaigos). Išsaugomas
  ankstesnysis įrašas, perimantis vėlesnių įrašų papildomus duomenis (el. paštą, nuotrauką, programą);
  jei bent vienas laikotarpis neterminuotas, sujungtas įrašas taip pat lieka neterminuotas (*ex officio*
  laikotarpių sujungti negalima).

### Siūlomi taisymai {#pasiulymai}

Tvarkyklė pati randa neatitikimus. Ne kiekvienas jų – klaida: iš anksto pažymėti tik tie
pataisymai, kurie laikotarpį **trumpina** ir todėl negali niekam netyčia grąžinti teisių. Kitus
pažymėk pats ir spausk **Taikyti pažymėtus**. Paspaudus pasiūlymą, pažymimi su juo susiję įrašai.

| Pasiūlymas | Kada atsiranda | Pataisymas |
|---|---|---|
| Persidengiantys laikotarpiai | To paties žmogaus du tos pačios pareigybės laikotarpiai persidengia. | Ankstesnis baigiamas diena prieš vėlesnio pradžią. **Pažymėtas iš anksto.** |
| Pabaiga anksčiau už pradžią | Įrašo datos sukeistos. | Pabaiga išvaloma. Nepažymėtas, nes taip laikotarpis tampa neterminuotas. |
| Vienas laikotarpis baigiasi kito pradžios dieną | Pabaiga turėtų būti diena prieš kitą pradžią. | Pabaiga perkeliama diena anksčiau. |
| *Ex officio* datos nesutampa su šaltiniu | Automatinis sekimas nesuveikė. | Tvarkoma perkeliant šaltinio laikotarpį. |
| Data nesutampa su kadencijos riba | Data nuo ribos nutolusi ne daugiau kaip 45 dienas. | Lygiuojama su kadencija. |
| Neterminuota nuo ankstesnės kadencijos | Neterminuotas laikotarpis prasidėjo jau pasibaigusioje kadencijoje. Dažniausiai tai **perrinktas narys**, todėl pasiūlymai suskleisti į vieną eilutę. | Jei žmogus pareigų nebeeina – užbaigti tos kadencijos pabaiga. |
| Perrinkta kelioms kadencijoms | Laikotarpis apima kelias kadencijas. Tai ne klaida, rodoma tik informacijai, suskleista. | Nėra. |
| Užimta mažiau vietų, nei numatyta | Pareigybėje yra laisvų vietų. | Nėra – tai sprendimas apie žmones. |
| Įtartinas *ex officio* įrašas be šaltinio | Žmogus turi pareigas pagal kitas pareigas (*ex officio*), bet pačių šaltinio pareigų nebeeina, o ryšys nutrūkęs. | Nėra – šie įrašai suteikia realias teises, todėl automatiškai neliesti. Paleisk komandą `duties:audit-ex-officio`. |

### Peržiūra ir išsaugojimas

Kai yra neišsaugotų pakeitimų, įrankių juostoje matai jų skaičių ir tris mygtukus:

- **Atšaukti** – atmeta visus neišsaugotus pakeitimus;
- **Peržiūrėti** – parodo, kaip įrašai atrodys po pakeitimų, kurie bus praleisti ir kiek *ex officio*
  įrašų pasikeis kartu;
- **Išsaugoti** – įrašo pakeitimus.

Mygtukai **Lygiuoti**, **Užbaigti**, **Taikyti datas** ir **Taikyti pažymėtus** visada pirmiausia
parodo peržiūrą.

## Kas ką gali {#teises}

| Rolė | Atidaryti tvarkyklę | Keisti laikotarpius |
|---|---|---|
| **Komunikacijos koordinatorius** | ✓ | ✓, savo padalinio pareigybėse |
| **Studentų atstovų koordinatorius** | ✓ | ✓, savo padalinio pareigybėse |
| **Centrinio biuro komunikacijos koordinatorius** | ✓ | ✓, visų padalinių pareigybėse |
| **Centrinio biuro studentų atstovų koordinatorius** | ✓ | ✓, visų padalinių pareigybėse |
| **Studentų atstovas** | – | – |

- Institucija rodoma tik tada, kai tavo rolė leidžia ją matyti.
- Jei pareigybė leidžia į ją skirti ir kitų padalinių narius, tų padalinių koordinatoriai gali
  keisti **savo padalinio narių** laikotarpius joje.
- Įrašai, kurių keisti negali, rodomi, bet jų tempti negalima, o skydelyje parašyta „Šio įrašo keisti
  negali“. Jei tarp keičiamų įrašų yra bent vienas, kurio keisti negali, visas pakeitimų rinkinys
  atmetamas.

## Pranešimai ir automatizavimas {#pranesimai}

| Kada | Kas nutinka |
|---|---|
| Išsaugai pakeitimą, liečiantį **tavo paties** pareigas | Pirmiausia parodomas įspėjimas, kad gali prarasti teises; pakeitimas įrašomas tik jį patvirtinus. |
| Pasikeičia šaltinio laikotarpis | Susiję *ex officio* laikotarpiai atnaujinami automatiškai. |
| Pasikeičia bet kuris laikotarpis | Teisės iš karto skaičiuojamos pagal naujas datas: pasibaigus pareigoms, su jomis suteiktos teisės dingsta. |

## Techninė informacija {#technine-informacija}

Šis skyrius skirtas tiems, kurie tvarko roles ar tikrina sistemos elgseną.

### Teisės

- Tvarkyklę atveria `viewAny` pareigybėms (`duties.read`), rodomą instituciją – `view`
  (`institutions.read`). Duomenis teikia `DutiableTimelineApiController` (`GET api.v1.admin.dutiableTimeline.index`),
  kuris tikrina `view` rodomai institucijai, pareigybei ar naudotojui. Didelėms institucijoms taikoma
  1500 įrašų riba (`MAX_ROWS`) – ją pasiekus, siūloma filtruoti pareigybes.
- Sausa pakeitimų peržiūra atliekama per `POST api.v1.admin.dutiableTimeline.preview`, kuri taip pat
  įvertina, ar pakeitimai palies paties administratoriaus pareigas (`self_affecting`).
- Keitimas ir sujungimas (`ApplyDutiableTimelineRequest`, `MergeDutiablesRequest`) reikalauja
  `DutiablePolicy::manageDutiable` **kiekvienam** įrašui; jis remiasi `DutyPolicy::managePeople`
  (`duties.update` pareigybės padalinyje arba jos `assignableTenants` padalinyje).
- Super administratoriui įspėjimas dėl savo teisių nerodomas.

### Kaip tai įgyvendinta

- Pakeitimai siunčiami kaip operacijų sąrašas (`set_dates`, `align_to_cadence`,
  `close_open_ended`); serveris jį iš naujo suplanuoja `PlanDutiableTimelineChanges` ir įrašo
  `ApplyDutiableTimelineChanges` vienoje transakcijoje. Kliento peržiūra tik informacinė.
- *Ex officio* įrašai (`via_dutiable_id`) operacijose praleidžiami ir sinchronizuojami
  `SyncExOfficioDutiables` klausytojo po `DutiableChanged` įvykio.
- Pasiūlymus skaičiuoja `AnalyzeDutiableTimeline`, klientas tą patį kartoja neišsaugotai būsenai
  (`useDutiableDiagnostics`); lygiavimo riba – 45 dienos.
- Institucijos kadencijos parenkamos `ResolveCadenceForInstitution`: savos kadencijos visiškai
  pakeičia bendras.
- Vieno laikotarpio forma – `AssignDutyUserSheet`, saugo `DutiableController`.
- Visas ekranas (`Patterns/FocusModeFrame`) nėra modalinis langas: pats grafikas prisegamas virš
  apvalkalo, todėl iš jo atidaryti langai rodomi virš jo.
