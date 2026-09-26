---
title: Organizacija
coverage: ignore
---

# Organizacija

Darbo sritis **Organizacija** skirta žmonėms ir struktūrai: nariams, pareigybėms, padaliniams,
studijų programoms, formoms ir registracijoms. Čia keičiasi, **kas** eina kokias pareigas, o nuo to
priklauso ir jų teisės platformoje.

## Kas mato šią sritį

| Skiltis | Adresas | Kas mato |
|---|---|---|
| Apžvalga | `/mano/dashboard/organizacija` | Visi, kurie mato bent vieną kitą skiltį |
| Nariai | `/mano/users` | Turintys teisę matyti naudotojus |
| Pareigybės | `/mano/duties` | Turintys teisę matyti pareigybes |
| Pareigybių atnaujinimas | `/mano/duties-update-users` | Turintys teisę kurti pareigybes |
| Padaliniai | `/mano/tenants` | Turintys teisę matyti padalinius |
| Studijų programos | `/mano/studyPrograms` | Turintys teisę matyti studijų programas |
| Narių / studentų atstovų registracija | `/mano/forms/{id}` | Galintys peržiūrėti atitinkamą formą (nustatoma nustatymuose) |
| Formos | `/mano/forms` | Turintys teisę matyti formas |

Mygtukas **+ Sukurti** šioje srityje siūlo **Pareigybių atnaujinimas** ir **Pareigybių
laikotarpiai**.

::: tip Svarbu administratoriams
Pakeitus pareigybės narius, pasikeičia ir jų teisės, nes teisės ateina per pareigybių roles
(žr. [Teisės ir rolės](/pagrindai/teises)). Baigus kadenciją, būtinai atnaujink pareigybes, kitaip
buvę nariai išlaikys prieigą.
:::
