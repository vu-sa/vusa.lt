---
doc_status: reviewed
title: Institucijos
area: institutions
models: [Institution, InstitutionCheckIn, InstitutionSecretary]
last_reviewed: 2026-10-03
tests:
  - tests/Feature/Admin/Management/InstitutionControllerTest.php
  - tests/Feature/Admin/Management/InstitutionCheckInTest.php
  - tests/Feature/Admin/People/InstitutionSecretaryTest.php
  - tests/Feature/InstitutionSubscriptionTest.php
  - tests/Feature/Institutions/InstitutionScopeTest.php
  - tests/Feature/Public/PublicMeetingVisibilityTest.php
  - tests/Feature/SoftDelete/ForceDeleteGuardsTest.php
  - tests/Feature/Tasks/InstitutionSecretaryTaskResyncTest.php
  - tests/Feature/Tasks/RepopulateInstitutionTasksTest.php
  - tests/Feature/Tasks/Handlers/PeriodicityGapTaskHandlerTest.php
  - tests/Feature/Tasks/Subscribers/InstitutionCheckInTaskSubscriberTest.php
  - tests/Feature/Tasks/InstitutionSecretaryTaskResyncTest.php
  - tests/Feature/Tasks/InstitutionSecretaryMailScopeTest.php
  - tests/Feature/Tasks/Subscribers/MeetingTaskSubscriberTest.php
  - tests/Feature/Notifications/InstitutionActivityAnswerTest.php
  - tests/Feature/Admin/Management/InstitutionActivityRequestControllerTest.php
  - resources/js/Components/ActionWindow/__tests__/ActivityRequestScreens.component.test.ts
  - tests/Browser/InstitutionActivityReplyTest.php
  - tests/Unit/InstitutionActivityStatusServiceTest.php
  - resources/js/Pages/Admin/People/__tests__/ShowInstitution.component.test.ts
  - resources/js/Pages/Admin/People/__tests__/IndexInstitution.component.test.ts
  - resources/js/Components/AdminForms/__tests__/InstitutionForm.component.test.ts
  - resources/js/Components/Institutions/__tests__/InstitutionDutiesSection.component.test.ts
  - resources/js/Components/Institutions/__tests__/InstitutionOverviewSection.component.test.ts
  - resources/js/Components/Institutions/__tests__/SecretariesSection.component.test.ts
  - resources/js/Components/ActionWindow/__tests__/InstitutionReportScreen.component.test.ts
  - resources/js/Features/Admin/AdminSearch/Config/__tests__/collectionFacetConfig.test.ts
  - tests/Feature/Permissions/StudentRepresentativeRoleTest.php
  - tests/Feature/Permissions/BaselineAccessTest.php
  - tests/Browser/VisakRecordPagesTest.php
---

# Institucijos

Institucija – organas ar organizacija, kurioje VU SA atstovauja studentams (pvz., fakulteto taryba,
studijų programos komitetas) arba kuri yra pati VU SA dalis (parlamentas, valdyba). Institucija
jungia kitus įrašus: jos **pareigybės** sieja žmones su institucija, o jos **posėdžiai** rodo, kaip
ji veikia.

Institucijų sąrašas pasiekiamas adresu `/mano/institutions`, o kiekviena institucija turi savo
puslapį `/mano/institutions/{id}`.

<DocScreenshot name="institution-record" alt="Institucijos puslapis: veiklos būklė, rūšis, padalinys, nariai, posėdžių viešumas ir paskutiniai posėdžiai" caption="Atstovės vaizdas: jos institucijos būklė, nariai ir paskutiniai posėdžiai." />

## Kaip tai veikia

### Rūšis ir tipai

