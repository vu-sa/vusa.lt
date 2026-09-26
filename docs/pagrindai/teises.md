---
title: Teisės ir rolės
coverage: ignore
---

# Teisės ir rolės

Teisės suteikia galimybę matyti ir tvarkyti tam tikrus įrašus. Jos niekada neskiriamos žmogui
tiesiogiai kasdieniame darbe: teisės sudedamos į **roles**, rolės priskiriamos **pareigybėms**, o
naudotojas teises gauna eidamas pareigybę.

::: warning Rašoma
Šis puslapis dar rašomas. Kai bus baigtas, jame bus skyriai **Kaip tai veikia**, **Veiksmai**,
**Kas ką gali** ir **Pranešimai ir automatika**, kaip [Rezervacijų](/rezervacijos/rezervacijos) skyriuje.
:::

## Rolės

Kasdieniame darbe užtenka žinoti roles. Kiekviena rolė – tai vienos atsakomybės teisių rinkinys:

| Rolė | Kam skiriama | Ką leidžia | Aprašyta |
|---|---|---|---|
| **Studentų atstovas** | Automatiškai – pareigybėms su tipu „Studentų atstovas“ | Fiksuoti savo institucijų posėdžius, kelti problemas | [ViSAK](/visak/) |
| **Problemų redaktorius** | Automatiškai – pareigybėms su tipu „Koordinatorius (-ė)“ | Kelti ir tvarkyti padalinio problemas, matyti visas | [Problemos](/visak/problemos) |
| **Išteklių administratorius** | Padalinio pirmininkui ir administratoriui | Tvarkyti padalinio daiktus ir jų rezervacijas | [Rezervacijos](/rezervacijos/#isteklu-administratorius) |
| **Padalinio puslapių redaktorius** | Tiems, kas atnaujina padalinio svetainės tekstus | Redaguoti esamus padalinio puslapius | [Puslapiai](/svetaine/puslapiai) |

Rolės, kurių pavadinime yra **„Centrinio biuro“**, leidžia tą patį visuose padaliniuose.

### Rolės pagal pareigybės tipą

Rolė gali būti susieta su **pareigybės tipu**. Tada ją automatiškai gauna kiekviena to tipo
pareigybė: susiejant rolę su tipu – visos esamos, vėliau – kiekviena, kuriai tipas priskiriamas.
Nuėmus tipą, rolė nuimama. Taip koordinatoriams ir studentų atstovams nereikia roles skirti ranka.

::: warning Kas gali priskirti tipą
Nauja pareigybė rolę gauna tik tada, kai tipą jai priskiria žmogus, kurio rolė leidžia tą tipą
priskirti (pvz., koordinatorius – studentų atstovų tipą), arba super administratorius. Tai
apsaugo nuo teisių išsidalijimo per tipus.
:::

## Teisės formatas

Rolės sudarytos iš **teisių**. Tikslias kiekvieno puslapio teises rasi jo pabaigoje, skyriuje **Techninė informacija**. Kiekviena teisė užrašoma `{išteklius}.{veiksmas}.{apimtis}`, pavyzdžiui, `news.update.padalinys`.

| Dalis | Reikšmės |
|---|---|
| Išteklius | Įrašų rūšis daugiskaita: `news`, `meetings`, `resources`… |
| Veiksmas | `read`, `create`, `update`, `delete`, `forceDelete` |
| Apimtis | `own` – tik su tavo pareigybe tiesiogiai susiję įrašai; `padalinys` – tavo padalinio įrašai; `*` – visi įrašai |

## Kaip sistema nusprendžia

1. **Super administratorius** gali viską.
2. Tikrinamos tiesiogiai naudotojui priskirtos teisės.
3. Tikrinamos teisės, gautos per pareigybių roles.
4. Pagal apimtį nustatoma, kuriems padaliniams ar įrašams teisė galioja.

Jei teisės neturi, ji **neišplečiama**: sistema nerodo nieko, o ne „bent jau savo padalinio“.

::: info Kodėl kai kurių puslapių nematai
Skiltys ir mygtukai, kuriems neturi teisių, paslepiami. Tiesiogiai atidarius tokį adresą, rodomas
puslapis „Prieiga uždrausta“.
:::

Kiekviename puslapyje skyrius **Kas ką gali** aprašo, kurios rolės ką leidžia.
