---
doc_status: reviewed
title: Įrašų failai
area: fileableFiles
models: [FileableFile]
last_reviewed: 2026-10-03
tests:
  - tests/Feature/Admin/FileableFileControllerTest.php
  - tests/Feature/SyncFileableFilesJobTest.php
  - tests/Unit/Models/FileableFileTest.php
  - tests/Unit/Services/Sharepoint/SharepointFileServiceTest.php
  - resources/js/Components/Files/__tests__/FileableFilesPanel.component.test.ts
  - resources/js/Components/Meetings/__tests__/MeetingPublicVisibilityDialog.component.test.ts
---

# Įrašų failai

Posėdžiai, institucijos, pareigybės ir jų tipai turi skirtuką **Failai**. Jame laikomi įrašo
darbo failai: posėdžio protokolas ir ataskaita, darbotvarkė, pristatymai, metodinė medžiaga ir
kiti dokumentai.

::: tip Failai niekada nerodomi vusa.lt
Skirtuko **Failai** failai nerodomi jokiame viešame puslapyje, net jei pats posėdis viešas.
Viešai skelbiami tik [Dokumentų](/visak/dokumentai) archyvo įrašai.
:::

## Kaip tai veikia

### Kur saugomi failai

Failai saugomi VU SA „Microsoft SharePoint“ diske, o platforma prisimena, kuriam įrašui failas
priklauso, jo tipą, datą ir dydį. Kiekvienas įrašas turi savo aplanką, pvz.,
`General/Padaliniai/<padalinys>/Institutions/<institucija>/Meetings/<data>`. Pervadinus
instituciją ar pareigybę, aplankas SharePoint pervadinamas kartu.

Posėdžio aplankas sukuriamas tik tada, kai posėdis turi instituciją su padaliniu. Jei įkėlimo
mygtuko nematai, patikrink, ar institucija priskirta padaliniui.

### Failų tipai

