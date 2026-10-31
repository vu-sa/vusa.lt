---
doc_status: reviewed
title: Laikotarpių tvarkyklė
area: dutiableTimeline
models: [Dutiable, Cadence]
last_reviewed: 2026-10-31
tests:
  - tests/Feature/Admin/People/DutiableTimelineControllerTest.php
  - tests/Feature/Admin/People/DutiableDiagnosticsTest.php
  - tests/Feature/Duties/ExOfficioReconcilerTest.php
  - tests/Feature/Admin/Management/DutiableControllerTest.php
  - tests/Feature/Api/Admin/DutiableTimelineApiControllerTest.php
  - tests/Feature/Api/Admin/DutiableTimelinePreviewTest.php
  - tests/Feature/CrossTenantDutyTest.php
  - tests/Feature/Admin/People/CadenceResolutionTest.php
  - tests/Feature/Admin/People/ResolveCadenceForInstitutionTest.php
  - tests/Feature/ExOfficioSyncTest.php
  - tests/Feature/Admin/People/DutyExOfficioReviewTest.php
  - tests/Feature/Permissions/AuditExOfficioDutiablesTest.php
  - resources/js/Features/Admin/DutiableTimeline/__tests__/ExOfficioReviewSheet.component.test.ts
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

**Laikotarpių tvarkyklė** – įrankis, kuriuo vienoje laiko juostoje rodomi ir sutvarkomi **visi
vienos institucijos pareigybių laikotarpiai**: kas, kada ir kiek laiko ėjo pareigas. Ji skirta
darbui su daug įrašų iš karto – pavyzdžiui, kadencijos pabaigoje ar tvarkant senus, netiksliai
suvestus duomenis. Vieną laikotarpį patogiau pakeisti jo formoje (žr. žemiau).

Tvarkyklė pasiekiama:

- ViSAK → **Laikotarpių tvarkyklė** (`/mano/dutiables/timeline`);
- per **Veiksmai → Laikotarpių tvarkyklė**;
- pareigybės ar nario puslapyje per **⋯ → Tvarkyti laikotarpius** – tada ji atsidaro lange ir rodo
  tik tos pareigybės ar to nario laikotarpius.

## Pareigybės laikotarpis {#laikotarpis}

**Pareigybės laikotarpis** – vieno žmogaus pareigos vienoje pareigybėje: nuo kada ir (jei jau
baigė) iki kada jis jas ėjo. Pagal laikotarpį sistema sprendžia, ar žmogus šiuo metu eina pareigas,
todėl nuo jo priklauso jo teisės, gaunami pranešimai ir tai, kas rodoma viešai.

Vienas žmogus toje pačioje pareigybėje gali turėti kelis laikotarpius (pvz., pertrauka tarp
kadencijų), o perrinktas narys dažniausiai turi vieną laikotarpį per kelias kadencijas.

### Vieno laikotarpio forma

Pareigybės puslapio narių sąraše prie žmogaus paspaudus **Redaguoti**, atsidaro forma **Redaguoti
pareigybės laikotarpį** (telefone – iš apačios). Joje:

