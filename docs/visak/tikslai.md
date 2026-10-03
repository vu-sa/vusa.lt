---
doc_status: reviewed
title: Tikslai (bandomoji funkcija)
area: goals
models: [Goal, Step]
last_reviewed: 2026-09-30
tests:
  - tests/Feature/Admin/Goals/GoalControllerTest.php
  - tests/Feature/Public/GoalsTest.php
  - tests/Feature/System/AdminNavigationCatalogTest.php
  - resources/js/Features/Admin/Goals/__tests__/StepSheetForm.component.test.ts
---

# Tikslai <Badge type="warning" text="Bandomoji funkcija" />

::: warning Bandomoji funkcija
Tikslus kol kas bando tik keli padaliniai. Kitiems padaliniams skiltis **Tikslai** nerodoma, o
jos adresai neatsidaro. Funkcija dar keisis – kas veikia ir ko trūksta, pranešk IT. Tai, kas
aprašyta čia, gali būti pakeista ar išimta be atskiro įspėjimo.
:::

**Tikslas** – tai, ko padalinys siekia per kadenciją, pvz., „Atnaujinti bendrabučių tvarkos
aprašą“. **Veiksmas** – tai, kas tam padaryta: posėdis, raštas, susitikimas ar sprendimas.

Tikslai ir [problemos](/visak/problemos) skiriasi: problema kyla „iš apačios“, kai kažkas
nutiko, o tikslas planuojamas metų pradžioje. Jie susitinka per veiksmus: vienas tikslas gali
spręsti kelias problemas, o veiksmas, užrašytas prie problemos, gali būti skaičiuojamas ir
tikslui.

Tikslų sąrašas pasiekiamas adresu `/mano/goals`, o kiekvienas tikslas turi savo puslapį
`/mano/goals/{id}`.

## Kaip tai veikia

Bandymas įjungiamas kiekvienam padaliniui atskirai. Jo nariai mato visų įjungtų padalinių
tikslus, o kurti ir keisti gali tik ten, kur turi tam teises. Išjungto padalinio tikslai
paslepiami, bet neištrinami. Problemos veiksmų skiltis rodoma tik įjungto padalinio problemoms.

<ChangelogNote version="v3.0" date="2026-10-02" title="Tikslų bandymas pagal padalinį">

IT bandymą įjungia kiekvienam padaliniui atskirai. Meniu ir puslapiai pasikeičia per kitą
užklausą; išjungus bandymą, tikslai ir veiksmai išlieka.

</ChangelogNote>

### Ką sudaro tikslas

