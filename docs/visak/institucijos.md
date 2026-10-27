---
doc_status: reviewed
title: Institucijos
area: institutions
models: [Institution, InstitutionCheckIn, InstitutionSecretary]
last_reviewed: 2026-10-27
tests:
  - tests/Browser/CollectionTrashRefreshTest.php
  - tests/Feature/Api/Admin/RecordActivityStatusTest.php
  - resources/js/Pages/Admin/People/__tests__/CreateRecordNavigation.component.test.ts
  - tests/Feature/Admin/Management/InstitutionControllerTest.php
  - tests/Feature/Admin/Management/InstitutionCheckInTest.php
  - tests/Feature/Admin/People/InstitutionSecretaryTest.php
  - tests/Feature/InstitutionSubscriptionTest.php
  - tests/Feature/Migrations/RemoveInstitutionNotificationMutesTest.php
  - tests/Feature/Institutions/InstitutionScopeTest.php
  - tests/Feature/Public/PublicMeetingVisibilityTest.php
  - tests/Feature/Public/ContactControllerTest.php
  - tests/Feature/Migrations/NormalizeInstitutionAliasesTest.php
  - tests/Feature/Institutions/InstitutionDeactivationTest.php
  - tests/Browser/InstitutionDeactivationTest.php
  - tests/Feature/Search/ContactSearchIndexSyncTest.php
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
  - resources/js/Components/ActionWindow/__tests__/CheckInUntilScreen.component.test.ts
  - tests/Feature/Api/Admin/ActionWindowApiControllerTest.php
  - resources/js/Components/Institutions/__tests__/InstitutionActivityReply.component.test.ts
  - tests/Browser/InstitutionActivityReplyTest.php
  - tests/Browser/VisakRecordPagesTest.php
  - resources/js/Components/ActionWindow/__tests__/InstitutionPickerScreen.component.test.ts
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

Atskiroje formoje sukūrus instituciją, atveriamas jos puslapis. Pareigybių atnaujinimo vedlyje institucija sukuriama liekant vedlyje.

### Veikianti ir nebeveikianti institucija {#aktyvumas}

<DocScreenshot name="institution-deactivation" alt="Institucijos redagavimo forma prieš užbaigiant veiklą: įspėjimas apie užbaigiamas pareigas ir paskutinę pareigų dieną" caption="Prieš išsaugant neaktyvią būseną parodomos pasekmės narių pareigoms." />

Institucijos redagavimo formoje pasirinkus **Nebeveikianti** ir išsaugojus, institucija
neberodoma viešuose kontaktų sąrašuose ir paieškoje. Jos viešas kontaktų puslapis nepasiekiamas
nei pagal identifikatorių, nei pagal trumpąjį adresą; padalinio koordinatorių ir kuratorių
kontaktų puslapiai taip pat nepasiekiami, jei pagrindinė padalinio institucija neaktyvi.
Prisijungusiam nariui šiuose viešuose adresuose taikoma ta pati taisyklė.

Išsaugojus neaktyvią būseną, dabartinių narių pareigybių laikotarpiai užbaigiami: paskutinė
pareigų diena įrašoma vakar, todėl iš šių pareigų gaunama prieiga baigiasi iškart. Tai taikoma
ir kitų padalinių deleguotiems nariams bei automatiškai gaunamoms ex-officio pareigoms.
Iš institucijos pareigų gaunamos ex-officio pareigos kitose institucijose taip pat užbaigiamos;
nesusijusios pareigos ir tiesiogiai suteiktos rolės lieka galioti.

Jei pareigybių laikotarpiai prasideda šiandien ar vėliau, deaktyvavimas neleidžiamas.
Formoje rodomos trukdančios pareigybės ir jų pradžios datos. Pirmiau šiuos laikotarpius
reikia atšaukti, perkelti arba pataisyti institucijos puslapyje.

Anksčiau užbaigti laikotarpiai ir jų datos nesikeičia. Buvusios pareigos su institucijos
pavadinimu ir datomis lieka nario puslapyje bei skiltyje **Rolės ir pareigybės**; pastarojoje
rodoma iki 10 užbaigtų laikotarpių. Pareigybių aprašai ir jų atskira aktyvumo būsena išlieka.

Neaktyviai institucijai galima įrašyti tik buvusias pareigas, kurių pabaigos data ankstesnė
už šiandieną. Vėl aktyvavus instituciją, viešas kontaktų puslapis, sąrašų ir paieškos įrašai
tampa prieinami, tačiau užbaigti laikotarpiai neatveriami iš naujo net vykdant ex-officio
sinchronizavimą. Grįžtančiam nariui kuriamas naujas laikotarpis.

Neaktyviai institucijai nebekuriamos naujos posėdžių periodiškumo užduotys, o esamos užduotys
automatiškai neužbaigiamos. Ankstesnių posėdžių viešumas ir toliau priklauso nuo posėdžių
viešumo nustatymų. Teisę valdyti instituciją turintys nariai ją ir toliau gali redaguoti Mano VU SA.
Anksčiau neaktyviomis pažymėtų institucijų pareigos automatiškai nekeičiamos, nes jų uždarymo datos nežinomos.

<ChangelogNote date="2026-10-02" title="Neaktyvių institucijų viešumas">

