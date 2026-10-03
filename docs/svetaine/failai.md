---
doc_status: reviewed
title: Failai
area: files
models: [File]
last_reviewed: 2026-10-01
tests:
  - tests/Feature/Admin/Resources/FilesControllerTest.php
  - tests/Feature/Api/Admin/FileApiControllerTest.php
  - tests/Feature/Services/FileUsageScannerTest.php
  - resources/js/Features/Admin/FileManager/__tests__/FileManagerUpload.component.test.ts
  - resources/js/Features/Admin/FileManager/__tests__/FileManagerView.component.test.ts
---

# Failai

Failų skiltyje tvarkomi svetainės dokumentai, nuotraukos, skaidrės ir kiti lankytojams ar turinio rengimui reikalingi failai. Į įkeltus dokumentus galima sukurti nuorodas tekste, o nuotraukas – tiesiogiai įterpti į puslapių bei naujienų blokus.

Skiltis pasiekiama adresu `/mano/files`. Failų tvarkyklę taip pat gali atverti teksto redaktoriuje.

## Kaip tai veikia

### Padalinių katalogų atskyrimas

Kad skirtingų padalinių failai nesusimaišytų ir nebūtų atsitiktinai perrašyti, kiekvienam padaliniui skiriamas atskiras katalogas:

- **Padalinio katalogas:** kiekvienas padalinys turi savo numatytąjį katalogą `padaliniai/{padalinys}` (pvz., `padaliniai/vusapadalinys` arba `padaliniai/vusa-mif`).
- Padalinio koordinatorius, atvėręs failų naršyklę, iškart mato savo padalinio aplanką ir kuria jame poaplankius (pvz., `dokumentai`, `nuotraukos`, `archyvas`).
- **Bendrieji aplankai:** formų viršeliams ir baneriams skirtos nuotraukos automatiškai saugomos į bendrus sistemos aplankus (`banners`, `news`, `pages`, `calendar`).
- **Saugūs failų keliai:** visi įkėlimai ir aplankų veiksmai tikrinami saugumo mechanizmais (`StoragePath`), neleidžiančiais išeiti už leistino aplanko ribų.

## Rekomendacijos {#susitarimai}

- **Aiškūs pavadinimai:** rinkis failo turinį nusakantį pavadinimą, pvz., `ataskaita-2026.pdf`. Venk tokių pavadinimų kaip `FINAL-naujas-2.pdf`, iš kurių neaišku, kuri versija galutinė.
- **Tinkamas formatas:** skaityti skirtiems dokumentams patogu naudoti PDF. Jei kolegos turės dokumentą pildyti ar redaguoti, pateik ir redaguojamą failą.
- **Nuotraukų dydis:** prieš įkeldamas sumažink nereikalingai dideles nuotraukas, išlaikydamas pakankamą kokybę. Taip puslapis greičiau įsikraus ir naudojant mobilųjį ryšį.
- **Aplankų tvarka:** failus grupuok pagal metus ar projektus, kad juos būtų lengva rasti.

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

- **Naudojimo patikra:** pasirinkęs failą gali patikrinti, kur jis naudojamas. Sistema patikrina puslapius, naujienas bei kitus įrašus ir parodo, ar failas kur nors naudojamas.
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
- Naudojimo paieška: `App\Services\FileUsageScanner` nuskaito duomenų bazės stulpelius ir Tiptap turinio JSON/HTML struktūras, ieškodamas failo pavadinimo ar URL paminėjimų.
- Paveikslėlių glaudinimas: atliekamas per `Intervention\Image` biblioteką valdiklio metode `compressImage()`.
