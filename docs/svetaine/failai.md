---
doc_status: reviewed
title: Failai
area: files
models: [File]
last_reviewed: 2026-10-03
tests:
  - tests/Feature/Admin/Resources/FilesControllerTest.php
  - tests/Feature/Api/Admin/FileApiControllerTest.php
  - tests/Feature/Services/FileUsageScannerTest.php
  - tests/Unit/Services/FileReferenceMatcherTest.php
  - resources/js/Features/Admin/FileManager/__tests__/FilePropertiesDrawer.component.test.ts
  - resources/js/Features/Admin/FileManager/__tests__/FileManagerUpload.component.test.ts
  - resources/js/Features/Admin/FileManager/__tests__/FileManagerView.component.test.ts
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
- **Bendrieji aplankai:** formų viršeliams ir baneriams skirtos nuotraukos automatiškai saugomos į bendrus sistemos aplankus (`banners`, `news`, `pages`, `calendar`).
- **Saugūs failų keliai:** visi įkėlimai ir aplankų veiksmai tikrinami saugumo mechanizmais (`StoragePath`), neleidžiančiais išeiti už leistino aplanko ribų.

## Veiksmai

### Failų naršyklė ir paieška

Failų tvarkyklėje (`/mano/files`) patogu naršyti tiek kompiuteryje, tiek telefone:

- **Rodiniai:** viršuje gali perjungti **tinklelio** (patogu peržiūrėti nuotraukoms) arba **sąrašo** (patogu dokumentams su ilgais pavadinimais) rodinį.
- **Rikiavimas:** rūšiuok pagal pavadinimą, įkėlimo datą ar failo dydį.
- **Paieška:** viršutinėje paieškos juostoje gali ieškoti failų tik dabartiniame aplanke arba pažymėti paiešką visuose pasiekiamuose aplankuose.

### Failų įkėlimas ir aplankų kūrimas

1. **Įkelti failus:** spausk **Įkelti** arba tiesiog nutempk failus pele iš savo kompiuterio į naršyklės langą. Įkėlimo juosta parodys, kiek failo jau įkelta.
2. **Sukurti aplanką:** spausk **Naujas aplankas**, įrašyk pavadinimą ir patvirtink. Sukurtas aplankas iškart atsiras sąraše.

### Failo savybės, peržiūra ir nuoroda

Pasirinkus failą, dešinėje pusėje atsidaro **savybių skydelis** (`FilePropertiesDrawer`):

- **Peržiūra:** atidaryk viso dydžio nuotraukos ar PDF dokumento peržiūros langą.
- **Kopijuoti nuorodą:** vienu paspaudimu nukopijuok viešą nuorodą į iškarpinę, kad galėtum ją įklijuoti į naujieną ar išsiųsti kolegoms.
- **Atsisiųsti:** atsisiųsk originalų failą į savo įrenginį.
- **Glaudinti:** sumažink paveikslėlio failo dydį, kad puslapis greičiau įsikrautų.

### Naudojimo patikra ir saugus trynimas {#naudojimo-patikra}

Prieš trinant failą, sistema leidžia apsisaugoti nuo puslapių sugadinimo:

<ChangelogNote version="v3.0" date="2026-10-02" title="Patikimesnė naudojimo patikra">

Patikra randa failą visuose turinio blokuose, nuotraukų laukuose ir nuorodose, net kai jo pavadinime yra tarpų ar lietuviškų raidžių, ir parodo, kur jis naudojamas.

</ChangelogNote>

- **Naudojimo patikra:** pasirinkęs failą, savybių skydelyje spausk **Tikrinti**. Sistema patikrina visus puslapių, naujienų ir padalinių pradinių puslapių turinio blokus, nuotraukų laukus (naujienų, puslapių, banerių, institucijų, narių), aprašymus ir nuorodas (renginių, pareigybių, formų, problemų, žymų, navigacijos, greitųjų nuorodų) bei dar neišsaugotus juodraščius. Įrašai šiukšlinėje taip pat skaičiuojami – juos atkūrus, failas vėl būtų reikalingas.
- **Rezultatas:** jei failas naudojamas, matai sąrašą, kur jis panaudotas, ir gali atsidaryti tą įrašą. Patikra visada atliekama iš naujo, todėl rodo ir ką tik įterptas nuorodas.
- **Kas nelaikoma naudojimu:** to paties pavadinimo failas kitame aplanke ir nuoroda į kitą svetainę (ne vusa.lt) su tokiu pačiu keliu.
- **Trynimas:** jei failas nebenaudojamas, pasirink **Ištrinti**.
- **Kelių failų trynimas:** pažymėk kelis failus ar aplankus ir spausk **Ištrinti pasirinktus**.

## Kas ką gali {#teises}

| Veiksmas | Padalinio koordinatorius | Centrinio biuro komunikacijos koordinatorius |
|---|---|---|
| Matyti savo padalinio aplanką | ✓ | ✓ |
| Matyti visus organizacijos aplankus | – | ✓ |
| Įkelti failus į savo aplanką | ✓ | ✓ |
| Kurti aplankus savo kataloge | ✓ | ✓ |
| Trinti failus savo kataloge | ✓ | ✓ |
| Įkelti failus į bendrus katalogus | tik per formų viršelių laukus | ✓ |

## Pranešimai ir automatizavimas {#pranesimai}

- El. laiškai ar pranešimai tvarkant failus nesiunčiami.
- **Saugi failų patikra:** `FileUsageScanner` padeda išvengti situacijų, kai ištrynus nuotrauką ar dokumentą viešoje svetainėje lieka neveikianti nuoroda ar tuščias paveikslėlio rėmelis.

## Techninė informacija {#technine-informacija}

Šis skyrius skirtas administratoriams ir sistemos priežiūrai.

### Teisės

- Prieiga prie failų sistemos kontroliuojama per `files` leidimų grupę:
  - Padalinio teisės: `files.create.padalinys`, `files.read.padalinys`, `files.update.padalinys`, `files.delete.padalinys`.
  - Centrinio biuro teisės: `files.create.*`, `files.read.*`, `files.update.*`, `files.delete.*`.
- Modelis `File` nėra Eloquent duomenų bazės lentelė; tai virtualus modelis, skirtas leidimų generavimui ir atpažinimui.

### Kaip tai įgyvendinta

- Administravimo valdiklis: `App\Http\Controllers\Admin\FilesController`.
- API valdiklis: `App\Http\Controllers\Api\Admin\FileApiController` (aptarnauja asinchroninę failų naršyklę, paiešką ir miniatiūrų teikimą).
- Failų saugojimo šaknis: `storage/app/public/files/`.
- Saugumo valdymas: `App\Support\StoragePath::normalizeRelative()` pašalina bet kokius bandymus išeiti iš katalogo (pvz., `../`), užkirsdamas kelią „Path Traversal“ atakoms.
- Naudojimo paieška: `App\Services\FileUsageScanner` turi tikrinamų stulpelių sąrašą (`targets()`). SQL užklausa atrenka eilutes pagal kelio dalis, kurios nesikeičia jokioje koduotėje, o `App\Services\FileUsage\FileReferenceMatcher` kiekvieną reikšmę iškoduoja (JSON, HTML entities, `%20`, NFC/NFD) ir lygina visą kelią `/uploads/files/…`. Senasis kelias `/uploads/…` (be `files/`) skaičiuojamas tik tada, kai tokiu adresu nėra kito failo.
- Paveikslėlių glaudinimas: atliekamas per `Intervention\Image` biblioteką valdiklio metode `compressImage()`.
