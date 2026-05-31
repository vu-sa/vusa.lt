---
title: Platformos atnaujinimai
lastUpdated: true
---

# Platformos atnaujinimai

Čia rasite visus mano.vusa.lt platformos pakeitimus ir patobulinimus.

## v1.11 — Pritaikoma šoninė juosta ir neseniai aplankyti puslapiai (2026-06-...) {#v1-11}

### Šoninė juosta

- ⭐ **Pritaikoma šoninė juosta** — paskyros meniu atsirado pasirinkimas „Pritaikyti šoninę juostą", kur galima paslėpti ar rodyti pasirinktas sekcijas (greitus veiksmus, sekamas institucijas, START FM, pagalbą, neseniai aplankytus) ir pakeisti jų tvarką. Logotipas, paskyros meniu ir pagrindinė navigacija visada matomi. Pasirinkimai išsaugomi paskyroje ir veikia visose naršyklėse
- ⭐ **Neseniai aplankyti puslapiai** — šoninėje juostoje ir komandų lange (Cmd/Ctrl+K, prieš paiešką) rodomi paskutiniai lankyti administravimo puslapiai
- ⭐ **Prisegti puslapiai** — administravimo puslapį gali prisegti (žvaigždute ties neseniai aplankytais arba komandų lange), kad jis liktų atskiroje „Prisegti" sekcijoje šoninėje juostoje; pasirinkimai išsaugomi paskyroje
- ✨ **Kompaktiškas vaizdas** — „Pritaikyti šoninę juostą" lange galima įjungti kompaktišką režimą, kuris sumažina tarpus šoninėje juostoje

### Kita

- ⭐ **Dokumentų peržiūra naršyklėje** — dokumentų nuorodose pridėtas `?web=1` parametras, kad jie atsidarytų tiesiai naršyklėje, o ne būtų bandoma atidaryti programėlėje
- 🔧 **Mobilios navigacijos uždarymas** — paspaudus nuorodą mobilioje versijoje, šoninis meniu automatiškai užsidaro
- 🔧 Failų trynimo pataisymas SharePoint aplinkoje
- 🔧 **Pataisyta filtrų išvalymo funkcija dokumentų paieškoje** — „Išvalyti filtrus" mygtukas dabar tinkamai išvalo visus filtrus ir parodo visus dokumentus
- ✨ **Supaprastinti datos intervalo filtrai** — pašalinti besidubliuojantys „3 mėn." ir „6 mėn." pasirinkimai; paliktas numatytasis „Neseniai" (3 mėn.), „1 metai", „Metų intervalas" ir „Pasirinkti datą"
- ✨ **Matomas paieškos mygtukas administravimo lentelėse** — visose turinio lentelėse šalia paieškos laukelio pridėtas mygtukas „Ieškoti"; taip pat pagerintas filtrų išdėstymas

