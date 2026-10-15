---
doc_status: reviewed
title: Pagalbos užklausos
area: supportRequests
models: [SupportRequest, SupportRequestArea, SupportRequestType]
last_reviewed: 2026-10-06
tests:
  - tests/Feature/Admin/SupportRequests/SupportRequestControllerTest.php
  - tests/Feature/Admin/SupportRequests/MySupportRequestControllerTest.php
  - tests/Feature/Admin/Discussions/CommentApiTest.php
  - resources/js/Pages/Admin/Dashboard/__tests__/ShowSupportRequests.component.test.ts
---

# Pagalbos užklausos

Skiltis **Pagalbos užklausos** (`/mano/support-requests`) skirta pranešimams apie platformos klaidas, veiklos sutrikimus, patobulinimų idėjoms ir pagalbos prašymams administruoti.

Kiekvienas prisijungęs narys gali užregistruoti problemą, o platformos administratoriai šioje skiltyje peržiūri užklausų eilę, keičia jų būsenas ir paskiria atsakingus asmenis.

## Rekomendacijos {#rekomendacijos}

::: tip
- Registruojant klaidą verta pateikti kuo tikslesnę informaciją: kas įvyko, kokiame puslapyje (URL), kokie žingsniai atlikti, ir prisegti ekrano nuotrauką.
- Parinktį „Visiems prisijungusiems“ rekomenduojama naudoti tik tiems patobulinimams, kuriuose nėra jautrių asmens ar organizacijos duomenų.
- Užbaigiant ar atmetant užklausą, komentare pravartu trumpai paaiškinti priimtą sprendimą.
:::

## Kaip tai veikia

Užklausos pateikiamos per formą `/mano/my-support-requests/create`. Užklausą sudaro pavadinimas, detalus aprašymas, pasirinktas tipas, veiklos sritis, matomumo lygis ir prisegtos ekrano nuotraukos. Galima pridėti iki penkių JPEG, PNG arba WebP vaizdų, kiekvieną iki 10 MB.

### Matomumo lygiai

Kuriant užklausą nurodoma, kas ją gali matyti. Pasirinktas matomumas rodomas užklausų sąraše ir užklausos puslapyje (rolėms skirtos užklausos – su rolių pavadinimais).

- **Privatu** – užklausą mato jos autorius, užklausas administruojantys nariai ir paskirtas atsakingas asmuo. Tai tinkamiausias pasirinkimas pranešant apie asmenines problemas ar klaidų atvejus.
- **Pasirinktoms rolėms** – užklausa matoma nariams, turintiems pasirinktas roles tiesiogiai arba per galiojančias pareigybes, bei administratoriams. Autorius gali pasirinkti tik tas roles, kurias pats turi tiesiogiai arba per galiojančias pareigybes.
- **Visiems prisijungusiems** – užklausą mato visi prie Mano VU SA prisijungę nariai. Tinka bendriems patobulinimų pasiūlymams ir diskusijoms.

### Susiję žmonės {#susije-zmones}

Jei problema aktuali keliems nariams, kuriant užklausą galima pridėti **susijusius žmones** – bet kuriuos platformos narius. Jie mato užklausą (net privačią), gali ją komentuoti ir gauna pranešimus apie naujus komentarus. Vėliau sąrašą gali keisti administratorius. Atsakingas asmuo vis tiek yra vienas.

### Būsenų ciklas

Užklausos sprendimo eigą rodo šios būsenos:

1. **Naujas** – užklausa užregistruota ir laukia administratoriaus peržiūros. Šioje būsenoje autorius dar gali redaguoti savo užklausos tekstą.
2. **Peržiūrima** – administratorius tikrina problemą ar vertina pasiūlymą.
3. **Suplanuota** – klaidos pataisymas ar patobulinimas įtrauktas į darbų planą.
4. **Vykdoma** – atliekami programavimo ar konfigūracijos darbai.
5. **Išspręsta** – problema pašalinta arba patobulinimas įdiegtas (galutinė būsena, užfiksuojama sprendimo data).
6. **Atmesta** – pasiūlymas ar prašymas atmestas (galutinė būsena). Rekomenduojama priežastį paaiškinti komentare; sistema to nereikalauja.

### Komentarai

Užklausos puslapyje esančioje diskusijoje galima pateikti patikslinimus ir aptarti sprendimo eigą. Įrašius `@`, galima paminėti užklausos autorių, atsakingą asmenį, susijusius žmones ir, jei užklausa matoma rolėms, tų rolių narius.

## Veiksmai

### Užklausos pateikimas

1. Navigacijoje pasirink **Pagalbos užklausos**, tada **Naujas pranešimas** (arba eik adresu `/mano/my-support-requests/create`).
2. Įrašyk **Pavadinimą** (trumpą problemos esmę).
3. Pasirink **Tipą** (pvz., *Klaida*, *Patobulinimas*, *Pagalba*) ir **Sritį** (pvz., *Posėdžiai*, *Rezervacijos*, *Svetainė*).
4. Pasirink **Matomumą** (*Privatu*, *Pasirinktoms rolėms* arba *Visiems prisijungusiems*) ir, jei reikia, pridėk **Susijusius žmones**.
5. Išsamiai aprašyk problemą ar pasiūlymą laukelyje **Aprašymas**.
6. Jei turi, prisek ekrano nuotraukas skiltyje **Ekrano nuotraukos ir failai**. Laukelyje **Susijęs puslapis (URL)** įrašyk puslapio adresą.
7. Išsaugok pranešimą.

### Užklausų sąrašas ir filtrai

Užklausų sąraše gali perjungti du skirtukus:

- **Visi** – visos užklausos, kurias gali matyti sąraše;
- **Mano pranešimai** – tavo pateiktos užklausos ir tos, prie kurių esi pridėtas kaip susijęs žmogus.

Filtruok pagal būseną, tipą, sritį arba atsakingą asmenį. Atskiro skirtuko „Priskirta man“ nėra.
Vien priskyrimas atsakingu asmeniu privačios užklausos į tavo sąrašą neįtraukia, nors jos puslapį
per tiesioginę nuorodą gali atverti.

### Užklausos peržiūra ir valdymas (`/mano/support-requests/{id}`)

Užklausos puslapyje:
- **Būsenos keitimas**: administratorius išskleidžiamajame meniu pasirenka naują būseną.
- **Atsakingo asmens priskyrimas**: administratorius priskiria užklausą komandos nariui arba pašalina priskyrimą.
- **Susiję žmonės**: administratorius mygtuku **Susiję žmonės** prideda arba pašalina susijusius žmones.
- **Redagavimas**: autorius (kol būsena yra „Naujas“) arba administratorius paspaudžia **Redaguoti**, kad pataisytų pavadinimą, aprašymą ar matomumą.
- **Šalinimas**: užklausas administruojantis narys veiksmų meniu gali pašalinti užklausą. Šiame meniu atkūrimo veiksmo nėra.
- **Komentavimas**: puslapio apačioje esančiame lauke parašyk komentarą ir paspausk **Komentuoti**.

## Kas ką gali {#teises}

| Veiksmas | Bet kuris narys | Užklausos autorius | Administratorius | Super Admin |
|---|---|---|---|---|
| Sukurti pagalbos užklausą | ✓ | ✓ | ✓ | ✓ |
| Matyti savo užklausą | – | ✓ | ✓ | ✓ |
| Matyti viešą ar savo rolės užklausą | ✓ | ✓ | ✓ | ✓ |
| Matyti užklausą, prie kurios esi pridėtas | ✓ | ✓ | ✓ | ✓ |
| Pridėti susijusius žmones kuriant užklausą | ✓ | ✓ | ✓ | ✓ |
| Keisti susijusius žmones vėliau | – | – | ✓ | ✓ |
| Redaguoti užklausos tekstą | – | ✓ (tik būsenoje „Naujas“) | ✓ | ✓ |
| Matyti visas užklausas (eilę) | – | – | ✓ | ✓ |
| Keisti būseną ir priskirti asmenį | – | – | ✓ | ✓ |
| Šalinti užklausą | – | – | ✓ | ✓ |

Čia administratoriumi vadinamas narys, kuriam suteikta visos platformos pagalbos užklausų peržiūros arba keitimo teisė. Rolė **Super Admin** taip pat leidžia administruoti užklausas. Atsakingas asmuo gali peržiūrėti privačią užklausą, bet negali jos redaguoti ar administruoti.

## Pranešimai ir automatizavimas {#pranesimai}

- **Nauja užklausa**: pateikus užklausą, pranešimą gauna platformos administratoriai (Super Admin), nepriklausomai nuo matomumo. Visiems prisijungusiems nariams apie viešas užklausas nepranešama.
- **Būsenos pasikeitimas**: administratoriui pakeitus užklausos būseną, pranešimą gauna autorius ir susiję žmonės (išskyrus tą, kuris būseną pakeitė).
- **Priskyrimas ir pridėjimas**: naujai priskirtas atsakingas asmuo ir pridėtas susijęs žmogus gauna po pranešimą su nuoroda į užklausą. Administratorius, jau gavęs pranešimą apie naują užklausą, antro negauna.
- Kaip gauti kiekvieną iš šių pranešimų (laiškas iškart, suvestinėje ar be laiško), pasirenkama **Pranešimų nustatymuose**, skiltyje *Sistema*.
- **Komentarų pranešimai**: apie naują komentarą sužino autorius, atsakingas asmuo ir susiję žmonės; rolių nariai – tik kai jie paminimi.
- **Sprendimo laiko fiksavimas**: nustačius būseną „Išspręsta“ arba „Atmesta“, automatiškai užfiksuojamas sprendimo laikas. Užklausą atvėrus pakartotinai, sprendimo laikas išvalomas.
- **Šalinimas**: pašalinta užklausa saugoma duomenų bazėje; jos atkūrimo maršrutas skirtas administravimui ir sąraše nepateikiamas.

## Techninė informacija {#technine-informacija}

- Valdikliai:
  - `SupportRequestController`: administravimo maršrutai `supportRequests.*` (`show`, `edit`, `update`, `destroy`, `restore`, `updateStatus`, `assign`, `syncInvolvedUsers`).
  - `MySupportRequestController`: naudotojo maršrutai `mySupportRequests.*` (`index`, `create`, `store`).
- Prieigos politika: `SupportRequestPolicy` (`isManager` reikalauja `supportRequests.read.*` arba `supportRequests.update.*`).
- Būsenos ir matomumas: `SupportRequestStatus`, `SupportRequestVisibility`.
- Modeliai: `SupportRequest` (palaiko `SoftDeletes`, `LogsActivity`, `InteractsWithMedia`), `SupportRequestType`, `SupportRequestArea`, `SupportService`.
- Pranešimai: `SupportRequestCreatedNotification`, `SupportRequestAssignedNotification`, `SupportRequestInvolvedNotification`, `SupportRequestStatusChangedNotification`; gavėjus administratorius grąžina `SupportRequest::managers()`.
- Susiję žmonės: ryšys `SupportRequest::involvedUsers()` (lentelė `support_request_user`); komentarų adresatus ir paminėjimus sprendžia `CommentableMentionResolver`.
