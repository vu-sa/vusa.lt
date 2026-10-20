---
doc_status: reviewed
title: Teisės ir atsakomybės
last_reviewed: 2026-10-21
tests:
  - tests/Feature/Permissions/MeetingPermissionsTest.php
  - tests/Feature/Admin/Search/SearchVisibilityParityTest.php
  - tests/Feature/Permissions/BaselineAccessTest.php
  - resources/js/Features/Admin/PermissionMatrix/__tests__/PermissionMatrix.component.test.ts
  - tests/Feature/Admin/Permissions/ResponsibilityControllerTest.php
  - tests/Feature/System/PermissionCatalogTest.php
  - tests/Feature/Permissions/RoleChangeRevokesAccessTest.php
  - tests/Feature/System/ModelPermissionSeederTest.php
---

# Teisės ir atsakomybės

Teisės suteikia galimybę matyti ir tvarkyti tam tikrus įrašus. Jos niekada neskiriamos žmogui
tiesiogiai kasdieniame darbe: teisės sudedamos į [atsakomybes](/pagrindai/atsakomybes),
atsakomybės suteikiamos **pareigybėms**, o narys teises gauna eidamas pareigybę.

## Ką gali kiekvienas narys {#bazine-prieiga}

<ChangelogNote version="v3.0" date="2026-10-02" title="Rolė prideda prieigą prie bendro pagrindo">

Vieša institucijų ir posėdžių informacija, problemos ir ištekliai pasiekiami be papildomos rolės. Savo pareigų metu vykusių posėdžių prieiga išlieka ir pareigoms pasibaigus; tai nesuteikia prieigos prie vėlesnių neviešų posėdžių.

</ChangelogNote>

Kai kas nepriklauso nuo atsakomybių: tai gali kiekvienas prisijungęs narys, net neturėdamas nė
vienos. Atsakomybės prideda tik tai, kas viršija šį pagrindą. Atsakomybės puslapyje
(**Sistema → Atsakomybės**) prie kiekvienos srities parašyta, ką visi nariai jau gali, o tokios
teisės pažymėtos užraktu **Visi nariai**: jų priskirti negalima, nes jos nieko nepridėtų.

| Įrašai | Ką gali kiekvienas narys |
|---|---|
| Institucijos | Matyti sąrašą ir aktyvių institucijų viešą informaciją; savo ir su jomis susietas institucijas – pilnai |
| Posėdžiai ir klausimai | Matyti viešus posėdžius; savo institucijų posėdžius, vykusius tavo pareigų metu, matyti ir keisti – ir pasibaigus pareigoms. Posėdžio klausimus kuria, redaguoja ir trina ir turintys posėdžių redagavimo teisę (pvz., studentų atstovai savo institucijos posėdžiuose) |
| Problemos | Matyti visų padalinių problemas |
| Ištekliai | Matyti visus išteklius |
| Pareigybės | Matyti savo dabartines ir buvusias pareigybes bei kadencijas |
| Užduotys | Matyti ir atlikti tau priskirtas užduotis (bet ne jas ištrinti; savų užduočių trynimo teisė nė vienai atsakomybei nepriskiriama) |
| Komentarai | Komentuoti visur, kur matai įrašą; savo komentarą redaguoti 15 min. po parašymo, o ištrinti – bet kada. Kitų komentarus trina redaguojantys įrašą arba atsakomybė su komentarų trynimo teise |
| Rezervacijos | Kurti rezervacijas; matyti ir keisti tas, kuriose dalyvauji. Savo rezervaciją ištrinti, kol nė vienas jos daiktas dar nepatvirtintas |
| Nariai | Matyti ir redaguoti save (**Profilis**); savęs ištrinti negalima |

