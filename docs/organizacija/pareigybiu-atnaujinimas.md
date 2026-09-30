---
doc_status: reviewed
title: Pareigybių atnaujinimas
area: duties
models: [Duty, User, Dutiable]
last_reviewed: 2026-09-30
tests:
  - tests/Feature/Admin/Management/DutyUserWizardTest.php
  - tests/Feature/Permissions/DutyBatchUsersSelfLockoutTest.php
  - resources/js/Pages/Admin/People/__tests__/DutyUserUpdateWizard.test.ts
  - resources/js/Components/DutyUserWizard/__tests__/Step3UserAssignment.component.test.ts
  - resources/js/Composables/__tests__/useDutyUserWizard.test.ts
---

# Pareigybių atnaujinimas

Vedlys vienu kartu atnaujina **vienos pareigybės narius**: užbaigia laikotarpius,
prideda esamus ar naujus narius ir leidžia suderinti jų datas. Kitai pareigybei vedlį paleisk iš naujo.

Atverk **+ Sukurti → Pareigybių atnaujinimas** arba `/mano/duties-update-users`.

## Kaip tai veikia

Vedlį sudaro keturi žingsniai: **Institucija → Pareigybė → Narių priskyrimas → Peržiūra**.
Pasirinkimas taikomas vienai pareigybei, o ne visai institucijai iš karto.

### 1 žingsnis. Institucijos pasirinkimas {#zingsnis-1}

Pasirink instituciją. Sąraše rasi savo administruojamų padalinių institucijas ir bendras
institucijas, kuriose tavo padaliniui leista skirti narius pagal kvotą. Naudok paiešką;
jei institucijos nėra, ją gali sukurti turėdamas kūrimo teisę.

### 2 žingsnis. Pareigybės pasirinkimas {#zingsnis-2}

Pasirink **vieną** institucijos pareigybę. Prie jos rodomas vietų užimtumas.
Jei pareigybės nėra, ją gali sukurti turėdamas kūrimo teisę.

### 3 žingsnis. Narių priskyrimas {#zingsnis-3}

- Pažymėk, kurių esamų narių laikotarpius užbaigi, ir patikrink pabaigos datas.
- Pridėk narį iš paieškos arba sukurk naują nario įrašą, įvesdamas prašomus kontaktinius duomenis.
- Patikrink naujų laikotarpių pradžią ir pabaigą. Jei reikia, pasirink studijų programą.
- Bendroje pareigybėje gali skirti savo padalinio narius pagal jam nustatytą kvotą.

**Pradžios ir pabaigos dienos įskaitomos.** Užbaigiant siūloma šiandienos data:
narys šiandien dar eina pareigas. Plačiau – [Laikotarpio datos](/organizacija/pareigybes#laikotarpio-datos).

Naujam laikotarpiui vedlys siūlo liepos 1 d. Jei iki artimiausios liepos liko mažiau nei
trys kalendoriniai mėnesiai, siūloma kitų metų liepos 1 d.; kitu atveju – artimiausia.
Tai **siūloma reikšmė**, o ne kadencijų taisyklė. Pakeisk ją pagal tikrą paskutinę pareigų dieną:
liepos 1 d. įrašius kaip pabaigą, ši diena taip pat bus aktyvi.

### 4 žingsnis. Peržiūra ir patvirtinimas {#zingsnis-4}

Patikrink, kas baigia pareigas, kas paskiriamas, jų datas ir naujų narių kontaktus.
Patvirtinus įrašomi pasirinktos pareigybės pakeitimai. Jei išsaugoti nepavyksta,
pataisyk rodomas klaidas ir bandyk dar kartą.

## Veiksmai

### Metinis padalinio perdavimas

Organizacinė rekomendacija: pradėk nuo vadovų, tada atnaujink koordinatorius ir studentų atstovus.
Kiekvienai pareigybei atlik atskirą vedlio eigą. Prieš keisdamas savo administravimo pareigas,
įsitikink, kad perdavimą galės tęsti kitas administratorius.

### Perrinktas narys

Jei narys tęsia tą patį laikotarpį, jo pabaigos datą pakeisk pareigybės puslapyje.
Jei reikia atskiro naujos kadencijos įrašo, užbaik seną ir pridėk naują laikotarpį.
Pasirink pagal tikrą paskyrimą; vedlys savaime nenusprendžia, ar prasidėjo nauja kadencija.

## Kas ką gali {#teises}

Prieigą, narių tvarkymo apimtį ir bendrų pareigybių kvotas aprašo
[pareigybių teisių lentelė](/organizacija/pareigybes#teises).
Vedliu naudojasi komunikacijos ir studentų atstovų koordinatoriai savo administruojamoje apimtyje.

::: warning Savęs užsirakinimo perspėjimas
Keisdamas savo paties administravimo laikotarpį gali gauti perspėjimą apie prieigos pokytį.
Perskaityk jį ir patvirtink tik tada, kai perdavimas suderintas. Patvirtinimas nepakeičia
pabaigos datos taisyklės: įrašius šiandieną, ji dar įskaitoma.
:::

## Pranešimai ir automatizavimas {#pranesimai}

- Paskyrimas keičia nario pareigybės laikotarpius ir iš jų gaunamą prieigą.
- Užbaigimas išsaugo laikotarpio istoriją; kitos nario pareigybės lieka galioti.
- **Naujo nario įrašo sukūrimas nesiunčia pakvietimo ir nesukuria prisijungimo instrukcijų.**
  Prisijungimo perdavimą suderink atskirai.
- Bendroms pareigybėms sistema tikrina padalinio kvotą.

## Susitarimai {#susitarimai}

Atnaujink paskyrimus po rinkimų, patikrink tikras kadencijos datas ir atskirk asmeninį
nario el. paštą nuo pareigybės institucinio adreso. Renkamosioms pareigoms rekomenduojama
nurodyti pabaigos datą. Tai organizaciniai susitarimai, ne automatiniai vedlio sprendimai.

## Techninė informacija {#technine-informacija}

- `useDutyUserWizard` saugo vieną `duty`; `Step2DutySelect` ją pasirinkęs pereina į kitą žingsnį.
- `duties.batchUpdateUsers` siunčia vienos pareigybės pakeitimus į `DutyController::batchUpdateUsers`.
  Viena transakcija apima šio prašymo pakeitimus, ne visos institucijos perdavimą.
- Vietinėms pareigybėms tikrinamas `DutyPolicy::update`, delegavimui – `DutyPolicy::managePeople` ir kvota.
- `User::create` sukuria įrašą; šiame sraute pakvietimo siuntimo nėra.
- `getSuggestedEndDate` taiko kalendorinių mėnesių skirtumą iki liepos, ne tikslų dienų intervalą.
- `guardSelfLockout` grąžina `access_change_warning`; pakartotiniam patvirtinimui siunčiamas
  `acknowledge_access_change`. Duomenų užkrovimo ir savęs užsirakinimo sutartis tikrina nurodyti testai.