Institucijai priskiriami [tipai](/sistema/tipai). Iš jų nustatoma institucijos
[rūšis](/visak/#institucijos-rusis): VU organas, VU SA darinys, nacionalinis ar tarptautinis
organas. Institucija be tipo laikoma **VU organu**. Jei bent vienas tipas yra išorinis organas,
visa institucija laikoma išorine.

Rūšis lemia, ką rodo ir ko reikalauja kiti puslapiai:

- VU SA dariniams forma siūlo **kontaktų** skiltį (adresas, darbo laikas, el. paštas, socialiniai
  tinklai). Ji pažymėta „Matoma vusa.lt“, nes rodoma viešame institucijos puslapyje.
- VU SA darinių posėdžiuose studentų pozicijos žymėti nereikia (žr.
  [Darbotvarkės klausimai](/visak/darbotvarkes-klausimai#vusa-isimtis)).
- [Koordinatorius](/visak/#koordinatorius) priskiriamas tik VU organams. Kaip jis parenkamas,
  aprašyta [Atsakomybėse](/pagrindai/atsakomybes).

### Aktyvi institucija

Jungiklis **Aktyvi institucija** nurodo, ar iš institucijos dar tikimasi posėdžių. Neaktyvi
institucija nerodoma viešame sąraše, jai nekuriamos užduotys dėl posėdžių periodiškumo, o nariai
be teisės ją matyti jos puslapio neatidaro.

### Posėdžių periodiškumas ir būklė {#periodiskumas}

**Periodiškumas** – kas kiek dienų tikimasi posėdžio ar pranešimo apie veiklą. Jį galima nurodyti
institucijos formoje (nuo 1 iki 365 dienų). Jei laukas tuščias, imamas trumpiausias iš
institucijos tipų periodiškumų, o jei ir jų nėra – **30 dienų**.

Pagal periodiškumą skaičiuojama institucijos **veiklos būklė** (Vėluoja, Artėja terminas,
Suplanuotas posėdis ir kt.). Kaip ji nustatoma, aprašyta [Apžvalgoje](/visak/apzvalga#busenos).
Tais pačiais pavadinimais būklės rodomos ir institucijų sąrašo filtre **Aktyvumas**.

### Posėdžių viešumas {#viesumas}

Ar institucijos posėdžiai rodomi vusa.lt, institucijoje **nenustatoma**. Tai lemia jos tipas:
[posėdžių nustatymuose](/sistema/nustatymai) pažymimi tipai, kurių institucijų posėdžiai yra
vieši (pvz., studijų programų komitetai). Institucijos puslapyje tai rodo laukas **Posėdžių
viešumas**. Kalendoriuje paskelbtas posėdis pats savaime viešu netampa.

### Ką rodo institucijos puslapis

Narių ir sekretorių nuotraukos rodomos mažos, kad sąrašuose ir kortelėse daugiau vietos liktų vardams bei pareigoms.

Viršuje – veiklos būklė, rūšis ir tipas, padalinys, nariai (užimtos ir visos vietos), posėdžių
viešumas ir koordinatoriai. Skirtukai:

| Skirtukas | Kas jame |
|---|---|
| **Apžvalga** | Aprašymas, paskutiniai posėdžiai su pirmais darbotvarkės punktais, dabartiniai sekretoriai |
| **Pareigybės** | Institucijos pareigybės ir jų nariai; čia keičiama pareigybių tvarka |
| **Posėdžiai** | Visi institucijos posėdžiai |
| **Kadencijos ir sekretoriai** | Kadencijos ir kiekvienos jų sekretoriai (tik galintiems redaguoti) |
| **Ryšiai** | Susijusios institucijos (tik jei jų yra, žr. [Ryšiai](/sistema/rysiai)) |
| **Problemos** | Su institucija susietos problemos, pirmiau neišspręstos (tik jei jų yra, žr. [Problemos](/visak/problemos#posedziai)) |
| **Failai** | Institucijos darbo failai SharePoint; viešai nerodomi (žr. [Įrašų failai](/visak/failai)) |
| **Užduotys** | Institucijos ir jos posėdžių užduotys; skaičius rodo neatliktas |

**Savo** instituciją (kurioje eini ar netrukus pradėsi eiti pareigas) matai pilnai, net be rolės.
**Kitą aktyvią** instituciją gali atidaryti kiekvienas narys, bet mato tik viešą jos pusę: apžvalgą, pareigybes ir – jei posėdžiai vieši – posėdžius. Jei posėdžiai nevieši,
parašyta, kad jie yra, bet nerodomi, o veiklos būklė rodoma kaip „Nerodoma“. Failų, užduočių,
komentarų ir sekretorių jis nemato.

## Veiksmai

### Fiksuoti veiklą

Pagrindinis institucijos puslapio mygtukas **Fiksuoti veiklą** atveria
[veiksmų langą](/pagrindai/platforma) jau pasirinktai institucijai. Jame renkiesi, ar
**užregistruoti posėdį** (žr. [Posėdžiai](/visak/posedziai#fiksuoti)), ar pranešti, kad **posėdžio
nebuvo**.

### Pranešimas „Posėdžio nebuvo“ {#posedzio-nebuvo}

Jei institucija kurį laiką nesirenka (pvz., per sesiją), apie tai pranešama: nurodomas laikotarpis
ir, jei reikia, pastaba (iki 500 simbolių). Tai galima padaryti per **+ Sukurti → Posėdžio nebuvo**
arba institucijos meniu **⋯ → Pridėti pažymą**.

- Pradžia gali būti ir praeityje. Pabaiga turi būti vėlesnė už pradžią ir ne vėliau kaip **po 3
  mėnesių** nuo šiandien.
- Kol pranešimas galioja, institucijos būklė yra „Užfiksuotas kontaktas“, o užduotis dėl
  periodiškumo užbaigiama.
- Jei vėliau užregistruojamas posėdis, patenkantis į pranešimo laikotarpį, pranešimas
  **sutrumpinamas** iki dienos prieš posėdį. Jei posėdis yra pranešimo pradžios dieną ar anksčiau,
  pranešimas **ištrinamas**.
- Galiojančius pranešimus galima panaikinti. Narys panaikina tik savo pranešimą, koordinatorius ir
  institucijos redaguotojas – visus.

### Paklausti atstovų, ar vyko posėdis {#paklausti}

Koordinatorius gali pats paklausti atstovų, ar institucija posėdžiavo – nelaukdamas automatinio
priminimo. Klausimą siųsk iš:

- **ViSAK → Padaliniai**, sąrašo **Reikia dėmesio** mygtuko **Paklausti atstovų** (pasirinktos visos
  sąrašo institucijos);
- **+ Sukurti → Paklausti, ar vyko posėdžiai** (institucijas pasirenki pats);
- institucijos meniu **⋯ → Paklausti, ar vyko posėdžiai**.

<ChangelogNote version="v3.0" date="2026-10-02" title="Dvi atskiros užklausos ir atsakymų istorija">
Pasirink, ar klausi apie institucijos veiklą, ar prašai papildyti posėdžių įrašus. Išsiųstas užklausas
ir atsakymus rasi institucijos skiltyje **Užklausos atstovams**.
</ChangelogNote>

Pirmiausia pasirink **Ar vyko posėdis?** arba **Papildyk posėdžių įrašus** – net jei institucijos
jau pasirinktos. Vienu kartu gali pasirinkti iki **100 institucijų**. Filtruok pagal padalinį arba
ieškok pavadinimo; prieš atveriant langą pasirinktos institucijos lieka sąrašo pradžioje.

Peržiūroje **Ką ir kam išsiųsime** matai kiekvieną gavėją ir jo laikotarpį.
**Kam laiško nesiųsime** paaiškina praleistas institucijas ir gavėjų pranešimų nustatymus:
laiškas dabar, santrauka, išjungtas el. paštas ar nutildyti pranešimai. Gali pridėti žinutę iki 500
simbolių. Patvirtinus rodoma, kiek užklausų ir laiškų įtraukta į eilę; tai dar nėra pristatymo patvirtinimas.

<DocScreenshot name="activity-request-review" narrow alt="Veiksmų lango peržiūra prieš siunčiant veiklos užklausas" caption="Veiksmų lango peržiūra: gavėjų sąrašas, laikotarpis ir koordinatoriaus žinutė prieš išsiunčiant užklausas." />

Užklausa skiriama dabartinės kadencijos sekretoriams, o jei jų nėra – dabartiniams studentų
atstovams. Tau pačiam užklausos nesiunčiame. Laikotarpis prasideda nuo institucijos dabartinės
kadencijos pradžios, bet atstovui negali prasidėti anksčiau už jo pareigybės laikotarpį. Jei dabartinės
kadencijos nėra, naudojama atstovo pareigų pradžia. Paskutinė pareigų diena dar yra galiojanti.
Laikotarpio pabaiga užfiksuojama siunčiant ir vėliau nesikeičia.

**Ar vyko posėdis?** nesiunčiama gavėjui, kurio laikotarpiu posėdis jau užfiksuotas.
**Papildyk posėdžių įrašus** klausia apie jau vykusius posėdžius, kurių darbotvarkės tuščios arba
neužpildyti sprendimai. Jei laikotarpiu posėdžių nėra, rinkis **Ar vyko posėdis?**; jei visos
darbotvarkės užpildytos, papildymo užklausos nesiunčiamos. Koordinatorius, kuris pats yra
institucijos atstovas, gali įtraukti ir save. Pakartotinės užklausos
tam pačiam gavėjui, apie tą pačią instituciją ir tą patį jau apimtą laikotarpį nesiunčiamos;
skirtingos užklausų rūšys vertinamos atskirai.

### Atsakymas iš laiško {#atsakymas}

Atsakyti gali **neprisijungęs**. Nuoroda atveria puslapį, o atsakymą įrašo tik tavo patvirtinimas.
Nuorodos galioja **14 dienų**.

<DocScreenshot name="activity-request-email" narrow alt="Atstovui siunčiamas pranešimo el. laiškas su veiklos klausimu ir atsakymo mygtukais" caption="Pranešimo el. laiškas atstovui su pasirašytomis greitojo atsakymo nuorodomis." />

- **Taip, vyko** – įrašyk vieno ar kelių posėdžių datas, formatus ir laiką. Sprendimui el. paštu
  laiko nereikia. Darbotvarkes papildysi prisijungęs prie Mano VU SA.
- **Ne, nevyko** – veiklos užklausoje patvirtink, kad visą nurodytą laikotarpį posėdžio nebuvo.
  Jei posėdis tuo metu jau įrašytas, šio atsakymo patvirtinti negalėsi.
- **Viskas užfiksuota** – įrašų papildymo užklausoje patvirtink, kad visi laikotarpio posėdžiai
  įrašyti ir jų darbotvarkės bei sprendimai užpildyti. Kol dar yra neužpildytų įrašų, patvirtinimo
  priimti negalima. Pranešimas apie laiką be posėdžių apima tik likusį tarpą po paskutinio posėdžio iki
  nurodytos pabaigos. Jau patvirtinti tarpai nedubliuojami.
- **Nesu šio organo narys (-ė)** – pranešk klaususiems koordinatoriams patikrinti atstovų duomenis.
  Kitų gavėjų užklausos lieka atviros.

<DocScreenshot name="activity-request-reply" narrow alt="Atsakymo puslapis be prisijungimo, kuriame atstovas pažymi posėdžius" caption="Viešas atsakymo puslapis: atstovė nurodo posėdžio datą, laiką ir formatą be prisijungimo." />

Įrašų papildymo laiške ir puslapyje matai posėdžius, kuriuos reikia papildyti. **Papildyti įrašus**
atveria užklausą; konkretaus posėdžio nuoroda veda į Mano VU SA, kur prisijungęs papildai
darbotvarkę ir sprendimus. Jei trūksta paties posėdžio, rinkis **Pridėti trūkstamą posėdį**.
Klaidingai užpildytos eilutės ir
pasirinktas atsakymas išlieka, kad galėtum pataisyti laukus. Kompiuteryje kalendorius rodomas
lietuviškai (angliškame puslapyje – angliškai), savaitė prasideda pirmadienį, laikas rašomas 24 val.
formatu. Telefono kalendoriaus kalba priklauso nuo tavo įrenginio.

Užfiksuotas posėdis gali užbaigti tik to laikotarpio veiklos užklausą. Įrašų papildymo užklausas
kitiems gavėjams užbaigia tik **Viskas užfiksuota**, kai tavo patvirtintas laikotarpis apima jų
klausiamą laikotarpį. Senas, nesusijęs posėdis ar pranešimas dabartinės priminimo užduoties neužbaigia.

### Užklausos atstovams {#uzklausos}

Institucijos puslapyje atverk **Užklausos atstovams**. Koordinatorius mato visas institucijos
užklausas, gavėjas – tik savąsias; kiti skaitytojai jų nemato. Naujausios siuntimo grupės rodomos
pirmos, pagal užklausos rūšį. Matai siuntėją arba automatinį šaltinį, laikotarpį, žinutę, gavėją,
atsakymą, laikus ir užfiksuotus rezultatus. Koordinatoriui nerodomos gavėjo atsakymo nuorodos.

Istorija įkeliama atskirai, po 20 siuntimo grupių; senesnes atverk mygtuku **Rodyti daugiau**.
Būsena rodo, ar dar laukiama atsakymo, jau atsakyta, veikla patvirtinta kitu įrašu, ar nuorodos
galiojimas baigėsi. Ankstesnės užklausos be užfiksuotos pabaigos išlaiko laikotarpį iki atsakymo dienos.

### Sekretoriai {#sekretoriai}

<ChangelogNote version="v3.0" date="2026-10-02" title="Sekretorius ir koordinatorius atlieka skirtingus darbus">

Sekretorių skirk kadencijai, jei jis fiksuos jos posėdžius: tų posėdžių užduotys ir laiškai keliaus jam. Koordinavimo atsakomybė pati savaime žmogaus sekretoriumi nepaskiria.

</ChangelogNote>

**Sekretorius** paskiriamas konkrečios kadencijos posėdžiams tvarkyti (skirtukas **Kadencijos ir
sekretoriai**). Sekretoriumi gali būti bet kuris narys, nebūtinai institucijos narys.

- Kai kadencijai paskirti sekretoriai, **tos kadencijos posėdžių užduotys ir laiškai tenka tik
  jiems**. Kiti institucijos nariai jų nebegauna. Jei sekretorių nėra, užduotys tenka tuo metu
  pareigas ėjusiems atstovams.
- Pakeitus sekretorius, atviros tos kadencijos užduotys perduodamos naujiems, o atliktos lieka
  kaip buvo. Perdavimas pranešimų nesiunčia.
- Skirtuke keisti gali dabartinės ir kitos kadencijos sekretorius. Ankstesnių kadencijų sekretoriai
  rodomi tik kaip istorija.
- Jei institucija turi savo kadencijas, sekretoriai skiriami joms, jei ne – bendroms kadencijoms
  (žr. [Kadencijos](/sistema/nustatymai#kadencijos)). Savas kadencijas nustatyk tame pačiame
  skirtuke paspaudęs **Nustatyti savas**; prireikia retai.
- Nustačius pirmą savo kadenciją, sekretoriai perkeliami iš ją dengiančios bendros kadencijos, o
  atviros užduotys perskirstomos. Ištrynus paskutinę savo kadenciją, vėl galioja bendrosios
  kadencijos ir jų sekretoriai.
- Sekretoriai nerodomi tarp narių, kontaktų ir paieškoje.

Kuo sekretorius skiriasi nuo koordinatoriaus, aprašyta [ViSAK](/visak/#koordinatorius) puslapyje.

### Pareigybės

Skirtuke **Pareigybės** matyti institucijos pareigybės ir jų nariai. Redaguoti galintys gali keisti
pareigybių tvarką (rodyklėmis, tada **Išsaugoti**) ir priskirti žmogų pareigybei. Pareigybės
kuriamos ir tvarkomos [Pareigybių](/organizacija/pareigybes) skiltyje.

### Sekti ir nutildyti {#sekti}

Institucijas galima **sekti**: gausi pranešimus apie jos naujus posėdžius ir užpildytas
darbotvarkes, o jos posėdžiai matysis tavo [Apžvalgoje](/visak/apzvalga). Sekti gali institucijos
puslapyje (**⋯ → Sekti**) arba sąraše, pažymėjęs kelias institucijas. Sąrašo greitasis filtras
**Sekamos** rodo tik jas.

- Sekti galima institucijas, kurias gali matyti, ir kitų padalinių aktyvias institucijas, kurių
  posėdžiai vieši.
- Savo institucijas seki automatiškai, kol eini jose pareigas; to išjungti negalima.
- **Nutildyti** – sekti toliau, bet pranešimų negauti.

### Sukurti, redaguoti, ištrinti

- **Sukurti** instituciją gali tik savo padalinyje. Būtinas lietuviškas pavadinimas. Lietuviškas
  pavadinimas, lietuviškas trumpinys ir techninė žymė (naudojama viešame adrese) turi būti
  **unikalūs visoje platformoje** – ištrintos institucijos neskaičiuojamos. Todėl trumpinys turėtų
  nurodyti ir padalinį (pvz., „VU TF SPK“, ne „SPK“). Techninė žymė, jei jos nenurodai, sudaroma iš
  pavadinimo.
- Jei redaguoji instituciją, kurios pavadinimas ar trumpinys sutampa su kitos, išsaugoti pavyks tik
  juos pakeitus.
- **Padalinio** vėliau pakeisti negalima (išskyrus super administratorių).
- **Ištrinta** institucija patenka į [šiukšlinę](/pagrindai/platforma#siuksline), o jos
  pareigybės ir posėdžiai lieka. Institucijos, kurioje pats eini pareigas, ištrinti negalima.
- Iš šiukšlinės instituciją galima **atkurti**. **Galutinai ištrinti** negalima, kol ji turi
  posėdžių, pareigybių ar pranešimų apie veiklą arba yra padalinio pagrindinė institucija.

## Kas ką gali {#teises}

| Veiksmas | Bet kuris narys | Institucijos narys | Studentų atstovas | Studentų atstovų koordinatorius | Komunikacijos koordinatorius | Centrinio biuro koordinatoriai |
|---|---|---|---|---|---|---|
| Matyti sąrašą ir aktyvių institucijų viešą pusę | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Matyti visą institucijos puslapį | – | ✓ | ✓, savo ir su jomis susietų | ✓, savo padalinio | ✓, savo padalinio | ✓ |
| Fiksuoti posėdį | – | – | ✓, savo institucijose | ✓, savo padalinio | ✓, savo padalinio | ✓ |
| Pranešti „Posėdžio nebuvo“ | – | ✓ | ✓, savo institucijose | ✓, savo padalinio | ✓, savo padalinio | ✓ |
| Paklausti atstovų, ar vyko posėdis | – | – | – | ✓, savo padalinio | ✓, savo padalinio | ✓ |
| Sukurti, redaguoti, skirti sekretorius | – | – | – | ✓, savo padalinio | ✓, savo padalinio | ✓ |
| Ištrinti ir atkurti | – | – | – | – | ✓, savo padalinio | ✓ |
| Galutinai ištrinti | – | – | – | – | – | – |

„Centrinio biuro koordinatoriai“ – rolės **Centrinio biuro studentų atstovų koordinatorius** ir
**Centrinio biuro komunikacijos koordinatorius**. Galutinai ištrinti gali tik super
administratorius. Pareigybė su studentų atstovų [koordinavimo atsakomybe](/pagrindai/atsakomybes#teises)
pranešimus „Posėdžio nebuvo“ tvarko ir atstovų apie posėdžius klausia kuruojamose institucijose, net be rolės.

## Pranešimai ir automatizavimas {#pranesimai}

| Kada | Kas gauna | Ką |
|---|---|---|
| Kasdien, kai būklė „Artėja terminas“ arba „Vėluoja“ | Kadencijos sekretoriai, o jei jų nėra – dabartiniai atstovai | Užduotį **„Pranešti apie veiklą“** ir laišką „Ar vyko posėdis?“, į kurį [atsakoma neprisijungus](#atsakymas) |
| Koordinatorius [paklausia atstovų](#paklausti) | Tie patys | Vieną laišką „Ar vyko posėdis?“ apie visas jų institucijas, su koordinatoriaus žinute |
| Gavėjas atsako „Nesu šio organo narys (-ė)“ | Klausęs koordinatorius, o automatinio priminimo atveju – institucijos koordinatoriai | Pranešimą patikrinti institucijos atstovus |
| Užregistruojamas dabartinį veiklos laikotarpį apimantis posėdis arba pranešimas „Posėdžio nebuvo“ | – | Periodiškumo užduotis pažymima atlikta automatiškai |
| Seki instituciją ir sukuriamas jos posėdis ar užpildoma darbotvarkė | Sekėjai, išskyrus nutildžiusius | Pranešimą |

- Užduotis nekuriama per akademines atostogas, jei institucija turi suplanuotą posėdį ar galiojantį
  pranešimą, ir **neaktyvioms** institucijoms. Jos terminas nepatenka į atostogas.
- Veiklos būklė sąrašui perskaičiuojama kas naktį, o užregistravus posėdį ar pranešimą –
  iš karto.

## Susitarimai {#susitarimai}

::: tip Institucijų tvarkymas
- Jei institucija nebesirenka (pvz., panaikinta), išjunk **Aktyvi institucija** – neištrink jos:
  jos posėdžių istorija lieka.
- Jei institucijoje keli atstovai, susitark su kitais, kuris fiksuoja posėdžius, arba paskirk
  kadencijos sekretorių.
:::

## Techninė informacija {#technine-informacija}

### Teisės

- Institucijų teises sprendžia `InstitutionPolicy`. `viewAny` leidžiamas visiems; viešą pusę
  (`viewSummary`) mato visi, jei institucija aktyvi.
- `view` visada leidžiamas nebaigtų pareigybių institucijoms (bazinė prieiga); kitaip
  `view`, `update`, `delete` tikrina `institutions.{read|update|delete}.{*|padalinys}`; `.own`
  egzistuoja tik skaitymui (savo pareigybių institucijos ir su jomis autorizuotu ryšiu susijusios).
- Rolės: Studentų atstovas – `institutions.read.own`; Studentų atstovų koordinatorius –
  `create|read|update.padalinys`; Komunikacijos koordinatorius – `create|read|update|delete.padalinys`;
  centrinio biuro rolės – tas pačias su `*`. `institutions.forceDelete.*` neturi nė viena rolė.
- Pranešimus „Posėdžio nebuvo“ tikrina `InstitutionCheckInPolicy`: kurti gali nariai ir
  administratoriai (`institutions.update.padalinys` institucijos padalinyje arba studentų atstovų
  koordinavimo atsakomybė institucijai); visus panaikinti – tik administratoriai. Klausti atstovų
  (`askAboutActivity`) gali tik administratoriai; super administratorius – visur.

### Kaip tai įgyvendinta

- Periodiškumas: `Institution::meeting_periodicity_days` → `min(types)` → 30.
- Viešumas: `Institution::has_public_meetings` lygina tipus su
  `MeetingSettings::public_meeting_institution_type_ids`.
- Rūšis: `InstitutionScopeResolver` (artimiausias tipas su `governance_scope`; išorinis laimi).
- Būklė: `InstitutionActivityStatusService`; naktinė komanda `institutions:refresh-activity-status`,
  tarp jų – `SyncInstitutionActivityIndex`.
- Periodiškumo užduotis: `tasks:repopulate institution` (kasdien 08:00) →
  `PeriodicityGapTaskHandler`; gavėjai – `ResolveTaskAssignees::forInstitution`.
- Pranešimo sutrumpinimas: `CheckInService::adjustForMeeting()` iš `MeetingController`.
- Klausimai „Ar vyko posėdis?“: `InstitutionActivityRequest` (vienas gavėjui ir institucijai),
  siunčia `SendInstitutionActivityRequests` (`HandleTaskCreated` automatiniam priminimui,
  `InstitutionActivityRequestController` koordinatoriui). Atsakymo puslapis
  `InstitutionActivityAnswerController` pasiekiamas per pasirašytą nuorodą (`signed`); GET nieko
  neįrašo, nes pašto skeneriai atveria nuorodas. Atsakymą įrašo `AnswerInstitutionActivityRequest`,
  o laikotarpį atitinkančius veiklos klausimus užbaigia `ResolveInstitutionActivityRequests`.
  Įrašų papildymo klausimus užbaigia tik aiškus laikotarpio patvirtinimas.
- Sekretoriai: `InstitutionSecretary`; perskyrimas – `ResyncTaskAssigneesForCadence`; perkėlimas į
  savą kadenciją – `CarrySecretariesIntoOverride` (`Cadence::booted()`).
