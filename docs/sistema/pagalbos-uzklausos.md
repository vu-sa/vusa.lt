---
doc_status: reviewed
title: Pagalbos užklausos
area: supportRequests
models: [SupportRequest, SupportRequestArea, SupportRequestType]
last_reviewed: 2026-10-02
tests:
  - tests/Feature/Admin/SupportRequests/SupportRequestControllerTest.php
  - tests/Feature/Admin/SupportRequests/MySupportRequestControllerTest.php
  - resources/js/Pages/Admin/Dashboard/__tests__/ShowSupportRequests.component.test.ts
---

# Pagalbos užklausos

Skiltis **Pagalbos užklausos** (`/mano/support-requests`) skirta pranešimams apie platformos klaidas, veiklos sutrikimus, patobulinimų idėjoms ir pagalbos prašymams administruoti.

Kiekvienas prisijungęs narys gali užregistruoti problemą, o platformos administratoriai šioje skiltyje peržiūri užklausų eilę, keičia jų būsenas ir paskiria atsakingus asmenis.

## Kaip tai veikia

Užklausos pateikiamos per formą `/mano/my-support-requests/create`. Užklausą sudaro pavadinimas, detalus aprašymas, pasirinktas tipas, veiklos sritis, matomumo lygis ir prisegtos ekrano nuotraukos. Gali pridėti iki penkių JPEG, PNG arba WebP vaizdų, kiekvieną iki 10 MB.

### Matomumo lygiai

Kuriant užklausą nurodoma, kas ją gali matyti:

- **Privatu** – užklausą mato jos autorius, užklausas administruojantys nariai ir paskirtas atsakingas asmuo. Tai tinkamiausias pasirinkimas pranešant apie asmenines problemas ar klaidų atvejus.
- **Pasirinktoms rolėms** – užklausa matoma nariams, turintiems pasirinktas roles tiesiogiai arba per galiojančias pareigybes, bei administratoriams. Autorius gali pasirinkti tik tas roles, kurias pats turi tiesiogiai arba per galiojančias pareigybes.
- **Visiems prisijungusiems** – užklausą mato visi prie Mano VU SA prisijungę nariai. Tinka bendriems patobulinimų pasiūlymams ir diskusijoms.

### Būsenų ciklas

Užklausos sprendimo eigą rodo šios būsenos:

1. **Naujas** – užklausa užregistruota ir laukia administratoriaus peržiūros. Šioje būsenoje autorius dar gali redaguoti savo užklausos tekstą.
2. **Peržiūrima** – administratorius tikrina problemą ar vertina pasiūlymą.
3. **Suplanuota** – klaidos pataisymas ar patobulinimas įtrauktas į darbų planą.
4. **Vykdoma** – atliekami programavimo ar konfigūracijos darbai.
5. **Išspręsta** – problema pašalinta arba patobulinimas įdiegtas (galutinė būsena, užfiksuojama sprendimo data).
6. **Atmesta** – pasiūlymas ar prašymas atmestas (galutinė būsena). Rekomenduojama priežastį paaiškinti komentare; sistema to nereikalauja.

### Komentarai

Užklausos puslapyje esančioje diskusijoje pateik patikslinimus ir aptark sprendimo eigą.

## Veiksmai

### Užklausos pateikimas

1. Navigacijoje pasirink **Pagalbos užklausos**, tada **Naujas pranešimas** (arba eik adresu `/mano/my-support-requests/create`).
2. Įrašyk **Pavadinimą** (trumpą problemos esmę).
3. Pasirink **Tipą** (pvz., *Klaida*, *Patobulinimas*, *Pagalba*) ir **Sritį** (pvz., *Posėdžiai*, *Rezervacijos*, *Svetainė*).
4. Pasirink **Matomumą** (*Privatu*, *Pasirinktoms rolėms* arba *Visiems prisijungusiems*).
5. Išsamiai aprašyk problemą ar pasiūlymą laukelyje **Aprašymas**.
6. Jei turi, prisek ekrano nuotraukas skiltyje **Ekrano nuotraukos ir failai**. Laukelyje **Susijęs puslapis (URL)** įrašyk puslapio adresą.
7. Išsaugok pranešimą.

### Užklausų sąrašas ir filtrai

Užklausų sąraše gali perjungti du skirtukus:

- **Visi** – visos užklausos, kurias gali matyti sąraše;
- **Mano pranešimai** – tavo pateiktos užklausos.

