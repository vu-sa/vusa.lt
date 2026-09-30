---
doc_status: reviewed
title: Užduotys
area: tasks
models: [Task]
last_reviewed: 2026-09-30
tests:
  - tests/Browser/VisakOverviewPagesTest.php
  - tests/Feature/Admin/Dashboard/UserTasksTest.php
  - tests/Feature/Tasks/TaskCompletionTest.php
  - tests/Feature/Tasks/TaskDeletionTest.php
  - tests/Feature/Tasks/TaskControllerAuthorizationTest.php
  - tests/Feature/Tasks/TaskNotificationAudienceTest.php
  - tests/Feature/Tasks/RepopulateInstitutionTasksTest.php
  - tests/Feature/Tasks/Handlers/PeriodicityGapTaskHandlerTest.php
  - tests/Feature/Tasks/Subscribers/MeetingTaskSubscriberTest.php
  - tests/Feature/Tasks/Subscribers/ApprovalTaskSubscriberTest.php
  - tests/Feature/Tasks/Subscribers/ReservationTaskSubscriberTest.php
  - tests/Feature/Tasks/Subscribers/InstitutionCheckInTaskSubscriberTest.php
  - tests/Feature/Notifications/SendTaskOverdueRemindersCommandTest.php
  - tests/Feature/Tasks/InstitutionSecretaryTaskResyncTest.php
  - tests/Feature/Tasks/InstitutionSecretaryMailScopeTest.php
  - resources/js/Pages/Admin/Tasks/__tests__/IndexTask.component.test.ts
  - resources/js/Features/Admin/TaskManager/__tests__/taskActions.test.ts
---

# Užduotys

Užduotis yra nedidelis darbas, priskirtas vienam ar keliems žmonėms. Ji gali turėti terminą, o gali
būti ir be jo. Dauguma užduočių sukuriamos **automatiškai**: užpildyti posėdžio darbotvarkę,
patvirtinti rezervaciją, pranešti apie institucijos veiklą. Kai susijęs darbas atliktas, jos
užbaigiamos pačios.

Tavo užduotys pasiekiamos adresu `/mano/tasks` (**Mano → Užduotys**). Tas pats sąrašas visiems
padalinio atstovams yra ViSAK srityje – žr. [Užduočių suvestinė](/visak/uzduociu-suvestine).

<DocScreenshot name="tasks-index" alt="Užduočių sąrašas: greitieji filtrai Vėluoja, Automatinės ir Atliktos, užduotys su terminais ir pažymėtos užduoties peržiūra" caption="Atstovės užduotys: viena vėluoja, dauguma bus užbaigtos automatiškai." />

## Kaip tai veikia

Kiekviena užduotis turi:

- pavadinimą ir, jei reikia, instrukcijas;
- terminą (nebūtinas);
- **atsakingus** – žmones, kuriems ji priskirta;
- **objektą** – posėdį, rezervaciją, instituciją arba žmogų, su kuriuo ji susijusi.

### Rankinės ir automatinės užduotys {#automatines}

**Rankinę** užduotį sukuria žmogus, o atliktą ją pažymi atsakingasis. **Automatinę** užduotį sukuria
sistema. Ji užbaigiama pati, kai atliekamas su ja susijęs darbas, todėl pažymėti jos atlikta rankiniu
būdu negalima.

