---
title: Problemos
area: problems
models: [Problem]
---

# Problemos

Problemos – iššūkiai, kliūtys ar klausimai, su kuriais susiduria studentai ar atstovai: studijų
proceso, komunikacijos, išteklių ar kitos srities. Registruojant problemas kuriama bendra sprendimų
žinių bazė, žiniomis dalijamasi su kitais padaliniais ir ta pati problema nesprendžiama du kartus.

1. Užregistruok problemą, kai ji atsiranda (**+ Sukurti → Nauja problema**).
2. Pridėk detalių.
3. Kai problema išspręsta, užregistruok sprendimą.
4. Filtruok ir peržiūrėk problemas pagal padalinį, būseną ir kategoriją.

Skiltis pasiekiama adresu `/mano/problems`.

::: warning Rašoma
Šis puslapis dar rašomas. Kai bus baigtas, jame bus skyriai **Kaip tai veikia**, **Veiksmai**,
**Kas ką gali** ir **Pranešimai ir automatika**, kaip [Rezervacijų](/rezervacijos/rezervacijos) skyriuje.
:::

## Kas ką gali

Problemas kelia ir tvarko **studentų atstovai** ir **koordinatoriai**. Koordinatoriai rolę
**Problemų redaktorius** gauna automatiškai pagal pareigybės tipą „Koordinatorius (-ė)“ (žr.
[Rolės pagal pareigybės tipą](/pagrindai/teises#roles-pagal-pareigybes-tipa)): jie mato visų
padalinių problemas ir tvarko savo padalinio.

## Techninė informacija {#technine-informacija}

### Teisės

- Problemų redaktorius: `problems.create.padalinys`, `problems.read.*`, `problems.update.padalinys`.
