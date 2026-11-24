---
doc_status: reviewed
last_reviewed: 2026-11-22
title: Pirmas pakeitimas
---

# Pirmas pakeitimas

Šis puslapis veda per vieną pakeitimą nuo atšakos iki pull request. Prieš pradedant projektas jau turi
veikti lokaliai: tai aprašo [README](https://github.com/vu-sa/vusa.lt/blob/dev/README.md#set-up-locally)
(`./dev/setup.sh`, hosts failas, sertifikatas, prisijungimas `test@test.com` / `password`).

## 1. Pasirink, ką keisti

Geriausia pradėti nuo mažo dalyko: klaidos, kurią gali atkartoti, teksto pataisymo ar trūkstamo testo.
Atviros užduotys yra [GitHub issues](https://github.com/vu-sa/vusa.lt/issues). Naują funkciją pirmiausia
aptark issue ar su prižiūrėtojais, kad darbas nenueitų veltui.

## 2. Susikurk atšaką nuo `dev`

Visas darbas eina į `dev` atšaką, kuri diegiama į bandomąją aplinką. `main` – produkcija, ji atnaujinama
tik per leidimus iš `dev`.

```bash
git fetch origin
git switch -c fix/trumpas-pavadinimas origin/dev
```

Jei repozitorijoje neturi rašymo teisių, pirmiausia ją fork'ink ir atšaką kurk savo kopijoje. Kai
reikia kelių atšakų vienu metu, `./dev/worktree.sh add <pavadinimas>` sukuria atskirą git worktree
`.worktrees/` aplanke ir jį paruošia (`.env`, priklausomybės, `public/build`).

## 3. Perskaityk taisykles failams, kuriuos keisi

Bendros taisyklės surašytos [`AGENTS.md`](https://github.com/vu-sa/vusa.lt/blob/dev/AGENTS.md).
Konkrečioms vietoms skirtos taisyklės yra `.ai/rules/`: atsidaryk
[`.ai/rules/index.md`](https://github.com/vu-sa/vusa.lt/blob/dev/.ai/rules/index.md), surask eilutes,
kurių šablonai apima tavo failus, ir perskaityk tas taisykles. Kai kuriuose aplankuose dar yra
`CLAUDE.md` su išsamesniu vadovu (pvz., `tests/CLAUDE.md`, `resources/js/Components/CLAUDE.md`).
Tas pačias taisykles skaito ir AI agentai, todėl kodas atrodo vienodai, kad ir kas jį rašytų.

## 4. Patikrink pakeitimą

```bash
./dev/check-changed.sh
```

Komanda tikrina tik failus, kuriuos tavo atšaka pakeitė nuo `origin/dev`:

- PHP failus formatuoja Pint ir tikrina PHPMD;
- JS ir Vue failus tikrina ESLint ir TypeScript ratchet, paleidžia su jais susijusius Vitest testus;
- Pest testus paleidžia per Test Impact Analysis, todėl vyksta tik tie testai, kuriuos pakeitimas paveikė. Worktree'e TIA neveikia, ten paleidžiami tik pakeisti testų failai.

Visas testų rinkinys paleidžiamas CI, kai atidarai pull request.

## 5. Kada pakeitimas baigtas

- Elgsenai, verslo taisyklei, saugumo ribai ar tikėtinai pasikartosiančiai klaidai yra testas.
  Tekstui, stiliui ir nebenaudojamo kodo šalinimui testo nereikia.
- Naudotojui matomas tekstas yra ir lietuviškai, ir angliškai.
- Naudotojui matomas pakeitimas turi įrašą `docs/changelog/v3.md` ir `docs/en/changelog/v3.md`,
  o atitinkamo skyriaus gido puslapis atnaujintas.
- Sąsajos pakeitimas patikrintas telefono pločiu ir abiejose temose.
- `./dev/check-changed.sh` praeina.

## 6. Atidaryk pull request į `dev`

Įkelk atšaką (`git push -u origin fix/trumpas-pavadinimas`) ir atidaryk pull request į `dev`. Šablonas
primena tuos pačius punktus. Trumpai aprašyk, kas pasikeitė ir kodėl, ir nurodyk issue, kurį
pakeitimas uždaro. Peržiūra ir CI parodys, ko dar trūksta.