Filtruok pagal būseną, tipą, sritį arba atsakingą asmenį. Atskiro skirtuko „Priskirta man“ nėra.
Vien priskyrimas atsakingu asmeniu privačios užklausos į tavo sąrašą neįtraukia, nors jos puslapį
per tiesioginę nuorodą gali atverti.

### Užklausos peržiūra ir valdymas (`/mano/support-requests/{id}`)

Užklausos puslapyje:
- **Būsenos keitimas**: administratorius išskleidžiamajame meniu pasirenka naują būseną.
- **Atsakingo asmens priskyrimas**: administratorius priskiria užklausą komandos nariui arba pašalina priskyrimą.
- **Redagavimas**: autorius (kol būsena yra „Naujas“) arba administratorius paspaudžia **Redaguoti**, kad pataisytų pavadinimą, aprašymą ar matomumą.
- **Šalinimas**: užklausas administruojantis narys veiksmų meniu gali pašalinti užklausą. Šiame meniu atkūrimo veiksmo nėra.
- **Komentavimas**: puslapio apačioje esančiame lauke parašyk komentarą ir paspausk **Komentuoti**.

## Kas ką gali {#teises}

| Veiksmas | Bet kuris narys | Užklausos autorius | Administratorius | Super Admin |
|---|---|---|---|---|
| Sukurti pagalbos užklausą | ✓ | ✓ | ✓ | ✓ |
| Matyti savo užklausą | – | ✓ | ✓ | ✓ |
| Matyti viešą ar savo rolės užklausą | ✓ | ✓ | ✓ | ✓ |
| Redaguoti užklausos tekstą | – | ✓ (tik būsenoje „Naujas“) | ✓ | ✓ |
| Matyti visas užklausas (eilę) | – | – | ✓ | ✓ |
| Keisti būseną ir priskirti asmenį | – | – | ✓ | ✓ |
| Šalinti užklausą | – | – | ✓ | ✓ |

Čia administratoriumi vadinamas narys, kuriam suteikta visos platformos pagalbos užklausų peržiūros arba keitimo teisė. Rolė **Super Admin** taip pat leidžia administruoti užklausas. Atsakingas asmuo gali peržiūrėti privačią užklausą, bet negali jos redaguoti ar administruoti.

## Pranešimai ir automatizavimas {#pranesimai}

- **Būsenos pasikeitimo pranešimas**: administratoriui pakeitus užklausos būseną, autorius automatiškai gauna pranešimą sistemoje su nuoroda į užklausą.
- **Sprendimo laiko fiksavimas**: nustačius būseną „Išspręsta“ arba „Atmesta“, automatiškai užfiksuojamas sprendimo laikas. Užklausą atvėrus pakartotinai, sprendimo laikas išvalomas.
- **Šalinimas**: pašalinta užklausa saugoma duomenų bazėje; jos atkūrimo maršrutas skirtas administravimui ir sąraše nepateikiamas.

## Rekomendacijos {#susitarimai}

- Registruodamas klaidą, pateik kuo tikslesnę informaciją: kas įvyko, kokiame puslapyje (URL), kokius žingsnius atlikai ir prisek ekrano nuotrauką.
- Parinktį „Visiems prisijungusiems“ naudok tik tiems patobulinimams, kuriuose nėra jautrių asmens ar organizacijos duomenų.
- Užbaigdamas ar atmesdamas užklausą, komentare trumpai paaiškink priimtą sprendimą.

## Techninė informacija {#technine-informacija}

- Valdikliai:
  - `SupportRequestController`: administravimo maršrutai `supportRequests.*` (`show`, `edit`, `update`, `destroy`, `restore`, `updateStatus`, `assign`).
  - `MySupportRequestController`: naudotojo maršrutai `mySupportRequests.*` (`index`, `create`, `store`).
- Prieigos politika: `SupportRequestPolicy` (`isManager` reikalauja `supportRequests.read.*` arba `supportRequests.update.*`).
- Būsenos ir matomumas: `SupportRequestStatus`, `SupportRequestVisibility`.
- Modeliai: `SupportRequest` (palaiko `SoftDeletes`, `LogsActivity`, `InteractsWithMedia`), `SupportRequestType`, `SupportRequestArea`, `SupportService`.
- Pranešimas: `SupportRequestStatusChangedNotification`.