> 🔗 [GitHub PR #569](https://github.com/vu-sa/vusa.lt/pull/569)

## v1.10 — Ex-officio pareigos ir atstovai iš kitų padalinių (2026-05-12) {#v1-10}

- ⭐ **Ex-officio pareigos** — pareigybės redagavimo lange galima nurodyti pareigas, kurios automatiškai suteikiamos kartu su šia (pvz. pirmininkavimas suteikia vietą dekanate). Susietų pareigybių datos sinchronizuojamos su pirmine, o pasibaigus pirminei — pažymima pabaiga
- ⭐ **Atstovai iš kitų padalinių** — pareigybei (kuri priklauso vienam padaliniui) galima leisti, kad narius į ją skirtų ir kiti padaliniai, kiekvienam nustatant kvotą. Tokios pareigybės matomos pareigybių sąraše (galima filtruoti) ir narių vedlyje; tų padalinių administratoriai gali tvarkyti tik savo padalinio atstovus ir tik neviršydami kvotos
- ✨ **Aktyvūs naudotojai narių sąraše** — pareigybės redagavimo formoje narių perdavimo sąraše pagal nutylėjimą rodomi tik per paskutinius 12 mėn. aktyvūs naudotojai (užima arba turėjo pareigybę, neseniai prisijungė arba yra naujas paskyra); visi kiti pasiekiami perjungus „Rodyti visus naudotojus"

> 🔗 [GitHub PR #568](https://github.com/vu-sa/vusa.lt/pull/568)

## v1.9 — Susitikimų ir administravimo patobulinimai (2026-05-06) {#v1-9}

- ⭐ **Bendri institucijų posėdžiai** — susitikimus dabar galima susieti su keliomis institucijomis, o ne tik su viena
- ✨ **Patobulintos turinio lentelės** — pagerinta datų rodymo, žymų sąraš7 formatavimas, sutrauktas tekstas ir nuorodos visoje administravimo panelėje
- ✨ **Greitųjų nuorodų formos optimizavimas** — patobulinta greitųjų nuorodų kūrimo ir valdymo sąsaja
- ✨ **Aiškesnis el. pašto valdymas naudotojų formose** — naudotojų formose aiškiau paaiškinami el. pašto laukai
- ✨ **Susitikimų rodymo patobulinimai** — atnaujintas susitikimų detalaus rodinio išdėstymas
- 🔧 **Pataisytas 23:59 rodymas susitikimuose** — susitikimai be konkrečios pabaigos laiko daugiau neberodo „23:59" el. laiškuose ir rodiniuose
- 🔧 **Pataisytos ir optimizuotos formos** — pagerintas didelių išskleidžiamųjų sąrašų veikimas keliose administravimo formose

> 🔗 [GitHub PR #566](https://github.com/vu-sa/vusa.lt/pull/566)

## v1.8 — Studijų komplektai (2026-05-05) {#v1-8}

- ⭐ **[Studijų komplektų puslapis](https://www.vusa.lt/ind-komplektai)** — viešas puslapis, kuriame galima naršyti studijų komplektus pagal fakultetą, su dalykų sąrašais ir dėstytojų atsiliepimais
- ✨ **Paieška ir filtravimas** — galima ieškoti pagal dalyko ar studijų komplekto pavadinimą, filtruoti pagal semestrą ir fakultetą
- ⭐ **Studijų komplektų valdymas administratoriams** — galima kurti ir valdyti studijų komplektus, dalykus ir atsiliepimus

> 🔗 [GitHub PR #565](https://github.com/vu-sa/vusa.lt/pull/565)

## v1.7 — Pranešimų patobulinimai (2026-04-06) {#v1-7}

- 🔧 **Pataisytas trigubas el. pašto pranešimų siuntimas** — kiekvienas pranešimas buvo tris kartus įtraukiamas į el. pašto santraukos eilę, dėl ko santraukos laiškuose buvo rodomas trigubai didesnis pranešimų skaičius.
- 🔧 **Pataisytas lietuviškas pranešimų tekstas** — kai kurių pranešimų pavadinime buvo rodomas neišverstas daugiskaitos formatas vietoj tinkamo lietuviško teksto.
- ✨ **Perskaitytų pranešimų sinchronizacija su el. pašto santrauka** — pažymėjus pranešimą kaip perskaitytą platformoje, jis nebepateks į būsimą el. pašto santrauką.

> 🔗 [GitHub PR #554](https://github.com/vu-sa/vusa.lt/pull/554)

## v1.6 — Įvairūs patobulinimai (2026-04-06) {#v1-6}

- 🔧 **Pataisytas puslapių veikimas su tuščiu turiniu** — puslapiuose su tuščiu turinio bloku daugiau nebus rodomas klaidos pranešimas.
- 🔧 **Pataisytos _Table of Contents_ nuorodų paspaudimas** — paspaudus ant antraščių rodyklėje, puslapyje bus parodoma atitinkama antraštė.
- 🔧 **Pašalintas problemų pavadinimo ilgio apribojimas**.
- 🔧 Sutvarkyta serverio klaida, pasitaikanti einant į **angliškus renginių puslapius be dedikuotų pavadinimų** per specifinę nuorodą.
- 🔧 **Failų įkėlimo klaidos taisymas** — failų įkėlimo komponentas naudojo neteisingą nuorodą leistiniems failų tipams gauti, dėl ko kildavo serverio klaida.
- 🔧 **Rezervacijų formos pataisymai**
  - Jeigu pradžios laikas nustatomas vėliau negu pabaigos laikas, gražiau pateikiamas įspėjimas.
  - Pakeitus datą daugiau nebepasirodo „neišsaugotų pakeitimų" perspėjimas.
  - Gražiau pateikti ir greičiau veikiantis resursų pasirinkimas.
  - Paspaudus „Pateikti" daugiau nebepasirodo „neišsaugotų pakeitimų" perspėjimas.

> 🔗 [GitHub PR #553](https://github.com/vu-sa/vusa.lt/pull/553)

## v1.5 — Kalendoriaus ir susitikimų patobulinimai (2026-04-03) {#v1-5}

- 🔧 **Praėję renginiai paslėpti viešame kalendoriuje** — mobilioje versijoje rodomi tik paspaudus „Rodyti ankstesnius"
- 🔧 **Šiandienos susitikimai rodomi skydeliuose** — pagrindiniame ir atstovavimo skydelyje dabar rodomi šiandienos susitikimai, net jie jų konkretus pradžios laikas jau praėjęs

> 🔗 [GitHub PR #550](https://github.com/vu-sa/vusa.lt/pull/550)

## v1.4 — Dokumentacijos atnaujinimas (2026-03-31) {#v1-4}

- ⭐ **Atnaujinimų puslapis** — dokumentacijoje sukurtas atnaujinimų puslapis, kuriame pristatomi platformos pakeitimai. Esant atnaujinimams, admin panelėje prie „Dokumentacija" nuorodos matysite indikatorių

> 🔗 [GitHub PR #546](https://github.com/vu-sa/vusa.lt/pull/546)

## v1.3 — Dokumentų patobulinimai (2026-03-23) {#v1-3}

- ⭐ **[Dokumentų](https://www.vusa.lt/dokumentai) veiksmai** — dokumentų sąraše dabar yra atidarymo, atsisiuntimo ir nuorodos kopijavimo mygtukai
- ✨ **Patikimesnis dokumentų įkėlimas** administratoriams

> 🔗 [GitHub PR #542](https://github.com/vu-sa/vusa.lt/pull/542)

## v1.2 — Teksto laukelis turinyje (2026-03-12) {#v1-2}

- ⭐ **Teksto laukelio blokas** — naujas turinio blokas, leidžiantis lankytojams pateikti atsakymus tiesiai puslapyje. Atsakymus galima peržiūrėti ir eksportuoti į Excel. Šiuo metu naudojamas [vusa.lt tvarumo skiltyje](https://vusa.lt/tvarumas/)

> 🔗 [GitHub PR #532](https://github.com/vu-sa/vusa.lt/pull/532)

## v1.1 — Problemų sekimas (2026-03-10) {#v1-1}

- ⭐ **Problemų valdymas** — nauja skiltis, kurioje galima registruoti, sekti ir valdyti problemas, susijusias su padaliniu
- ⭐ **Susiejimas su institucijomis** — problemas galima susieti su konkrečiomis institucijomis

> 🔗 [GitHub PR #531](https://github.com/vu-sa/vusa.lt/pull/531)

## v1.0 — Platformos modernizacija (2026-02-07) {#v1-0}

> 📰 Pilnas aprašymas: [mano.vusa.lt v1.0: Platformos modernizacija](/blog/2026-02-07-v1-modernization)

> 🔗 [GitHub PR #504](https://github.com/vu-sa/vusa.lt/pull/504)
