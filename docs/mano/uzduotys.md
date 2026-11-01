---
doc_status: reviewed
title: Užduotys
area: tasks
models: [Task]
last_reviewed: 2026-11-01
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
| **Pranešti apie veiklą: …** | Kasdien 10.00, kai institucijai artėja arba jau praėjo posėdžių [periodiškumo](/visak/apzvalga#busenos) terminas | Institucijos atstovams | Savaitė po to, kai institucija pradeda vėluoti; atostogų dienos neskaičiuojamos | Užregistravus posėdį arba pranešus apie veiklą |
| **Rezervacijos tvirtinimas** | Pateikus rezervaciją | Rezervacijų administravimo paskirties turėtojams; nebaigta peržiūra perduodama dabartiniams nariams | Jei tvirtinimo eigoje nustatytas | Priėmus sprendimą. Plačiau – [Rezervacijos](/rezervacijos/rezervacijos#pranesimai) |
| **Atsiimti / Grąžinti rezervacijos išteklius** | Patvirtinus / išdavus išteklių | Rezervacijos teikėjams | Atsiėmimo / grąžinimo laikas | Atsiėmus / grąžinus visus išteklius |

Jei institucijos kadencijai paskirti sekretoriai, posėdžio ir periodiškumo užduotys skiriamos
jiems. Kitu atveju – atstovams, kurie ėjo pareigas posėdžio dieną.

Institucijai be užregistruotos veiklos periodiškumo užduotis kuriama artėjant pirmojo dabartinio
gavėjo terminui, skaičiuojamam nuo dienos, kai jis pradėjo eiti pareigas. Laiškas siunčiamas
tik praėjus kiekvieno gavėjo terminui, ne vos sukūrus užduotį, ir ne dažniau kaip kartą per mėnesį
apie tą pačią instituciją. Atostogų dienos neskaičiuojamos. Kol periodiškumo užduotis atvira, jos
atsakingieji kasdien atnaujinami pagal dabartinius institucijos atstovus ar sekretorius.

### Atšauktos užduotys {#atsauktos}

Periodiškumo užduotis **atšaukiama**, kai ji nebeaktuali, nors niekas jos neatliko:

- prasidėjo nauja kadencija, institucija posėdžių dar neturėjo, o naujiems atstovams terminas dar nesuėjo;
- institucija pažymėta kaip neveikianti.

Atšaukta užduotis dingsta iš neatliktų, joje rodoma žymė **Atšaukta**, o atstovų rodikliuose ji
neskaičiuojama nei kaip atlikta, nei kaip neatlikta. Apie atšaukimą niekam nepranešama, o jos
neatsakyti klausimai „Ar vyko posėdis?“ nebegalioja. Kai naujiems atstovams terminas artės, bus
sukurta nauja užduotis – jau jiems.

Pavyzdys ir bendros rankinių bei automatinių užklausų sąlygos – [Institucijų gide](/visak/institucijos#paklausti).

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

### Veiksmai su užduotimi {#veiksmai-su-uzduotimi}

| Veiksmas | Kada rodomas |
|---|---|
| **Pranešti apie veiklą** | Neatlikta periodiškumo užduotis: registruojamas posėdis arba pranešimas, kad posėdžio nebuvo |
| **Pridėti darbotvarkės klausimą** / **Peržiūrėti darbotvarkę** | Neatlikta darbotvarkės užduotis: nuoroda į posėdžio darbotvarkę |
| **Pažymėti atlikta** / **Grąžinti į neatliktas** | Tik rankinė užduotis |
| **Ištrinti** | Tik jei turi teisę ją ištrinti |

Paspaudus pavadinimą, atveriama užduotis: instrukcijos, terminas ir atsakingi, o būseną rodo ženklo
spalva. Eilučių vaizde ir lentelėje užduotį atlikta pažymi varnelė, pagrindinis veiksmas – laukiantis žingsnis arba **Atidaryti** – rodomas
prie užduoties, o telefone jis yra meniu **⋯** viršuje, kartu su kitais veiksmais. Peržiūros vaizde veiksmai rodomi dešinėje, o rodyklėmis ↑ ir ↓ pereinama prie kitos užduoties. Varnelę ir **Pažymėti atlikta** mato tik tie, kas gali užduotį keisti: kiti ją
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
| Kai užduotį priskiria žmogus | Atsakingi | Pranešimą „Nauja užduotis“. Automatinės užduotys (posėdžio darbotvarkė, išteklių atsiėmimas ir grąžinimas, patvirtinimai, veiklos klausimai) sukuriamos be šio pranešimo – apie jas praneša tai, kas jas sukūrė (pvz., [naujas posėdis](/pagrindai/pranesimai#auditorija) ar rezervacijos būsena) |
| Kasdien 8.00, likus 3 d. arba, jei taip pasirinkta, 1 d. iki termino | Atsakingi | Priminimą. Priminimų pasirinkimas valdomas [pranešimų nustatymuose](/pagrindai/pranesimai). Užduočiai „Pranešti apie veiklą“ jų nesiunčiama – vietoj to vieną kartą siunčiamas laiškas [„Ar vyko posėdis?“](/visak/institucijos#pranesimai) |
| Pirmadieniais 14.30, jei per savaitę atsirado naujų vėluojančių užduočių arba po ankstesnio priminimo praėjo 4 savaitės | Žmonės, turintys vėluojančių užduočių | Priminimą apie vėluojančias užduotis, kuris numatytai įtraukiamas į pranešimų suvestinę |

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
- Periodiškumo užduotis kuria `tasks:repopulate institution` (kasdien 10.00). Atšaukimas –
  `BaseTaskHandler::cancel()`: nustatomi ir `completed_at`, ir `cancelled_at`, todėl visos atvirų
  užduočių užklausos jos nebemato, o `RepStatisticsScope::tasks()` ją praleidžia. Priminimus
  siunčia `TaskNotifier::notifyDaysLeft` (išskyrus `periodicity_gap`) ir
  `notifications:task-overdue-reminders` gavėjams iš `Task::notifiableUsers()` (`ResolveTaskAudience`).
