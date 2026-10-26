---
doc_status: reviewed
title: Failai
area: files
models: [File]
last_reviewed: 2026-10-19
tests:
  - tests/Browser/ContentEditorImageSaveTest.php
  - resources/js/Components/ui/upload/__tests__/ImageUploadMedia.component.test.ts
  - tests/Feature/Jobs/Media/UpgradeImageMediaTest.php
  - tests/Feature/Public/ResizedImageControllerTest.php
  - resources/js/Components/Brand/__tests__/MediaImage.component.test.ts
  - tests/Feature/Admin/Resources/FilesControllerTest.php
  - tests/Feature/Api/Admin/FileApiControllerTest.php
  - tests/Feature/Services/FileStorageServiceTest.php
  - tests/Feature/Services/FileUploadWriterTest.php
  - tests/Feature/Services/FileUsageScannerTest.php
  - tests/Unit/Services/FileReferenceMatcherTest.php
  - tests/Feature/Api/Admin/PendingUploadApiControllerTest.php
  - tests/Feature/Actions/Media/SyncImageMediaTest.php
  - tests/Feature/Console/MediaLegacyFilesCommandTest.php
  - tests/Feature/Console/MigrateImagesCommandTest.php
  - tests/Feature/Migrations/DropLegacyImageColumnsTest.php
  - tests/Feature/Services/Media/LegacyImageBackfillTest.php
  - resources/js/Features/Admin/FileManager/__tests__/FilePropertiesDrawer.component.test.ts
  - resources/js/Features/Admin/FileManager/__tests__/FileManagerUpload.component.test.ts
  - resources/js/Features/Admin/FileManager/__tests__/FileManagerView.component.test.ts
  - resources/js/Features/Admin/FileManager/__tests__/FolderStrip.component.test.ts
---

# Failai

Failų skiltyje tvarkomi svetainės dokumentai, nuotraukos, skaidrės ir kiti lankytojams ar turinio rengimui reikalingi failai. Į įkeltus dokumentus galima sukurti nuorodas tekste, o nuotraukas – tiesiogiai įterpti į puslapių bei naujienų blokus.

Skiltis pasiekiama adresu `/mano/files`. Failų tvarkyklę taip pat galima atverti teksto redaktoriuje.

## Rekomendacijos {#rekomendacijos}

::: tip
- **Aiškūs pavadinimai:** rekomenduojama rinktis failo turinį nusakantį pavadinimą, pvz., `ataskaita-2026.pdf`. Geriau nenaudoti tokių pavadinimų kaip `FINAL-naujas-2.pdf`, iš kurių neaišku, kuri versija galutinė.
- **Tinkamas formatas:** skaityti skirtiems dokumentams patogu naudoti PDF. Jei kolegos turės dokumentą pildyti ar redaguoti, verta pateikti ir redaguojamą failą.
- **Nuotraukų dydis:** prieš įkeliant rekomenduojama sumažinti per dideles nuotraukas, išlaikant pakankamą kokybę. Taip puslapis greičiau įsikrauna ir naudojant mobilųjį ryšį.
- **Aplankų tvarka:** failus verta grupuoti pagal metus ar projektus, kad juos būtų lengva rasti.
:::

## Kaip tai veikia

### Padalinių katalogų atskyrimas

Kad skirtingų padalinių failai nesusimaišytų ir nebūtų atsitiktinai perrašyti, kiekvienam padaliniui skiriamas atskiras katalogas:

- **Padalinio katalogas:** kiekvienas padalinys turi savo numatytąjį katalogą `padaliniai/{padalinys}` (pvz., `padaliniai/vusapadalinys` arba `padaliniai/vusa-mif`).
- Padalinio koordinatorius, atvėręs failų naršyklę, iškart mato savo padalinio aplanką ir kuria jame poaplankius (pvz., `dokumentai`, `nuotraukos`, `archyvas`).
- **Formų nuotraukos** (naujienų, puslapių, banerių, institucijų, renginių, narių ir pareigybių) failų tvarkyklėje nebesaugomos – jos priklauso pačiam įrašui. Žr. [Formų nuotraukos](#formu-nuotraukos).
- **Saugūs failų keliai:** įkėlimai ir aplankų veiksmai negali išeiti už leistino aplanko ribų. Keliai, bandantys pasiekti aukštesnį aplanką, atmetami.

### Formų nuotraukos {#formu-nuotraukos}

<ChangelogNote date="2026-10-17" title="Nuotraukos priklauso įrašui">

Formose įkeltos nuotraukos saugomos prie įrašo, o svetainė kiekvienam ekranui parenka tinkamo dydžio versiją.

</ChangelogNote>

- Pasirinkus failą iš karto rodoma jo peržiūra. Kol rodoma **Įkeliama…**, nuotrauka dar siunčiama ir paruošiama; baigus rodomas patvirtinimas. Nepavykus įkelti, klaida lieka po nuotrauka ir galima spausti **Bandyk dar kartą** arba pasirinkti kitą failą. Nepavykęs pakeitimas anksčiau išsaugotos nuotraukos nepanaikina.
- Nuotrauka formoje įkeliama iš karto, bet įrašui priskiriama tik paspaudus **Išsaugoti**. Neišsaugotos nuotraukos po mėnesio ištrinamos. Per valandą vienas žmogus gali įkelti iki 50 nuotraukų.
- Priskirti nuotrauką gali tas, kas gali redaguoti įrašą: naujienos nuotrauką – naujienos redaktorius, nario nuotrauką – pats narys arba narius tvarkantis koordinatorius. Kito žmogaus įkeltos, dar neišsaugotos nuotraukos panaudoti negalima.
- Įkelta nuotrauka sumažinama iki 3840 px ilgesniosios kraštinės ir išsaugoma WebP formatu. Papildomai sukuriamos 400, 900, 1600 ir 2400 px pločio versijos – svetainė jas parenka pagal ekraną.
- Fokuso taškas, nuotraukos autorius ir aprašas saugomi kartu su nuotrauka, todėl pakeitus nuotrauką jie nepasimeta kitame įraše.
- Ta pati nuotrauka gali būti naudojama keliuose įrašuose tik kaip kopija – pvz., kuriant naujienos vertimą jos nuotrauka nukopijuojama.
- Senosios nuotraukos iš bendrų aplankų (`news`, `contacts`, `banners`, `institutions`) po atnaujinimo palaipsniui nukopijuojamos prie įrašų; kol tai vyksta, rodoma senoji nuotrauka. Nuotraukos iš kitų svetainių ir nebeegzistuojantys failai lieka kaip buvę – jų fokuso tašką ir autorių vis tiek galima keisti. Pačius senus failus galima tvarkyti tik po to, kai patikrinama, kad jų niekas nebenaudoja.

### Nuotraukų dydžiai {#nuotrauku-dydziai}

<ChangelogNote date="2026-10-17" title="Ryškesnės nuotraukos">

Svetainė kiekvienai vietai parenka pakankamo dydžio nuotraukos versiją, todėl nuotraukos ryškios ir didelės raiškos ekranuose.

</ChangelogNote>

- Ir formų, ir failų tvarkyklėje įkeltos nuotraukos saugomos iki 3840 px ilgesniosios kraštinės. Naršyklė pagal ekraną ir vietą puslapyje parsisiunčia mažesnę versiją, todėl mažoms kortelėms, meniu ir avatarams siunčiami nedideli failai.
- Naršyklei siūlomos versijos neviršija originalo pločio. Jei mažesnės versijos nepakanka, siunčiamas originalas be papildomo kokybės praradimo.
- Logotipai, ekrano nuotraukos pagalbos užklausose ir permatomi paveikslėliai saugomi be kokybės nuostolių, kad raidės liktų aiškios.
- Anksčiau įkeltų, jau sumažintų nuotraukų kokybės atkurti negalima. Kur nuotrauka atrodo neryški, reikia įkelti didesnį originalą.

### Įkėlimų apdorojimas {#ikelimas}

<ChangelogNote date="2026-10-02" title="Patikimesnis failų įkėlimas">

Vieno pavadinimo failai neperrašo vienas kito, o neišsaugotas failas nepažymimas kaip sėkmingai įkeltas.

</ChangelogNote>

- Vienas failas gali būti iki **50 MB**. JPEG, PNG ir WebP paveikslėliai sumažinami iki 3840 px ilgesniosios kraštinės ir saugomi WebP formatu. GIF animacijos, SVG ir kiti failai grupinio įkėlimo metu nekeičiami.
- Jei pavadinimas jau užimtas, naujam failui pridedama laiko žyma, o prireikus – ir skaitiklis. Ankstesnis failas išlieka, net jei keli įkėlimai vyksta vienu metu.
- Jei dalies failų išsaugoti nepavyksta, sėkmingai įkelti failai išlieka, o pranešime nurodomi nepavykę failai. Jei aplanką tuo metu naudoja kitas įkėlimas, galima bandyti dar kartą.
- Prieiga peržiūrėti aplanką savaime nesuteikia teisės į jį įkelti failų. Įkėlimui tikrinamos būtent to aplanko kūrimo teisės.

## Veiksmai

### Failų naršyklė ir paieška {#narsykle}

<ChangelogNote date="2026-10-18" title="Patogesnė failų tvarkyklė">

Aplankų medis lieka matomas slenkant, failų tipų filtras rodo plėtinius, o lentelėje matomos miniatiūros.

</ChangelogNote>

Failų tvarkyklėje (`/mano/files`) patogu naršyti tiek kompiuteryje, tiek telefone:

- **Aplankai:** kairėje rodomas aplankų medis – šaknis, visi aukštesni aplankai ir atverto aplanko poaplankiai. Jis lieka matomas slenkant. Tie patys poaplankiai rodomi ir virš failų, su elementų skaičiumi; ilgas sąrašas suskleidžiamas.
- **Rodiniai:** **kortelės** patogios nuotraukoms, **lentelė** – dokumentams su ilgais pavadinimais. Abiejuose rodiniuose matomos nuotraukų miniatiūros.
- **Failo tipas:** filtras rodo kiekvieno tipo plėtinius (pvz., *JPG, PNG, WEBP*) ir kiek tokių failų yra aplanke.
- **Rikiavimas:** pagal pavadinimą, įkėlimo datą ar dydį, abiem kryptimis.
- **Paieška:** ieškoma dabartiniame aplanke; **Visuose aplankuose** ieško ir poaplankiuose. Klavišas `/` perkelia į paieškos lauką.
- **Rodoma po 50 failų**, daugiau – paspaudus **Rodyti daugiau**.
- **Nuoroda į vaizdą:** adrese išsaugomas aplankas, paieška, tipas, rikiavimas, rodinys ir atvertas failas, todėl nuorodą galima nusiųsti kolegai, o mygtukas **Atgal** grąžina į tą patį vaizdą.
- **Pažymėti žvaigždute** failai saugomi tavo naršyklėje ir rodomi skiltyje **Pažymėti žvaigždute**, net jei jie skirtinguose aplankuose.

### Failų įkėlimas ir aplankų kūrimas

1. **Įkelti failus:** spausk **Įkelti failus** arba nutempk failus į failų sąrašą. Nutempus failus ant aplanko, jie įkeliami į tą aplanką.
2. **Sukurti aplanką:** spausk **Pridėti aplanką**, įrašyk pavadinimą ir patvirtink. Sukurtas aplankas iškart atsiras sąraše.

### Failų žymėjimas ir grupiniai veiksmai {#zymejimas}

- Failą pažymėti galima jo varnele, o kelis iš eilės – laikant **Shift**. **Ctrl** (**⌘**) + paspaudimas prideda failą prie pažymėtų, **Ctrl** + **A** pažymi visus rodomus.
- Kai kas nors pažymėta, apačioje rodoma juosta: **Žymėti žvaigždute**, **Atsisiųsti**, **Perkelti** ir **Ištrinti**.

### Failo informacija, peržiūra ir nuoroda {#failo-informacija}

Paspaudus failą atsidaro **informacijos skydelis** (telefone – iš apačios):

- **Peržiūrėti:** nuotraukos rodomos per visą ekraną didelės raiškos versija, PDF, vaizdo ir garso įrašai atidaromi naršyklėje. Rodyklėmis (ir klaviatūros ← →) galima pereiti prie kito aplanko failo.
- **Kopijuoti nuorodą:** nukopijuoja viešą nuorodą į iškarpinę.
- **Atsisiųsti:** atsisiunčia originalų failą.
- **Optimizuoti:** sumažina didelio JPG ar PNG paveikslėlio failo dydį.
- Tie patys veiksmai pasiekiami ir per failo meniu **⋯**.

### Pervadinimas ir perkėlimas {#pervadinimas}

<ChangelogNote date="2026-10-18" title="Failų pervadinimas ir perkėlimas">

Failus ir aplankus galima pervadinti, failus – perkelti į kitą aplanką, o tuščius aplankus – ištrinti.

</ChangelogNote>

- **Pervadinti** galima failą arba aplanką. Failo plėtinys nesikeičia – jis pridedamas automatiškai.
- **Perkelti** galima vieną ar kelis failus: pasirenkamas aplankas ir spaudžiama **Perkelti čia**. Jei paskirties aplanke jau yra toks pat pavadinimas, tas failas praleidžiamas, o kiti perkeliami.
- Pervadinus ar perkėlus failą, pasikeičia jo nuoroda. Puslapiai ir naujienos, kuriose jis įdėtas, jo neberodys, todėl pervadinant failą iš karto parodoma, kur jis naudojamas. Nuorodos turinyje automatiškai neatnaujinamos.

### Naudojimo patikra ir saugus trynimas {#naudojimo-patikra}

Prieš trinant failą, sistema leidžia apsisaugoti nuo puslapių sugadinimo:

<ChangelogNote date="2026-10-02" title="Patikimesnė naudojimo patikra">

Patikra randa failą visuose turinio blokuose, nuotraukų laukuose ir nuorodose, net kai jo pavadinime yra tarpų ar lietuviškų raidžių, ir parodo, kur jis naudojamas.

</ChangelogNote>

- **Naudojimo patikra:** pasirinkęs failą, informacijos skydelyje spausk **Tikrinti naudojimą**. Sistema patikrina visus puslapių, naujienų ir padalinių pradinių puslapių turinio blokus, nuotraukų laukus (naujienų, puslapių, banerių, institucijų, narių), aprašymus ir nuorodas (renginių, pareigybių, formų, problemų, žymų, navigacijos, greitųjų nuorodų) bei dar neišsaugotus juodraščius. Įrašai šiukšlinėje taip pat skaičiuojami – juos atkūrus, failas vėl būtų reikalingas.
- **Rezultatas:** jei failas naudojamas, matai sąrašą, kur jis panaudotas, ir gali atsidaryti tą įrašą. Patikra visada atliekama iš naujo, todėl rodo ir ką tik įterptas nuorodas.
- **Kas nelaikoma naudojimu:** to paties pavadinimo failas kitame aplanke ir nuoroda į kitą svetainę (ne vusa.lt) su tokiu pačiu keliu.
- **Trynimas:** pasirink **Ištrinti**. Trynimo lange pirmiausia spausk **Tikrinti naudojimą** – mygtukas **Ištrinti** įsijungia tik po patikros. Jei failas kur nors naudojamas, matysi, kur – ištrinti vis tiek galima, bet tos nuorodos nustos veikti.
- **Kelių failų trynimas:** pažymėk kelis failus ir apačios juostoje spausk **Ištrinti**. Patikra vyksta iš karto visiems pažymėtiems failams; naudojami failai išvardijami su vietomis, kur jie naudojami.
- **Aplanko trynimas:** aplanko meniu **⋯** pasirink **Ištrinti aplanką**. Ištrinti galima tik tuščią aplanką – jei jame dar yra failų ar poaplankių, meniu parodo, kiek jų liko.

## Kas ką gali {#teises}

Jei nėra prieigos prie failų tvarkyklės, tiesioginė nuoroda parodo prieigos klaidą.
Turint prieigą prie savo padalinio aplanko, bendrojo aplanko nuoroda nukreipia į pasiekiamą aplanką.
Vien teisė peržiūrėti failus nesuteikia teisės kurti aplankų, optimizuoti paveikslėlių, pervadinti, perkelti ar trinti. Veiksmai, kurių atliktame aplanke negali, nerodomi.

<ChangelogNote date="2026-10-02" title="Grupinio trynimo teisės tikrinamos iš anksto">

Jei bent vieno pasirinkto failo trinti negalima dėl teisių, netrinamas nė vienas pasirinktas failas.
Sąsajoje parodoma prieigos klaida; pasirinkimą galima pakeisti ir bandyti dar kartą.

</ChangelogNote>

| Veiksmas | Padalinio koordinatorius | Centrinio biuro komunikacijos koordinatorius |
|---|---|---|
| Matyti savo padalinio aplanką | ✓ | ✓ |
| Matyti visus organizacijos aplankus | – | ✓ |
| Įkelti failus į savo aplanką | ✓ | ✓ |
| Kurti aplankus savo kataloge | ✓ | ✓ |
| Trinti failus ir tuščius aplankus savo kataloge | ✓ | ✓ |
| Pervadinti failus ir aplankus savo kataloge | ✓ | ✓ |
| Perkelti failus tarp savo aplankų | ✓ | ✓ |
| Pervadinti padalinio aplanką | – | ✓ |
| Įkelti nuotrauką formoje | kas gali redaguoti įrašą | kas gali redaguoti įrašą |

## Pranešimai ir automatizavimas {#pranesimai}

- El. laiškai ar pranešimai tvarkant failus nesiunčiami.
- **Saugi failų patikra:** `FileUsageScanner` padeda išvengti situacijų, kai ištrynus nuotrauką ar dokumentą viešoje svetainėje lieka neveikianti nuoroda ar tuščias paveikslėlio rėmelis.

## Techninė informacija {#technine-informacija}

Šis skyrius skirtas administratoriams ir sistemos priežiūrai.

### Teisės

- Tiesioginės užklausos be reikiamų teisių gauna HTTP 403. Inertia užklausos grąžinamos atgal su `error` pranešimu (302, trynimo veiksmams – 303).

- Prieiga prie failų sistemos kontroliuojama per `files` leidimų grupę:
  - Padalinio teisės: `files.create.padalinys`, `files.read.padalinys`, `files.update.padalinys`, `files.delete.padalinys`.
  - Centrinio biuro teisės: `files.create.*`, `files.read.*`, `files.update.*`, `files.delete.*`.
- Modelis `File` nėra Eloquent duomenų bazės lentelė; tai virtualus modelis, skirtas leidimų generavimui ir atpažinimui.

### Kaip tai įgyvendinta

- Administravimo valdikliai: `App\Http\Controllers\Admin\FilesController` ir `FileOrganizationController` (pervadinimas `files.rename` – `updateInDirectory` teisė tėviniame aplanke; perkėlimas `files.move` – `updateInDirectory` kiekviename šaltinio aplanke ir `createInDirectory` paskirties aplanke, tikrinama prieš perkeliant). Failų sąrašo puslapis gauna `abilities` – ką veikėjas gali atvertame aplanke.
- API valdiklis: `App\Http\Controllers\Api\Admin\FileApiController` (aptarnauja asinchroninę failų naršyklę, paiešką ir miniatiūrų teikimą).
- Failų saugojimo šaknis: `storage/app/public/files/`.
- Bendri keliai, failų sąrašai ir grupiniai įkėlimai: `App\Services\FileStorageService`. `normalizeFilePath()` atmeta `..` segmentus ir neleidžia failų tvarkyklės keliui išeiti už `public/files` šaknies.
- Įkėlimo paskirtis ir kūrimo teisės: `StoreFilesRequest::uploadDirectory()`. Teksto redaktoriaus turinio aplankas nustatomas pagal kūrimo teisių apimtį.
- Formų nuotraukos: Spatie Media Library kolekcijos per `App\Models\Traits\HasImageMedia` (originalas iki 3840 px; konversijos `thumb` 400, `medium` 900, `large` 1600, `wide` 2400 px, WebP; be nuostolių – `ImageConversions::isArtwork()`). Laikinai įkelta nuotrauka – `PendingUpload` (`POST /api/v1/admin/pending-uploads`), įrašui priskiriama per `App\Actions\Media\SyncImageMedia`, išvaloma `model:prune` po 32 dienų.
- Nuotraukų perkėlimas: po diegimo rankiniu būdu paleidžiama `php artisan media:migrate-images` – pirmiausia staging aplinkoje, paskui produkcijoje. Komanda esamas formų nuotraukas pertvarko pagal dabartinius dydžius (didesnį originalą, jei jis išlikęs, iš naujo sumažina iki 3840 px ir sukuria visas versijas), o senąsias nuotraukas iš bendrų aplankų nukopijuoja prie įrašų. Darbai vykdomi `long-running` eilėje, o komanda rodo eigą, kol jie baigiasi (`--detach` – grįžti iškart).
- Parinktys: `--status` rodo, kas liko; `--limit=N` apdoroja daugiausia N naujausių kiekviename žingsnyje (pvz., bandymui staging aplinkoje); `--target=news.image` – tik vieną seną stulpelį; `--sync` vykdo iškart, be eilės; `--cleanup` ištrina failus, kuriuos pakeitė baigti atnaujinimai. Be parinkčių apdorojama viskas. Paleisti iš naujo saugu – kas jau atlikta, praleidžiama.
- Eiga: be parinkčių rodoma eigos juosta kiekvienam žingsniui; `-v` vietoj jos rodo kiekvieną tikrinamą įrašą, šaltinį ir rezultatą; `--quiet` paslepia išvestį.
- Senųjų failų patikra: `media:legacy-files -v` rodo tikrinamus aplankus, failus, jų naudojimą ir eigą. Galima pridėti `--group=contacts` vienam aplankui. `--verbose` yra `-v` atitikmuo; `--quiet` paslepia įprastą išvestį ir eigą.
- Senųjų stulpelių pašalinimas: migracija atsisako juos išmesti, kol liko perkeltinų nuotraukų, neatnaujintos medijos ar neišvalytų pakeistų failų. Pašalinus stulpelius, `media:migrate-images` tikrina tik likusias paskirtis; nurodžius pašalintą paskirtį su `--target`, nieko nekeičiama.
- Senųjų failų trynimas: `media:legacy-files --delete` leidžiama tik pašalinus senuosius stulpelius ir ne staging aplinkoje, kurios failai bendri su produkcija. Prieš kiekvieno failo pašalinimą naudojimas patikrinamas iš naujo; skaičiuojamos ir šiukšlinėje esančių įrašų bei redaktoriaus juodraščių nuorodos. Prieš produkcijos valymą išsaugomos duomenų bazės ir failų atkūrimo kopijos. Migracijos atšaukimas atkuria stulpelius, bet ne pašalintas jų reikšmes.
- Rašymas: `FileUploadWriter` vienodai tvarko paprastus ir optimizuotus failus, tikrina rašymo rezultatą ir naudoja aplanko užraktą pavadinimui parinkti bei failui išsaugoti.
- Naudojimo paieška: `App\Services\FileUsageScanner` turi tikrinamų stulpelių sąrašą (`targets()`). SQL užklausa atrenka eilutes pagal kelio dalis, kurios nesikeičia jokioje koduotėje, o `App\Services\FileUsage\FileReferenceMatcher` kiekvieną reikšmę iškoduoja (JSON, HTML entities, `%20`, NFC/NFD) ir lygina visą kelią `/uploads/files/…`. Senasis kelias `/uploads/…` (be `files/`) skaičiuojamas tik tada, kai tokiu adresu nėra kito failo.
- Paveikslėlių glaudinimas: atliekamas per `Intervention\Image` biblioteką valdiklio metode `compressImage()`.
- Failų tvarkyklės nuotraukų versijos: viešas maršrutas `/img/{plotis}/{kelias}` (`ResizedImageController`, `App\Support\Media\ResizableImage`, pločiai 400–3840 px) pagal poreikį sukuria ir išsaugo sumažintą kopiją; niekada nedidina. Turinio blokams serveris perduoda `ContentPart::image_srcsets`, tekstui versijas įterpia `ContentPart::html`. Sąraše yra tik už originalą mažesnės versijos ir tikrojo pločio originalas; nežinant matmenų naudojamas tik originalo adresas.
