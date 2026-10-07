---
doc_status: reviewed
title: Dokumentai
area: documents
models: [Document]
last_reviewed: 2026-10-07
tests:
  - tests/Feature/Admin/Resources/DocumentControllerTest.php
  - tests/Feature/Admin/Resources/DocumentPublicationTest.php
  - tests/Feature/Admin/Resources/DocumentPickTest.php
  - tests/Feature/DocumentLinkConcurrencyTest.php
  - tests/Feature/Services/SharepointDocumentDiscoveryTest.php
  - tests/Feature/Services/SharepointDocumentFieldsTest.php
  - tests/Feature/DocumentShortcutSyncTest.php
  - tests/Feature/DocumentSyncTest.php
  - tests/Feature/DocumentPermissionRevocationTest.php
  - tests/Feature/DocumentSyncMissingModelTest.php
  - tests/Feature/Meetings/MeetingDocumentTest.php
  - tests/Feature/Search/SearchExperienceTest.php
  - tests/Browser/SearchExperienceTest.php
  - resources/js/Components/Files/__tests__/DocumentFolderBrowser.component.test.ts
---

# Dokumentai

Dokumentai – viešai skelbiami VU SA ir padalinių nuostatai, ataskaitos, veiklos planai ir
protokolai. Archyvą atverk per **ViSAK → Dokumentai** (`/mano/documents`). Dokumentus
tvarkantiems jis taip pat pasiekiamas Svetainės srityje.

## Susitarimai {#susitarimai}

### Ką keliame į dokumentų naršyklę

- VU SA P nuostatus;
- VU SA P tarpines ir metines ataskaitas;
- VU SA P metų veiklos planus;
- ataskaitinių-rinkiminių, neeilinių rinkiminių ir neeilinių ataskaitinių-rinkiminių konferencijų
  protokolus;
- kolegialaus valdymo organo (Valdybos, VU SA MIF atveju – Tarybos) protokolus.

### Kaip keliame

- Dokumentus kelia VU SA P administratoriai. Studentų iniciatyvos dokumentų kol kas nekelia.
- Protokolai keliami į archyvą ir svetainę **.pdf** formatu. Šablonai gali būti .docx ar kito
  formato.
- Jei padalinyje veikia tarptautiniai studentai, dokumentai keliami lietuvių ir anglų kalbomis.
- Pastebėjus klaidą įkeltame dokumente, jis ištrinamas, pataisomas ir įkeliamas iš naujo.

## Kaip tai veikia

### Archyvas ir susieti failai {#archyvas}

Archyvo įrašas saugo dokumento duomenis ir viešą SharePoint nuorodą. Pats failas lieka
SharePoint. Dokumento rūšis, data, kalba ir institucija padeda jį rasti ir viešoje svetainėje.

Prie posėdžio ar pareigybės skirtuke **Failai** įkelti failai nėra archyvo įrašai ir viešai
nerodomi – žr. [Įrašų failai](/visak/failai). Bendrą integraciją paaiškina
[SharePoint integracija](/sistema/sharepoint).
Posėdžio dokumentų susiejimas aprašytas [Posėdžių gide](/visak/posedziai).

<ChangelogNote version="v3.0" date="2026-10-02" title="Dokumentų archyvas pasiekiamas visiems nariams">

Dokumentus naršyk be valdytojo rolės. SharePoint tikrinimas, paskelbimas ir slėpimas lieka
juos tvarkantiems; pirmą kartą sąrašas atrenkamas pagal tavo padalinius ir centrinę VU SA.

</ChangelogNote>

### SharePoint failai ir jų būsenos {#laukia}

Platforma kas 15 minučių pati perskaito SharePoint aplanką **Dokumentų sistema** ir kiekvieną jo
failą įrašo į Dokumentus. Failo nereikia rinktis SharePoint lange – jis atsiranda pats, o skubant galima spausti
**Tikrinti SharePoint** – kai patikrinimas baigiasi, nauji failai ir skaičiai atsinaujina patys.
Maždaug kartą per savaitę archyvas perskaitomas visas iš naujo.

