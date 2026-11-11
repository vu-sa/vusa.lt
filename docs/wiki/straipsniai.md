---
title: Wiki įrašai
doc_status: reviewed
last_reviewed: 2026-11-11
tests:
  - tests/Browser/WikiCompletionTest.php
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
  - resources/js/Features/Admin/Knowledge/__tests__/WikiLinkPreview.component.test.ts
  - resources/js/Features/Admin/Knowledge/__tests__/wikiSearch.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningLibrary.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/KnowledgeItemForm.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningSectionsSheet.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/ShowKnowledgeItem.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningCompletionControl.component.test.ts
  - resources/js/Features/Admin/Knowledge/__tests__/WikiSubjectsSheet.component.test.ts
  - resources/js/Components/TipTap/__tests__/TiptapLinkButton.component.test.ts
---

# Wiki įrašai

Wiki sudaro **įrašai**. Įrašas gali būti trumpa sąvoka (pvz., *Kvorumas*), institucija
(*Studijų kokybės vertinimo centras*, SKVC) ar dokumentas su paaiškinimu (*VU statutas*). Visi jie
vienodi: turi pavadinimą, trumpą santrauką, jei reikia – santrumpą, išsamesnį tekstą ir šaltinį.
Įrašai vienas į kitą nurodo tekste, todėl iš vieno lengva pereiti prie susijusių.

<ChangelogNote date="2026-11-11" title="Wiki">

Sąvokos ir žinių bazės medžiaga sujungtos į įrašus. Nuorodos tekste rodo trumpą paaiškinimą,
o susiję įrašai surenkami automatiškai.

</ChangelogNote>

## Susitarimai

- **Santrauka – vienas sakinys** (iki 200 ženklų): kas tai yra. Ji rodoma paieškoje, sąrašuose ir
  užvedus ant nuorodos, todėl turi būti suprantama be viso įrašo. Visa kita – įrašo tekste.
- **Nuoroda vietoj pakartojimo.** Jei tekste minima sąvoka ar institucija, turinti savo įrašą,
  ji susiejama nuoroda, o ne paaiškinama iš naujo.
- **Šaltiniai laikomi Archyve.** Naujas dokumentas ar pristatymas pirmiausia įkeliamas į SharePoint
  „Dokumentų sistemą“ (Archyvą), o įrašas į jį nurodo. Vieši teisės aktai susiejami nuoroda.
- **Būtinų įrašų nedaug.** Vienai auditorijai pakanka kelių būtinų įrašų; visa kita –
  rekomenduojama, papildoma arba tiesiog lieka Wiki be auditorijos.

## Kaip tai veikia

### Įrašai ir nuorodos {#nuorodos}

