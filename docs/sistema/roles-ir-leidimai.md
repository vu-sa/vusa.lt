---
doc_status: reviewed
title: Rolės ir leidimai
area: roles
models: [Role, Permission]
last_reviewed: 2026-09-30
tests:
  - tests/Feature/Admin/Permissions/RoleControllerTest.php
  - tests/Feature/Admin/Permissions/PermissionControllerTest.php
  - tests/Feature/System/ModelPermissionSeederTest.php
  - resources/js/Components/AdminForms/__tests__/RolePermissionForms.component.test.ts
  - resources/js/Features/Admin/PermissionTable/__tests__/PermissionTable.component.test.ts
---

# Rolės ir leidimai

Darbo srities **Sistema** skiltys **Rolės** (`/mano/roles`) ir **Leidimai** (`/mano/permissions`) skirtos
platformos prieigos teisėms tvarkyti.

Čia apibrėžiama, ką naudotojai gali matyti ir daryti sistemoje. Šios skiltys skirtos platformos
administratoriams. Kaip veikia teisės ir bazinė nario prieiga, skaityk
[Teisėse ir rolėse](/pagrindai/teises).

## Kaip tai veikia

Prieigą aprašo leidimai ir rolės:

1. **Leidimai** – teisės atlikti konkrečius veiksmus (pvz., kurti naujienas, trinti posėdžius).
2. **Rolės** – leidimų rinkiniai, skirti tam tikram darbui
   (pvz., *Komunikacijos koordinatorius*, *Išteklių administratorius*, *Studentų atstovas*).
3. **Priskyrimas pareigybėms**: kasdieniame darbe rolės priskiriamos ne asmenims, o **pareigybėms**.
   Visi pareigybę einantys asmenys automatiškai naudojasi jos rolėmis.
4. **Automatinis priskyrimas per tipus**: rolė gali būti susieta su [pareigybės tipu](/sistema/tipai).
   Tada kiekviena to tipo pareigybė rolę gauna automatiškai (pvz., tipas *Studentų atstovai* suteikia
   rolę *Studentų atstovas*).

::: info Rolės yra bendros, teisės – su apimtimis
Rolės yra bendros visai platformai. Tačiau rolės viduje kiekvienai teisei nustatoma
galiojimo apimtis: **tavo padalinyje**, **tik su tavimi susijusiuose įrašuose** arba
**visoje platformoje**. Pavyzdžiui, fakulteto komunikacijos koordinatorius gali redaguoti tik savo
padalinio naujienas, o Centrinio biuro koordinatorius – visų padalinių naujienas.
:::

## Rekomendacijos {#susitarimai}

Prieš kurdamas naują rolę, patikrink, ar darbui pakanka esamos. Suteik tik reikalingus veiksmus ir
pasirink siauriausią tinkamą apimtį. Prieš trindamas rolę, patikrink, kurios pareigybės ją naudoja.

## Veiksmai

### Rolių sąrašas (`/mano/roles`)

Sąraše pateikiamos visos sistemoje sukurtos rolės, paskutinio atnaujinimo data ir priskirtų teisių skaičius.
Naudok paiešką pagal rolės pavadinimą.

### Naujos rolės kūrimas

Naujos rolės kuriamos centralizuotai, kai atsiranda nauja darbo sritis:

1. Rolių sąrašo viršuje paspausk **Nauja rolė**.
2. Įvesk unikalų rolės pavadinimą lietuvių kalba (pvz., *Projektų koordinatorius*).
3. Išsaugok. Grįžęs į rolių sąrašą, atverk naują rolę ir nustatyk jos teises.

### Rolės teisių nustatymas (`/mano/roles/{id}`)

Rolės puslapyje rodomas teisių tinklelis, sugrupuotas pagal įrašų rūšis (naujienos, posėdžiai,
rezervacijos, pareigybės ir kt.):

