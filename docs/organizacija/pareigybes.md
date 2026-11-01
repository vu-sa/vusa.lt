---
doc_status: reviewed
title: Pareigybės
area: duties
models: [Duty, Role, Tenant, Type, Institution, ResponsibilityGrant]
last_reviewed: 2026-11-01
tests:
  - tests/Browser/CollectionTrashRefreshTest.php
  - resources/js/Components/AdminForms/__tests__/DutyForm.component.test.ts
  - resources/js/Pages/Admin/People/__tests__/ShowDuty.component.test.ts
  - tests/Feature/Api/Admin/RecordActivityStatusTest.php
  - tests/Feature/Permissions/DutyTypeAssignmentTest.php
  - resources/js/Pages/Admin/People/__tests__/IndexDuty.component.test.ts
  - tests/Feature/Admin/DutyMergeTest.php
  - tests/Feature/Api/Admin/DutyApiControllerTest.php
  - tests/Browser/PostCreateNavigationTest.php
  - tests/Browser/DutySeatReferenceTest.php
  - resources/js/Pages/Admin/People/__tests__/CreateRecordNavigation.component.test.ts
  - resources/js/Utils/__tests__/String.test.ts
  - resources/js/Features/Admin/DutiableTimeline/__tests__/dutyNames.test.ts
  - tests/Feature/Public/ContactEagerLoadingTest.php
  - tests/Feature/Public/PublicPagesTest.php
  - resources/js/Components/Public/__tests__/ContactWithPhoto.component.test.ts
  - tests/Feature/Search/ContactSearchIndexSyncTest.php
  - tests/Browser/DutyNamePresentationTest.php
  - tests/Feature/Duties/UpdateDutyTest.php
  - tests/Feature/Duties/DutyAssignmentServiceTest.php
  - tests/Feature/Admin/Management/DutyControllerTest.php
  - tests/Feature/Admin/DutyMergeTest.php
  - tests/Feature/Admin/People/DutyForceDeleteTest.php
  - tests/Feature/Admin/People/DutyResponsibilityControllerTest.php
  - tests/Feature/Permissions/DutySelfLockoutTest.php
  - tests/Feature/CrossTenantDutyTest.php
  - tests/Feature/Permissions/ScheduledDutyAuthorizationTest.php
  - tests/Feature/Api/Admin/DutyApiControllerTest.php
  - tests/Feature/Api/Admin/DutySearchApiControllerTest.php
  - tests/Feature/Api/Admin/DutyCadenceApiControllerTest.php
  - tests/Feature/Admin/People/DutyLifecycleTest.php
  - tests/Feature/Admin/Management/DutiableControllerTest.php
  - tests/Unit/Services/DutyNameNormalizerTest.php
  - tests/Feature/Migrations/DateDutySuccessionsTest.php
  - tests/Browser/DutyResponsibilitiesLayoutTest.php
  - resources/js/Pages/Admin/People/__tests__/ShowDuty.component.test.ts
  - resources/js/Pages/Admin/People/__tests__/ShowUser.component.test.ts
  - resources/js/Components/Duties/__tests__/DutyCurrentHoldersCard.component.test.ts
  - resources/js/Components/Duties/__tests__/DutyHolderCard.component.test.ts
  - resources/js/Components/Duties/__tests__/DutyLabel.component.test.ts
  - resources/js/Components/Duties/__tests__/DutyLineageCard.component.test.ts
  - resources/js/Components/Duties/__tests__/DutyOtherDutiesCard.component.test.ts
  - resources/js/Components/Duties/__tests__/DutySummaryCard.component.test.ts
  - resources/js/Components/Duties/__tests__/InflectedDutyName.component.test.ts
  - resources/js/Components/AdminForms/__tests__/DuplicateDutyWarning.component.test.ts
  - resources/js/Components/AdminForms/__tests__/DutyForm.component.test.ts
  - resources/js/Features/Admin/Responsibilities/__tests__/DutyResponsibilitiesSection.component.test.ts
  - resources/js/Features/Admin/Occupancy/__tests__/occupancy.test.ts
  - resources/js/Features/Admin/Occupancy/__tests__/AssignDutyUserSheet.component.test.ts
  - resources/js/Features/Admin/Occupancy/__tests__/MemberSearchField.component.test.ts
  - resources/js/Components/Duties/__tests__/DutyPreview.component.test.ts
  - resources/js/Composables/__tests__/useDuplicateDutyCheck.test.ts
---

# Pareigybės

**Pareigybė** – tai VU SA ar universiteto institucijoje einamos pareigos (pavyzdžiui, padalinio
pirmininkas, komunikacijos koordinatorius, kuratorius, studentų atstovas taryboje ar komisijoje).

Pareigybės yra visos platformos teisių ir atsakomybių ašis: **naudotojai patys savaime dažniausiai neturi
jokių administratoriaus teisių** (tik išskirtiniais atvejais). Kasdieniame darbe administravimo prieiga suteikiama per pareigybės atsakomybes. Bazinė nario prieiga,
tiesiogiai paskirtos teisės ir super administratoriaus prieiga aprašytos [Teisėse ir atsakomybėse](/pagrindai/teises).

Pareigybių sąrašas pasiekiamas adresu `/mano/duties`, o kiekviena pareigybė turi savo puslapį
`/mano/duties/{id}`.

## Kaip tai veikia

Atskiroje formoje sukūrus pareigybę, atveriamas jos puslapis, kuriame galima tvarkyti narius ir ryšius.

### Teisės ir atsakomybės per pareigybes {#teises-ir-roles}

Kasdieniame administravimo darbe atsakomybes suteik **pareigybei**, kad keičiantis nariams nereikėtų
iš naujo sudėlioti prieigos.