Kiekvienas failas turi būseną:

| Būsena | Ką reiškia | Kur rodoma |
|---|---|---|
| **Laukia** | Naujas SharePoint failas, dar nenuspręsta, ar jį rodyti | Tik dokumentų valdytojams |
| **Paskelbtas** | Turi viešą nuorodą ir rodomas vusa.lt, paieškoje bei posėdžių puslapiuose | Visiems |
| **Paslėptas** | Nusprendta vusa.lt nerodyti; vieša nuoroda atšaukta | Tik dokumentų valdytojams |
| **Pašalintas iš SharePoint** | Failas ištrintas ar perkeltas iš archyvo; vusa.lt nebėra rodomas | Tik dokumentų valdytojams |

Pirmą kartą perskaičius archyvą, jau anksčiau jame buvę ir niekada neįkelti failai įrašomi kaip
**Paslėpti** – juos galima rasti ir paskelbti vėliau. Skiltyje **Laukia** atsiranda tik po to
SharePoint įkelti failai.

Dokumentų valdytojams Dokumentai rodomi kaip vienas failų sąrašas, sudarytas iš duomenų bazės: visi
paskelbti dokumentai ir visi tavo tvarkomi failai, nesvarbu, kokios jų būsenos. Kitų padalinių
paskelbtus dokumentus matai, bet jų nekeiti. Po pavadinimu renkiesi, kaip žiūrėti:

- **Sąrašas** – visi failai pasirinktame aplanke ir giliau, naujausiai pakeisti pirmi; prie failo
  nurodomas jo aplankas;
- **Aplankai** – tie patys aplankai kaip SharePoint; prie aplanko nurodoma, kiek jame failų laukia,
  paskelbta ir paslėpta.

Abiem atvejais atsidaroma aplanke, kuriame yra tavo padalinio failai, o kelias viršuje leidžia
pakilti aukščiau. Failus galima atrinkti pagal būseną (**Visi**, **Laukia**, **Paskelbti**,
**Paslėpti**, o kai tokių yra – **Pašalinti iš SharePoint**) ir rūšį bei ieškoti pagal pavadinimą.
Pasirinkimas išlieka perjungiant tarp sąrašo ir aplankų. Iš pradžių rodoma 100 failų, kitus atveria
**Rodyti daugiau**.

Failo būsena rodoma atskirame stulpelyje; paspaudus ją galima pasirinkti **Paskelbtas** arba
**Paslėptas**. Pažymėjus kelis failus, apačioje atsiranda **Paskelbti** ir **Paslėpti** visiems iš
karto. Prie failo nurodomas jo poaplankis atidaryto aplanko atžvilgiu; ilgas kelias sutrumpinamas,
o visas rodomas užvedus pelę. **Pašalinti iš SharePoint** visada rodo viso archyvo tokius failus.

Paslėpus anksčiau rodytą failą, jo vieša SharePoint nuoroda atšaukiama ir nustoja veikti. Vėl
parodžius sukuriama nauja nuoroda; trumpoji `vusa.lt/d/…` nuoroda lieka ta pati. Prie failo
nurodomas jo SharePoint aplankas ir pažymima, jei SharePoint trūksta padalinio, turinio rūšies,
datos ar kalbos, arba jei nurodytas padalinys nesutampa su jokia institucija. Duomenys taisomi
SharePoint (**Atidaryti SharePoint**) ir per kelias minutes atsinaujina čia. Perkeltas ar
pervadintas failas ar aplankas čia atsiranda naujoje vietoje, net jei pats failas nepakeistas.
Jei SharePoint nurodytas padalinys dar neturėjo institucijos, ją sukūrus failai priskiriami jai
automatiškai.

Į Dokumentus patenka tik dokumentų failai: **PDF**, **Word** (.doc, .docx), **Excel** (.xls,
.xlsx), **PowerPoint** (.ppt, .pptx), **OpenDocument** (.odt, .ods, .odp), **.rtf**, **.txt** ir
SharePoint nuorodos (**.url**). Nuotraukos, vaizdo įrašai, maketų failai (pvz., .psd, .ai) ir
archyvai (.zip, .rar) lieka tik SharePoint. Kitame SharePoint aplanke esantis failas taip pat
neįtraukiamas, nebent jis jau buvo dokumentas anksčiau.

