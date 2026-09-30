---
doc_status: reviewed
title: Atsakomybės
area: duties
models: [Duty, DutyResponsibility]
last_reviewed: 2026-09-30
tests:
  - tests/Feature/Responsibilities/ResponsibilityResolverTest.php
  - tests/Feature/Admin/People/DutyResponsibilityControllerTest.php
  - tests/Feature/Migrations/BackfillStudentRepCoordinationResponsibilitiesTest.php
  - tests/Feature/Admin/Management/InstitutionCheckInTest.php
  - tests/Browser/DutyResponsibilitiesLayoutTest.php
---

# Atsakomybės

**Pareigybės atsakomybė** nurodo, kokį darbą atlieka jos nariai. **Rolė** nurodo, ką jie gali
daryti platformoje. Pareigybės puslapio skirtuke **Atsakomybės** šie du dalykai rodomi greta.

Kol kas galima priskirti **studentų atstovų koordinavimą**. Koordinatorius yra dabar pareigybę
einantis žmogus; pasikeitus žmogui, atsakomybė lieka pareigybei. Ji taikoma tik VU organams.

<DocScreenshot name="duty-responsibilities" alt="Pareigybės skirtukas Atsakomybės: studentų atstovų koordinavimas visam padaliniui ir šalia – pareigybės rolės" caption="Atsakomybės ir rolės pareigybės puslapyje greta." />

## Kaip tai veikia

### Kaip nustatomas koordinatorius {#koordinatorius}

Atsakomybę galima priskirti vienam **VU organui**, jo **tipui** arba visam **padaliniui**. Ieškant
koordinatoriaus pirmiausia tikrinamas organas, tada jo tipas ir aukštesni tipai, galiausiai
padalinys. Artimesnis priskyrimas laimi; tame pačiame lygmenyje gali būti kelios atsakingos
pareigybės. Tuščia artimesnė pareigybė vis tiek užima tą lygmenį, todėl kol ji neturi nario,
koordinatorius nerodomas.

Institucijos puslapyje parašyta, iš kur parinktas matomas koordinatorius. **Organizacijos
apžvalga** parodo padalinius, kuriuose niekas šiuo metu neina visam padaliniui koordinuoti
priskirtų pareigų.

## Veiksmai

Pareigybės puslapio skirtuke **Atsakomybės** spausk **Pridėti atsakomybę** ir pasirink, kam ji
taikoma: VU organui, jo tipui ar visam padaliniui. **Pašalinti** nuima atsakomybę. Pakeitimai
įrašomi į veiklos istoriją.

## Kas ką gali {#teises}

**Komunikacijos koordinatorius** ir **Studentų atstovų koordinatorius** gali pridėti ar nuimti
atsakomybę pareigybei, kurią jiems leidžiama redaguoti. Padalinį ar organą jie gali pasirinkti tik
savo tvarkomoje srityje; tipą – tik jeigu gali jį redaguoti. Super administratorius gali tvarkyti
visas pareigybes.

Atsakomybė pati nesuteikia bendros prieigos prie institucijų. Koordinatorius gauna su konkrečiu
organu susijusius studentų atstovų registracijos ir posėdžių pranešimus, gali tvarkyti to organo
veiklos pranešimus ir mato atitinkamas registracijas. Kitą prieigą suteikia rolės ir jų teisės.

## Techninė informacija {#technine-informacija}

- Atsakomybės yra `duty_responsibilities`; jas skaito `ResponsibilityResolver`.
- Priskyrimą riboja pareigybės `update` patikra ir tikslui taikoma padalinio ar tipo teisė.
- Buvusi koordinatorių rolės parinktis atstovavimo nustatymuose pašalinta; buvusios rolės
  pareigybėms perkėlimo metu priskirtas padalinio koordinavimas.