1. Kai naudotojas paskiriamas į pareigybę (sukuriant [pareigybės laikotarpį](/visak/pareigybiu-laikotarpiai#laikotarpis)),
   jis automatiškai įgyja visas pareigybės atsakomybes ir jų teises.
2. Jei pareigybę eina keli žmonės (pvz., keli kuratoriai ar kolegialaus organo nariai), visi jie
   naudojasi tomis pačiomis pareigybės teisėmis.
3. Pasibaigus paskutinei pareigų dienai, prieiga iš šio laikotarpio nustoja galioti.
   Kitos pareigybės ir bazinė nario prieiga dėl to neišnyksta.

::: tip Pareigos pasikeitė – keičiamas narys
Kai keičiasi padalinio pirmininkas ar koordinatorius, **nereikia kurti naujos pareigybės**.
Pakanka senajam nariui nustatyti kadencijos pabaigos datą ir prie tos pačios pareigybės priskirti
naująjį narį. Taip išsaugoma visa pareigybės istorija, instituciniai ryšiai ir el. pašto adresas.
Masiniam narių keitimui skirtas [Pareigybių atnaujinimo vedlys](/organizacija/pareigybiu-atnaujinimas).
:::

### Tipai ir automatinis atsakomybių suteikimas {#tipai}

Pareigybių sąrašo **Filtrai → Pareigybės tipas** leidžia pasirinkti konkretų tipą arba **Be tipo**.
**Be tipo** taip pat pasiekiamas greituoju filtru virš sąrašo. Filtras veikia ir šiukšliadėžėje.

Kiekvienai pareigybei galima priskirti vieną ar kelis **tipus** (pvz., *Pirmininkas*,
*Koordinatoriai*, *Kuratoriai*, *Studentų atstovai*).

Tipai atlieka dvi svarbias funkcijas:
- **Viešas grupavimas ir atvaizdavimas**: pagal tipą viešoje svetainėje kontaktai atvaizduojami
  tinkamose skiltyse (pvz., padalinio kontaktų bloke ar studentų atstovų sąrašuose).
- **Automatinis atsakomybių suteikimas**: kai kuriems tipams suteiktos atsakomybės:
  - Priskyrus tipą **Studentų atstovai**, pareigybė automatiškai gauna atsakomybę **Studentų atstovas** (studentų atstovavimas: leidžia institucijoje fiksuoti posėdžius ir darbotvarkės klausimus, o pareigybė laikoma studentų atstovu).
  - Priskyrus tipą **Koordinatoriai**, pareigybė automatiškai gauna atsakomybę **Problemų redaktorius** (leidžiančią kelti ir tvarkyti padalinio problemas).
  - **Svarbu:** tipas *Koordinatoriai* **nesuteikia** padalinio valdymo teisių (pvz., naujienų, puslapių, pareigybių ar išteklių). Šios srities atsakomybės (pvz., *Komunikacijos koordinatorius*, *Išteklių administratorius*) pareigybei suteikiamos pareigybės puslapio skirtuke **Atsakomybės**.
  - Nuėmus tipą, per jį gauta atsakomybė iš pareigybės pašalinama, nebent ji suteikta ir tiesiogiai.

::: warning Kas gali priskirti tipą
Tipą priskirti ar pašalinti gali jį deleguoti įgaliotas narys arba atsakomybes valdantis administratorius, turintis teisę redaguoti konkrečią pareigybę. Pavyzdžiui, Komunikacijos koordinatorius gali priskirti tipus *Koordinatoriai*, *Kuratoriai*, *Pirmininkas*, o Studentų atstovų koordinatorius – *Studentų atstovai*. Delegavimas kyla tik iš šiuo metu einamų pareigų. Esami tipai, kurių keisti negalima, formoje rodomi atskirai ir išsaugomi keičiant kitus laukus.
:::

### Vietų skaičius ir užimtumas {#uzimtumas}

**Vietų skaičius** – neprivalomas, ranka įrašomas informacinis skaičius, pavyzdžiui,
Parlamento ar komisijos vietų skaičius. Jis pildomas pareigybės formos
**Papildomuose nustatymuose**. Kai skaičius nenustatytas, rodomas mygtukas **Pridėti vietų skaičių**:
jį paspaudus atsiveria laukas su pradine reikšme **1**. Mygtukas **Išvalyti** pašalina skaičių ir vėl paslepia lauką.
Pakeitimai įrašomi išsaugojus formą. Kai skaičius įrašytas, pareigybės puslapyje jis rodomas atskirai nuo dabartinių narių skaičiaus.

Šis skaičius neriboja narių priskyrimo ir nenustato, ar pareigybė pilnai užimta.
Institucijos puslapyje rodomi dabartiniai nariai, nesumuojant pareigybių vietų.
Bendrų pareigybių [padalinių kvotos](#bendros-pareigybes) tikrinamos atskirai.

- Veikianti pareigybė be dabartinių narių laikoma **neužimta**. Sąrašuose ji žymima perspėjimo
  ženklu, o pareigybės puslapyje rodomas **Pareigos neužimtos**.
  Puslapio faktuose rodoma tik **Neužimta**, nekartojant narių skaičiaus **0**.
- [Nebeveikianti](#istorija) pareigybė neužimta nelaikoma – jos niekas ir nebeturi eiti.

<ChangelogNote date="2026-10-25" title="Vietų skaičius – tik informacijai">

Ankstesni vietų skaičiai išvalyti. Naujos pareigybės kuriamos be šio skaičiaus; ten, kur jis
naudingas, galima įrašyti ranka. Narių priskyrimo languose ir laikotarpių tvarkyklėje nebėra
pagal šį skaičių rodomų užimtumo perspėjimų.

</ChangelogNote>

### Atsakomybės {#atsakomybes}

Pareigybės puslapyje yra skirtukas **Atsakomybės**. Čia nustatoma, už kokį padalinį, institucijos
tipą ar konkretų organą ši pareigybė atsako (pavyzdžiui, *Studentų atstovų koordinavimas*).

Atsakomybė nurodo sistemai, kas gauna atstovų registracijos anketas, posėdžių klausimus ir veiklos
ataskaitas. Plačiau apie atsakomybių mechanizmą skaityk skyriuje [Atsakomybės](/pagrindai/atsakomybes).

### Bendros pareigybės tarp padalinių (kvotos) {#bendros-pareigybes}

Kai kurios institucijos (pvz., VU SA Parlamentas) priklauso bendram dariniui, tačiau jų nariai renkami iš visų fakultetinių
padalinių.

Tokioms pareigybėms naudojamas **padalinių kvotų mechanizmas**:
- Pareigybę sukūręs padalinys nustato, kurie kiti padaliniai gali skirti savo atstovus ir kokia
  jiems skirta vieta (kvota).
- Fakulteto koordinatorius mato šią instituciją ir pareigybę savo sąraše kaip išorinę ir gali
  paskirti savo fakulteto studentą, neviršydamas skirtos kvotos.
- Fakulteto administratorius negali keisti pačios pareigybės pavadinimo ar kitų padalinių atstovų,
  tačiau gali valdyti savo deleguotus narius.

Kurio padalinio kvotai skaičiuojama konkreti vieta, galima pakeisti **Priskirti** lange, lauke
**Kieno kvotai skaičiuojama**. Laukas rodomas tik turintiems teisę redaguoti pareigybių laikotarpius
pareigybės padalinyje (Superadministratoriui – visur). Pasirinkus „Pareigybės padalinio vieta (ne
kvota)“, vieta priklauso pačios pareigybės padaliniui.

- Pasirinkus jau užpildytą kvotą, laikotarpis vis tiek išsaugomas, o kvota rodoma viršyta (pvz., 4/3).
  Fakulteto koordinatoriams pilna kvota ir toliau neleidžia pridėti naujo nario.
- Jei narys nepriklauso pasirinktam padaliniui, išsaugant parodomas įspėjimas: to padalinio
  administratoriai šios vietos redaguoti negalės.
- Prie deleguoto nario rodoma žyma **Deleguota** su padalinio pavadinimu.

<ChangelogNote date="2026-10-26" title="Kvotos pasirinkimas laikotarpyje">

Anksčiau vietą prie padalinio kvotos susiedavo tik pats fakulteto koordinatorius, paskirdamas narį.
Dabar ją galima nustatyti ar pataisyti ir redaguojant laikotarpį.

</ChangelogNote>

### Ex officio pareigos {#ex-officio}

Kai kurios pareigos pagal įstatus priklauso automatiškai (*ex officio*), pavyzdžiui, padalinio
pirmininkas pagal pareigas yra ir padalinio tarybos narys.

- Prie pirmininko pareigybės nurodomos tikslinės pareigybės.
- Paskyrus naują pirmininką arba pakeitus jo kadencijos datas, sistema automatiškai sukuria arba
  atnaujina susietus tarybos nario laikotarpius.
- Kaip laikotarpiai keičiasi pridėjus ar pašalinus ryšį, aprašyta [laikotarpių tvarkyklės gide](/visak/pareigybiu-laikotarpiai#ex-officio-sekimas).
- Žmogaus puslapyje *ex officio* pareigos rodomos po jas suteikiančiomis pareigomis.
- Rankiniu būdu keisti *ex officio* laikotarpio datų negalima – jos visada seka pagrindinių
  pareigų datas.

### Nebeveikiančios pareigybės, ankstesni pavadinimai ir pirmtakės {#istorija}

Nebeveikianti pareigybė sąraše pažymėta ženklu prieš pavadinimą ir išblukinta, o atrinkti ją galima filtru **Būsena**.
Turint teisę redaguoti konkrečią pareigybę, būsena keičiama peržiūros kortelėje arba formoje. Formoje naudojami tie patys
žali ir pilki būsenų pasirinkimai; formos pakeitimas įsigalioja paspaudus **Išsaugoti**.
Jei pareigybę dar eina narys, pirmiau reikia užbaigti jo pareigybių laikotarpį.
Institucijos būsena rodoma atskira žyma **Nebeveikianti institucija** ir nekeičia pačios pareigybės būsenos.

Pareigybės istorija neturėtų trūkinėti, net jei pareigybė keitėsi:

- **Nebeveikianti pareigybė** – tokia, kurios nebeinama (pvz., projektų koordinatoriaus vieta vienais metais
  nebuvo užpildyta arba pareigybė buvo padalinta į dvi). Ji lieka su visais buvusiais nariais, tačiau
  nebesiūloma [Pareigybių atnaujinimo vedlyje](/organizacija/pareigybiu-atnaujinimas) ir pareigybių
  paieškoje priskiriant narį. Nebeveikiančia pažymėti galima tik tada, kai pareigybės šiuo metu niekas neina.
- **Ankstesni pavadinimai** – kai pareigybė **tik pervadinama**, tai ta pati pareigybė: senasis pavadinimas
  išsaugomas su data, iki kada jis galiojo. Seni laikotarpiai pareigybės ir nario puslapiuose rodomi su tuo metu
  galiojusiu pavadinimu („Kaip: …“). Viešuose kontaktuose rodomi tik dabartiniai nariai, todėl ten matomas
  dabartinis pavadinimas.
- **Ankstesnė pareigybė (pirmtakė)** – kai pareigybės **sujungiamos arba padalinamos**, kiekviena lieka
  atskira pareigybe su savo nariais, o naujoji susiejama su pirmtake nurodant datą **Nuo** – pirmą dieną,
  kai darbą dirbo naujoji. Data pasiūloma pagal pirmąjį pareigybės laikotarpį arba kadencijos pradžią.
  Pirmtakės nariai rodomi naujosios laikotarpių istorijoje, bet tik tie, kurių laikotarpiai prasidėjo iki tos datos;
  istorija sekama ir per kelias pirmtakes atgal.

Pavyzdys: *Socialinis koordinatorius* ir *Akademinis koordinatorius* 2019 m. sujungti į *Socialinis-akademinis
koordinatorius*, o 2022 m. vėl atskirti. Bendroji pareigybė susiejama su abiem pirmtakėmis nuo 2019-07-01, o
abi senosios pareigybės vėl susiejamos su bendrąja nuo 2022-07-01 – naujų įrašų kurti nereikia. *Socialinio
koordinatoriaus* istorijoje tada matomi jo paties nariai ir bendrosios pareigybės nariai, bet ne *Akademinio
koordinatoriaus* nariai iki sujungimo.

Skiltyje **Apie pareigybę → Pareigybės istorija** visi pokyčiai rodomi vienoje datuotoje laiko juostoje:
pervadinimai, iš kurių pareigybių darbas perimtas („Perėmė darbą iš“ arba „Sujungta iš“) ir kam perduotas
(„Darbą perėmė“ arba „Padalinta į“).

**Tai buvo tik pervadinimas.** Jei pervadinimas anksčiau buvo įrašytas kaip nauja pareigybė su pirmtake, ryšio
eilutėje rodomas mygtukas **Tai buvo tik pervadinimas**. Jis siūlomas, kai ryšys yra vienas su vienu (tą dieną
pirmtakė perdavė darbą tik šiai pareigybei, o ši perėmė tik iš jos), abi pareigybės yra toje pačioje institucijoje ir
gali redaguoti abi. Patvirtinus pirmtakės laikotarpiai perkeliami į šią pareigybę, jos pavadinimas išsaugomas kaip
ankstesnis (galiojęs iki dienos prieš **Nuo**), o pati pirmtakė ištrinama.

<ChangelogNote date="2026-10-02" title="Pareigybės istorija">

Pareigybę galima pažymėti nebeveikiančia, išsaugoti ankstesnius jos pavadinimus ir susieti su pareigybe,
kurios darbą ji perėmė.

</ChangelogNote>

<ChangelogNote date="2026-10-27" title="Datuota pareigybės istorija">

Pirmtakės susiejamos su data, todėl sujungtas ir vėl atskirtas pareigybes galima susieti iš naujo. Pervadinimą, įrašytą
kaip naują pareigybę, galima paversti ankstesniu pavadinimu. Būsenos vadinamos „Veikianti“ ir „Nebeveikianti“.

</ChangelogNote>

### Pareigų galūnių giminizavimas, linksniavimas ir dublikatų prevencija {#lytis-ir-dublikatai}

Pritaikoma ir pora **Pavaduotojas / Pavaduotoja**, taip pat kai po jos eina sritis, pvz.,
**Pavaduotojas studijoms / Pavaduotoja studijoms**. Dublikatų patikra atpažįsta abi gimines
ir daugiskaitą (**Pavaduotojai / Pavaduotojos**), bet išlaiko skirtingas pareigų sritis.
Vardo atpažinimas turi išimčių, todėl vien galūnė **-a** ne visada reiškia moteriškąją giminę;
aiškiai nurodyti įvardžiai ir laikotarpio parinktis išlaikyti originalų pavadinimą turi pirmenybę.

Lietuvių kalboje pareigybių pavadinimai kinta pagal einančio asmens įvardį arba vardo giminę (*Pirmininkas* /
*Pirmininkė*, *Koordinatorius* / *Koordinatorė*).

Bendras pareigybės pavadinimas antraštėse, sąrašuose ir peržiūrose kas kelias sekundes keičia
vyriškąją ir moteriškąją galūnę. Keičiamą dalį žymi vientisas VU SA spalvos pabraukimas;
antraštės dydis ir šriftas išlieka. Jei įrenginyje įjungtas sumažintas judesys, rodoma viena
nejudanti vyriškoji forma. Angliški pavadinimai nekinta. Formoje antraštė
keičiasi iškart, kai rašai pavadinimą ar perjungi LT / EN. Viršutinėje juostoje lieka išsaugotas
pavadinimas be galūnių animacijos; kuriant naują įrašą – „Nauja pareigybė“. Po laukeliu lieka galūnių keitimo
paaiškinimas, o pavadinimo peržiūra rodoma pačioje antraštėje.

Prie konkretaus nario pavadinimas nekaitaliojamas: pirmiausia taikomi jo įvardžiai
(**ji / she**, **jis / he**, **jie / they**). Jei dabartinės kalbos įvardžiai nenurodyti ar neatpažinti,
sistema tikrina kitos kalbos įvardžius, tada – vardą. Kai vardo galūnė neleidžia atpažinti
giminės, lieka įrašytas pavadinimas. Tai galioja dabartinėms, būsimoms ir buvusioms pareigoms,
paieškai, laikotarpių tvarkyklei ir viešiems kontaktams. Laikotarpio parinktis
**Pareigos pavadinimo galūnės negiminizavimas** išlaiko įrašytą formą.

Kad duomenų bazėje nesidaugintų dubliuotos pareigybės:
- Pareigybės pavadinimas kuriamas viena bendra forma.
- Sistema automatiškai moka linksniuoti ir pritaikyti lyties galūnę pagal pareigas einančio asmens
  profilio duomenis.
- Kuriant naują pareigybę, sistema tikrina normalizuotą pavadinimą: jei
  institucijoje jau yra „Komunikacijos koordinatorius“, bandant sukurti „Komunikacijos koordinatorė“
  arba „Komunikacijos koordinatorius (-ė)“, formoje iškart pasirodo perspėjimas **Aptiktas pareigybės dublikatas**
  su nuoroda į jau esantį įrašą.
- Tikrinamos ir **nebeveikiančios** pareigybės bei **ankstesni pavadinimai**: tokiu atveju geriau
  aktyvuoti esamą pareigybę, kad jos istorija liktų vienoje vietoje.
- Panašios pareigybės kitose to paties padalinio institucijose išvardijamos su nuoroda į jų puslapį;
  kitų padalinių atitikmenys tik suskaičiuojami.

<ChangelogNote date="2026-10-20" title="Dublikatų patikra abiem kalbomis">

Kuriant ar redaguojant pareigybę, tikrinami abu jos pavadinimai – lietuviškas ir angliškas.
Įvedus anglišką pavadinimą, atitikmuo randamas ir tada, kai pareigybė turi kitokį lietuvišką
pavadinimą. Tai taikoma savo institucijai, kitoms to paties padalinio institucijoms ir kitiems
padaliniams. Perspėjimas netrukdo išsaugoti pareigybės.

</ChangelogNote>

## Veiksmai

### Pareigybių sąrašas ir filtrai {#sarasas}

Pareigybių sąraše (`/mano/duties`) pateikiamos visos pasiekiamos pareigybės:

- **Paieška**: galima ieškoti pagal pareigybės pavadinimą, el. paštą arba instituciją.
- **Atidaryti** rodomas eilutėje, o **Redaguoti** yra meniu **⋯**, jei pareigybę gali redaguoti.
- **Greitasis filtras „Neužimtos“**: vienu paspaudimu atrenka pareigybes, kurios šiuo metu neturi
  narių.
- **Greitasis filtras „Galimi dublikatai“**: atrenka pareigybes, kurių pavadinimas toje pačioje
  institucijoje sutampa su kita pareigybe – tiksliai, kita gimine („Koordinatorius“ ir „Koordinatorė“)
  arba su kitos pareigybės ankstesniu pavadinimu. Tai tos pačios taisyklės, pagal kurias perspėjama
  [kuriant ar redaguojant pareigybę](#lytis-ir-dublikatai). Rastus dublikatus gali
  [sujungti](#sujungimas) Superadministratorius; šiukšliadėžėje šis filtras nerodomas.
- **Duomenų kokybės filtrai** šoninėje juostoje:
  - *Neužimtos* – pareigybės be aktyvių narių.
  - *Trūksta EN pavadinimo* – pareigybės, neturinčios angliško vertimo.
  - *Trūksta LT pavadinimo* – pareigybės be lietuviško pavadinimo.
  - *Pasikartojantys nariai* – pareigybės, kuriose tas pats žmogus turi kelis persidengiančius
    laikotarpius.
  - *Nebeveikiančios* – pareigybės, kurių nebeinama, bet istorija išlaikoma.
  - *Galimi dublikatai* – tas pats kaip greitasis filtras.
- **Filtras „Istorija“** (ir greitasis filtras **„Su istorija“**) atrenka pareigybes, kurios buvo
  *pervadintos*, *susietos su kitomis pareigybėmis* (pirmtakėmis ar perėmėjomis) arba turi bet kurį iš šių pakeitimų.
- **Šiukšliadėžė** viršuje leidžia peržiūrėti ištrintas pareigybes ir jas atkurti.

### Pareigybės puslapis {#puslapis}

Atidarius pareigybę (`/mano/duties/{id}`), viršuje matai instituciją, užimtų ir visų vietų
skaičių (pvz., 3 / 5), pareigybės el. paštą ir kategorijas, jei jos priskirtos. Puslapis
suskirstytas į skirtukus:

<DocScreenshot name="duty-record" alt="Pareigybės puslapis: institucija, užimtos vietos, el. paštas, dabartiniai nariai su veiksmais Redaguoti ir Užbaigti pareigas bei laikotarpių istorija" caption="Parlamento nario pareigybė: trys iš penkių vietų užimtos." />

1. **Nariai**:
   - *Dabartiniai nariai*: kas šiuo metu eina pareigas ir nuo kada. Žyma **Ex-officio** rodo, kad
     pareigos kyla iš kitos pareigybės, o **Deleguota** – kad narys atstovauja kitam padaliniui.
   - *Būsimi nariai*: nariai, kurių pareigos prasidės ateityje (rodoma, tik jei tokių yra).
   - *Laikotarpių istorija*: pasibaigę laikotarpiai – kas ir kada šias pareigas ėjo anksčiau,
     įskaitant [pirmtakės](#istorija) narius. Jei tuo metu pareigybė vadinosi kitaip, rodomas tas pavadinimas.
   - Mygtukas **Priskirti** atidaro nario priskyrimo formą. Prie kiekvieno nario rasi
     **Redaguoti**, o prie dabartinių – ir **Užbaigti pareigas**.
2. **Apie pareigybę**:
   - Pareigybės aprašymas.
   - Kitos tos pačios institucijos pareigybės ir kiek narių jas eina.
   - *Pareigybės istorija*: datuota laiko juosta – pervadinimai, pirmtakės ir pareigybės, perėmusios šios darbą
     (žr. [Istorija](#istorija)). Mygtukas **Susieti ankstesnę pareigybę** leidžia pasirinkti ir nebeveikiančias pareigybes.
3. **Atsakomybės**:
   - Padaliniai, institucijų tipai ar konkretūs organai, kuriuos ši pareigybė koordinuoja.
   - Mygtukas **Pridėti atsakomybę** leidžia priskirti naują koordinavimo sritį.
   - Rodomos ir per tipą ar instituciją gautos atsakomybės; jų teisės lemia, ką pareigybė gali daryti platformoje.
4. **Failai** (rodoma, jei pareigybė turi SharePoint aplanką ar kategoriją):
   - Su pareigybe susieti dokumentai ir ataskaitos.

### Laikotarpio datos {#laikotarpio-datos}

**Abi datos įskaitomos:** laikotarpis nuo 2025-05-30 iki 2025-06-01 apima gegužės 30, 31 ir birželio 1 d.
Pradžia negali būti vėlesnė už pabaigą. Pabaigos data yra paskutinė aktyvi diena. **Užbaigti pareigas šiandien** įrašo šiandieną;
narys lieka dabartinių narių sąraše iki dienos pabaigos ir tampa buvusiu kitą dieną.
Istorinis įrašas išlieka. Jei paskutinė pareigų diena buvo vakar, įrašyk vakarykštę datą.

<ChangelogNote date="2026-10-02" title="Paskutinė pareigų diena">

Dabartinių ir buvusių narių sąrašai dabar vienodai įskaito pabaigos dieną.
Užbaigimo patvirtinimas aiškiai paaiškina, kad šiandien pareigos dar galioja.

</ChangelogNote>

### Nario priskyrimas ir šalinimas {#priskirti-nari}

Paspaudus **Priskirti** pareigybės puslapyje:
1. Pasirenkamas narys. Paieškoje pirmiausia rodomi dabartiniai nariai, o buvę pažymimi „Buvęs narys · metai“.
   Jei žmogaus sistemoje dar nėra, jį galima sukurti čia pat – užtenka vardo ir pavardės, o el. paštas
   nebūtinas (aktualu buvusiems nariams; be el. pašto jie tiesiog negauna laiškų). Sukurti naują narį
   gali tie, kurie turi teisę kurti narius.
2. Pasirenkama **kadencija**. Pagal nutylėjimą – dabartinė: pradžia šiandien, pabaiga – kadencijos
   pabaiga. Pasirinkus ankstesnę kadenciją, datos užpildomos visam jos laikotarpiui. Kadencijos imamos
   iš institucijos [kadencijų](/sistema/nustatymai#kadencijos), o jei institucija savų neturi – iš bendrųjų.
3. Jei reikia, datos pataisomos ranka – tada pasirinkimas tampa „Kitos datos“.
4. **Papildomi nustatymai** (suskleisti): papildomas el. paštas, nuotrauka, originalus pareigybės
   pavadinimas ir **Papildomas aprašymas**, rodomas viešame kontaktų sąraše vietoj pareigybės aprašymo.
   Kuratorių pareigybėms čia rodoma ir studijų programa bei jos pastaba.

Jei per pasirinktą laikotarpį tas pats žmogus jau eina šias pareigas, jo pasirinkti negalima.
Pasibaigęs laikotarpis neužima vietos, todėl jam padalinio kvota netaikoma.

<ChangelogNote date="2026-10-02" title="Buvusių narių priskyrimas">

Priskyrimo formoje atsirado kadencijos pasirinkimas, galimybė sukurti narį be el. pašto ir suskleisti
papildomi nustatymai.

</ChangelogNote>

Norint užbaigti nario pareigas:
- Narių sąraše spausk **Redaguoti** ir nustatyk pabaigos datą arba pasirinkite **Užbaigti pareigas šiandien**.
- **Ištrinti priskyrimą** naudok tik tuo atveju, jei narys buvo priskirtas per klaidą. Teisingai
  baigtos kadencijos turi turėti pabaigos datą, kad išliktų veiklos istorijoje.

### Kūrimas ir redagavimas {#kurimas}

Nauja pareigybė kuriama per **Veiksmai → Pareigybė** arba mygtuku **Nauja pareigybė** sąrašo
viršuje (`/mano/duties/create`):
- **Pavadinimas** (LT ir EN) – rašomas pagrindine forma (pvz., *Finansų koordinatorius*).
- **Institucija** – institucija, kuriai pareigybė priklauso.
- **Institucinis el. paštas** – dažniausiai `@vusa.lt` dėžutė. Šis el. paštas naudojamas ir prisijungimui
  prie Office 365.
- **Vietų skaičius** – numatytas narių skaičius (pagal nutylėjimą 1).
- **Tipai** – pažymimi tinkami tipai. Atsakomybės suteikiamos pareigybės puslapyje.
- **Kontaktų rikiavimas** – kaip pareigybės nariai išrikiuojami viešuose kontaktuose. Visi institucijos
  kontaktai rodomi viename sąraše, o pareigybė nurodoma žmogaus kortelėje. „Pagal padalinį“ narius
  rikiuoja pagal atstovaujamą padalinį ir nurodo jį šalia pareigybės (pvz., „Parlamento narė · VU SA MIF“),
  „Pagal studijų programą“ – pagal programą, kuri taip pat rodoma kortelėje. Padalinio kontaktuose
  pirmiausia rodomi koordinatoriai, tada kuratoriai, tada kiti.
  „Pagal padalinį“ siūloma tik tada, kai kitiems padaliniams leista skirti atstovus; pašalinus visus
  padalinius, rikiavimas grąžinamas į „Numatytasis“.

Redagavimas pasiekiamas pareigybės puslapyje per **⋯ → Redaguoti pareigybę** (`/mano/duties/{id}/edit`).
Redaguojant papildomai rodoma:
- **Būsena** – **Veikianti** arba [**Nebeveikianti**](#istorija).
- **Ankstesni pavadinimai** – išskleidžiamas sąrašas po pavadinimo lauku, su data „Naudotas iki“. Pakeitus
  pavadinimą, senasis pridedamas automatiškai (galiojęs iki vakar) ir pažymimas „Naujas“; jei tai buvo tik rašybos
  klaida, eilutę galima ištrinti.
- Retesni laukai formoje nerodomi, kol jų neprireikia: formos apačioje esantys punktyriniai mygtukai
  **Pridėti ankstesnį pavadinimą** ir **Leisti kitiems padaliniams skirti atstovus** atveria atitinkamą dalį.
  Pašalinus visus padalinius (**Nebeleisti kitiems padaliniams skirti atstovų**), dalis vėl paslepiama.

### Pareigybių sujungimas {#sujungimas}

<ChangelogNote date="2026-10-18" title="Sujungia tik Superadministratorius, dublikatai ištrinami visam laikui">

Į paliekamą pareigybę nuo šiol perkeliamos ir atsakomybės, tikslai, komentarai, failai, užduotys
bei tiesiogiai suteiktos teisės.

</ChangelogNote>

Jei istorijoje buvo sukurtos kelios tos pačios pareigybės versijos (pavyzdžiui, „Atstovas taryboje“
ir „Atstovė taryboje“, arba dublikatas dėl rašybos klaidos), Superadministratorius gali jas **sujungti į vieną**:

1. Pareigybių sąraše viršuje spausk **Sujungti pareigybes** (įsijungia pasirinkimo režimas).
2. Pažymėk bent dvi pareigybes, kurias norite sujungti.
3. Apačioje paspausk mygtuką **Sujungti** – atsidarys sujungimo langas.
4. Pasirink, kurią pareigybę **pasilikti kaip pagrindinę**.
5. Patvirtink sujungimą:
   - Visi narių laikotarpiai iš sujungiamų pareigybių perkeliami į pagrindinę.
   - Jei tas pats žmogus abiejose pareigybėse turėjo persidengiančius laikotarpius, jie sujungiami
     į vieną ištisinį laikotarpį. Ex officio laikotarpiai lieka atskiri – jie seka pareigybę, iš kurios kyla.
   - Tipai, atsakomybės, tiesiogiai suteiktos teisės, padalinių kvotos, ankstesni pavadinimai,
     pirmtakių ryšiai, tikslai, komentarai, failai ir užduotys perkeliami į pagrindinę pareigybę (be dubliavimo).
   - Pagrindinės pareigybės nariai iškart gauna perkeltų atsakomybių prieigą.
   - Sujungtos pareigybės **ištrinamos visam laikui** – į šiukšlinę jos nepatenka, o sujungimas
     įrašomas pagrindinės pareigybės pakeitimų istorijoje.

### Šiukšlinė {#trynimas}

- Pareigybės ištrynimas per **⋯ → Ištrinti pareigybę** perkelia ją į šiukšliadėžę.
- Ištrintą pareigybę galima bet kada **atkurti** iš šiukšliadėžės sąrašo.
- **Galutinis ištrynimas visam laikui yra blokuojamas**, jei pareigybė kada nors
  turėjo bent vieną narį. Taip apsaugomas duomenų vientisumas ir studentų atstovavimo istorija.
  Jei pareigybė nebenaudojama, tiesiog palik ją ištrintą šiukšliadėžėje.

## Kas ką gali {#teises}

| Veiksmas | Studentų atstovas | Komunikacijos koordinatorius | Studentų atstovų koordinatorius | Centrinio biuro koordinatoriai | Super administratorius |
|---|---|---|---|---|---|
| Matyti savo pareigybę | ✓ | ✓ | ✓ | ✓ | ✓ |
| Matyti padalinio pareigybes | – | ✓ | ✓ | ✓, visų padalinių | ✓ |
| Sukurti ir redaguoti padalinio pareigybę | – | ✓ | ✓ | ✓, visų padalinių | ✓ |
| Priskirti narius savo padalinyje | – | ✓ | ✓ | ✓, visų padalinių | ✓ |
| Priskirti narius pagal savo padalinio kvotą bendrose pareigybėse | – | ✓ | ✓ | ✓ | ✓ |
| Priskiriant sukurti naują narį | – | ✓ | ✓ | ✓ | ✓ |
| Pažymėti pareigybę nebeveikiančia, tvarkyti ankstesnius pavadinimus ir pirmtakes | – | ✓ | ✓ | ✓, visų padalinių | ✓ |
| Sujungti pareigybes | – | – | – | – | ✓ |
| Pridėti pareigybės atsakomybes | – | ✓ | ✓ | ✓ | ✓ |
| Ištrinti pareigybę į šiukšlinę | – | ✓ | ✓ | ✓ | ✓ |
| Ištrinti pareigybę visam laikui (tik be istorijos) | – | – | – | – | ✓ |

„Centrinio biuro koordinatoriai“ lentelėje – **Centrinio biuro komunikacijos koordinatorius**
ir **Centrinio biuro studentų atstovų koordinatorius**. Apimtis ribojama administruojamais padaliniais.

::: warning Savęs užsirakinimo apsauga
Jei administratorius bando iš savo paties pareigybės pašalinti administracines teises suteikiančią
atsakomybę arba užbaigti savo paties narystę, sistema operaciją sustabdo ir parodo įspėjimą
apie prieigos pokytį. Tai apsaugo administratorių nuo netyčinio prieigos praradimo. Pakeitimas
įrašomas tik tada, kai vartotojas aiškiai patvirtina veiksmą.
:::

## Pranešimai ir automatizavimas {#pranesimai}

| Kada | Kas nutinka | Kas gauna |
|---|---|---|
| Sukuriamas nario laikotarpis su pradžios data šiandien | Iškart suteikiamos pareigybės atsakomybių teisės | Paskirtas narys |
| Baigiasi paskutinė pareigybės laikotarpio diena | Prieiga iš šio laikotarpio nustoja galioti | Buvęs narys |
| Paskirtas narys būsimai datai (ateityje) | Sukuriamas būsimas laikotarpis; dabartinių narių sąraše jis atsiras pradžios dieną | Būsimas narys |
| Pareigybei priskiriamas tipas su atsakomybėmis (pvz., „Studentų atstovai“) | Pareigybei automatiškai suteikiamos tipo atsakomybės | Pareigybės nariai |
| Pareigybei priskiriama atsakomybė | Asmuo pradedamas laikyti srities koordinatoriumi | Koordinatorius |
| Sukuriama nauja studentų atstovų registracija | Pranešimą gauna pareigybė, turinti to organo atsakomybę | Koordinatorius |


::: details Dažni klausimai
**Ką daryti, jei koordinatorius atsistatydino viduryje kadencijos?**
Nario kortelėje per **Redaguoti** nustatoma šiandienos data kaip pabaigos data. Tada
prie tos pačios pareigybės priskiriamas naujas narys.

**Ar galima pareigybei priskirti kelis el. pašto adresus?**
Ne, pareigybė turi vieną pagrindinį institucinį el. paštą. Tačiau kiekvienas narys savo narystės
laikotarpyje gali turėti nurodytą papildomą kontaktinį el. paštą ar asmeninį paštą.
:::

## Techninė informacija {#technine-informacija}

Šis skyrius skirtas tiems, kurie tvarko atsakomybes ar tikrina sistemos elgseną.

### Teisės

- Pareigybių administravimą reglamentuoja `DutyPolicy`.
- `duties.read.*` leidžia matyti visas sistemos pareigybes; `duties.read.padalinys` riboja matomumą
  iki vartotojo atstovaujamo padalinio institucijų.
- Kiekvienas naudotojas, kuris bent kartą ėjo pareigybę, turi teisę peržiūrėti tos pareigybės įrašą
  (`DutyPolicy::view`).
- `DutyPolicy::managePeople` tikrina, ar vartotojas gali priskirti ir šalinti narius: savame
  padalinyje pakanka `duties.update.padalinys`, o bendrose pareigybėse tikrinama, ar vartotojo
  padalinys yra `assignableTenants` sąraše ir ar deleguojamas studentas priklauso tam padaliniui.
- Trynimas visam laikui (`duties.forceDelete.padalinys`) tikrina `GuardsForceDelete` sutartį:
  jei `dutiables()->exists()`, trynimas nutraukiamas su paaiškinimu, apsaugant nuo duomenų bazės
  išorinių raktų klaidų.

### Kaip tai įgyvendinta

- Pareigybės modelis `Duty` naudoja `HasRoles` (Spatie; `assignRole()` rašo tiesioginius atsakomybių suteikimus), `HasTranslations` (daugiakalbystei),
  `LogsModelActivity`, `LogsRelationshipChanges` ir `SoftDeletes`.
- Narių priskyrimo ryšys yra polimorfinis `dutiables` (`Dutiable` pivotas), palaikantis `start_date`,
  `end_date`, `study_program_id`, `additional_email`, `additional_photo`.
- Kokius tipus naudotojas gali priskirti, nustato `GetAttachableTypesForDuty` (`role_can_attach_duty_types`).
  Tipui suteiktos atsakomybės (`responsibility_grants`, `grantee_type = duty_type`) pareigybėms
  projektuojamos per `DutyDutyType` įvykius ir `ProjectDutyResponsibilities`.
- Pavadinimų unikalumą ir normalizavimą užtikrina `DutyNameNormalizer` (sulygina vyr./mot. galūnes,
  skliaustus `(-ė)` ir linksnius); `DutySimilarityFinder` tikrina ir `duty_names`, ir neaktyvias pareigybes.
- `duties.is_active` slepia pareigybę iš vedlio ir paieškos (`is_active:!=false`); ankstesni pavadinimai
  saugomi `duty_names` (`used_until` imtinai, `Duty::nameOn()`), pirmtakės – `duty_successions`.
  Pirmtakę galima susieti tik su pareigybe, kurią naudotojas gali peržiūrėti (`DutyPolicy::view`).
- Priskyrimo formos kadencijos ateina iš `api.v1.admin.duties.cadences` (`ResolveCadenceForDuty::applicableFor`);
  naujas narys kuriamas tame pačiame `dutiables.store` užklausime (`new_user`), jei veikiantis naudotojas gali kurti narius.
- Savęs užsirakinimo apsaugą užtikrina `AdminController::guardSelfLockout` kartu su
  `DutySelfLockoutChecker`.
- `current_duties` tikrina ir pradžią, ir imtinę pabaigą. `authorization_duties` apima ir
  dar neprasidėjusius laikotarpius; tai tikrina `ScheduledDutyAuthorizationTest`.
  Būsimas laikotarpis dėl to netampa dabartiniu.


## Savarankiški ir ex officio laikotarpiai

Automatinis ex officio priskyrimas nekeičia jau įvesto savarankiško laikotarpio. Prireikus jį galima aiškiai susieti su šaltiniu [laikotarpių tvarkyklėje](/visak/pareigybiu-laikotarpiai). Prieš patvirtinant rodomos susiejimo pasekmės. Visą pareigybės istoriją patikrinti ir sutvarkyti gali [Tikrinti ex officio](/visak/pareigybiu-laikotarpiai#ex-officio-tikrinimas) veiksmas pareigybės puslapyje.
