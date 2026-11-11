---
title: Žinių bazė
doc_status: reviewed
last_reviewed: 2026-11-11
tests:
  - tests/Feature/Admin/Learning/KnowledgeItemDocumentPreviewTest.php
  - tests/Feature/Admin/Learning/KnowledgeItemsTest.php
  - tests/Feature/Admin/Learning/LearningDocumentsTest.php
  - tests/Feature/Admin/Learning/LearningSectionsTest.php
  - tests/Feature/Admin/Learning/ResolveLearningPathTest.php
  - tests/Feature/System/AdminNavigationCatalogTest.php
  - resources/js/Features/Admin/Learning/__tests__/LearningDocuments.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningLibrary.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/KnowledgeItemForm.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/ShowGlossaryTerm.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningTerms.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningSectionsSheet.component.test.ts
  - resources/js/Features/Admin/Learning/__tests__/LearningStepGlossaryEditor.component.test.ts
---

# Žinių bazė

**Žinių bazė** – tai, ką atstovui verta žinoti: dokumentai, pristatymai ir nuorodos, sugrupuoti į
skyrius, ir sąvokų žodynėlis. Kaip ir [Mano kelias](/mano/kelias), ji sudaroma pagal tavo pareigas:
matai tik tau skirtą medžiagą ir tai, kiek jos jau perskaitei.

<ChangelogNote date="2026-11-11" title="Žinių bazė">

Žinių bazė pakeitė pareigybių ir institucijų tipų failus: dokumentai, pristatymai ir nuorodos su
paaiškinimu, kam jų reikia, o kiekviena sąvoka turi savo puslapį.

</ChangelogNote>

## Susitarimai

- **Šaltiniai laikomi Archyve.** Naujas dokumentas ar pristatymas pirmiausia įkeliamas į SharePoint
  „Dokumentų sistemą“ (Archyvą), o medžiaga į jį nurodo. Vieši teisės aktai susiejami nuoroda.
- **Aprašymas paaiškina, ką iš medžiagos sužinosi.** Svarbiau už patį failą – kam jis reikalingas ir
  į ką atkreipti dėmesį.
- **Būtinos medžiagos nedaug.** Vienai auditorijai pakanka kelių būtinų dokumentų; visa kita –
  rekomenduojama arba papildoma.
- **Sąvoka paaiškinama medžiagoje.** Sąvoka susiejama su medžiaga, kurioje ji paaiškinta, o ne su
  kelio veiksmais.

## Kaip tai veikia

### Medžiaga ir skyriai