- **Kadencija** – pasirinkus kadenciją datos užpildomos pagal ją
  (žr. [nario priskyrimą](/organizacija/pareigybes#priskirti-nari));
- **Pradžios data** ir **Pabaigos data** (neprivaloma – be jos laikotarpis neterminuotas);
- suskleistuose **Papildomuose nustatymuose**:
  - **Papildomas el. paštas** – rodomas prie šio nario kontaktų;
  - **Papildoma nuotrauka** – rodoma vietoj nario profilio nuotraukos;
  - **Studijų programa** ir jos pastaba (pvz., „1 kursas“) – rodomos skliaustuose po pareigybės
    pavadinimu (tik kuratorių pareigybėms arba jei jau nurodytos);
  - ar viešai naudoti originalų pareigybės pavadinimą ir **Papildomas aprašymas**, rodomas vietoj
    pareigybės aprašymo;
- **Užbaigti pareigas šiandien** – nustato šiandienos pabaigos datą, kuri dar įskaitoma
  (žr. [laikotarpio datas](/organizacija/pareigybes#laikotarpio-datos)); **Ištrinti priskyrimą** –
  pašalina laikotarpį visai (tai rekomenduojama daryti tik klaidingai sukurtam įrašui).

<DocScreenshot name="dutiable-sheet" phone alt="Forma „Redaguoti pareigybės laikotarpį“: pareigybė, narys, kadencija, pradžios ir pabaigos datos ir suskleisti papildomi nustatymai" caption="Vieno pareigybės laikotarpio forma pareigybės puslapyje." />

*Ex officio* laikotarpio (žr. [Ex officio pareigos](#ex-officio)) datų formoje keisti negalima, o
užbaigti ar ištrinti jo nesiūloma.

## Kaip tai veikia

<DocScreenshot name="dutiable-timeline" alt="Laikotarpių tvarkyklė: kairėje pareigybės ir nariai, per vidurį laikotarpių juostos ant kadencijų fono, dešinėje pažymėtas įrašas ir pasiūlymai" caption="Tamsios juostos – dabartinės pareigos, pilkos – pasibaigusios, fone – kadencijos." href="/mano/dutiables/timeline" />

### Institucija

Tvarkyklė visada rodo vieną instituciją – jos pavadinimas yra puslapio antraštė. Paspaudus jį
galima pasirinkti kitą: pirmiausia siūlomos institucijos, kuriose pats naudotojas eina pareigas (daugiausia pareigų
turinčios – viršuje), o **Ieškoti tarp visų institucijų…** leidžia rasti bet kurią prieinamą
instituciją. Rodyklė šalia pavadinimo atveria institucijos puslapį.

- Pirmą kartą atidarius rodoma institucija, kurioje eini daugiausia pareigų.
- Vėliau atsidaro ta, kurią žiūrėjai paskutinę, nebent adrese nurodyta kita
  (`?institution=…`). Tokia nuoroda galima pasidalyti su kolegomis.

### Kaip skaityti juostas {#juostos}

Kairėje – pareigybės, po kiekviena – ją ėję ar einantys žmonės ir kiek laiko jie ėjo pareigas.
Kiekviena juosta – vienas laikotarpis. Prie nario rodoma žyma, jei priskyrimas turi papildomų
duomenų (kontaktinę nuotrauką, el. paštą, studijų programą ar viešą aprašymą).

| Juosta | Reikšmė |
|---|---|
| **Tamsi** | Dabartinės pareigos: pabaigos nėra arba ji dar neatėjo. |
| **Pilka** | Pasibaigusios pareigos. |
| **Blyški su punktyriniu kraštu** | *Ex officio* pareigos – jos sekamos iš kitos pareigybės. |
| **Gintarinė** | Neišsaugotas pakeitimas. Kaip pasikeis susiję *ex officio* laikotarpiai, matyti peržiūroje prieš išsaugant. |
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
šaltinį, kartu pasikeičia ir *ex officio* laikotarpis. Šio laikotarpio tempti ar keisti
rankiniu būdu negalima – jį pažymėjus, šoniniame skydelyje pateikiamas mygtukas šaltinio įrašui pažymėti.

### Filtrai ir rodinys

- **Kadencija** – rodo tik pasirinktų kadencijų laikotarpius. Kelias kadencijas apimantis laikotarpis
  rodomas prie kiekvienos jų. Tame pačiame meniu pasirenkama, ar rodyti **pasibaigusius**
  laikotarpius; kai jie paslėpti, prie filtro matyti ženklas.
- **Padalinys** – atsiranda tik tada, kai yra kitiems padaliniams atstovaujančių žmonių.
- Mygtuku virš vardų suskleidžiamos arba išskleidžiamos visos pareigybės. Suskleista pareigybė rodo, kiek
  laikotarpių joje yra ir kiek laiko ji buvo užimta.
- Kur nurodytos studijų programos, įrašus galima surikiuoti pagal jas.
- Mastelis ir „rodyti pasibaigusius“ išsaugomi ir kitą kartą atsidaro tokie patys.

### Išdėstymas ir visas ekranas {#visas-ekranas}

<ChangelogNote date="2026-10-02" title="Naujas išdėstymas ir visas ekranas">

Iki v3 pasirinkimas, pasiūlymai ir išsaugojimas buvo po grafiku ir užėmė nemažą ekrano dalį.
Dabar išsaugojimas yra įrankių juostoje, o pasirinkimas ir pasiūlymai – šoniniame skydelyje.

</ChangelogNote>

Puslapis užima visą ekrano aukštį: ilgas grafikas slenka savo viduje, todėl įrankių juosta su
**Išsaugoti** visada matoma. Dešinėje – šoninis skydelis su pažymėtu įrašu ir siūlomais taisymais.
Telefone ir planšetėje skydelis atsidaro iš apačios, kai pažymi juostą, arba mygtuku **Pažymėta ir
pasiūlymai**.

Mygtukas **⛶** išplečia grafiką per visą ekraną. Iš jo atidaryti langai ir meniu rodomi virš grafiko.
Grįžti galima tuo pačiu mygtuku arba **Esc** (jei atidarytas langas ar meniu, pirmas **Esc** uždaro jį).

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
pataisymus galima pažymėti ranka ir paspausti **Taikyti pažymėtus**. Paspaudus pasiūlymą, pažymimi su juo susiję įrašai.

| Pasiūlymas | Kada atsiranda | Pataisymas |
|---|---|---|
| Persidengiantys laikotarpiai | To paties žmogaus du tos pačios pareigybės laikotarpiai persidengia. | Ankstesnis baigiamas diena prieš vėlesnio pradžią. **Pažymėtas iš anksto.** |
| Pabaiga anksčiau už pradžią | Įrašo datos sukeistos. | Pabaiga išvaloma. Nepažymėtas, nes taip laikotarpis tampa neterminuotas. |
| Vienas laikotarpis baigiasi kito pradžios dieną | Pabaiga turėtų būti diena prieš kitą pradžią. | Pabaiga perkeliama diena anksčiau. |
| *Ex officio* datos nesutampa su šaltiniu | Automatinis sekimas nesuveikė. | Tvarkoma perkeliant šaltinio laikotarpį. |
| Data nesutampa su kadencijos riba | Data nuo ribos nutolusi ne daugiau kaip 45 dienas. | Lygiuojama su kadencija. |
| Neterminuota nuo ankstesnės kadencijos | Neterminuotas laikotarpis prasidėjo jau pasibaigusioje kadencijoje. Dažniausiai tai **perrinktas narys**, todėl pasiūlymai suskleisti į vieną eilutę. | Jei žmogus pareigų nebeeina – užbaigti tos kadencijos pabaiga. |
| Perrinkta kelioms kadencijoms | Laikotarpis apima kelias kadencijas. Tai ne klaida, rodoma tik informacijai, suskleista. | Nėra. |
| Įtartinas *ex officio* įrašas be šaltinio | Laikotarpis nesusietas su šaltiniu, nors žmogus tuo pačiu metu ėjo jį suteikiančias pareigas. Kas šaltinio pareigų tuo metu nėjo, nežymimas. | Pareigybės puslapyje pasirink [Tikrinti ex officio](#ex-officio-tikrinimas). |

### Peržiūra ir išsaugojimas

Kai yra neišsaugotų pakeitimų, įrankių juostoje rodomas jų skaičius ir trys mygtukai:

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
- Įrašai, kurių keisti negali, rodomi, bet jų tempti negalima, o skydelyje nurodoma „Šio įrašo keisti
  negali“. Jei tarp keičiamų įrašų yra bent vienas, kurio keisti negali, visas pakeitimų rinkinys
  atmetamas.

## Pranešimai ir automatizavimas {#pranesimai}

| Kada | Kas nutinka |
|---|---|
| Išsaugomas pakeitimas, liečiantis **tavo paties** pareigas | Pirmiausia parodomas įspėjimas, kad gali prarasti teises; pakeitimas įrašomas tik jį patvirtinus. |
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
- *Ex officio* įrašai (`via_dutiable_id`) operacijose praleidžiami, o juos rašo tik
  `ReconcileExOfficioSeats` toje pačioje transakcijoje kaip ir šaltinio pakeitimą.
- Pasiūlymus skaičiuoja `AnalyzeDutiableTimeline`, klientas tą patį kartoja neišsaugotai būsenai
  (`useDutiableDiagnostics`); lygiavimo riba – 45 dienos.
- Institucijos kadencijos parenkamos `ResolveCadenceForInstitution`: savos kadencijos visiškai
  pakeičia bendras.
- Vieno laikotarpio forma – `AssignDutyUserSheet`, saugo `DutiableController`.
- Visas ekranas (`Patterns/FocusModeFrame`) nėra modalinis langas: pats grafikas prisegamas virš
  apvalkalo, todėl iš jo atidaryti langai rodomi virš jo.


## Ex officio priskyrimų peržiūra

Peržiūra rodoma tik pasirinkus savarankišką laikotarpį, kuris sutampa su to paties žmogaus pareigomis, suteikiančiomis šią *ex officio* vietą. Tada galima patvirtinti, kad jis nepriklauso nuo kitų pareigų, arba susieti jį su siūlomu šaltiniu. Patvirtintą laikotarpį vėliau vis dar galima susieti. Susiejant rodomos naujos datos: vėliau jos seks šaltinį, o ištrynus šaltinį bus ištrintas ir susietas priskyrimas. Keisti galima tik priskyrimus, kuriuos turi teisę valdyti. Automatinis sinchronizavimas savarankiškų laikotarpių nekeičia.

Sujungus šaltinio laikotarpius, susieti ex officio priskyrimai apima visą sujungtą laikotarpį, įskaitant pertraukas. Papildomi susietų priskyrimų duomenys išsaugomi. Neaiškūs seni priskyrimai žymimi peržiūrai; įspėjimas savaime nereiškia, kad pareigos suteiktos klaidingai.

### Tikrinimas visoje istorijoje {#ex-officio-tikrinimas}

<ChangelogNote date="2026-10-30" title="Ex officio tikrinimas">

Sistema laikotarpius lygina su šaltinio pareigomis tuo pačiu metu, o ne su šiandienos būsena.

</ChangelogNote>

### Kaip *ex officio* laikotarpiai seka pagrindines pareigas {#ex-officio-sekimas}

Kiekvienam pagrindinių pareigų laikotarpiui, pagal kiekvieną pareigybės formoje nurodytą ryšį, tenka vienas prijungtas *ex officio* laikotarpis su tomis pačiomis datomis.

| Kas nutinka | Rezultatas |
| --- | --- |
| Pridedamas ar keičiamas pagrindinių pareigų laikotarpis | Prijungtas laikotarpis sukuriamas arba atnaujinamas tuo pačiu išsaugojimu. |
| Ištrinamas pagrindinių pareigų laikotarpis | Ištrinami ir prijungti laikotarpiai. |
| Pareigybei pridedamas ryšys | Dabartiniams ir būsimiems laikotarpiams sukuriami prijungti laikotarpiai su pagrindinių pareigų datomis. Praeities laikotarpius pridėk per **Tikrinti ex officio**. |
| Pašalinamas ryšys | Prasidėję prijungti laikotarpiai baigiasi tą dieną, neprasidėję ištrinami. Toliau jie nebesikeičia. Tą patį ryšį pridėjus iš naujo, laikotarpis vėl seka pagrindines pareigas, todėl pertrauka istorijoje nelieka. |

Pašalinus ryšį, laikotarpis lieka pažymėtas kaip *ex officio* ir užrakintas. Jei žmogus šias pareigas eina toliau arba laikotarpį reikia ištrinti, laikotarpių tvarkyklėje pasirink jį ir spausk **Atsieti nuo pareigų**. Datos lieka tokios pačios, o laikotarpis tampa savarankiškas, todėl jį galima keisti ar ištrinti. Kol ryšys yra, atsieti negalima: pirmiausia jį pašalink pareigybės formoje.

Institucijos uždarymo metu pasibaigę laikotarpiai nekeičiami. Vėl atidarius instituciją, *ex officio* laikotarpis tęsiamas nuo atidarymo dienos, o uždarymo metas neatkuriamas.

Kas naktį sistema tikrina, ar *ex officio* laikotarpiai sutampa su pagrindinėmis pareigomis ir ar kurio nors netrūksta. Radinius matyti sistemos būsenos puslapyje. Tikrinimas nieko nekeičia.

### Tikrinimas {#ex-officio-tikrinimo-eiga}

Pareigybės puslapyje atidaryk papildomų veiksmų meniu ir pasirink **Tikrinti ex officio**. Veiksmas rodomas, jei gali tvarkyti pareigybės narius ir pareigybė susijusi su *ex officio* pareigomis. Tikrinama tik ši pareigybė: atidarius šaltinio pareigybę, rodomi tik jos pačios suteikiami laikotarpiai, o atidarius tikslinę – visi jos laikotarpiai ir jų šaltiniai.

Sistema peržiūri visą istoriją ir siūlo:

| Pasiūlymas | Kada | Kas pasikeičia |
| --- | --- | --- |
| Susieti su šaltiniu | Laikotarpis sutampa su šaltinio pareigomis, bet nesusietas. | Laikotarpis perima šaltinio datas. Laikas už šaltinio ribų lieka atskiru savarankišku laikotarpiu. |
| Pridėti trūkstamą laikotarpį | Žmogus ėjo šaltinio pareigas, bet *ex officio* laikotarpio nėra. | Sukuriamas susietas laikotarpis, taip pat ir pasibaigusiems laikotarpiams. |
| Suvienodinti datas | Susieto laikotarpio datos nesutampa su šaltiniu. | Datos perkeliamos pagal šaltinį. |
| Ištrinti dubliuojantį įrašą | Nesusietą laikotarpį visą dengia to paties padalinio susieti laikotarpiai. | Trūkstami duomenys perkeliami į susietą laikotarpį, o įrašas ištrinamas. |

Prieš taikant rodomos senos ir naujos datos. Iš anksto pažymėti tik pakeitimai, kurie nekeičia šiandienos prieigos ir vietų kvotos. Trūkstamus laikotarpius, dublikatus ir pakeitimus, kurie keičia prieigą ar padalinį, pažymi pats. Būsimų pareigų teisės galioja jau dabar, todėl toks pakeitimas laikomas prieigos pakeitimu.

Sistema nežino, nuo kada pareigos suteikia kitas, todėl istorijai taiko šiandienos ryšį. Pridėk trūkstamą laikotarpį tik tada, jei ryšys galiojo ir tuo metu.

- **Šaltinio pareigos baigėsi, bet laikotarpis tęsiasi.** Pasirink, ar užbaigti jį kartu su šaltiniu (numatyta), ar likusį laiką palikti savarankišku laikotarpiu.
- **Sutapimas turi būti ilgesnis nei 7 dienos.** Trumpesnis sutapimas laikomas kadencijų persidengimu.
- **Niekas nesiūloma**, jei žmogus šaltinio pareigų tuo metu nėjo arba laikotarpis patvirtintas kaip savarankiškas.
- **Kai kurie įrašai tik išvardijami** su priežastimi: kai laikotarpis tik iš dalies sutampa su jau susietu, kai šaltinis pats yra *ex officio* laikotarpis arba kai neturi teisės tvarkyti visų susijusių laikotarpių.
- Atskiriant išsaugoma kiekviena diena, net jei liko tik kelios. Laikotarpio dalis prarandama tik pasirinkus užbaigti jį kartu su šaltiniu.
- Atskirti savarankiški laikotarpiai nuotraukos neperima – ji lieka pradiniame įraše.