Pažymėjus instituciją neaktyvia, užbaigiamos dabartinės narių pareigos ir iš jų gaunamos ex-officio pareigos.
Istorija išsaugoma, o viešas kontaktų puslapis tampa nepasiekiamas.

</ChangelogNote>

### Rūšis ir tipai

Institucijai priskiriami [tipai](/sistema/tipai). Iš jų nustatoma institucijos
[rūšis](/visak/#institucijos-rusis): VU organas, VU SA darinys, nacionalinis ar tarptautinis
organas. Institucija be tipo laikoma **VU organu**. Jei bent vienas tipas yra išorinis organas,
visa institucija laikoma išorine.

Rūšis lemia, ką rodo ir ko reikalauja kiti puslapiai:

- VU SA dariniams forma siūlo **kontaktų** skiltį (adresas, darbo laikas, el. paštas, socialiniai
  tinklai). Ji pažymėta „Matoma vusa.lt“, nes rodoma viešame institucijos puslapyje.
- Viešoje padalinių kontaktų kategorijoje kortelė rodo institucijos nuotrauką, jei ji įkelta;
  jei nuotraukos nėra, rodomas logotipas.
- VU SA darinių posėdžiuose studentų pozicijos žymėti nereikia (žr.
  [Darbotvarkės klausimai](/visak/darbotvarkes-klausimai#vusa-isimtis)).
- [Koordinatorius](/visak/#koordinatorius) priskiriamas tik VU organams. Kaip jis parenkamas,
  aprašyta [Atsakomybėse](/pagrindai/atsakomybes).

### Veikianti institucija {#aktyvi-institucija}

Formos lauke **Būsena** pasirenkama **Veikianti** arba **Nebeveikianti** – tai nurodo, ar institucija dar veikia ir ar iš jos tikimasi posėdžių.
Tai ne tas pats, kas institucijos puslapio **Veiklos būklė**, rodanti, ar posėdžiai vyksta laiku. Nebeveikianti
institucija nerodoma viešame sąraše, jai nekuriamos užduotys dėl posėdžių periodiškumo, o nariai
be teisės ją matyti jos puslapio neatidaro.

Institucijų sąraše būseną galima keisti tiesiogiai stulpelyje **Būsena** arba prie įrašo eilutėje.
Keitimas rodomas tik turint teisę redaguoti konkrečią instituciją. Prieš pažymint ją neaktyvia,
patvirtinimo lange rodomas užbaigiamų pareigybių laikotarpių skaičius, pabaigos data ir trukdantys laikotarpiai.

Neaktyvios institucijos nepatenka į asmeninę ir padalinių Gantt diagramas bei dabartinę statistiką.
Jau užfiksuoti istoriniai rodikliai nesikeičia. Administravimo sąrašuose, ryšiuose, paieškoje ir pasirinkimo
laukuose jos lieka matomos pagal turimas teises, su žyma **Nebeveikianti institucija**. Ši žyma rodoma ir įrašo puslapyje.

<ChangelogNote date="2026-10-25" title="Institucijų aktyvumo būsena">

Aktyvumo būsena keičiama sąraše arba formoje. Neaktyvios institucijos neįtraukiamos į dabartines Gantt diagramas ir statistiką,
o administravimo vietose pažymimos atskira žyma.

</ChangelogNote>

<ChangelogNote date="2026-10-27" title="Veikianti ir nebeveikianti">

Būsenos „Aktyvi“ ir „Neaktyvi“ pervadintos į „Veikianti“ ir „Nebeveikianti“, kad nesipainiotų su posėdžių veiklos būkle.

</ChangelogNote>

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

Viršuje – veiklos būklė, rūšis ir tipas, padalinys, dabartinių narių skaičius, posėdžių
viešumas ir koordinatoriai. Skirtukai:

| Skirtukas | Kas jame |
|---|---|
| **Apžvalga** | Aprašymas, paskutiniai posėdžiai su pirmais darbotvarkės punktais, dabartiniai sekretoriai |
| **Pareigybės** | Institucijos pareigybės ir jų nariai; čia keičiama pareigybių tvarka |
| **Posėdžiai** | Visi institucijos posėdžiai |
| **Kadencijos ir sekretoriai** | Kadencijos ir kiekvienos jų sekretoriai (tik galintiems redaguoti) |
| **Ryšiai** | Susijusios institucijos ir tiesioginiai ryšiai; rodomas, kai ryšių yra arba juos gali tvarkyti (žr. [Ryšiai](/sistema/rysiai)) |
| **Problemos** | Su institucija susietos problemos, pirmiau neišspręstos (tik jei jų yra, žr. [Problemos](/visak/problemos#posedziai)) |
| **Failai** | Institucijos darbo failai SharePoint; viešai nerodomi (žr. [Įrašų failai](/visak/failai)) |
| **Užduotys** | Institucijos ir jos posėdžių užduotys; skaičius rodo neatliktas |

**Savo** institucija (kurioje eini ar netrukus pradėsi eiti pareigas) matoma pilnai, net be rolės.
**Kitą aktyvią** instituciją gali atidaryti kiekvienas narys, bet mato tik viešą jos pusę: apžvalgą, pareigybes ir – jei posėdžiai vieši – posėdžius. Jei posėdžiai nevieši,
nurodoma, kad jie yra, bet nerodomi, o veiklos būklė rodoma kaip „Nerodoma“. Failų, užduočių,
komentarų ir sekretorių toks lankytojas nemato.

## Veiksmai

### Fiksuoti veiklą

Pagrindinis institucijos puslapio mygtukas **Fiksuoti veiklą** atveria
[veiksmų langą](/pagrindai/platforma) jau pasirinktai institucijai. Jame renkiesi, ar
**užregistruoti posėdį** (žr. [Posėdžiai](/visak/posedziai#fiksuoti)), ar pranešti, kad **posėdžio
nebuvo**.

### Pranešimas „Posėdžio nebuvo“ {#posedzio-nebuvo}

Jei institucija nesirinko ar kurį laiką nesirinks (pvz., per sesiją), apie tai pranešama: nurodomas
laikotarpis ir, jei reikia, pastaba (iki 500 simbolių). Tai galima padaryti per **Veiksmai → Posėdžio
nebuvo**, pradžios mygtuką **Posėdžio nebuvo** arba institucijos meniu **⋯ → Pridėti pažymą**.

- Veiksmų lange laikotarpio pradžia parenkama pati ir parodoma su priežastimi: tai vėliausia iš
  **tavo kadencijos pradžios** (ta pati, nuo kurios klausia [veiklos užklausos](#paklausti)),
  **dienos po paskutinio posėdžio** ir **dienos po ankstesnio pranešimo**. Jei nė vienos nėra,
  pradedama nuo šiandien.
- Pabaiga renkama iš **Iki šiandien** (iki šiol posėdžių nebuvo), **Dar dvi savaites** ar **Iki
  mėnesio pabaigos**, arba įvedamos savos datos. Jei jau užregistruotas būsimas posėdis, laikotarpis
  baigiasi dieną prieš jį.
- Pradžia gali būti ir praeityje. Pabaiga turi būti vėlesnė už pradžią ir ne vėliau kaip **po 3
  mėnesių** nuo šiandien. Laikotarpis negali apimti jau užregistruoto posėdžio.
- Pranešimas, apimantis visą atviros veiklos užklausos laikotarpį, ją užbaigia.
- Kol pranešimas galioja, institucijos būklė yra „Apie veiklą pranešta“, o užduotis dėl
  periodiškumo užbaigiama.
- Jam pasibaigus, jei nėra dar galiojančio posėdžio termino, rodoma **Pranešimo laikotarpis baigėsi**.
  Ši būklė galioja pusę institucijos periodiškumo, be akademinių atostogų; paskui institucija vėluoja.
  Būklę paaiškina pranešimo pabaigos data, net jei institucija dar neturi nė vieno posėdžio.
- Jei vėliau užregistruojamas posėdis, patenkantis į pranešimo laikotarpį, pranešimas
  **sutrumpinamas** iki dienos prieš posėdį. Jei posėdis yra pranešimo pradžios dieną ar anksčiau,
  pranešimas **ištrinamas**.
- Galiojančius pranešimus galima panaikinti. Narys panaikina tik savo pranešimą, koordinatorius ir
  institucijos redaguotojas – visus.

### Paklausti atstovų, ar vyko posėdis {#paklausti}

Koordinatorius gali pats paklausti atstovų, ar institucija posėdžiavo – nelaukdamas automatinio
priminimo. Klausimą galima išsiųsti iš:

- **ViSAK → Padaliniai**, sąrašo **Reikia dėmesio** mygtuko **Paklausti atstovų** (pasirinktos visos
  sąrašo institucijos);
- **Veiksmai → Paklausti, ar vyko posėdžiai** (institucijas pasirenki pats);
- institucijos meniu **⋯ → Paklausti, ar vyko posėdžiai**.

<ChangelogNote date="2026-10-02" title="Dvi atskiros užklausos ir atsakymų istorija">
Galima pasirinkti, ar klausiama apie institucijos veiklą, ar prašoma papildyti posėdžių įrašus. Išsiųstos užklausos
ir atsakymai pateikiami institucijos skiltyje **Užklausos atstovams**.
</ChangelogNote>

Pirmiausia pasirenkama **Ar vyko posėdis?** arba **Papildyk posėdžių įrašus** – net jei institucijos
jau pasirinktos. Tada pasirenkama, kaip bus renkami gavėjai:

- **Pagal institucijas** – klausiama institucijos studentų atstovų.
  Filtruojama pagal padalinį arba ieškoma pagal pavadinimą; prieš atveriant langą pasirinktos institucijos
  (pvz., iš **Reikia dėmesio**) lieka sąrašo pradžioje, o tų, kurių paklausti nebūtų ko, žymėjimas
  nuimamas.
- **Pagal atstovus** – pasirenkami atstovai; kiekvieno klausiama apie visas jo institucijas. Ieškoma
  pagal vardą arba institucijos pavadinimą.

Vienu kartu galima apimti iki **100 institucijų**. Institucija ar žmogus, kurių paklausti nebūtų ko
(pvz., jau paklausta, posėdis jau užfiksuotas), sąraše rodomi neaktyvūs su priežastimi, savo vietoje.
Jei bus klausiama tik dalies gavėjų ar institucijų, eilutėje parašyta, kiek jų liks.

Peržiūroje **Ką ir kam išsiųsime** gavėjai sugrupuoti taip, kaip pasirinkta: pagal institucijas arba
pagal atstovus. Kiekviena eilutė turi žymimąjį langelį – galima nužymėti tuos, kurių šįkart klausti nenorima.
**Kam laiško nesiųsime** paaiškina praleistas institucijas ir gavėjų pranešimų nustatymus:
laiškas dabar, santrauka, išjungtas el. paštas ar nutildyti pranešimai. Galima pridėti žinutę iki 500
simbolių. Patvirtinus rodoma, kiek užklausų paruošta, kiek yra gavėjų ir kiek laiškų numatyta siųsti iš karto. Tai dar nėra pristatymo patvirtinimas.

<DocScreenshot name="activity-request-review" narrow alt="Veiksmų lango peržiūra prieš siunčiant veiklos užklausas" caption="Veiksmų lango peržiūra: gavėjų sąrašas, laikotarpis ir koordinatoriaus žinutė prieš išsiunčiant užklausas." />

Užklausa skiriama dabartiniams institucijos studentų atstovams. Jei kadencijai paskirtas
[sekretorius](#sekretoriai) (dažniausiai VU SA vidinėse institucijose), renkantis pagal institucijas
klausiama jo, o ne atstovų; atstovas, kuris pasirinktas tiesiogiai, įtraukiamas ir tada, kai yra sekretorius.

Jei nėra nei sekretoriaus, nei gavėjų, priežastis atskiria du atvejus: institucijoje nėra
pareigybių su tipu **Studentų atstovas**, arba tokių pareigybių yra, bet jos neturi dabartinių
atstovų. Vien pareigybės pavadinimo neužtenka – tipas turi būti priskirtas pačiai pareigybei.
Nepriskirtas pareigybes galima rasti [pareigybių sąrašo filtru **Be tipo**](/organizacija/pareigybes#tipai).

**Ar vyko posėdis?** laikotarpis prasideda vėliausią iš šių dienų: kadencijos pradžią, dieną po
paskutinio posėdžio arba dieną po ankstesnio pranešimo „Posėdžio nebuvo“ – kaip ir
[pranešimo pradžia](#posedzio-nebuvo). Kadencijos pradžia atstovui negali būti ankstesnė už jo
pareigybės laikotarpį; jei dabartinės kadencijos nėra, naudojama atstovo pareigų pradžia.
**Papildyk posėdžių įrašus** laikotarpis prasideda nuo kadencijos pradžios, nes apima posėdžius,
kuriuos reikia papildyti. Paskutinė pareigų diena dar yra galiojanti. Laikotarpio pabaiga
užfiksuojama siunčiant ir vėliau nesikeičia.

<ChangelogNote date="2026-10-18" title="Klausiama tik apie laiką po paskutinės veiklos">

Anksčiau laikotarpis visada prasidėdavo nuo kadencijos pradžios, todėl atstovų būdavo klausiama ir apie
laiką, apie kurį jie jau pranešė.

</ChangelogNote>

<ChangelogNote date="2026-10-19" title="Galima paklausti pasibaigus pranešimui">

Koordinatorius gali siųsti **Ar vyko posėdis?**, kai institucija vėluoja arba jos būklė yra
**Pranešimo laikotarpis baigėsi**. Antruoju atveju gavėjų pasirinkime ir siuntimo peržiūroje rodoma
pranešimo pabaigos data. Automatinis klausimas siunčiamas tik pradėjus vėluoti.

Institucija turi būti aktyvi. Būsimas posėdis, šiuo metu galiojantis pranešimas „Posėdžio nebuvo“
arba dar nepraėjęs paskutinio posėdžio terminas klausimą sustabdo. Vien artėjančio posėdžio termino
neužtenka. Akademinių atostogų dienos neskaičiuojamos. Priežastis, kodėl klausti dar negalima,
rodoma renkantis gavėjus. **Papildyk posėdžių įrašus** sąlygos yra atskiros ir dėl šios būklės nesikeičia.

Jei institucijoje dar neužregistruota nei posėdžių, nei pranešimų apie veiklą, laikas skaičiuojamas
nuo kiekvieno gavėjo pareigų pradžios dabartinėje kadencijoje. Sekretoriui naudojama kadencijos pradžia.
Pavyzdžiui, jei laikotarpis prasideda **rugsėjo 1 d.**, periodiškumas yra **30 dienų** ir nėra
atostogų, klausti galima nuo **spalio 2 d.** Vėliau pareigas pradėjęs atstovas laukia savo termino.
Apžvalgoje tokia institucija ir toliau žymima **Nėra duomenų**, kol užregistruojama veikla.

</ChangelogNote>

Pranešimas, kuris prasidės tik ateityje, dabartinio neapimto laikotarpio neuždengia.
Sąlygos tikrinamos ir patvirtinant siuntimą, ir prieš pristatant laukiančius laiškus ar santraukas.
**Papildyk posėdžių įrašus** klausia apie jau vykusius posėdžius, kurių darbotvarkės tuščios arba
neužpildyti sprendimai. Jei laikotarpiu posėdžių nėra, pasirenkama **Ar vyko posėdis?**; jei visos
darbotvarkės užpildytos, papildymo užklausos nesiunčiamos. Papildyti įrašus galima prašyti ir po
neseniai vykusio posėdžio ar kol galioja pranešimas „Posėdžio nebuvo“.
Koordinatorius, kuris pats yra institucijos atstovas, gali įtraukti ir save.

Kol tam pačiam gavėjui apie tą pačią instituciją galioja neatsakyta ir neužbaigta užklausa,
tos pačios rūšies užklausa pakartotinai nesiunčiama, net jei laikotarpio pabaiga jau kita.
Nuoroda galioja 14 dienų. Atsakytos ar užbaigtos užklausos saugo nuo pakartotinio klausimo
apie visą jų jau apimtą laikotarpį; atsakymas „Ne mano institucija“ naujo klausimo neblokuoja.
Skirtingos užklausų rūšys vertinamos atskirai.

Automatinės užklausos siunčiamos kartą per dieną. Kaip ir koordinatoriaus siuntime, kiekvienas gavėjas
gauna vieną laišką apie visas tą dieną klausiamas institucijas; jame gali būti ir **Ar vyko posėdis?**,
ir **Papildyk posėdžių įrašus**.

Automatinė užklausa apie instituciją siunčiama ne dažniau kaip kartą per mėnesį. Mėnuo skaičiuojamas
nuo paskutinės bet kurios užklausos apie ją – koordinatoriaus ar automatinės, bet kurios rūšies,
net jei jos nuoroda jau nebegalioja ar atsakyta „Ne mano institucija“: koordinatoriui gali prireikti
laiko atnaujinti atstovus. Koordinatorius pats paklausti gali bet kada, taikant aukščiau aprašytas sąlygas.
Automatinė užklausa siunčiama dabartiniams užduoties **Pranešti apie veiklą** atsakingiesiems – jie
kasdien atnaujinami pagal dabartinius institucijos atstovus ar sekretorius.

### Atsakymas iš laiško {#atsakymas}

Atsakyti galima **neprisijungus**. Nuoroda atveria puslapį, o atsakymas įrašomas tik patvirtinus.
Nuorodos galioja **14 dienų**. Laiškas pradedamas pasisveikinimu vardu ir paaiškina, kad kreipiamasi į studentų atstovą (-ę). Nuoroda į Mano VU SA ir trumpas platformos paaiškinimas pateikiami poraštėje. Laiške pateikiami nedideli neutralūs atsakymo mygtukai; institucija ir laikotarpis nurodomi prie kiekvieno klausimo.

<DocScreenshot name="activity-request-email" narrow alt="Atstovui siunčiamas pranešimo el. laiškas su veiklos klausimu ir atsakymo mygtukais" caption="Pranešimo el. laiškas atstovui su pasirašytomis greitojo atsakymo nuorodomis." />

- **Taip, vyko** – įrašomos vieno ar kelių posėdžių datos, formatai ir laikas. Sprendimui el. paštu
  laiko nereikia. Darbotvarkės pildomos prisijungus prie Mano VU SA.
- **Ne, nevyko** – veiklos užklausoje patvirtinama, kad visą nurodytą laikotarpį posėdžio nebuvo.
  Kaip ir [pranešime „Posėdžio nebuvo“](#posedzio-nebuvo), galima nurodyti, kad posėdžių nebus ir toliau:
  **Dar dvi savaites**, **Iki mėnesio pabaigos** arba iki pasirinktos datos, ne vėliau kaip po
  3 mėnesių ir ne vėliau kaip dieną prieš jau suplanuotą posėdį.
  Jei posėdis tuo metu jau įrašytas, šio atsakymo patvirtinti negalima.
- **Viskas užfiksuota** – įrašų papildymo užklausoje patvirtinama, kad visi laikotarpio posėdžiai
  įrašyti ir jų darbotvarkės bei sprendimai užpildyti. Kol dar yra neužpildytų įrašų, patvirtinimo
  priimti negalima. Pranešimas apie laiką be posėdžių apima tik likusį tarpą po paskutinio posėdžio iki
  nurodytos pabaigos. Jau patvirtinti tarpai nedubliuojami.
- **Neatstovauju šiai institucijai** – pranešama klaususiems koordinatoriams patikrinti atstovų duomenis.
  Kitų gavėjų užklausos lieka atviros.

<DocScreenshot name="activity-request-reply" narrow alt="Atsakymo puslapis be prisijungimo, kuriame atstovas pažymi posėdžius" caption="Viešas atsakymo puslapis: atstovė nurodo posėdžio datą, laiką ir formatą be prisijungimo." />

Įrašų papildymo laiške ir puslapyje rodomi posėdžiai, kuriuos reikia papildyti. **Papildyti įrašus**
atveria užklausą; konkretaus posėdžio nuoroda veda į Mano VU SA, kur prisijungus papildoma
darbotvarkė ir sprendimai. Jei trūksta paties posėdžio, pasirenkama **Pridėti trūkstamą posėdį**.
Klaidingai užpildytos eilutės ir
pasirinktas atsakymas išlieka, kad būtų galima pataisyti laukus. Kompiuteryje kalendorius rodomas
lietuviškai (angliškame puslapyje – angliškai), savaitė prasideda pirmadienį, laikas rašomas 24 val.
formatu. Laiko pasiūlymai pateikiami kas 15 minučių nuo 08:00 iki 20:00; galima įrašyti ir kitą tikslų laiką, pvz., 07:12 ar 14:32. Telefono kalendoriaus kalba priklauso nuo įrenginio nustatymų.

Užfiksuotas posėdis gali užbaigti tik to laikotarpio veiklos užklausą. Įrašų papildymo užklausas
kitiems gavėjams užbaigia tik **Viskas užfiksuota**, kai patvirtintas laikotarpis apima jų
klausiamą laikotarpį. Senas, nesusijęs posėdis ar pranešimas dabartinės priminimo užduoties neužbaigia.

#### Kai institucijoje keli atstovai {#keli-atstovai}

Kiekvienas atstovas gauna savo užklausą, bet atsakymai pildo tą patį institucijos įrašą. Kai vienas
atstovas užfiksuoja posėdį ar patvirtina, kad jo nebuvo, kitų užklausos užsibaigia. Atvėrus savo
nuorodą rodoma, kas ir kada atsakė, bei skiltis **Jau užfiksuota** su laikotarpio posėdžiais ir
patvirtintais tarpais be posėdžių. Žinant apie posėdį, kurio kolega neįrašė, galima pridėti jį ten pat
(**Žinai apie kitą posėdį? Pridėk jį.**); tą patį posėdį pakartotinai įrašius, dublikatas nesukuriamas.
Patvirtinti, kad posėdžio nebuvo, kai kolega jau įrašė posėdį, negalima. Jei atstovo laikotarpis
prasidėjo anksčiau nei kolegos, puslapis rodo, kurią laikotarpio dalį dar liko patvirtinti.
Kolegos atsakymai laiškais nesiunčiami.

Kadencijos viduryje pareigas pradėjęs atstovas automatinę užklausą gauna tą pačią dieną kaip ir kiti,
bet klausiamas tik nuo savo pareigų pradžios. Jei institucijoje dar neužregistruota jokios veiklos,
jo klausiama, kai suėjus jo paties terminui praėjo ir [mėnuo nuo paskutinės užklausos](#paklausti).

- **Ne, nevyko** įrašo tik dienas, kurių dar nepatvirtino joks pranešimas. Kolegų pranešimai
  nekeičiami, o atšaukti atstovas gali tik savo pranešimą.
- Kai institucija nebevėluoja, užduotis **Pranešti apie veiklą** užsibaigia, bet **Ar vyko posėdis?**
  užklausa lieka atvira, kol dalis jos laikotarpio nepatvirtinta. Pavyzdžiui, jei vėliau pareigas
  pradėjęs atstovas patvirtina tik savo laikotarpį, kolegos vis dar gali atsakyti apie ankstesnę dalį –
  įrašoma tik ji. Visą laikotarpį apėmusios užklausos užsibaigia kartu su užduotimi.
- Užfiksuotas posėdis sutrumpina kolegos pranešimą „Posėdžio nebuvo“ arba jį ištrina
  ([kaip ir visada](#posedzio-nebuvo)). Iš laiško posėdį galima įrašyti tik savo klausiamame laikotarpyje.

### Užklausos atstovams {#uzklausos}

Institucijos skiltyje **Užklausos atstovams** koordinatorius mato visas institucijos
užklausas, gavėjas – tik savąsias; kiti skaitytojai jų nemato. Naujausios siuntimo grupės rodomos
pirmos, pagal užklausos rūšį. Rodomas siuntėjas arba automatinis šaltinis, laikotarpis, žinutė, gavėjas,
atsakymas, laikai ir užfiksuoti rezultatai. Koordinatoriui nerodomos gavėjo atsakymo nuorodos.

<DocScreenshot name="activity-request-history" alt="Institucijos skiltis Užklausos atstovams: užklausos rūšis, gavėjas, būsena, laikotarpis, siuntėjas, žinutė ir galiojimo pabaiga" caption="Koordinatoriaus vaizdas: kam ir kada išsiųsta užklausa ir ar jau atsakyta." />

Istorija įkeliama atskirai, po 20 siuntimo grupių; senesnės atveriamos mygtuku **Rodyti daugiau**.
Būsena rodo, ar dar laukiama atsakymo, jau atsakyta, veikla patvirtinta kitu įrašu (ir kuris
kolega atsakė), ar nuorodos galiojimas baigėsi. Ankstesnės užklausos be užfiksuotos pabaigos išlaiko laikotarpį iki atsakymo dienos.

Dar neatsakytos užklausos taip pat pažymimos [posėdžių laiko juostoje](/visak/padaliniai#posedziu-laiko-juosta),
tiems patiems žmonėms kaip ir ši skiltis.

### Sekretoriai {#sekretoriai}

<ChangelogNote date="2026-10-02" title="Sekretorius ir koordinatorius atlieka skirtingus darbus">

Sekretorius skiriamas kadencijai, jei jis fiksuos jos posėdžius: tų posėdžių užduotys ir laiškai keliauja jam. Koordinavimo atsakomybė pati savaime žmogaus sekretoriumi nepaskiria.

</ChangelogNote>

**Sekretorius** paskiriamas konkrečios kadencijos posėdžiams tvarkyti (skirtukas **Kadencijos ir
sekretoriai**). Sekretoriumi gali būti bet kuris narys, nebūtinai institucijos narys.

- Kai kadencijai paskirti sekretoriai, **tos kadencijos posėdžių užduotys ir laiškai tenka tik
  jiems**. Kiti institucijos nariai jų nebegauna. Jei sekretorių nėra, užduotys tenka tuo metu
  pareigas ėjusiems atstovams.
- Pakeitus sekretorius, atviros tos kadencijos užduotys perduodamos naujiems, o atliktos lieka
  kaip buvo. Perdavimas pranešimų nesiunčia.
- Skirtuke keisti galima dabartinės ir kitos kadencijos sekretorius. Ankstesnių kadencijų sekretoriai
  rodomi tik kaip istorija.
- Jei institucija turi savo kadencijas, sekretoriai skiriami joms, jei ne – bendroms kadencijoms
  (žr. [Kadencijos](/sistema/nustatymai#kadencijos)). Savas kadencijas galima nustatyti tame pačiame
  skirtuke paspaudus **Nustatyti savas**; to prireikia retai.
- Nustačius pirmą savo kadenciją, sekretoriai perkeliami iš ją dengiančios bendros kadencijos, o
  atviros užduotys perskirstomos. Ištrynus paskutinę savo kadenciją, vėl galioja bendrosios
  kadencijos ir jų sekretoriai.
- Sekretoriai nerodomi tarp narių, kontaktų ir paieškoje.

Kuo sekretorius skiriasi nuo koordinatoriaus, aprašyta [ViSAK](/visak/#koordinatorius) puslapyje.

### Pareigybės

Skirtuke **Pareigybės** matyti institucijos pareigybės ir jų nariai. Redaguoti galintys gali keisti
pareigybių tvarką (rodyklėmis, tada **Išsaugoti**) ir priskirti žmogų pareigybei. Pareigybės
kuriamos ir tvarkomos [Pareigybių](/organizacija/pareigybes) skiltyje.

### Sekti ir nebesekti {#sekti}

Institucijas galima **sekti**: gausi pranešimus apie jos naujus posėdžius ir užpildytas
darbotvarkes, o jos posėdžiai matysis tavo [Apžvalgoje](/visak/apzvalga). Sekti galima institucijos
puslapyje (**⋯ → Sekti**) arba sąraše, pažymėjus kelias institucijas. Sąrašo greitasis filtras
**Sekamos** rodo tik jas.

- Sekti galima institucijas, kurias galima matyti, ir kitų padalinių aktyvias institucijas, kurių
  posėdžiai vieši.
- **Nebesekti** – pašalinti instituciją iš sekamų sąrašo ir nebegauti jos sekėjams skirtų pranešimų.
- Pranešimai dėl einamų pareigų nepriklauso nuo pasirinkimo sekti instituciją.

<ChangelogNote date="2026-10-02" title="Vienas pasirinkimas – sekti arba nebesekti">
Atskiras institucijos nutildymas pašalintas. Anksčiau sektos ir nutildytos institucijos
nebesekamos, kad jų pranešimai nebūtų vėl įjungti. Pranešimų nustatymuose išlieka laikinas
visų pranešimų nutildymas ir siuntimo kanalų pasirinkimai.
</ChangelogNote>

### Sukurti, redaguoti, ištrinti

- **Sukurti** instituciją galima tik savo padalinyje. Būtinas lietuviškas pavadinimas. Lietuviškas
  pavadinimas ir lietuviškas trumpinys turi būti **unikalūs visoje platformoje** – ištrintos
  institucijos neskaičiuojamos. Todėl trumpinys turėtų nurodyti ir padalinį (pvz., „VU TF SPK“, ne „SPK“).
- Jei redaguojama institucija, kurios pavadinimas ar trumpinys sutampa su kitos, išsaugoti pavyks tik
  juos pakeitus.
- **Padalinio** vėliau pakeisti negalima (išskyrus super administratorių).

#### Viešas adresas {#adresas}

Kiekviena institucija turi savo viešą adresą, pvz., `mif.vusa.lt/lt/kontaktai/programu-sistemos-ba`.
Jis sudaromas iš lietuviško pavadinimo ir yra unikalus visoje platformoje, įskaitant ištrintas
institucijas. Jei toks adresas jau užimtas, priekyje pridedamas padalinio trumpinys
(pvz., `fsf-ma-sociologija`).

- Pakeitus pavadinimą, adresas pasikeičia kartu su juo, nebent jis buvo pakeistas ranka
  (formos skiltis **Nuoroda**). Adrese gali būti tik mažosios raidės be lietuviškų ženklų, skaičiai ir brūkšneliai.
- Senas adresas toliau nukreipia į instituciją. Ankstesni adresai rodomi po nuoroda, skiltyje
  **Ankstesni vieši adresai**; nebereikalingą galima ištrinti – tada jis nebeveiks.
- Padalinio pagrindinės institucijos ir centrinio biuro adresas nekeičiamas.

<ChangelogNote date="2026-10-19" title="Kiekviena institucija turi savo adresą">

Anksčiau kelios institucijos galėjo turėti tą patį adresą, todėl viešose kortelėse visos vesdavo į
vieną instituciją. Dabar adresai sudaryti iš pavadinimų, o seni adresai nukreipia į naujus.

</ChangelogNote>
- **Ištrinta** institucija patenka į [šiukšlinę](/pagrindai/platforma#siuksline), o jos
  pareigybės ir posėdžiai lieka. Institucijos, kurioje einamos pareigos, ištrinti negalima.
- Šiukšlinės veiksmas **Atkurti ir atidaryti** atkuria instituciją ir iškart atidaro jos įrašą. Įrašai be pavadinimo sąraše ir įrašo puslapyje rodomi kaip **Be pavadinimo · nuorodos kodas**, kad juos būtų galima atskirti ir rasti pagal kodą. **Galutinai ištrinti** negalima, kol ji turi
  posėdžių, pareigybių ar pranešimų apie veiklą arba yra padalinio pagrindinė institucija.
- Galutinai ištrynus instituciją, šiukšlinės sąrašas atsinaujina automatiškai.

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
| Kasdien 10.00, kai būklė „Artėja terminas“ arba „Vėluoja“ | Dabartiniai studentų atstovai (jei paskirtas – kadencijos sekretorius) | Užduotį **„Pranešti apie veiklą“** |
| Kasdien 10.00, kai būklė tampa „Vėluoja“ | Tie patys | Vieną laišką „Ar vyko posėdis?“ (arba „Papildyk posėdžių įrašus“, jei paskutinio posėdžio įrašai nepilni), į kurį [atsakoma neprisijungus](#atsakymas). Neatsakius, po savaitės užduotis patenka į savaitinę vėluojančių užduočių suvestinę |
| Koordinatorius [paklausia atstovų](#paklausti) | Tie patys | Vieną laišką „Ar vyko posėdis?“ apie visas jų institucijas, su koordinatoriaus žinute |
| Gavėjas atsako „Nesu šio organo narys (-ė)“ | Klausęs koordinatorius, o automatinio priminimo atveju – institucijos koordinatoriai | Pranešimą patikrinti institucijos atstovus |
| Užregistruojamas dabartinį veiklos laikotarpį apimantis posėdis arba pranešimas „Posėdžio nebuvo“ | – | Periodiškumo užduotis pažymima atlikta automatiškai, o jos laiško klausimai užbaigiami |
| Seki instituciją ir sukuriamas jos posėdis ar užpildoma darbotvarkė | Sekėjai, galintys matyti posėdį | Pranešimą |

- Užduotis nekuriama per akademines atostogas, jei institucija turi suplanuotą posėdį ar galiojantį
  pranešimą, ir **neaktyvioms** institucijoms. Jos terminas nepatenka į atostogas.
- Veiklos būklė sąrašui perskaičiuojama kas naktį, o užregistravus posėdį ar pranešimą –
  iš karto.

### Periodiškumas po pranešimo

Pranešimas apie tai, kad posėdžio nebus, suteikia atskirą pusės periodiškumo papildomą laikotarpį. Tik akademinės atostogos stabdo laiką. Datos pavyzdys ir bendros taisyklės pateiktos [Apžvalgoje](/visak/apzvalga#periodiskumo-ir-pranesimo-pavyzdys).

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
- Periodiškumo užduotis: `tasks:repopulate institution` (kasdien 10.00) →
  `PeriodicityGapTaskHandler`; gavėjai – `ResolveTaskAssignees::forInstitution`. Būklei įkeliami visi
  pranešimai „Posėdžio nebuvo“; pasibaigusiems taikoma pusė periodiškumo. Kasdienis vykdymas taip pat
  užbaigia likusias atviras periodiškumo užduotis, kai dabartinė būklė dėmesio nebereikalauja.
- Pranešimo sutrumpinimas: `CheckInService::adjustForMeeting()` iš `MeetingController`.
- Klausimai „Ar vyko posėdis?“: `InstitutionActivityRequest` (vienas gavėjui ir institucijai),
  siunčia `SendInstitutionActivityRequests` (`tasks:repopulate institution` per `forTask()`, kai
  būklė „Vėluoja“ – vieną kampaniją užduočiai; `InstitutionActivityRequestController` koordinatoriui).
  Laikotarpio pradžią skaičiuoja `ResolveCheckInStarts::startFor()`. Atsakymo puslapis
  `InstitutionActivityAnswerController` pasiekiamas per pasirašytą nuorodą (`signed`); GET nieko
  neįrašo, nes pašto skeneriai atveria nuorodas. Atsakymą įrašo `AnswerInstitutionActivityRequest`,
  o laikotarpį atitinkančius veiklos klausimus užbaigia `ResolveInstitutionActivityRequests`.
  Įrašų papildymo klausimus užbaigia tik aiškus laikotarpio patvirtinimas.
- Sekretoriai: `InstitutionSecretary`; perskyrimas – `ResyncTaskAssigneesForCadence`; perkėlimas į
  savą kadenciją – `CarrySecretariesIntoOverride` (`Cadence::booted()`).
