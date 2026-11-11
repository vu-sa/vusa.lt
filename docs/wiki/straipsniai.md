---
title: Wiki straipsniai
doc_status: reviewed
last_reviewed: 2026-11-11
tests:
  - tests/Feature/Admin/Learning/KnowledgeItemDocumentPreviewTest.php
  - tests/Feature/Admin/Learning/KnowledgeItemsTest.php
  - tests/Feature/Admin/Learning/LearningDocumentsTest.php
  - tests/Feature/Admin/Learning/LearningLibraryTest.php
  - tests/Feature/Admin/Learning/LearningRoutesTest.php
  - tests/Feature/Admin/Learning/LearningSectionsTest.php
  - tests/Feature/Admin/Learning/LearningWritesTest.php
  - tests/Feature/Admin/Learning/ResolveLearningPathTest.php
  - tests/Feature/System/AdminNavigationCatalogTest.php
  - resources/js/Features/Admin/Knowledge/__tests__/ShowKnowledgeBase.component.test.ts
  - resources/js/Features/Admin/Knowledge/__tests__/WikiProse.component.test.ts
  - resources/js/Features/Admin/Knowledge/__tests__/wikiSearch.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningLibrary.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/KnowledgeItemForm.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningSectionsSheet.component.test.ts
---

# Wiki straipsniai

Wiki sudaro **straipsniai**. Straipsnis gali būti trumpa sąvoka (pvz., *Kvorumas*), institucija
(*Studijų kokybės vertinimo centras*, SKVC) ar dokumentas su paaiškinimu (*VU statutas*). Visi jie
vienodi: turi pavadinimą, trumpą santrauką, jei reikia – santrumpą, išsamesnį tekstą ir šaltinį.
Straipsniai vienas į kitą nurodo tekste, todėl iš vieno lengva pereiti prie susijusių.

<ChangelogNote date="2026-11-11" title="Wiki">

Sąvokos ir žinių bazės medžiaga sujungtos į straipsnius. Nuorodos tekste rodo trumpą paaiškinimą,
o susiję straipsniai surenkami automatiškai.

</ChangelogNote>

## Susitarimai

- **Santrauka – vienas ar du sakiniai.** Ji rodoma paieškoje, sąrašuose ir užvedus ant nuorodos,
  todėl turi būti suprantama be viso straipsnio.
- **Nuoroda vietoj pakartojimo.** Jei tekste minima sąvoka ar institucija, turinti savo straipsnį,
  ji susiejama nuoroda, o ne paaiškinama iš naujo.
- **Šaltiniai laikomi Archyve.** Naujas dokumentas ar pristatymas pirmiausia įkeliamas į SharePoint
  „Dokumentų sistemą“ (Archyvą), o straipsnis į jį nurodo. Vieši teisės aktai susiejami nuoroda.
- **Būtinų straipsnių nedaug.** Vienai auditorijai pakanka kelių būtinų straipsnių; visa kita –
  rekomenduojama, papildoma arba tiesiog lieka Wiki be auditorijos.

## Kaip tai veikia

### Straipsniai ir nuorodos {#nuorodos}

