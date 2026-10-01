---
doc_status: reviewed
title: Greitosios nuorodos
area: quickLinks
models: [QuickLink]
last_reviewed: 2026-10-01
tests:
  - tests/Feature/Admin/Content/QuickLinkControllerTest.php
  - tests/Browser/PublicInitialRenderTest.php
  - tests/Feature/Public/PublicAssetsTest.php
  - resources/js/Components/Public/Nav/__tests__/QuickLink.component.test.ts
  - resources/js/Utils/__tests__/mountTranslatedApp.test.ts
  - resources/js/Pages/Admin/Content/__tests__/IndexQuickLink.component.test.ts
  - resources/js/Components/AdminForms/__tests__/QuickLinkForm.component.test.ts
---

# Greitosios nuorodos

Greitosios nuorodos – po pagrindiniu svetainės meniu rodoma horizontali nuorodų juosta su piktogramomis. Jos skirtos dažniausiai studentų ieškomiems puslapiams, registracijoms, kuratoriams ar kontaktams greitai pasiekti.

Skiltis pasiekiama adresu `/mano/quickLinks`.

## Kaip tai veikia

### Padalinių ir kalbų atskyrimas

- **Kiekvienas padalinys turi savo nuorodas:** pavyzdžiui, VU SA MIF greitosios nuorodos skiriasi nuo VU SA TSPMI ar bendrų VU SA nuorodų.
- **Atskiras kalbų valdymas:** lietuviškai ir angliškai svetainės versijai nuorodos nustatomos visiškai atskirai. Tai leidžia tarptautiniams studentams pateikti būtent jiems aktualią informaciją anglų kalba.
- **Svarbi nuoroda:** svarbios nuorodos gali būti išskirtos ryškesne spalva ar stiliumi.

## Rodymas svetainėje {#rodymas}

<ChangelogNote version="v2.35" date="2026-10-01" title="Meniu paruošiamas prieš pirmą rodymą" />

Nuorodas ir jų piktogramas svetainė pateikia kartu su puslapiu. Piktogramos pasirinkimo keisti nereikia. Jei piktograma dar nepalaikoma vietiniame rinkinyje, ji įkeliama atskirai; nuoroda veikia ir be jos.

Angliškame puslapyje meniu vertimai paruošiami prieš jį parodant, taip pat kai esi prisijungęs. Padalinio logotipas pradedamas įkelti iš anksto, pagal dabartinį padalinį ir puslapio kalbą.

## Rekomendacijos {#susitarimai}

- **Atranka:** pateik dažniausiai reikalingas nuorodas. Patikrink telefone, ar svarbiausius puslapius lengva rasti ir ar juosta nėra perkrauta.
- **Trumpi užrašai:** naudok aiškius pavadinimus, pvz., „Kuratoriai“, „DUK“, „Stipendijos“.
- **Piktogramos:** parink su turiniu susijusią piktogramą, tačiau pasirūpink, kad nuorodos paskirtis būtų aiški ir iš teksto.
- **Aktualumas:** peržiūrėk nuorodas pasikeitus studentams aktualiai informacijai, pavyzdžiui, prieš mokslo metų pradžią ar sesiją.

## Veiksmai

### Nuorodų sąrašas ir rikiavimas

Sąrašo puslapyje (`/mano/quickLinks`):

- **Padalinio ir kalbos pasirinkimas:** viršuje pasirink, kurio padalinio ir kurios kalbos (LT ar EN) nuorodas nori peržiūrėti.
- **Eiliškumo keitimas:** nutempk nuorodų korteles pele ar lietimu į norimą vietą ir spausk **Išsaugoti tvarką**. Nauja tvarka iškart taikoma viešoje svetainėje.

### Naujos nuorodos kūrimas

Puslapio viršuje spausk **Nauja greitoji nuoroda** (arba eik adresu `/mano/quickLinks/create`):

1. **Tekstas** – įrašyk trumpą mygtuko pavadinimą (pvz., „Kuratoriai“, „DUK“, „Stipendijos“).
2. **Nuoroda** – įvesk vidinio puslapio kelią (pvz., `/lt/kuratoriai`) arba visą išorinį adresą (pvz., `https://vilnius.lt`).
3. **Kalba** – pasirink, kuriai kalbos versijai (lietuvių ar anglų) skirta ši nuoroda.
4. **Piktograma** – išskleidžiamajame piktogramų sąraše parink labiausiai tinkančią piktogramą.
5. **Svarbi nuoroda** – pažymėk varnelę, jei nori pabrėžti šią nuorodą.
6. Spausk **Išsaugoti**.

### Redagavimas

Paspausk nuorodos pavadinimo sąraše arba veiksmų meniu pasirink **Redaguoti**. Gali pakeisti tekstą, adresą, priskirtą piktogramą ar svarbumo būseną.

### Šalinimas ir atkūrimas

- **Perkėlimas į šiukšlinę:** veiksmų meniu pasirink **Ištrinti**. Nuoroda iškart paslepiama viešoje svetainėje.
- **Šiukšlinė ir atkūrimas:** pažymėjęs **Šiukšlinė**, gali atkurti anksčiau pašalintą nuorodą arba ištrinti ją negrįžtamai (`forceDelete`).

## Kas ką gali {#teises}

| Veiksmas | Narys be rolės | Komunikacijos koordinatorius | Centrinio biuro komunikacijos koordinatorius |
|---|---|---|---|
| Matyti sąrašą | – | ✓ (savo padalinio) | ✓ (visų) |
| Kurti naują nuorodą | – | ✓ (savo padaliniui) | ✓ (visiems) |
| Keisti rikiavimo tvarką | – | ✓ (savo padalinio) | ✓ (visų) |
| Redaguoti nuorodą | – | ✓ (savo padalinio) | ✓ (visų) |
| Trinti į šiukšlinę / atkurti | – | ✓ (savo padalinio) | ✓ (visų) |
| Ištrinti visam laikui | – | ✓ (savo padalinio) | ✓ (visų) |

## Pranešimai ir automatizavimas {#pranesimai}

- Pakeitus greitąsias nuorodas pranešimai nesiunčiami.
- Pakeista nuorodų tvarka ar nauji įrašai viešame padalinio puslapyje pasirodo iš karto.

## Techninė informacija {#technine-informacija}

Šis skyrius skirtas administratoriams ir sistemos priežiūrai.

### Teisės

- Prieigą tikrina `QuickLinkPolicy`.
- Leidimai:
  - Padalinio teisės: `quickLinks.create.padalinys`, `quickLinks.read.padalinys`, `quickLinks.update.padalinys`, `quickLinks.delete.padalinys`.
  - Centrinio biuro teisės: `quickLinks.create.*`, `quickLinks.read.*`, `quickLinks.update.*`, `quickLinks.delete.*`.

### Kaip tai įgyvendinta

- Modelis: `App\Models\QuickLink`, naudojantis `SoftDeletes`.
- Valdiklis: `App\Http\Controllers\Admin\QuickLinkController`.
- Rikiavimo atnaujinimas: maršrutas `POST /mano/quickLinks/update-order` (`quickLinks.update-order`), apdorojamas per `UpdateQuickLinkOrderRequest`.
- Viešasis komponentas: `resources/js/Components/Public/Nav/QuickLink.vue`.
