---
doc_status: reviewed
title: Atsakomybės
area: duties
models: [Duty, Role, ResponsibilityGrant]
last_reviewed: 2026-10-19
tests:
  - tests/Feature/Responsibilities/ResponsibilityResolverTest.php
  - tests/Feature/Responsibilities/ProjectDutyResponsibilitiesTest.php
  - tests/Feature/Admin/People/DutyResponsibilityControllerTest.php
  - tests/Feature/Admin/Management/InstitutionCheckInTest.php
  - resources/js/Features/Admin/Responsibilities/__tests__/DutyResponsibilitiesSection.component.test.ts
---

# Atsakomybės

**Pareigybė turi atsakomybes; atsakomybė lemia, ką darai, ką matai platformoje ir kas tave randa.**

Atsakomybė nusako pareigybės darbą (pvz., „Komunikacija“, „Organizacijos valdymas“) ir kartu
suteikia tam darbui reikalingas [teises](/pagrindai/teises). Pasikeitus žmogui, atsakomybė lieka
pareigybei. Atsakomybės kuriamos ir jų teisės nustatomos skiltyje
[Atsakomybės ir leidimai](/sistema/roles-ir-leidimai).

<ChangelogNote version="v3.10" date="2026-10-19" title="Rolės ir atsakomybės sujungtos">

Atskirų rolių nebėra: kiekviena buvusi rolė tapo atsakomybe su tomis pačiomis teisėmis. Pareigybės
skirtuke **Atsakomybės** matyti visos jos atsakomybės, ir tos, kurias ji gauna per tipą ar
instituciją.

</ChangelogNote>

## Kaip tai veikia

### Iš kur pareigybė gauna atsakomybes

1. **Tiesiogiai** – suteikta pačiai pareigybei.
2. **Per pareigybės tipą** – pvz., tipas „Studentų atstovai“ suteikia studentų atstovavimą.
3. **Per instituciją** – retai, kai ją turi gauti kiekviena institucijos pareigybė.

### Sistemos paskirtys {#paskirtys}

Kelios atsakomybės dar nurodo sistemai, ką rasti:

| Paskirtis | Ką lemia |
|---|---|
| Studentų atstovavimas | Kas laikomas studentų atstovu: rodomas posėdžiuose, gauna užduotis, kai institucija neturi sekretoriaus, ir veiklos užklausas. |
| Studentų atstovų koordinavimas | Į ką kreipiasi VU organų studentų atstovai, kas gauna jų registracijas ir posėdžių pranešimus. |
| Narių registracija | Kas gauna ir mato naujų narių registracijas savo padalinyje. |

Kuri atsakomybė atlieka kurią paskirtį, nustatoma
[Sistemos paskirtyse](/sistema/nustatymai#sistemos-paskirtys). Plati prieiga (pvz., „Organizacijos
valdymas“) nepadaro nario nei koordinatoriumi, nei registracijų gavėju – tam reikia būtent tos
paskirties atsakomybės.

### Kaip nustatomas koordinatorius {#koordinatorius}

Koordinavimo atsakomybė suteikiama nurodant, už ką pareigybė atsakinga: vieną **VU organą**, jo
**tipą** arba visą **padalinį**. Ieškant koordinatoriaus pirmiausia tikrinamas organas, tada jo
tipas ir aukštesni tipai, galiausiai padalinys. Artimesnis priskyrimas laimi; tame pačiame lygmenyje
gali būti kelios atsakingos pareigybės. Tuščia artimesnė pareigybė vis tiek užima tą lygmenį, todėl
kol ji neturi nario, koordinatorius nerodomas.

Institucijos puslapyje parašyta, iš kur parinktas matomas koordinatorius. **Organizacijos apžvalga**
parodo padalinius, kuriuose niekas šiuo metu neina visam padaliniui koordinuoti priskirtų pareigų.

### Teisių trūkumai

Paskirtis pati teisių nesuteikia. Jei koordinavimo atsakomybę turinčiai pareigybei per jos
atsakomybes trūksta teisių matyti ir redaguoti padalinio institucijas, posėdžius ar narius,
pareigybės ir atsakomybės puslapiuose rodomas įspėjimas.

## Veiksmai

Pareigybės puslapio skirtuke **Atsakomybės** spausk **Pridėti atsakomybę**, pasirink atsakomybę ir,
jei ji turi paskirtį, nurodyk, už ką pareigybė atsakinga. **Pašalinti** nuima tiesiogiai suteiktą
atsakomybę; per tipą ar instituciją gautą atsakomybę keisk atitinkamame tipe ar atsakomybės puslapyje.

## Kas ką gali {#teises}

Pareigybę redaguojantis koordinatorius (pvz., **Komunikacijos koordinatorius**) gali suteikti tik
tas atsakomybes, kurias turi pats, ir tik savo tvarkomos srities pareigybėms. Paskirties apimtį
(padalinį ar organą) jis gali rinktis tik savo srityje; tipą – jei gali jį redaguoti. Atsakomybes
valdantys administratoriai ir super administratorius gali suteikti bet kurią atsakomybę.

## Techninė informacija {#technine-informacija}

- Atsakomybės – `App\Models\Role` (`roles`), suteikimai – `responsibility_grants`; paskirtis –
  `ResponsibilityPurpose`, apimtis – `ResponsibilityScope`.
- Paskirties turėtojus randa `ResponsibilityResolver`; teisių trūkumus – `ResponsibilityPermissionGaps`.
- Ankstesnė `duty_responsibilities` lentelė perkelta į suteikimus ir paliekama tik skaitymui.
