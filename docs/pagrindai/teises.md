---
title: Teisės ir rolės
last_reviewed: 2026-09-27
tests:
  - tests/Feature/Permissions/BaselineAccessTest.php
  - resources/js/Components/AdminForms/__tests__/RolePermissionForms.component.test.ts
  - resources/js/Features/Admin/PermissionTable/__tests__/PermissionTable.component.test.ts
  - tests/Feature/Admin/Permissions/RoleControllerTest.php
  - tests/Feature/System/ModelPermissionSeederTest.php
---

# Teisės ir rolės

Teisės suteikia galimybę matyti ir tvarkyti tam tikrus įrašus. Jos niekada neskiriamos žmogui
tiesiogiai kasdieniame darbe: teisės sudedamos į **roles**, rolės priskiriamos **pareigybėms**, o
naudotojas teises gauna eidamas pareigybę.

## Ką gali kiekvienas narys {#bazine-prieiga}

Kai kas nepriklauso nuo rolių: tai gali kiekvienas prisijungęs narys, net neturėdamas nė vienos
rolės. Rolės prideda tik tai, kas viršija šį pagrindą. Rolės puslapyje (**Sistema → Rolės**) prie
kiekvienos įrašų rūšies parašyta, ką visi nariai jau gali, o tokios teisės pažymėtos užraktu
**Visi nariai**: jų rolei priskirti negalima, nes jos nieko nepridėtų.

| Įrašai | Ką gali kiekvienas narys |
|---|---|
| Institucijos | Matyti sąrašą ir aktyvių institucijų viešą informaciją; savo institucijas – pilnai |
| Posėdžiai ir klausimai | Matyti viešus posėdžius; savo institucijų posėdžius, vykusius tavo pareigų metu, matyti ir keisti – ir pasibaigus pareigoms |
| Problemos | Matyti visų padalinių problemas |
| Ištekliai | Matyti visus išteklius |
| Pareigybės | Matyti savo dabartines ir buvusias pareigybes |
| Užduotys | Matyti ir atlikti tau priskirtas užduotis (bet ne jas ištrinti; savų užduočių trynimo teisė nė vienai rolei nepriskiriama) |
| Komentarai | Komentuoti visur, kur matai įrašą; redaguoti ir trinti savo komentarus. Kitų komentarus trina redaguojantys įrašą arba rolė su komentarų trynimo teise |

## Rolės {#roles}

Kasdieniame darbe užtenka žinoti roles. Kiekviena rolė – tai vienos atsakomybės teisių rinkinys:

| Rolė | Kam skiriama | Ką leidžia | Aprašyta |
|---|---|---|---|
| **Studentų atstovas** | Automatiškai – pareigybėms su tipu „Studentų atstovas“ | Fiksuoti savo institucijų posėdžius, kelti problemas | [ViSAK](/visak/) |
| **Problemų redaktorius** | Automatiškai – pareigybėms su tipu „Koordinatorius (-ė)“ | Kelti ir tvarkyti padalinio problemas | [Problemos](/visak/problemos) |
| **Išteklių administratorius** | Padalinio pirmininkui ir administratoriui | Tvarkyti padalinio daiktus ir jų rezervacijas | [Rezervacijos](/rezervacijos/#istekliu-administratorius) |
| **Padalinio puslapių redaktorius** | Tiems, kas atnaujina padalinio svetainės tekstus | Redaguoti esamus padalinio puslapius | [Puslapiai](/svetaine/puslapiai) |

Rolės, kurių pavadinime yra **„Centrinio biuro“**, leidžia tą patį visuose padaliniuose.

### Rolės pagal pareigybės tipą {#roles-pagal-pareigybes-tipa}

Rolė gali būti susieta su **pareigybės tipu**. Tada ją automatiškai gauna kiekviena to tipo
pareigybė: susiejant rolę su tipu – visos esamos, vėliau – kiekviena, kuriai tipas priskiriamas.
Nuėmus tipą, rolė nuimama. Taip koordinatoriams ir studentų atstovams nereikia roles skirti ranka.

::: warning Kas gali priskirti tipą
Nauja pareigybė rolę gauna tik tada, kai tipą jai priskiria žmogus, kurio rolė leidžia tą tipą
priskirti (pvz., koordinatorius – studentų atstovų tipą), arba super administratorius. Tai
apsaugo nuo teisių išsidalijimo per tipus.
:::

## Teisės formatas {#teises-formatas}

Rolės sudarytos iš **teisių**. Tikslias kiekvieno puslapio teises rasi jo pabaigoje, skyriuje **Techninė informacija**. Kiekviena teisė užrašoma `{išteklius}.{veiksmas}.{apimtis}`, pavyzdžiui, `news.update.padalinys`.

| Dalis | Reikšmės |
|---|---|
| Išteklius | Įrašų rūšis daugiskaita: `news`, `meetings`, `resources`… |
| Veiksmas | `read`, `create`, `update`, `delete`, `forceDelete` |
| Apimtis | `own` – tik su tavo pareigybe tiesiogiai susiję įrašai; `padalinys` – tavo padalinio įrašai; `*` – visi įrašai |

## Kaip sistema nusprendžia {#kaip-sistema-nusprendzia}

1. **Super administratorius** gali viską.
   Kiekvienam nariui galioja ir [bazinė prieiga](#bazine-prieiga).
2. Tikrinamos tiesiogiai naudotojui priskirtos teisės.
3. Tikrinamos teisės, gautos per pareigybių roles.
4. Pagal apimtį nustatoma, kuriems padaliniams ar įrašams teisė galioja.

Jei teisės neturi, ji **neišplečiama**: sistema nerodo nieko, o ne „bent jau savo padalinio“.

::: info Kodėl kai kurių puslapių nematai
Skiltys ir mygtukai, kuriems neturi teisių, paslepiami. Tiesiogiai atidarius tokį adresą, rodomas
puslapis „Prieiga uždrausta“.
:::

Kiekviename puslapyje skyrius **Kas ką gali** aprašo, kurios rolės ką leidžia.

## Techninė informacija {#technine-informacija}

- Bazinę prieigą be rolių užtikrina `BaselineAccess` taisyklės ir `ModelPolicy` patikros, testuojamos `BaselineAccessTest`.
- Rolių ir leidimų matricą, jų priskyrimą pareigybėms ir tipams valdo `RoleController`, tikrina `RoleControllerTest` ir `RolePermissionForms.component.test.ts`.
- Leidimų lentelės atvaizdavimą ir būsenas tikrina `PermissionTable.component.test.ts`.
- Standartinius modelių leidimus ir jų formatus generuoja `ModelPermissionSeeder`, testuojamas `ModelPermissionSeederTest`.
