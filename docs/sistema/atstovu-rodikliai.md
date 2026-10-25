---
doc_status: reviewed
title: Atstovų rodikliai
area: repMetrics
last_reviewed: 2026-10-25
tests:
  - tests/Feature/Api/Admin/RecordActivityStatusTest.php
  - tests/Feature/Api/Admin/RepMetricCadenceTest.php
  - resources/js/Pages/Admin/Dashboard/Components/__tests__/TenantRepMetrics.component.test.ts
---

# Atstovų rodikliai

Atstovų rodikliai pateikiami **ViSAK → Padaliniai**, po posėdžių laiko juosta, šalia dabartinių atstovų aktyvumo. Juos mato nariai, kuriems prieinami pasirinktų padalinių rodikliai. Atskiros rodiklių skilties sistemos meniu nebėra.

Skaičiavimo taisyklės ir laikotarpių paaiškinimas pateikiami [Padalinių gide](/visak/padaliniai#atstovavimo-rodikliai). Palyginimo kadencija ir tai, kurių organų (pagal nutylėjimą – VU organų) institucijos skaičiuojamos, pasirenkama [atstovavimo nustatymuose](/sistema/nustatymai#atstovavimas).

Dabartiniai rodikliai skaičiuojami tik aktyvioms institucijoms. Jau užfiksuotos ankstesnių dienų reikšmės ir istoriniai įrašai išsaugomi.

Mano VU SA: [Padaliniai](app:/mano/dashboard/atstovavimas/padaliniai).