Straipsnio puslapyje rodomas pavadinimas su santrumpa, santrauka, tekstas, šaltinis (dokumentas – su
pirmojo puslapio peržiūra), **Susiję straipsniai**, **Apie ką šis straipsnis** ir įžvalgos – kitų
atstovų patirtis ir klausimai (žr. [Komentarai](/visak/komentarai#temos)).

Nuorodos į kitus straipsnius tekste pabrauktos taškais. Užvedus pelę arba pasiekus nuorodą
klaviatūra atsiveria kortelė su straipsnio pavadinimu, santrumpa ir santrauka; paspaudus atveriamas
straipsnis. Telefone pirmas palietimas atveria kortelę, antras – straipsnį. Jei straipsnis, į kurį
nurodoma, ištrintas ar paslėptas, nuoroda rodoma kaip paprastas tekstas. Tokios pačios nuorodos
veikia ir [Mano kelio](/mano/kelias) žingsnių aprašymuose.

**Susiję straipsniai** surenkami automatiškai: tai straipsniai, į kuriuos šis nurodo tekste, ir
straipsniai, kurie nurodo į šį. Atskirai jų susieti nereikia.

### Apie ką straipsnis {#apie}

Straipsnį galima susieti su institucija, institucijos tipu ar pareigybės tipu – pvz., straipsnį
*Studijų programos komitetas* su institucijos tipu „Studijų programos komitetas“. Tokie straipsniai
rodomi institucijos puslapio santraukoje, bloke **Wiki** (ir tiesiogiai su institucija susieti, ir
susieti su jos tipu).

„Apie ką“ nenulemia, kam straipsnis rodomas Mano kelyje – tam skirtos auditorijos.

### Tau skirti straipsniai

Straipsnis gali turėti vieną ar kelias auditorijas su svarba ir paaiškinimu „Tau tai reiškia“.
Auditorijos veikia taip pat kaip [Mano kelyje](/mano/kelias#auditorijos), tik straipsnis neturi
kadencijos etapo. Tau skirtą straipsnį galima pažymėti perskaitytu, o rekomenduojamą ar papildomą –
praleisti. Jei straipsnis pažymėtas „Peržiūrėti kiekvieną kadenciją“, kiekvienos kadencijos pradžioje
jį pažymi iš naujo; jei šaltinio dokumentas Archyve atnaujinamas jau perskaičius, straipsnis
pažymimas „Atnaujinta – peržiūrėk“. Archyve paslėptą dokumentą gali atidaryti visi, kurie mato
straipsnį, o viešame vusa.lt jis nerodomas.

Straipsnis be auditorijos lieka Wiki ir paieškoje, bet niekieno kelyje nerodomas.

### Pradžia {#pradzia}

Wiki pradžioje yra paieška, kuri ieško pavadinimuose, santrumpose ir santraukose (lietuviškos raidės
nebūtinos: „skvc“ ar „studiju“ randa tai, ko reikia). Įvedus užklausą vietoj pradžios blokų rodomi
rezultatai.

Be paieškos rodoma:

- **Tau svarbiausia** – iki penkių neperskaitytų būtinų ir rekomenduojamų straipsnių bei tavo
  pažanga. **Visi tau skirti straipsniai** atveria visą sąrašą **Pagal svarbą** arba **Pagal
  skyrius**.
- **Skyriai** – kiekvienas skyrius su aprašymu ir pirmaisiais straipsniais; **Visi** parodo likusius.
  Straipsniai be skyriaus rodomi prie **Kiti straipsniai**.
- **Neseniai atnaujinta** ir raidžių juosta, vedanti į rodyklę.

### Rodyklė A–Ž {#rodykle}

Visi straipsniai pagal abėcėlę, sugrupuoti pagal pirmąją raidę, su santrumpa ir santrauka. Raidžių
juosta lieka viršuje slenkant; paieška veikia taip pat kaip pradžioje.

Atvėrus straipsnį, **Rodyklė A–Ž** skirtuke rodomas jo pavadinimas. Rodyklė šalia pavadinimo
atveria meniu su nuoroda atgal į **Rodyklę A–Ž**.

### Biblioteka {#biblioteka}

Biblioteka skirta tiems, kas tvarko Wiki ar Mano kelią (žr. [Kas ką gali](#teises)): visi straipsniai
su paieška, greitieji filtrai **Sąvokos** (straipsniai be šaltinio), **Esminis** ir
**Rekomenduojamas**, kiti (auditorija, skyrius) – skiltyje **Filtrai**. Matomos žymos: dokumentas
nebegalioja, atnaujintas po peržiūros arba straipsnis neturi nei teksto, nei šaltinio. Čia pat
tvarkomi skyriai.

Mano kelio žingsnių biblioteka yra atskirai: **Organizacija → Kelio žingsniai**.

## Veiksmai

### Ieškoti ir skaityti

1. Atverk [Wiki](app:/mano/knowledge) darbo sričių pasirinkime.
2. Įrašyk sąvoką, santrumpą ar dokumento pavadinimą arba pasirink skyrių.
3. Straipsnyje užvesk pelę ant nuorodos (telefone – paliesk), kad pamatytum trumpą paaiškinimą.
4. Tau skirtą straipsnį pažymėk perskaitytu langeliu prie pavadinimo.

### Parašyti straipsnį {#prideti-straipsni}

1. **+ Sukurti** → **Naujas žinių bazės straipsnis**.
2. Įrašyk pavadinimą, santrumpą (jei yra) ir santrauką.
3. Tekste parašyk `[[` ir kelias pavadinimo raides – pasirink straipsnį, į kurį nori nurodyti.
   Tekste galima formatuoti, pridėti paveikslėlių ir YouTube vaizdo įrašų.
4. Jei straipsnis paaiškina dokumentą, pasirink šaltinį: dokumentą iš Archyvo arba nuorodą.
   Anglišką šaltinį nurodyk tik tada, kai jis skiriasi.
5. Dešinėje pasirink, kieno tai straipsnis (padalinio ar visos VU SA), skyrių ir eilės numerį.
6. Jei gali tvarkyti Mano kelią, skiltyje **Kam skirta** pridėk auditorijas ir, prireikus, įjunk
   **Peržiūrėti kiekvieną kadenciją**.
7. Išsaugok. Straipsnio puslapyje prie **Apie ką šis straipsnis** spausk **Tvarkyti**, jei
   straipsnis apie instituciją ar tipą.

### Tvarkyti skyrius

1. Bibliotekoje atverk **Skyriai**.
2. Spausk **Sukurti skyrių** arba **Redaguoti** prie esamo. Įrašyk pavadinimus ir aprašymus abiem
   kalbomis, eilės numerį ir padalinį, jei jis privalomas.
3. **Ištrinti** paprašys patvirtinimo. Skyriaus straipsniai liks prie **Kiti straipsniai**.

## Kas ką gali {#teises}

| Kas | Ką gali |
|---|---|
| Kiekvienas narys | Skaityti visą Wiki, žymėti sau skirtus straipsnius perskaitytais, rašyti įžvalgas |
| Studentų atstovų koordinatorius | Rašyti ir redaguoti savo padalinio straipsnius ir skyrius; straipsniams nustatyti tik savo padaliniu apribotas auditorijas |
| Centrinio biuro studentų atstovų koordinatorius | Rašyti ir redaguoti visus straipsnius, skyrius ir auditorijas |

Teisę rašyti Wiki galima suteikti ir kitoms atsakomybėms (pvz., komunikacijai) – tada jos gali
redaguoti straipsnius, bet ne jų auditorijas.

## Pranešimai ir automatizavimas

Ši skiltis pranešimų nesiunčia. Ar šaltinis atnaujintas, tikrinama atvėrus straipsnį ar Wiki.

## Techninė informacija

- Straipsnis – `KnowledgeItem` (`abbreviation`, `summary`, `tenant_id`; šaltiniai `KnowledgeItemSource`,
  skyriai `KnowledgeSection`). Atskiro sąvokų modelio nebėra.
- Nuorodos saugomos tekste kaip `<a data-knowledge-id="…">` (leidžia `HtmlSanitizerService`), o
  `SyncKnowledgeLinks` po išsaugojimo perrašo `knowledge_links` lentelę; susiję straipsniai –
  `outgoingLinks` ∪ `incomingLinks`. Kortelių duomenys (`linkedPages`) siunčiami kartu su puslapiu.
- „Apie ką“ – `knowledge_item_subjects` (leidžiami tipai: `institution`, `institution_type`,
  `duty_type`); institucijos puslapis juos gauna atidėtai (`knowledgePages`).
- Auditorijos (`learning_audiences`) ir pažanga (`learning_progress`) bendros su kelio žingsniais;
  `per_term` straipsnis įskaitomas, jei pažymėtas ne anksčiau kaip 90 dienų iki kadencijos pradžios.
- Teisės: straipsnio turinys – `knowledgeItems.*` (`.padalinys` – straipsnio padalinys, `.*` – visi);
  auditorijos – `learningSteps.*`, o Mano kelio tvarkytojai gali redaguoti ir straipsnius, kurių
  auditorijas tvarko. Skyriai – bet kuri iš šių teisių.
- Bandomasis turinys: `LearningPathPilotSeeder` (straipsniai atpažįstami pagal `key`, kartojant
  nieko nedubliuoja).