| Laukas | Privaloma | Pastabos |
|---|---|---|
| Pavadinimas | Taip, bent viena kalba | Iki 255 simbolių |
| Padalinys | Taip | Tik tas, kuriame gali kurti tikslus |
| Būsena | Taip | Naujas tikslas – **Planuojamas** |
| Kadencija | Ne | Numatytoji – dabartinė |
| Laukiamas rezultatas | Ne | Iš ko suprasi, kad tikslas pasiektas; vienas ar du sakiniai |
| Aprašymas | Ne | Plačiau apie tikslą |
| Atsakinga pareigybė | Ne | Tik to paties padalinio pareigybė |
| Rodomas vusa.lt | Ne | Žr. [Viešas puslapis](#viesas) |
| Metų įvertinimas | Ne | Pildomas redaguojant, dažniausiai metų pabaigoje |

Atsakinga yra **pareigybė**, o ne žmogus: pasikeitus pareigas einančiam nariui, tikslas lieka
priskirtas tai pačiai pareigybei. Tikslo puslapyje prie pareigybės rodomi ją dabar einantys
nariai.

### Būsenos {#busenos}

| Būsena | Reiškia |
|---|---|
| **Planuojamas** | Tikslas užsibrėžtas, darbas dar neprasidėjo |
| **Vykdomas** | Tikslo siekiama |
| **Pasiektas** | Tikslas pasiektas |
| **Nepasiektas** | Kadencija baigėsi, tikslas nepasiektas |
| **Atsisakyta** | Nuspręsta tikslo nebesiekti |

### Tikslo veiksmai {#tikslo-veiksmai}

Veiksmas – vienas įrašas apie tai, kas padaryta. Jį sudaro:

| Laukas | Privaloma | Pastabos |
|---|---|---|
| Kas padaryta | Taip, bent viena kalba | Trumpas aprašas, pvz., „Pateiktas raštas dekanui“ |
| Data | Taip | Kada tai įvyko |
| Atliko | Ne | Vienas ar keli nariai. Naujam veiksmui iš anksto parinktas tas, kas jį užrašo |
| Plačiau | Ne | Laisvas tekstas |
| Susiję su | Ne | [Darbotvarkės klausimas](/visak/darbotvarkes-klausimai) arba [dokumentas](/visak/dokumentai) |
| Nuoroda | Ne | Bet kuri `https://` nuoroda, pvz., į protokolą ar straipsnį |

Prie kiekvieno veiksmo rodoma, kas jį **atliko** ir kas **užrašė** (jei tai ne tas pats žmogus).
Susieti galima tik su tuo darbotvarkės klausimu ar dokumentu, kurį pats gali atidaryti. Kolega,
kuris susieto įrašo matyti negali, mato „Įrašas, kurio negali matyti“, bet vis tiek gali taisyti
kitus veiksmo laukus – ryšys nedingsta.

Veiksmas visada priklauso tikslui, problemai arba abiem:

- Užrašytas **tikslo** puslapyje, veiksmas gali būti skaičiuojamas ir vienai su tikslu susietai
  problemai.
- Užrašytas **problemos** puslapyje, veiksmas gali būti skaičiuojamas ir vienam su problema
  susietam tikslui.
- Susieti veiksmą galima tik su tuo tikslu ar problema, kurie jau susieti tarpusavyje (žr.
  [Susieti problemą](#susieti)).

Veiksmai rodomi nuo naujausio. Ištrintas veiksmas dingsta ir iš tikslo, ir iš problemos.

### Darbotvarkės klausimai {#darbotvarkes-klausimai}

Darbotvarkės klausimo puslapyje, skiltyje **Susiję tikslai**, klausimą galima **pridėti prie
tikslo**. Tada tikslui automatiškai sukuriamas veiksmas: klausimo pavadinimas, posėdžio data ir
nuoroda į klausimą. Taip tikslo istorija pildosi iš posėdžių, o ne tik ranka.

- Pridėti galima tik prie tų tikslų, kuriuos gali redaguoti; klausimą reikia galėti atidaryti.
- Tą patį klausimą prie to paties tikslo galima pridėti tik kartą.
- Mygtukas **Atsieti** ištrina tą veiksmą.
- Darbotvarkės klausimą susieti su veiksmu galima ir ranka, lauke **Susiję su**.

### Problemos puslapyje {#problemos-puslapyje}

Bandomuosiuose padaliniuose problemos skirtuke **Atlikti žingsniai** rodomi **veiksmai** – tie
patys įrašai kaip tiksluose, su data, atlikusiais nariais ir nuorodomis. Anksčiau įrašytas laisvas
tekstas „Atlikti žingsniai“ lieka viršuje kaip santrauka. Skirtukas **Sprendžiama per tikslus**
rodo, kokie tikslai sprendžia šią problemą. Kitiems padaliniams problemos puslapis atrodo kaip
įprastai.

### Viešas puslapis {#viesas}

Pažymėjus **Rodomas vusa.lt**, tikslas atsiranda padalinio svetainėje adresu
`{padalinys}.vusa.lt/lt/tikslai` (angliškai – `/en/goals`). Tikslai sugrupuoti pagal kadenciją,
naujausia – viršuje. Kiekvienas tikslas turi savo puslapį.

| Viešai rodoma | Viešai nerodoma |
|---|---|
| Pavadinimas, būsena, kadencija, laukiamas rezultatas, aprašymas | Susietos problemos |
| Atsakingos pareigybės pavadinimas | Pareigybę einančių narių vardai |
| Metų įvertinimas – kai tikslas **Pasiektas**, **Nepasiektas** arba **Atsisakyta** | Kas ir kada tikslą sukūrė |
| Visi tikslo veiksmai su data ir aprašu | Kas veiksmą atliko ir užrašė |
| Veiksmo nuoroda; posėdis – jei posėdžio puslapis viešas; dokumentas – jei jis viešai pasiekiamas | Darbotvarkės klausimai ir dokumentai, kurių lankytojas negali atidaryti |

::: warning Veiksmai viešame tikslo puslapyje matomi visi
Viešai rodomi **visi** tikslo veiksmai, taip pat ir tie, kurie užrašyti problemos puslapyje ir
skaičiuojami tikslui. Prieš pažymėdamas tikslą viešu, peržiūrėk jo veiksmus.
:::

Viešų tikslų sąrašo svetainės meniu kol kas neturi – nuorodą į jį padalinys prideda pats,
pvz., [navigacijoje](/svetaine/navigacija) ar puslapyje. Viešas puslapis veikia tik bandomuosiuose
padaliniuose.

## Veiksmai

### Sukurti tikslą

Spausk **+ Sukurti → Naujas tikslas** arba sąrašo mygtuką **Naujas tikslas**. Jei gali kurti
tikslus tik viename padalinyje, jis parenkamas automatiškai.

### Užrašyti veiksmą

- Tikslo puslapyje spausk **Pridėti veiksmą**.
- Problemos puslapyje atsidaryk skirtuką **Atlikti žingsniai** ir spausk **Pridėti veiksmą**.
- Darbotvarkės klausimo puslapyje spausk **Pridėti prie tikslo** (žr.
  [Darbotvarkės klausimai](#darbotvarkes-klausimai)).

Veiksmą pataisyti ar ištrinti gali iš meniu **⋯** prie jo.

### Susieti problemą {#susieti}

Tikslo puslapio skirtuke **Problemos** spausk **Susieti problemą** ir pasirink problemą. Galima
susieti **bet kurio padalinio** problemą – pvz., kai keli padaliniai sprendžia tą pačią
bendrabučių problemą. Mygtukas **Atsieti** panaikina ryšį, bet veiksmų neištrina.

### Įvertinti tikslą

Kadencijos pabaigoje redaguok tikslą: pakeisk būseną ir užpildyk **Metų įvertinimą** – kas
pavyko, kas ne ir kodėl. Tai padeda kitai kadencijai perimti darbą.

### Ištrinti tikslą

Tikslo puslapyje **⋯ → Ištrinti tikslą** arba formos apačioje. Tikslas ištrinamas **visam
laikui** kartu su jo veiksmais – šiukšlinės tikslai neturi. Susietos problemos lieka, bet
veiksmai, kurie buvo skaičiuojami ir tikslui, ištrinami.

### Filtruoti ir ieškoti

Sąraše galima ieškoti pagal pavadinimą, padalinį ar atsakingą pareigybę ir filtruoti pagal
būseną, padalinį ir kadenciją.

## Kas ką gali {#teises}

| Veiksmas | Bandomojo padalinio narys | Turintys tikslų teises padalinyje | Super administratorius |
|---|---|---|---|
| Matyti įjungtų padalinių tikslus | ✓ | ✓ | ✓ |
| Kurti, redaguoti, ištrinti tikslą | – | ✓, savo padalinyje | ✓ |
| Susieti ir atsieti problemas | – | ✓, savo padalinio tikslams | ✓ |
| Užrašyti veiksmą prie tikslo | – | ✓, savo padalinio tikslams | ✓ |
| Užrašyti veiksmą prie problemos | Jei gali redaguoti problemą | Jei gali redaguoti problemą | ✓ |
| Pridėti darbotvarkės klausimą prie tikslo | – | ✓, savo padalinio tikslams, jei klausimą gali atidaryti | ✓ |

Bandymo metu tikslų teisės nepriskirtos nė vienai rolei: platformos administratorius jas prideda
bandomojo padalinio rolei (žr. [Rolės ir leidimai](/sistema/roles-ir-leidimai)). Kas gali
redaguoti problemas – žr. [Problemos](/visak/problemos#teises).

Visiems, taip pat super administratoriui, ši lentelė taikoma tik įjungtiems padaliniams.

## Pranešimai ir automatizavimas {#pranesimai}

Tikslai pranešimų nesiunčia ir užduočių nekuria. Veiksmas automatiškai sukuriamas tik tada, kai
darbotvarkės klausimas pridedamas prie tikslo; kitus veiksmus užrašai pats.

Tikslo pakeitimai ir jo veiksmai matomi tikslo puslapio **⋯ → Pakeitimų istorija**; veiksmas,
užrašytas tik prie problemos, – problemos istorijoje.

## Techninė informacija {#technine-informacija}

### Įjungimas {#ijungimas}

- Bandymą valdo `App\Support\Experiments\GoalsExperiment`, kuris skaito
  `tenants.goals_enabled`. Numatytoji reikšmė – `false`; dalyvaujančius padalinius IT įjungia
  duomenų bazėje. Aplinkos kintamųjų ir bendro jungiklio nėra.
- Išjungto padalinio tikslų ir veiksmų adresai bei viešas `publicGoals.*` grąžina 404.
  Duomenys išlieka; vėl įjungus padalinį, jie pasiekiami.
- Pakeitimas taikomas per kitą užklausą, taip pat ir kešuojamam administravimo meniu.
- Narys dalyvauja bandyme, kai dabar eina pareigas bent viename įjungtame padalinyje.
  Pareigos, kurios baigiasi šiandien, dar galioja; pasibaigusios ir būsimos – ne.
- Super administratorius gali dirbti visuose įjungtuose padaliniuose be narystės, tačiau
  išjungtų padalinių tikslų taip pat nepasiekia.

### Teisės

- Skaityti: `GoalPolicy::viewAny`/`view` leidžia kiekvienam bandymo dalyviui matyti įjungtų padalinių tikslus.
- Rašyti: `goals.{create|update|delete}.{padalinys|*}`; teisės sukuriamos migracijoje
  `2026_09_28_170000_create_goals_experiment_tables`.
- Veiksmai savo teisių neturi: tikrinama `update` teisė tėviniam tikslui ar problemai; maršrutai
  naudoja `scopeBindings()`, todėl kito tikslo veiksmo pasiekti negalima.
- Problemos susiejimas tikrina tik tikslo `update`.

### Duomenys

- Lentelės `goals`, `goal_problem`, `steps`, `step_user` (kas atliko; užrašęs – `steps.created_by`);
  `steps.goal_id` ir `steps.problem_id` – abu neprivalomi, bent vienas nustatomas per užklausą.
- Veiksmo nuorodos: `steps.agenda_item_id`, `steps.document_id`, `steps.url`. Nuoroda rodoma tik
  tam, kas gali ją atidaryti (`AgendaItemPolicy::viewSummary`, `DocumentPolicy::view`;
  `StepResource`), o jos id išlieka, kad redagavimas jos neištrintų.
- Darbotvarkės klausimo pridėjimas prie tikslo – `AgendaItemGoalController`: veiksmas su
  `agenda_item_id`, data – posėdžio pradžia; tikrinama tikslo `update` ir klausimo `viewSummary`.
- Pakeitimų istorija: `goal` – `Auditables::TYPES`; `step` – `SUBJECT_TYPES`, šaknis
  (`ActivityRoots`) – tikslas, o jo nesant – problema.
- Kadencija – tik bendroji (`cadences.institution_id IS NULL`).
- Pašalinti bandymą: atšaukti migraciją (ištrina lenteles ir teises) ir kodą, pažymėtą „Goals
  pilot“.