Padalinys lemia, kas failą mato ir tvarko: padalinio dokumentų valdytojas mato tik savo padalinio
failus. Failas be padalinio rodomas tik visų padalinių dokumentus tvarkantiems.

Failui grįžus į SharePoint (pvz., atkūrus iš šiukšlinės), jis vėl įgauna ankstesnę būseną.
Trumpoji nuoroda `vusa.lt/d/…` veikia tik paskelbtam dokumentui; vėl paskelbus, ta pati nuoroda
vėl veikia.

<ChangelogNote version="v3.0" date="2026-10-02" title="Visi SharePoint archyvo failai Dokumentuose">

Nauji archyvo failai patys atsiranda su būsena **Laukia**, o dokumentų valdytojas nusprendžia,
kuriuos rodyti vusa.lt – sąraše arba SharePoint aplankuose, keisdamas failo būseną. Įprastas
**Pasirinkti iš SharePoint** langas liko: pasirinktas failas iškart paskelbiamas.

</ChangelogNote>

### Sąrašas ir peržiūra {#sarasas}

Ieškok pagal pavadinimą, atverk filtrus arba pasirink greitąjį dokumentų rūšies filtrą.
Pradinius padalinių filtrus gali pakeisti. Lentelėje matai datą, pavadinimą, rūšį, instituciją
ir kalbą; peržiūros rodinyje – daugiau dokumento informacijos.