| Užduotis | Kada sukuriama | Kam | Terminas | Kada užbaigiama |
|---|---|---|---|---|
| **Sukurti posėdžio darbotvarkės klausimus** | Užregistravus posėdį be darbotvarkės | Institucijos atstovams | 3 d. po posėdžio | Sukūrus pirmą darbotvarkės klausimą |
| **Užpildyti darbotvarkės klausimų informaciją** | Užregistravus posėdį su darbotvarke | Institucijos atstovams | Posėdžio diena | Užpildžius visus klausimus. Eiga rodoma, pvz., „2 iš 3“ |
| **Pranešti apie veiklą: …** | Kasdien 8.00, kai institucijai artėja arba jau praėjo posėdžių [periodiškumo](/visak/apzvalga#busenos) terminas | Institucijos atstovams | Ne anksčiau nei po 7 d., atostogų dienos neskaičiuojamos | Užregistravus posėdį arba pranešus apie veiklą |
| **Rezervacijos tvirtinimas** | Pateikus rezervaciją | Išteklio padalinio valdytojams | Jei tvirtinimo eigoje nustatytas | Priėmus sprendimą. Plačiau – [Rezervacijos](/rezervacijos/rezervacijos#pranesimai) |
| **Atsiimti / Grąžinti rezervacijos išteklius** | Patvirtinus / išdavus išteklių | Rezervacijos teikėjams | Atsiėmimo / grąžinimo laikas | Atsiėmus / grąžinus visus išteklius |

Jei institucijos kadencijai paskirti sekretoriai, posėdžio ir periodiškumo užduotys skiriamos
jiems. Kitu atveju – atstovams, kurie ėjo pareigas posėdžio dieną.

### Kada užduotis vėluoja {#veluoja}

Užduotis **vėluoja**, jei jos terminas jau praėjo, o ji dar neatlikta. Užduotis be termino niekada
nevėluoja, atlikta – taip pat.

## Veiksmai

### Sąrašas ir filtrai {#filtrai}

Užduotis galima rodyti trimis vaizdais: **peržiūra** (numatytasis: sąrašas ir šalia pasirinktos
užduoties informacija), **lentelė** arba **eilutės**.

- **Greitieji filtrai**: **Vėluoja**, **Automatinės**, **Atliktos**. Šalia filtro rodomas užduočių
  skaičius, jei jis didesnis už 0. Skaičiai skaičiuojami iš visų tavo užduočių, nepaisant kitų
  pasirinktų filtrų.
- **Būsena**: pagal nutylėjimą rodomos tik **neatliktos**. Galima pasirinkti **atliktas** arba **visas**.
- **Tipas**: posėdžio, rezervacijos ar institucijos užduotys.
- **Paieška** pagal pavadinimą.

Pagal nutylėjimą sąrašas rikiuojamas taip: pirmiausia neatliktos, tarp jų pirmiausia vėluojančios,
tada pagal artimiausią terminą, o užduotys be termino – pabaigoje. Galima rinktis ir **Naujausios**.
Atliktos užduotys rikiuojamos nuo vėliausiai atliktos.

Nuoroda į konkrečią užduotį (pvz., iš pranešimo) atidaro ją iš karto, net jei ji nėra pirmame puslapyje.

### Veiksmai su užduotimi

| Veiksmas | Kada rodomas |
|---|---|
| **Pranešti apie veiklą** | Neatlikta periodiškumo užduotis: registruoji posėdį arba pranešimą, kad posėdžio nebuvo |
| **Pridėti darbotvarkės klausimą** / **Peržiūrėti darbotvarkę** | Neatlikta darbotvarkės užduotis: nuoroda į posėdžio darbotvarkę |
| **Pažymėti atlikta** / **Grąžinti į neatliktas** | Tik rankinė užduotis |
| **Ištrinti** | Tik jei turi teisę ją ištrinti |

Eilučių vaizde užduotį atlikta pažymi varnelė eilutės pradžioje, o peržiūros vaizde veiksmai
rodomi dešinėje. Varnelę ir **Pažymėti atlikta** mato tik tie, kas gali užduotį keisti: kiti ją
mato, bet pažymėti negali.

Jei gali matyti ir padalinio užduotis, sąraše rodomas mygtukas **Visos užduotys**, vedantis į
[užduočių suvestinę](/visak/uzduociu-suvestine).

## Kas ką gali {#teises}

| Veiksmas | Bet kuris narys | Atsakingas už užduotį | Centrinio biuro studentų atstovų koordinatorius |
|---|---|---|---|
| Matyti savo užduotis | ✓ | ✓ | ✓ |
| Pažymėti rankinę užduotį atlikta ar grąžinti | – | ✓ | ✓ |
| Pažymėti automatinę užduotį atlikta | – | – | – |
| Ištrinti rankinę ar automatinę užduotį | – | – | ✓ |

Atsakingas už užduotį jos ištrinti negali. Ištrinti gali tik turintys užduočių trynimo teisę – ir
automatinę užduotį, pvz., kai ji nebegali užsibaigti pati, nes ištrintas jos objektas.

Rankinę užduotį sukurti galima sau arba savo padalinio institucijai. Kitam žmogui ar kito padalinio
institucijai užduoties sukurti negalima.

## Pranešimai ir automatizavimas {#pranesimai}

| Kada | Kas gauna | Ką |
|---|---|---|
| Kasdien 8.00, likus 7, 3 ir 1 d. iki termino | Atsakingi | Priminimą. Kurių priminimų nori, pasirenki [pranešimų nustatymuose](/pagrindai/pranesimai) |
| Kiekvieną pirmadienį 9.00 | Žmonės, turintys vėluojančių užduočių | Suvestinę apie vėluojančias užduotis |

Automatinių posėdžio ir institucijos užduočių priminimai siunčiami tik tiems atsakingiems, kurie
vis dar eina pareigas toje institucijoje. Pasibaigus kadencijai užduotis lieka, bet priminimų
nebėra. Rankinės užduoties priminimus gauna visi, kuriuos priskyrė žmogus.

## Techninė informacija {#technine-informacija}

- Abu užduočių sąrašai – `TaskController::index` (`mine`, maršrutas `tasks.index`) ir `TaskController::summary` (`tenant`, maršrutas `tasks.summary`) –
  naudoja tą patį `BuildTaskIndexQuery` ir `Admin/Tasks/IndexTask.vue`. Filtrai ir puslapiai
  kraunami per `api.v1.admin.tasks.index` (`TaskApiController`). Filtrus tikrina `IndexTasksRequest`.
- Sukurtos užduoties atributų (pavadinimo, termino, atsakingų) keitimas per HTTP nenumatytas –
  galima tik atnaujinti atlikimo būseną (`TaskController::updateCompletionStatus`) arba užduotį ištrinti (`TaskController::destroy`).
- Automatiniai tipai – `App\Tasks\Enums\ActionType`. Rankiniu būdu užbaigti galima tik `manual`.
  `updateCompletionStatus` kitus atmeta pranešimu `messages.task.automatic_not_markable`.
- Atlikimo būseną atnaujinti gali atsakingi arba turintys `tasks.update.padalinys` / `tasks.update.*`
  (`TaskPolicy::update`). Ištrinti – `tasks.delete.{*|padalinys|own}`, automatines taip pat
  (`Task::isDeletableBy`). `.own` (tavo pareigybių bendradarbių užduotys) nė vienai standartinei
  rolei nepriskiriama.
- Periodiškumo užduotis kuria `tasks:repopulate institution --force` (kasdien 8.00). Priminimus
  siunčia `TaskNotifier::notifyDaysLeft` ir `notifications:task-overdue-reminders` gavėjams iš
  `Task::notifiableUsers()` (`ResolveTaskAudience`).