Įkeliant failui parenkamas tipas: **Protokolai**, **Ataskaitos**, **Darbotvarkės**,
**Pristatymai**, **Metodinė medžiaga**, **Šablonai**, **Veiklą reglamentuojantys dokumentai**
arba **Kita**. Posėdžio protokolas ir ataskaita rodomi atskirai, o jų būsena matoma posėdžio
lauke **Po posėdžio** (žr. [Posėdžiai](/visak/posedziai#protokolas)).

Institucijos ir pareigybės skirtuke **Failai** po jų pačių failais rodomi ir jų tipo failai
(**Tipo dokumentai**), pvz., visų komitetų bendri šablonai. Juos tvarko tipą redaguojantys.

### Nuoroda į failą {#nuoroda}

Dauguma atstovų neturi VU SA „Microsoft“ paskyros, todėl failas atveriamas per nuorodą, kuriai
prisijungti nereikia. Pirmą kartą atvėrus failą (ar nukopijavus jo nuorodą) platforma sukuria
tokią nuorodą ir vėliau naudoja tą pačią.

Nuoroda pati nenustoja galioti. Kiekvienas, kuriam ją persiuntei, gali atverti failą tol, kol
nuorodos neatšauksi arba failo neištrinsi. Failų sąraše niekur viešai nėra, bet nuoroda yra
tikras raktas į failą – siųsk ją tik tiems, kam failas skirtas.

### Viešas posėdis ir failai {#viesumas}

Posėdžio lauke **Matomumas** paspaudęs **Kas rodoma?** pamatysi, kas iš posėdžio rodoma vusa.lt.
Viešame posėdžio puslapyje rodoma data, institucija, būsena, darbotvarkės klausimai su balsavimo
rezultatais, tuo metu pareigas ėję atstovai ir skirtuko **Dokumentai** dokumentai, turintys viešą
nuorodą. Failai, užduotys ir komentarai nerodomi niekada.

## Veiksmai

### Įkelti failus

1. Atverk įrašo skirtuką **Failai** ir spausk **Įkelti failus** (arba nutempk failus į skirtuką).
   Posėdyje gali spausti ir trūkstamo protokolo ar ataskaitos eilutę – tipas bus parinktas.
2. Kiekvienam failui pasirink tipą ir datą, jei reikia – pakeisk pavadinimą.
3. Patvirtink. Nepavykę failai lieka lange, kad galėtum bandyti dar kartą, o įkelti nebekeliami.

### Atverti ir pasidalinti

Paspausk failo pavadinimą – failas atsidarys naujame lange. Mygtukas **Kopijuoti nuorodą**
nukopijuoja tą pačią [nuorodą](#nuoroda), kurią gali persiųsti.

### Atšaukti nuorodą {#atsaukti}

Jei nuoroda pateko ne tiems žmonėms ar failas nebeturi būti pasiekiamas buvusiems gavėjams:

1. Failo eilutėje spausk **Atšaukti nuorodą** (rodoma tik failams, kurių nuoroda jau sukurta).
2. Patvirtink. Senoji nuoroda nustoja veikti iš karto.

Kitą kartą atvėrus failą sukuriama nauja nuoroda, todėl tie, kam failas vis dar reikalingas, gaus
naują nuorodą iš tavęs.

<ChangelogNote version="v3.0" date="2026-10-02" title="Nuorodos atšaukimas">

Iki v3.0 kartą sukurta failo nuoroda veikdavo tol, kol failas būdavo ištrintas.

</ChangelogNote>

### Ištrinti failą

Failo eilutėje spausk **Ištrinti** ir patvirtink. Failas perkeliamas į SharePoint šiukšlinę, o jo
nuoroda nustoja veikti. Jei SharePoint laikinai nepasiekiamas, failas pažymimas kaip ištrintas
ir iš sąrašo dingsta.

### Atverti aplanką SharePoint

Įrašą redaguojantiems po failų sąrašu rodoma nuoroda **Atidaryti aplanką SharePoint**. Ji
veikia tik turintiems VU SA „Microsoft“ paskyrą; kitiems failai pasiekiami tik per platformą.

## Kas ką gali {#teises}

Failų teisės seka paties įrašo teises:

| Veiksmas | Kas gali |
|---|---|
| Matyti failus, juos atverti ir kopijuoti nuorodą | Kas mato visą įrašo puslapį (ne tik viešą jo pusę) |
| Įkelti, ištrinti failus ir atšaukti nuorodas | Kas gali redaguoti įrašą |

Kas mato ir redaguoja posėdžius bei institucijas, aprašyta puslapiuose
[Posėdžiai](/visak/posedziai#teises) ir [Institucijos](/visak/institucijos#teises).

## Pranešimai ir automatizavimas {#pranesimai}

- Kartą per savaitę platforma patikrina failus SharePoint: atnaujina pavadinimą, tipą ir dydį, o
  SharePoint nebesančius failus pašalina iš sąrašo.
- Jei atveriant failą paaiškėja, kad jo SharePoint nebėra, failas pažymimas ir rodomas
  pranešimas.

## Techninė informacija {#technine-informacija}

- Modelis: `FileableFile` (`fileable_files`), įrašams – `HasSharepointFiles` ir
  `SharepointFileableContract`; leidžiami tipai – `AllowedFileablesEnum`.
- Valdiklis: `FileableFileController` (`fileableFiles.store`, `open`, `publicLink`,
  `revokePublicLink`, `destroy`); teisės – `FileableFilePolicy` (perduoda įrašo `view`/`update`).
- Tarnyba: `SharepointFileService` (`uploadFile`, `publicLinkFor`, `revokePublicLink`,
  `folderUrlOrNull`); nuoroda – anoniminis SharePoint leidimas be galiojimo pabaigos, jo ID
  saugomas `public_link_permission_id`.
- Aplanko nuoroda: `filesystems.sharepoint.vusa_drive_url` (`SHAREPOINT_VUSA_DRIVE_URL`); be jos
  nuoroda nerodoma.
- Sinchronizavimas: `SyncFileableFilesJob` (pirmadieniais 03:00); aplankų pervadinimas –
  `UpdateSharepointFolder`.
- Viešumo paaiškinimas: `MeetingPublicVisibilityDialog.vue`, atitinka
  `ContactController::showMeeting()` ir `GetPublicMeetingDocuments`.