Paspaudęs viešą nuorodą turintį pavadinimą, atversi failą naujame naršyklės lange.
Jei nuorodos nėra, pavadinimas nėra aktyvus. Šis paskelbtų dokumentų sąrašas skirtas nariams,
kurie dokumentų netvarko; dokumentų valdytojai mato [visų failų sąrašą](#laukia).

### Vieša dokumentų paieška {#viesa-paieska}

Svetainės dokumentų paieška atpažįsta žodžių formas: **įstatai** randa ir **įstatų**, o
**įstat** ieško pagal rašomo žodžio pradžią. Sutapimas paryškinamas pačiame pavadinime.
Svarbių tipų dokumentai rodomi aukščiau tarp vienodai aktualių rezultatų.

Nustatymų valdytojas gali pasirinkti dokumentus ir frazes skiltyje
[Dokumentų nustatymai](/sistema/nustatymai#dokumentai). Jie rodomi atskiroje skiltyje
**Rekomenduojami dokumentai** ir nekartojami bendrame sąraše. Pasirinkti filtrai taikomi ir
rekomendacijoms. Pradinė bendro sąrašo tvarka – **Naujausi pirmi**, pažymėta ir rikiavimo
valdiklyje; rekomendacijos lieka virš jo. Įvedus paiešką sąrašas savaime rikiuojamas
**Pagal aktualumą**. **Seniausi pirmi** rodo vien chronologinį sąrašą.

## Veiksmai {#veiksmai}

### Paskelbti ar paslėpti failą {#paskelbti}

1. Įkelk failą į SharePoint archyvą ir užpildyk jo duomenis pagal [susitarimus](#susitarimai).
2. Dokumentuose pasirink būseną **Laukia** ir rask failą sąraše ar aplanke. Jei failo dar nėra,
   spausk **Tikrinti SharePoint** ir palauk kelias minutes.
3. Paspausk failo būseną ir pasirink **Paskelbtas**. Jau paskelbtą failą paslėpsi pasirinkęs
   **Paslėptas**. Kelis failus pažymėk ir spausk **Paskelbti** ar **Paslėpti** apačioje.
4. Jei failui trūksta duomenų, prieš paskelbiant tai parodoma – gali paskelbti iš karto arba
   pirma pataisyti duomenis SharePoint.

Tą patį galima padaryti ir įprastu būdu: **Pasirinkti iš SharePoint** atveria SharePoint langą,
pasirinkti failai iškart paskelbiami. Jei patikrinimas failo dar nerado, jis įtraukiamas tuo pat
metu. Pasirinkti galima tik dokumentų archyvo (**Dokumentų sistema**) failus; jei failui trūksta
duomenų, tai parašoma pranešime po paskelbimo. Langas veikia tik per https.

Paskelbus vieša nuoroda sukuriama fone, todėl paskelbtųjų sąraše dokumentas atsiranda po kelių
minučių. Kol nuoroda kuriama, prie failo rodoma „Vieša nuoroda dar nesukurta“, o aplankas
atsinaujina pats, kai nuoroda sukuriama. Bandomojoje
ir vietinėje aplinkoje gyvo SharePoint archyvo nuorodos nekuriamos – ten failas lieka be nuorodos. Paslėptą failą vėliau galima vėl parodyti tame pačiame aplanke. Posėdžio puslapyje
laukiantys to padalinio failai rodomi skiltyje **Susieti dokumentai** – **Paskelbti ir susieti**
failą paskelbia ir susieja su posėdžiu (žr. [Posėdžių gidą](/visak/posedziai)).

### Atnaujinti duomenis {#sinchronizavimas}

Pakeitimai SharePoint atsinaujina patys per 15 minučių; **Tikrinti SharePoint** sąrašo viršuje
patikrina archyvą iš karto, o rasti nauji ar pakeisti failai atsiranda neperkrovus puslapio.
Dokumento turinį ir archyvo metaduomenis keisk SharePoint; atskiros redagavimo formos nėra.

Pranešimas apie užduoties įtraukimą į eilę dar nereiškia, kad sinchronizacija baigta.
Dokumentų sąrašas nuorodos būseną atnaujina pats. Jei rodoma „Nepavyko sukurti viešos nuorodos“,
patikrink, ar failas vis dar yra SharePoint ir ar jo duomenys tinkami, tada spausk
**Bandyti dar kartą**.
Jei nepavyksta, pateik [pagalbos užklausą](/sistema/pagalbos-uzklausos) su dokumento pavadinimu
ir atliktais žingsniais.

### Nerodyti dokumento {#trynimas}

SharePoint esančio dokumento platformoje ištrinti negalima: failas kitą kartą vėl atsirastų sąraše.
Vietoj to pakeisk jo būseną į **Paslėptas** – jis nebebus rodomas vusa.lt, o vieša nuoroda
atšaukiama. Visai pašalinti failą galima tik SharePoint; tada jis pažymimas
**Pašalintas iš SharePoint**.

Tokius įrašus galima ištrinti: pasirink būseną **Pašalinti iš SharePoint**, prie įrašo spausk
**Ištrinti** (arba pažymėk kelis ir spausk **Ištrinti** apačioje) ir patvirtink. Įrašas ir jo
sąsaja su posėdžiu ištrinami visam laikui.

Jei paskelbto dokumento nuorodos sukurti nepavyko, po pavadinimu rodoma „Nepavyko sukurti viešos
nuorodos“ ir **Bandyti dar kartą**. Toks dokumentas neatsiranda paskelbtųjų sąraše, kol nuoroda
sukuriama.

## Kas ką gali {#teises}

| Veiksmas | Bet kuris prisijungęs narys | Padalinio dokumentų valdytojas | Super Admin |
|---|---|---|---|
| Naršyti paskelbtus dokumentus ir atverti viešas nuorodas | ✓ | ✓ | ✓ |
| Tikrinti SharePoint iš karto | – | ✓ | ✓ |
| Matyti laukiančius, paslėptus ir pašalintus failus | – | ✓ Savo padalinio | ✓ Visų, ir be padalinio |
| Paskelbti, paslėpti ar pasirinkti iš SharePoint | – | ✓ Savo padalinio | ✓ Visų, ir be padalinio |
| Atnaujinti ar bandyti dar kartą sukurti nuorodą | – | ✓ Savo padalinio | ✓ Visų padalinių |
| Ištrinti pašalintus iš SharePoint | – | ✓ Savo padalinio | ✓ Visų, ir be padalinio |

Kitų padalinių paskelbti failai sąraše rodomi, bet jų būsena nekeičiama.

**Padalinio dokumentų valdytojas** – atskira rolė. Vien išteklių administratoriaus rolė
nesuteikia dokumentų valdymo.

## Pranešimai ir automatizavimas {#pranesimai}

SharePoint archyvas tikrinamas kas 15 minučių, visas perskaitomas kartą per savaitę. Jei vienas
patikrinimas pašalintų neįprastai daug dokumentų, jis sustabdomas ir nieko nekeičia – tai
saugo nuo klaidingos SharePoint konfigūracijos. Nepavykęs patikrinimas kartojamas kitą kartą;
ilgiau nei valandą nepavykstantis rodomas [Sistemos būsenoje](/sistema/sistemos-busena). Failas,
kurio paties duomenys netinkami tris patikrinimus iš eilės, praleidžiamas, kad nestabdytų kitų;
jis vėl bandomas pasikeitus arba per savaitinį pilną patikrinimą, o praleistų failų skaičius
rodomas Sistemos būsenoje. Laikinos SharePoint klaidos (perkrova, sutrikimas, prisijungimas)
failo nepraleidžia – jis bandomas kitą kartą. Jei SharePoint paprašo ilgesnės pertraukos,
patikrinimai sustabdomi tiek, kiek prašoma, ir tęsiami nuo tos pačios vietos. Neperskaitoma SharePoint data paliekama tuščia ir pažymima kaip
trūkstama.

Sinchronizavimo užduotys vykdomos fone. Jos atnaujina duomenis ir būseną;
į eilę įtrauktas dokumentas, ištrintas iki užduoties vykdymo, nebeatkuriamas.
Dokumentą paslėpus, atskira užduotis atšaukia SharePoint viešos prieigos leidimą. Jei dokumentas
paslepiamas tuo metu, kai jo nuoroda dar kuriama, sukurta nuoroda panaikinama (nepavykus –
pakartotinai fone). Vieno dokumento nuorodos užduotys vykdomos po vieną, todėl greitai vėl
paskelbtas dokumentas nepraranda nuorodos.

## Techninė informacija {#technine-informacija}

- Sąrašą ir veiksmus valdo `DocumentController`; `index` leidžia naršyti be valdymo rolės.
- `documents.create.padalinys` leidžia matyti nepaskelbtus failus ir paleisti `documents.discover`;
  pavienio atnaujinimo ir paskelbimo apimtį tikrina `DocumentPolicy`. Eilutėms ji perduodama kaip
  `can.update` (`DocumentRowResource`), archyvo eilutėms – `abilities.updateTenantShortnames`.
- Paskelbimas ir paslėpimas – tik per `UpdateDocumentStatus`, kuris eilutę skaito iš naujo su
  `lockForUpdate()`; nuorodą saugantis `DocumentSharepointSyncService` laikosi to paties užrakto, todėl
  paslėpimas visada mato atšauktiną leidimą. Posėdžio susiejimas eina per `linkToMeeting()`; su kitu
  posėdžiu susietas dokumentas atmetamas (`StoreMeetingDocumentRequest::after()`).
- Sąsaja: `IndexDocument.vue` – nariams Typesense archyvas (`useTypesenseCollectionSource`),
  `documents.create` turintiems – `DocumentFolderBrowser.vue` (`api.v1.admin.documents.folder`:
  `flat=1` – sąrašas, `show=all|pending|published|hidden|removed`, `limit` iki 500 atnaujinimui;
  apimtis – `Document::browsableBy()`, t. y. paskelbti ∪ `manageableBy()`). Senos `?queue=` ir
  `?browse=folders` nuorodos nukreipiamos į `?status=` / `?layout=`. Kol kuriama nuoroda,
  `DocumentFolderBrowser` apklausia API kas 3 s (iki 1 min.); keitimo užklausos siunčiamos po vieną.
- Pašalintų trynimas: `DELETE api.v1.admin.documents.destroyRemoved` (`DestroyRemovedDocumentsRequest`,
  `DocumentPolicy::delete`, tik su `removed_from_sharepoint_at`).
- SharePoint failų langas: `documents.pick` ir `meetings.documents.storeFromSharepoint`
  (`ResolvePickedDocuments` → `SharepointDocumentDiscovery::importPicked()`); reikia `VITE_SHAREPOINT_*`
  ir https.
- Atnaujinimas: `SyncDocumentFromSharePointJob`; leidimo atšaukimas: `RevokeSharepointPermissionJob`
  (`afterCommit`). Abu vykdomi numatytojoje eilėje po vieną dokumentui
  (`DocumentSharepointLock`, bendras `WithoutOverlapping`); atšaukimas praleidžiamas, jei vėl paskelbtas
  dokumentas naudoja tą patį leidimą; `sharepoint:sync-documents` tikrina tik paskelbtų
  dokumentų nuorodas. Nepakitęs (`eTag`) paskelbtas dokumentas be nuorodos vis tiek sinchronizuojamas.
- Typesense: `Document::toSearchableArray()` grąžina `[]` nepaskelbtam dokumentui, nes eilės
  `MakeSearchable` iš naujo `shouldBeSearchable()` neklausia.
- Aplanką nurodo `SHAREPOINT_DOCUMENT_DISCOVERY_FOLDER` (numatytasis – `Dokumentų sistema`). Graph `delta` veikia tik
  bibliotekos šaknyje, todėl skaitoma visa biblioteka, o nauji įrašai kuriami tik iš šio aplanko ir
  tik `filesystems.sharepoint.document_discovery_extensions` failų tipams. `SharepointDocumentDiscovery`
  skaito archyvo `delta` sąrašą (`DiscoverSharepointDocumentsJob` kas 15 min. `long-running` ryšiu,
  pilnas – kai nuo paskutinio pilno praėjo 7 dienos),
  komanda `sharepoint:discover-documents {--full} {--dry-run} {--allow-mass-removal} {--limit=}`.
  `--limit=N` prideda N naujų failų aplankų tvarka, nieko nepažymi pašalintu ir neišsaugo `delta`
  vietos – kartojant failai pridedami dalimis, o paleidimas be `--limit` darbą užbaigia. Graph
  ribojimai (429/503) palaukiami pagal visą `Retry-After`; ilgesnis nei 120 s sustabdo atradimą iki
  nurodyto laiko (`SharepointThrottledException`). Tik 404/410 ir neperskaitomi duomenys skaičiuojami
  į praleidimą; 5xx, 401/403 ir neatsakyti ribojimai tik sulaiko `delta` vietą. Būsena –
  `documents.status` (`pending`, `published`, `hidden`) ir `removed_from_sharepoint_at`; kol
  `DocumentDiscoverySettings::last_full_run_at` tuščias, nauji įrašai kuriami kaip `hidden`;
  į Typesense patenka tik paskelbti dokumentai, kiti rodomi iš duomenų bazės. Paskelbti ar paslėpti – `documents.status`, apimtį tikrina
  `DocumentPolicy::publish`.
- `DocumentPublicationTest` tikrina paskelbimą, paslėpimą ir padalinių ribas;
  `SharepointDocumentDiscoveryTest` – naujus, pakeistus, ištrintus ir atkurtus failus, masinio
  šalinimo apsaugą (tikrinama prieš bet kokį įrašymą), aplankų perkėlimus (vienas žemėlapis, taikomas
  pradiniam keliui), vietos taisymą be metaduomenų užklausos, pavienio failo klaidas ir padalinių
  priskyrimą iš naujo.
- `DocumentControllerTest` tikrina naršymą be valdymo veiksmų, atnaujinimą ir padalinių ribas. `DocumentSyncTest` ir `DocumentPermissionRevocationTest` tikrina foninę eigą;
  `MeetingDocumentTest` – dokumentų susiejimą su posėdžiais.