Įrašo puslapyje rodomas pavadinimas su santrumpa, tekstas (pradedamas santrauka), šaltinis (dokumentas – su
pirmojo puslapio peržiūra), **Apie ką šis įrašas** ir įžvalgos – kitų
atstovų patirtis ir klausimai (žr. [Komentarai](/visak/komentarai#temos)).

Skyriaus žyma virš pavadinimo lygiuojama su juo; perskaitymo langelis rodomas atskirai kairėje.
Telefone šaltinio nuoroda rodoma po dokumento miniatiūra.
„Turinio peržiūros data nenurodyta“ reiškia, kad įrašas neturi užfiksuotos turinio peržiūros datos.
Ši data atnaujinama išsaugant įrašą redaktoriuje; tavo perskaitymo pažangą žymi langelis.

**Susiję įrašai** rodomi šoninėje juostoje, telefone – po įrašo tekstu. Sąraše yra
pavadinimai ir santrumpos be aprašymų; varnelė rodo tavo jau išmoktus įrašus.
Užvedus pelę arba pasiekus nuorodą klaviatūra rodoma įrašo peržiūra su santrauka;
telefone pirmas palietimas atveria peržiūrą, antras – įrašą.

Nuorodos į kitus įrašus tekste pabrauktos taškais. Užvedus pelę arba pasiekus nuorodą
klaviatūra atsiveria kortelė su įrašo pavadinimu, santrumpa ir santrauka; paspaudus atveriamas
įrašas. Telefone pirmas palietimas atveria kortelę, antras – įrašą. Jei įrašas, į kurį
nurodoma, ištrintas ar paslėptas, nuoroda rodoma kaip paprastas tekstas. Tokios pačios nuorodos
veikia ir [Mano kelio](/mano/kelias) žingsnių aprašymuose.

**Susiję įrašai** surenkami automatiškai: tai įrašai, į kuriuos šis nurodo tekste, ir
įrašai, kurie nurodo į šį. Atskirai jų susieti nereikia.

### Apie ką įrašas {#apie}

Įrašą galima susieti su institucija, institucijos tipu ar pareigybės tipu – pvz., įrašą
*Studijų programos komitetas* su institucijos tipu „Studijų programos komitetas“. Tokie įrašai
rodomi institucijos puslapio santraukoje, bloke **Wiki** (ir tiesiogiai su institucija susieti, ir
susieti su jos tipu).

„Apie ką“ nenulemia, kam įrašas rodomas Mano kelyje – tam skirtos auditorijos.

### Išmokta {#ismokta}

Kiekvieną įrašą galima pažymėti išmoktu langeliu prie pavadinimo – ir tą, kuris tau skirtas, ir
bet kurį kitą. Įrašo puslapyje pažangą rodo tik langelis, be papildomo teksto prie pavadinimo.
Pažymėti įrašai rodomi su varnele pradžioje ir rodyklėje. Ne tavo kelio
įrašai, kuriuos pažymėjai, surenkami sąraše **Visi tau skirti įrašai** → **Pagal svarbą**,
grupėje **Savarankiškai** ir įskaitomi į visos Wiki pažangą. Praleisti galima tik tau
skirtus įrašus.

### Tau skirti įrašai {#tau-skirti-straipsniai}

Antrajame Wiki meniu po **Wiki** yra atskiras puslapis **Tau skirti įrašai**.
Wiki žymi atversta knyga, tau skirtus įrašus – knyga su varnele, rodyklę – knyga su raide A.
Jame pasirink **Pagal svarbą** arba **Pagal skyrius**; pasirinkimas išlieka puslapio nuorodoje.
Į šį puslapį veda ir Wiki pradžios mygtukas **Visi tau skirti įrašai** bei Mano kelio Wiki blokas.

Wiki pradžioje viršuje rodomas tik visos Wiki pažangos matuoklis: kiek visų aktyvių įrašų
pažymėjai išmoktais. Praleisti įrašai į šį skaičių neįtraukiami.

Puslapyje **Tau skirti įrašai** matuoklis rodo tik tau skirtus įrašus: būtinų segmentas pažymėtas VU SA spalva, rekomenduojamų –
tamsiu kontūru, papildomų – punktyru. Užpildyta dalis rodo atliktą pažangą, neužpildyta lieka
permatoma; prie kiekvienos svarbos yra skaičius. Praleistas įrašas taip pat įskaitomas į tau skirtų
įrašų pažangą. Tas pats matuoklis naudojamas Mano kelio Wiki bloke, tik be svarbos paaiškinimų po juosta.

Įrašas gali turėti vieną ar kelias auditorijas su svarba ir paaiškinimu „Tau tai reiškia“.
Auditorijos veikia taip pat kaip [Mano kelyje](/mano/kelias#auditorijos), tik įrašas neturi
kadencijos etapo. Tau skirtą įrašą galima pažymėti perskaitytu, o rekomenduojamą ar papildomą –
praleisti. Pažymėjimas išlieka pasikeitus kadencijai. Jei šaltinio dokumentas Archyve atnaujinamas jau perskaičius, įrašas
pažymimas „Atnaujinta – peržiūrėk“. Archyve paslėptą dokumentą gali atidaryti visi, kurie mato
įrašą, o viešame vusa.lt jis nerodomas.

Įrašas be auditorijos lieka Wiki ir paieškoje, bet niekieno kelyje nerodomas.

### Pradžia {#pradzia}

Wiki pradžioje yra paieška, kuri ieško pavadinimuose, santrumpose ir santraukose (lietuviškos raidės
nebūtinos: „skvc“ ar „studiju“ randa tai, ko reikia). Įvedus užklausą vietoj pradžios blokų rodomi
rezultatai.

Be paieškos rodoma:

- **Tau svarbiausia** – iki penkių neperskaitytų būtinų ir rekomenduojamų įrašų bei tavo
  pažanga. **Visi tau skirti įrašai** atveria atskirą puslapį **Tau skirti įrašai**.
- **Skyriai** – kiekvienas skyrius su aprašymu ir pirmaisiais įrašais; **Visi** parodo likusius.
  Įrašai be skyriaus rodomi prie **Kiti įrašai**.
- **Neseniai atnaujinta** ir raidžių juosta, vedanti į rodyklę.

### Rodyklė A–Ž {#rodykle}

Visi įrašai pagal abėcėlę, sugrupuoti pagal pirmąją raidę, su santrumpa ir santrauka. Raidžių
juosta lieka viršuje slenkant; paieška veikia taip pat kaip pradžioje.
Paspaudus raidę, jos antraštė atsiduria žemiau abiejų navigacijos juostų ir lieka matoma.

Atvėrus įrašą, **Rodyklė A–Ž** skirtuke rodomas jo pavadinimas. Rodyklė šalia pavadinimo
atveria meniu su nuoroda atgal į **Rodyklę A–Ž**.

### Biblioteka {#biblioteka}

Biblioteka skirta tiems, kas tvarko Wiki ar Mano kelią (žr. [Kas ką gali](#teises)): visi įrašai
su paieška, greitieji filtrai **Sąvokos** (įrašai be šaltinio), **Esminis** ir
**Rekomenduojamas**, kiti (auditorija, skyrius) – skiltyje **Filtrai**. Matomos žymos: dokumentas
nebegalioja, atnaujintas po peržiūros arba įrašas neturi nei teksto, nei šaltinio. Čia pat
tvarkomi skyriai.

Eilutės veiksmas **Atidaryti** su rodykle atveria įrašą; **Redaguoti** yra eilutės **⋯** meniu.

Mano kelio žingsnių biblioteka yra atskirai: **Organizacija → Kelio žingsniai**.

## Veiksmai

### Ieškoti ir skaityti

1. Atverk [Wiki](app:/mano/knowledge) darbo sričių pasirinkime.
2. Įrašyk sąvoką, santrumpą ar dokumento pavadinimą arba pasirink skyrių.
3. Įraše užvesk pelę ant nuorodos (telefone – paliesk), kad pamatytum trumpą paaiškinimą.
4. Kai išmoksi, pažymėk langelį prie pavadinimo – tai galima bet kuriam įrašui.

### Parašyti įrašą {#prideti-straipsni}

1. **+ Sukurti** → **Naujas žinių bazės įrašas**.
2. Įrašyk pavadinimą, santrumpą (jei yra) ir vieno sakinio santrauką.
3. Tekste parašyk `[[` ir kelias pavadinimo raides – pasirink įrašą, į kurį nori nurodyti.
   Esamą nuorodą pakeisi ją pažymėjęs ir paspaudęs **Redaguoti nuorodą**: skirtuke **Wiki įrašas**
   gali pakeisti nuorodos tekstą ar įrašą, į kurį ji veda. Tekste galima formatuoti, pridėti
   paveikslėlių ir YouTube vaizdo įrašų.
4. Jei įrašas paaiškina dokumentą, pasirink šaltinį: dokumentą iš Archyvo arba nuorodą (įklijavus
   adresą į dokumento paiešką, jis tampa nuoroda). Šaltinis nebūtinas; jei jis yra tik viena kalba,
   rodomas abiem. Redaguojant galima perjungti šaltinio tipą ir grįžti: pasirinktas dokumentas ir
   įvesta nuoroda išlieka juodraštyje. Išsaugomas tik tuo metu pasirinktas šaltinis.
5. Dešinėje pasirink, kieno tai įrašas (padalinio ar visos VU SA), skyrių ir eilės numerį.
6. Jei gali tvarkyti Mano kelią, skiltyje **Kam skirta** pridėk auditorijas. Pasirinktai grupei
   įrašas bus rodomas puslapyje **Tau skirti įrašai** ir Mano kelio Wiki bloke.
7. Išsaugok. Įrašo puslapyje prie **Apie ką šis įrašas** spausk **Tvarkyti**, jei
   įrašas apie instituciją ar tipą: institucija pasirenkama paieškos lange, tipai – iš medžio.

### Tvarkyti skyrius

1. Bibliotekoje atverk **Skyriai**.
2. Spausk **Sukurti skyrių** arba pieštuką prie esamo. Įrašyk pavadinimą ir aprašymą lietuviškai,
   tada perjunk **EN** ir įrašyk angliškai; padalinį – jei jis privalomas. Naujas skyrius atsiduria
   sąrašo gale.
3. **Keisti tvarką** leidžia tempti skyrius už rankenėlės arba perkelti rodyklėmis; spausk
   **Išsaugoti tvarką**. Tvarką keisti gali tas, kas gali redaguoti visus sąrašo skyrius.
4. **Ištrinti** paprašys patvirtinimo. Skyriaus įrašai liks prie **Kiti įrašai**.

## Kas ką gali {#teises}

| Kas | Ką gali |
|---|---|
| Kiekvienas narys | Skaityti visą Wiki, žymėti bet kurį įrašą išmoktu, praleisti sau skirtus rekomenduojamus, rašyti įžvalgas |
| Studentų atstovų koordinatorius | Rašyti ir redaguoti savo padalinio įrašus ir skyrius; įrašams nustatyti tik savo padaliniu apribotas auditorijas |
| Centrinio biuro studentų atstovų koordinatorius | Rašyti ir redaguoti visus įrašus, skyrius ir auditorijas |

Teisę rašyti Wiki galima suteikti ir kitoms atsakomybėms (pvz., komunikacijai) – tada jos gali
redaguoti įrašus, bet ne jų auditorijas.

## Pranešimai ir automatizavimas

Ši skiltis pranešimų nesiunčia. Ar šaltinis atnaujintas, tikrinama atvėrus įrašą ar Wiki.

## Techninė informacija

- Įrašas – `KnowledgeItem` (`abbreviation`, `summary`, `tenant_id`; šaltiniai `KnowledgeItemSource`,
  skyriai `KnowledgeSection`). Atskiro sąvokų modelio nebėra.
- Nuorodos saugomos tekste kaip `<a data-knowledge-id="…">` (leidžia `HtmlSanitizerService`), o
  `SyncKnowledgeLinks` po išsaugojimo perrašo `knowledge_links` lentelę; susiję įrašai –
  `outgoingLinks` ∪ `incomingLinks`. Kortelių duomenys (`linkedPages`) siunčiami kartu su puslapiu.
- „Apie ką“ – `knowledge_item_subjects` (leidžiami tipai: `institution`, `institution_type`,
  `duty_type`); institucijos puslapis juos gauna atidėtai (`knowledgePages`).
- Auditorijos (`learning_audiences`) ir pažanga (`learning_progress`) bendros su kelio žingsniais; pažymėti
  galima bet kurį aktyvų įrašą, o ne kelio pažymėjimai pradžioje grąžinami kaip `selfLearned`;
  pažymėjimas išlieka pasikeitus kadencijai. Atnaujinus šaltinio dokumentą, įrašą reikia peržiūrėti iš naujo.
- Teisės: įrašo turinys – `knowledgeItems.*` (`.padalinys` – įrašo padalinys, `.*` – visi);
  auditorijos – `learningSteps.*`, o Mano kelio tvarkytojai gali redaguoti ir įrašus, kurių
  auditorijas tvarko. Skyriai – bet kuri iš šių teisių.
- Bandomasis turinys: `LearningPathPilotSeeder` (įrašai atpažįstami pagal `key`, kartojant
  nieko nedubliuoja).