Kiekviena medžiaga turi pavadinimą, šaltinį (Archyvo dokumentą arba nuorodą), aprašymą ir vieną ar
kelias auditorijas su svarba ir paaiškinimu „Tau tai reiškia“. Auditorijos veikia taip pat kaip
[Mano kelyje](/mano/kelias#auditorijos), tik medžiaga neturi kadencijos etapo – ji rikiuojama pagal
svarbą ir skyrius.

Žinių bazės sąraše **Pagal svarbą** pirmiausia rodoma neperskaityta medžiaga, suskirstyta į
**Būtina**, **Rekomenduojama** ir **Papildoma**. **Pagal skyrius** rodo skyrių aprašymus ir jų
medžiagą; be skyriaus likusi medžiaga yra gale, prie **Kita**. Po pavadinimu matyti, kiek būtinos
medžiagos jau perskaityta. Mano kelio viršuje esantis Žinių bazės blokas siūlo vieną kitą skaitinį.

Medžiagą galima pažymėti perskaityta, o rekomenduojamą ar papildomą – praleisti. Jei medžiaga
pažymėta „Peržiūrėti kiekvieną kadenciją“, kiekvienos kadencijos pradžioje ją pažymi iš naujo. Jei
šaltinio dokumentas Archyve atnaujinamas jau perskaičius, medžiaga pažymima „Atnaujinta – peržiūrėk“.
Archyve paslėptą dokumentą gali atidaryti visi, kurie mato medžiagą, o viešame vusa.lt jis nerodomas;
sąraše jis pažymėtas „neviešas“.

Medžiagos puslapyje matyti svarba, skyrius, šaltinis (dokumentas – su pirmojo puslapio peržiūra),
susijusios sąvokos ir įžvalgos – kitų atstovų patirtis ir klausimai (žr. [Komentarai](/visak/komentarai#temos)).

### Sąvokos

**Sąvokos** – žodynėlis (studijų programos komitetas, akreditacija, kvorumas…). Sąvoka gali turėti
santrumpą (pvz., SPK); paieška randa ir pagal ją. Sąvokų sąrašas surikiuotas pagal tai, kas
svarbiausia tau: pirmiausia sąvokos iš tavo būtinos medžiagos, tada iš rekomenduojamos ir papildomos,
galiausiai – kitos. Atskiros sąvokų eilės nėra – ją nulemia medžiaga.

Sąvokos puslapyje rodomas apibrėžimas ir **Kur ši sąvoka paaiškinta**: tavo medžiaga su svarba ir
perskaitymo žyma, o žemiau – kita žinių bazės medžiaga. Medžiagos puslapyje sąvokos žyma atveria
trumpą apibrėžimą ir nuorodą į sąvokos puslapį.

### Skiltys {#skirtukai}

Pradžios srityje **Žinių bazė** yra trečias skyrius, iškart po **Mano kelias**. Rodyklė šalia jo
pavadinimo atveria:

- **Žinių bazė** – tavo medžiaga ir pažanga.
- **Sąvokos** – žodynėlis.
- **Biblioteka** – tik tiems, kas gali tvarkyti Mano kelią (žr. [Kas ką gali](#teises)). Joje visa
  medžiaga su paieška; greiti filtrai – **Esminis** ir **Rekomenduojamas**, kiti (auditorija, skyrius)
  – skiltyje **Filtrai**. Matomos žymos: dokumentas nebegalioja, atnaujintas po peržiūros arba nėra
  šaltinio.

Atvėrus medžiagą ar sąvoką skirtuko pavadinimas tampa jos pavadinimu. Telefone šie puslapiai
pasiekiami skyriaus pasirinkime ir per **Meniu**.

## Veiksmai

### Skaityti žinių bazę

1. Atverk [Žinių bazę](app:/mano/knowledge).
2. Pasirink **Pagal svarbą** arba **Pagal skyrius**.
3. **Skaityti** atveria šaltinį naujame skirtuke; pavadinimas atveria medžiagos puslapį su įžvalgomis.
4. Pažymėk langelį kairėje. Rekomenduojamą ar papildomą medžiagą gali **Praleisti** per **⋯** meniu.

### Pridėti medžiagą

1. **+ Sukurti** → **Nauja žinių bazės medžiaga** (arba Bibliotekoje **Nauja medžiaga**).
2. Įrašyk pavadinimą ir pasirink šaltinį: dokumentą iš Archyvo arba nuorodą. Anglišką šaltinį nurodyk
   tik tada, kai jis skiriasi.
3. Aprašyme paaiškink, ką atstovas sužinos. Galima formatuoti tekstą, pridėti paveikslėlių ir YouTube
   vaizdo įrašų.
4. Skiltyje **Kam skirta** spausk **Pridėti auditoriją**: pasirink auditorijos tipą ir tikslą,
   svarbą ir, jei reikia, padalinį bei paaiškinimus. Jei gali tvarkyti tik savo padalinio medžiagą,
   padalinys privalomas.
5. Dešinėje pasirink skyrių ir eilės numerį skyriuje, prireikus įjunk **Peržiūrėti kiekvieną
   kadenciją**.
6. Išsaugok. Medžiagos puslapyje per **⋯** → **Tvarkyti sąvokas** susiek sąvokas.

### Tvarkyti skyrius

1. Bibliotekoje atverk **Skyriai**.
2. Spausk **Sukurti skyrių** arba **Redaguoti** prie esamo. Įrašyk pavadinimus ir aprašymus abiem
   kalbomis, eilės numerį ir padalinį, jei jis privalomas.
3. **Ištrinti** paprašys patvirtinimo. Skyriaus medžiaga liks sąraše **Kita**.

### Tvarkyti sąvokas

1. Atverk [Sąvokas](app:/mano/knowledge/glossary) ir spausk **Nauja sąvoka**, arba sąvokos puslapyje –
   **⋯** → **Redaguoti**.
2. Įrašyk sąvoką, santrumpą (jei yra) ir apibrėžimą.
3. Sąvoką ištrinti galima jos redagavimo lange; medžiaga lieka.

## Kas ką gali {#teises}

| Kas | Ką gali |
|---|---|
| Kiekvienas narys | Skaityti savo žinių bazę ir sąvokas, žymėti perskaitytą medžiagą, rašyti įžvalgas |
| Studentų atstovų koordinatorius | Atverti Biblioteką, kurti ir redaguoti medžiagą, kurios visos auditorijos apribotos savo padaliniu, ir savo padalinio skyrius |
| Centrinio biuro studentų atstovų koordinatorius | Kurti ir redaguoti visą medžiagą, skyrius ir sąvokas |

## Pranešimai ir automatizavimas

Ši skiltis pranešimų nesiunčia. Ar šaltinis atnaujintas, tikrinama atvėrus žinių bazę.

## Techninė informacija

- Medžiaga – `KnowledgeItem` (šaltiniai `KnowledgeItemSource`, skyriai `KnowledgeSection`). Auditorijos
  (`learning_audiences`) ir pažanga (`learning_progress`) bendros su kelio žingsniais, todėl
  `ResolveLearningPath` sudaro abu vienu kartu; medžiagai etapas nesaugomas.
- Pateikimas – `KnowledgeBasePresenter`; sąvokų tvarka ir sąvokos puslapis – `LearningAdminPresenter`
  (geriausia žiūrinčiojo svarba → skyriaus eilė → medžiagos eilė → pavadinimas). Paslėpta medžiaga
  sąvokos puslapyje nerodoma.
- `per_term` medžiaga įskaitoma, jei pažymėta ne anksčiau kaip 90 dienų iki kadencijos pradžios.
  `updated` – kai `documents.sharepoint_modified_at` vėlesnis už pažymėjimą.
- Teisės: `KnowledgeItemPolicy` naudoja tas pačias `learningSteps.*` teises kaip kelio žingsniai –
  `.padalinys` tik savo padaliniu apribotoms auditorijoms, `.*` visoms; sąvokos – `learningSteps.update.*`.
- Bandomasis turinys: `LearningPathPilotSeeder` (medžiaga atpažįstama pagal `key`, kartojant
  nieko nedubliuoja).
