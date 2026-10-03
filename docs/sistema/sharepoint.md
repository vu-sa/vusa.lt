---
doc_status: reviewed
title: SharePoint integracija
last_reviewed: 2026-10-03
tests:
  - tests/Feature/SharepointStagingProtectionTest.php
  - tests/Feature/SyncFileableFilesJobTest.php
  - tests/Feature/Listeners/UpdateSharepointFolderTest.php
---

# SharePoint integracija

Platforma nesaugo dokumentų savo serveryje – jie laikomi VU SA „Microsoft 365“ SharePoint. Mano
VU SA per „Microsoft Graph“ sąsają juos įkelia, atveria ir susieja su platformos įrašais. Bendros
SharePoint failų naršyklės platformoje nėra: aplankus tiesiogiai tvarko VU SA „Microsoft“
paskyrą turintys žmonės pačiame SharePoint.

## Kaip tai veikia

SharePoint naudojamas dviem tikslais:

| Kas | Kur aprašyta | Ar rodoma viešai |
|---|---|---|
| Įrašų failai – posėdžių, institucijų, pareigybių ir jų tipų skirtukas **Failai** | [Įrašų failai](/visak/failai) | Niekada |
| Dokumentų archyvas – nuostatai, nutarimai, protokolai ir kiti skelbiami dokumentai | [Dokumentai](/visak/dokumentai) | Taip, per vusa.lt dokumentų paiešką ir viešus posėdžius |

Kiekvienas įrašas turi savo aplanką `General` medyje. Pervadinus instituciją, pareigybę ar tipą,
platforma pervadina ir aplanką, todėl aplankų ranka nepervadink ir neperkelk.

### Apsauga ne gamybinėse aplinkose

Bandomojoje (*staging*) aplinkoje failų keitimas, šalinimas ir nuorodų kūrimas ar atšaukimas
blokuojamas, kai įjungtas skaitymo režimas arba pasirinkta neleistina svetainė ar gamybinis
diskas. Veiksmas nėra imituojamas ir automatiškai nenukreipiamas į kitą diską.

## Pranešimai ir automatizavimas {#pranesimai}

- Įrašų failai kartą per savaitę sutikrinami su SharePoint: atnaujinami jų duomenys, o
  SharePoint nebesantys failai pašalinami iš sąrašų.
- Dokumentų archyvo sinchronizavimas aprašytas puslapyje [Dokumentai](/visak/dokumentai).

## Susitarimai {#susitarimai}

- Netrink ir nepervadink šakninių aplankų (`General`, `Padaliniai`, padalinių santrumpų), nes
  pagal juos sudaromi įrašų aplankai.

## Techninė informacija {#technine-informacija}

- Graph sąsaja: `SharepointGraphService` (`microsoft/microsoft-graph`), diskas –
  `filesystems.sharepoint.vusa_drive_id`, archyvas – `archive_drive_id`.
- Įrašų failai: `SharepointFileService`, `FileableFile`; aplankų pervadinimas –
  `UpdateSharepointFolder`; sinchronizavimas – `SyncFileableFilesJob`.
- Apsauga: `StagingProtection::ensureSharepointIsWritable()`; platformos užklausas atskirai riboja
  `StagingReadOnlyMode`.
