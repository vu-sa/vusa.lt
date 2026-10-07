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
prideda esamus ar naujus narius ir leidžia suderinti jų datas. Kitai pareigybei vedlys paleidžiamas iš naujo.

Vedlys pasiekiamas per **+ Sukurti → Pareigybių atnaujinimas** arba adresu `/mano/duties-update-users`.

## Rekomendacijos {#rekomendacijos}

::: tip
Po rinkimų rekomenduojama atnaujinti, kas eina pareigas, patikrinti tikras kadencijos datas ir atskirti asmeninį
nario el. paštą nuo pareigybės institucinio adreso. Renkamosioms pareigoms siūloma
nurodyti pabaigos datą. Tai patarimai, kurių vedlys automatiškai nepritaiko.
:::

## Kaip tai veikia

Vedlį sudaro keturi žingsniai: **Institucija → Pareigybė → Narių priskyrimas → Peržiūra**.
Pasirinkimas taikomas vienai pareigybei, o ne visai institucijai iš karto.

### 1 žingsnis. Institucijos pasirinkimas {#zingsnis-1}

Pirmame žingsnyje pasirenkama institucija. Sąraše pateikiamos administruojamų padalinių
institucijos ir bendros institucijos, kuriose padaliniui leista skirti narius pagal kvotą.
Galima naudotis paieška; jei institucijos nėra, ją galima sukurti turint kūrimo teisę.

### 2 žingsnis. Pareigybės pasirinkimas {#zingsnis-2}

Pasirenkama **viena** institucijos pareigybė. Prie jos rodomas vietų užimtumas.
Jei pareigybės nėra, ją galima sukurti turint kūrimo teisę.

### 3 žingsnis. Narių priskyrimas {#zingsnis-3}

- Pažymima, kurių esamų narių laikotarpiai užbaigiami, ir patikrinamos pabaigos datos.
- Narys pridedamas iš paieškos arba sukuriamas naujas nario įrašas, įvedant prašomus kontaktinius duomenis.
- Patikrinama naujų laikotarpių pradžia ir pabaiga. Jei reikia, pasirenkama studijų programa.
- Bendroje pareigybėje galima skirti savo padalinio narius pagal jam nustatytą kvotą.

**Pradžios ir pabaigos dienos įskaitomos.** Užbaigiant siūloma šiandienos data:
narys šiandien dar eina pareigas. Plačiau – [Laikotarpio datos](/organizacija/pareigybes#laikotarpio-datos).

Naujam laikotarpiui vedlys siūlo liepos 1 d. Jei iki artimiausios liepos liko mažiau nei
trys kalendoriniai mėnesiai, siūloma kitų metų liepos 1 d.; kitu atveju – artimiausia.
Tai **siūloma reikšmė**, o ne kadencijų taisyklė. Ją verta pakeisti pagal tikrą paskutinę pareigų dieną:
liepos 1 d. įrašius kaip pabaigą, ši diena taip pat bus aktyvi.

### 4 žingsnis. Peržiūra ir patvirtinimas {#zingsnis-4}

Šiame žingsnyje peržiūrima, kas baigia pareigas, kas paskiriamas, jų datos ir naujų narių kontaktai.
Patvirtinus įrašomi pasirinktos pareigybės pakeitimai. Jei išsaugoti nepavyksta,
rodomos klaidos, kurias reikia ištaisyti.

## Veiksmai

### Metinis padalinio perdavimas

Organizacinė rekomendacija: rekomenduojama pradėti nuo vadovų, tada atnaujinti koordinatorius ir studentų atstovus.
Kiekvienai pareigybei atliekama atskira vedlio eiga. Prieš keičiant savo administravimo pareigas,
svarbu įsitikinti, kad perdavimą galės tęsti kitas administratorius.

### Perrinktas narys

Jei narys tęsia tą patį laikotarpį, jo pabaigos data keičiama pareigybės puslapyje.
Jei reikia atskiro naujos kadencijos įrašo, užbaigiamas senas ir pridedamas naujas laikotarpis.
Pasirenkama pagal tai, ar narys iš tikrųjų perrinktas naujai kadencijai; vedlys savaime nenusprendžia, ar prasidėjo nauja kadencija.

## Kas ką gali {#teises}

Prieigą, narių tvarkymo apimtį ir bendrų pareigybių kvotas aprašo
[pareigybių teisių lentelė](/organizacija/pareigybes#teises).
Vedliu naudojasi komunikacijos ir studentų atstovų koordinatoriai savo administruojamoje apimtyje.

::: warning Savęs užsirakinimo perspėjimas
Keisdamas savo paties administravimo laikotarpį gali gauti perspėjimą apie prieigos pokytį.
Perspėjimą patvirtinti reikėtų tik tada, kai perdavimas suderintas. Patvirtinimas nepakeičia
pabaigos datos taisyklės: įrašius šiandieną, ji dar įskaitoma.
:::

## Pranešimai ir automatizavimas {#pranesimai}

- Naujo nario pridėjimas keičia jo pareigybės laikotarpius ir iš jų gaunamą prieigą.
- Užbaigimas išsaugo laikotarpio istoriją; kitos nario pareigybės lieka galioti.
- **Naujo nario įrašo sukūrimas nesiunčia pakvietimo ir nesukuria prisijungimo instrukcijų.**
  Prisijungimo perdavimas suderinamas atskirai.
- Bendroms pareigybėms sistema tikrina padalinio kvotą.

## Techninė informacija {#technine-informacija}

- `useDutyUserWizard` saugo vieną `duty`; `Step2DutySelect` ją pasirinkęs pereina į kitą žingsnį.
- `duties.batchUpdateUsers` siunčia vienos pareigybės pakeitimus į `DutyController::batchUpdateUsers`.
  Viena transakcija apima šio prašymo pakeitimus, ne visos institucijos perdavimą.
- Vietinėms pareigybėms tikrinamas `DutyPolicy::update`, delegavimui – `DutyPolicy::managePeople` ir kvota.
- `User::create` sukuria įrašą; šiame sraute pakvietimo siuntimo nėra.
- `getSuggestedEndDate` taiko kalendorinių mėnesių skirtumą iki liepos, ne tikslų dienų intervalą.
- `guardSelfLockout` grąžina `access_change_warning`; pakartotiniam patvirtinimui siunčiamas
  `acknowledge_access_change`. Duomenų užkrovimo ir savęs užsirakinimo sutartis tikrina nurodyti testai.