Posėdžiams svarbu atskirti peržiūrą nuo redagavimo: nepasibaigusių pareigybių institucijų,
ryšiais pasiekiamų ir sekretoriumi prižiūrimų institucijų posėdžius galima matyti be atskiros
atsakomybės. Automatiškai redaguojami tik posėdžiai, kurių data patenka į **tavo pareigų laikotarpį**.
Papildoma apimtis **Savo institucijų, visų kadencijų** leidžia redaguoti ir kitų laikotarpių posėdžius, bet tik
teisę suteikiančių pareigybių institucijose. Plačiau – [Posėdžių teisės](/visak/posedziai#teises).

## Atsakomybės {#roles}

Kasdieniame darbe užtenka žinoti atsakomybes. Dažniausios (pavadinimai kol kas tokie, kokie buvo
rolių):

| Atsakomybė | Kam skiriama | Ką leidžia | Aprašyta |
|---|---|---|---|
| **Studentų atstovas** | Automatiškai – pareigybėms su tipu „Studentų atstovas“ | Fiksuoti savo institucijų posėdžius, kelti problemas | [ViSAK](/visak/) |
| **Problemų redaktorius** | Automatiškai – pareigybėms su tipu „Koordinatorius (-ė)“ | Kelti ir tvarkyti padalinio problemas | [Problemos](/visak/problemos) |
| **Išteklių administratorius** | Padalinio pirmininkui ir administratoriui | Tvarkyti padalinio daiktus ir jų rezervacijas | [Rezervacijos](/rezervacijos/#istekliu-administratorius) |
| **Padalinio puslapių redaktorius** | Tiems, kas atnaujina padalinio svetainės tekstus | Redaguoti esamus padalinio puslapius | [Puslapiai](/svetaine/puslapiai) |

Atsakomybės, kurių pavadinime yra **„Centrinio biuro“**, leidžia tą patį visuose padaliniuose.

### Atsakomybės pagal pareigybės tipą {#roles-pagal-pareigybes-tipa}

Atsakomybė gali būti suteikta **pareigybės tipui**. Tada ją automatiškai gauna kiekviena to tipo
pareigybė: visos esamos ir kiekviena, kuriai tipas priskiriamas vėliau. Nuėmus tipą, atsakomybė
nuimama, nebent ji suteikta pareigybei ir tiesiogiai.

::: warning Kas gali priskirti tipą
Pareigybė atsakomybę per tipą gauna tik tada, kai tipą jai priskiria žmogus, kurio atsakomybė leidžia
tą tipą priskirti (pvz., koordinatorius – studentų atstovų tipą), arba super administratorius. Tai
apsaugo nuo teisių išsidalijimo per tipus.
:::

## Kaip sistema nusprendžia {#kaip-sistema-nusprendzia}

1. **Super administratorius** gali viską.
   Kiekvienam nariui galioja ir [bazinė prieiga](#bazine-prieiga).
2. Tikrinamos tiesiogiai naudotojui priskirtos teisės.
3. Tikrinamos teisės, gautos per pareigybių atsakomybes.
4. Pagal apimtį nustatoma, kuriems padaliniams ar įrašams teisė galioja.

Jei teisės neturi, ji **neišplečiama**: sistema nerodo nieko, o ne „bent jau savo padalinio“.

Pakeitus atsakomybės teises, jos suteikimus ar pareigybės tipus, nauja prieiga galioja nuo kito
puslapio atvėrimo – taip pat ir atimta.

::: info Kodėl kai kurių puslapių nematai
Skiltys ir mygtukai, kuriems neturi teisių, paslepiami. Tiesiogiai atidarius tokį adresą, rodomas
puslapis „Prieiga uždrausta“.
:::

Kiekviename puslapyje skyrius **Kas ką gali** aprašo, kurios atsakomybės ką leidžia.

## Techninė informacija {#technine-informacija}

### Teisės formatas {#teises-formatas}

Atsakomybės sudarytos iš **teisių**. Tikslios kiekvieno puslapio teisės pateikiamos jo pabaigoje, skyriuje **Techninė informacija**. Kiekviena teisė užrašoma `{išteklius}.{veiksmas}.{apimtis}`, pavyzdžiui, `news.update.padalinys`.

| Dalis | Reikšmės |
|---|---|
| Išteklius | Įrašų rūšis daugiskaita: `news`, `meetings`, `resources`… |
| Veiksmas | `read`, `create`, `update`, `delete`, `forceDelete` |
| Apimtis | `own` – tik su tavo pareigybe tiesiogiai susiję įrašai; `padalinys` – tavo padalinio įrašai; `*` – visi įrašai |


- Bazinę prieigą be atsakomybių užtikrina `BaselineAccess` taisyklės ir `ModelPolicy` patikros, testuojamos `BaselineAccessTest`.
- Visus galimus leidimus aprašo `PermissionCatalog`; jį naudoja `ModelPermissionSeeder`, teisių tinklelis ir `permissions:audit`.
- Atsakomybių teises ir suteikimus valdo `ResponsibilityController`, tikrina `ResponsibilityControllerTest` ir `PermissionMatrix.component.test.ts`.
- Be modelių leidimų, `settings.update.*` leidžia keisti nustatymus.