1. Kiekvienai įrašų rūšiai pasirink leidžiamą veiksmą (skaityti, kurti, redaguoti, trinti) ir jo apimtį.
2. **Užrakintos teisės**: teisės, kurias pagal [bazinę prieigą](/pagrindai/teises#bazine-prieiga) turi
   kiekvienas prisijungęs narys (pvz., matyti savo pareigybes, savo institucijos posėdžius ar rašyti
   komentarus), pažymėtos užraktu **Visi nariai**. Jų pasirinkti nereikia ir negalima.
3. Pasirinkti pakeitimai įrašomi paspaudus skilties išsaugojimo mygtuką.

### Rolės susiejimas su pareigybėmis ir tipais

- **Skirtuke „Pareigybės“**: pažymėk konkrečias pareigybes, kurioms ši rolė turi galioti nuolat.
- **Skirtuke „Tipai“**: pažymėk tipus, kuriuos šią rolę turintis narys gali priskirti pareigybėms.
  Šis skirtukas nenustato, kokias roles automatiškai suteikia tipas – tai atskiras
  [tipų ir rolių ryšys](/sistema/tipai).

### Rolės trynimas

Jei rolė nebenaudojama, rolės puslapio veiksmų meniu pasirink **Šalinti**. Prieš trinant
rekomenduojama įsitikinti, kad rolė nebėra priskirta aktyvioms pareigybėms.

### Leidimų sąrašas (`/mano/permissions`)

Skiltis rodo sistemos leidimų sąrašą: jų pavadinimus, įrašų rūšis, veiksmus ir apimtis.
Šis sąrašas yra informacinis: leidimai generuojami sistemos kode pagal platformos modelius ir
šioje skiltyje rankiniu būdu nekuriami.

## Kas ką gali {#teises}

| Veiksmas | Komunikacijos ar studentų atstovų koordinatorius | Superadministratorius |
|---|---|---|
| Matyti rolių ir leidimų skiltis | – | ✓ |
| Kurti, redaguoti ir trinti roles | – | ✓ |
| Keisti rolės teisių tinklelį | – | ✓ |
| Susieti rolę su pareigybėmis ir tipais | – | ✓ |
| Priskirti esamą rolę savo padalinio pareigybei | ✓ Pareigybės formoje | ✓ Visur |

Rolių kūrimas ir jų teisių keitimas yra centralizuotas ir prieinamas **superadministratoriui**
arba nariui, kurio rolė suteikia reikiamą visos platformos rolių administravimo teisę. Lentelė rodo
įprastą koordinatoriaus prieigą; papildomai suteiktos teisės gali ją išplėsti.

::: warning superadministratoriaus apsauga
Rolės **Super Admin** pavadinimo keisti ir jos ištrinti negalima. Superadministratorius turi
visos platformos prieigą, kuri nepriklauso nuo rolės teisių lentelėje pasirinktų leidimų.
:::

## Pranešimai ir automatizavimas {#pranesimai}

- **Prieigos atnaujinimas**: keičiant roles, atnaujinama susijusių paskyrų prieigos ir navigacijos
  talpykla. Pakeitęs teises, patikrink, ar jos atitinka numatytą darbą.
- **Rolės pagal tipą**: priskyrus pareigybei leidžiamą tipą, jai suteikiamos su tuo tipu susietos rolės.
  Pašalinus tipą, su juo susietos rolės taip pat pašalinamos.

## Techninė informacija {#technine-informacija}

- Rolių administravimą reglamentuoja `RoleController` ir `RolePolicy`.
- Leidimų peržiūrą valdo `PermissionController` ir `PermissionPolicy`.
- Teisių grupių sinchronizavimą atlieka `RoleController::syncPermissionGroup()`, naudojant `SyncRolePermissionGroupRequest`.
- `syncDuties()` priskiria rolę pareigybėms; `syncAttachableTypes()` nustato, kokius tipus rolės turėtojas gali priskirti (`role_can_attach_types`). Automatinį rolės suteikimą pagal tipą aprašo atskiras `role_type` ryšys.
- Leidimų apimtys: `own`, `padalinys`, `*`. Rolių ir leidimų skiltis galima deleguoti per atitinkamus `roles.*.*` ir `permissions.*.*` leidimus; lentelė rodo įprastų koordinatoriaus rolių prieigą.
- Pakeitimų metu vykdomas `clearCacheforRoleUsers()`, išvalantis `PermissionMapBuilder` ir `HandleInertiaRequests::adminNavigationCacheKey()`.
- Automatinį rolių priskyrimą per tipus užtikrina `TypeableObserver::saved()` kartu su `GetAttachableTypesForDuty`.
- Testai: `RoleControllerTest`, `PermissionControllerTest`, `ModelPermissionSeederTest`, `RolePermissionForms.component.test.ts`, `PermissionTable.component.test.ts`.
