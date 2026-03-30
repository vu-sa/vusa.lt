---
title: "mano.vusa.lt v1.0: Platformos modernizacija"
date: 2026-02-07
author: Justinas Kavoliūnas
featured: true
tags:
  - atnaujinimas
excerpt: Didžiausias mano.vusa.lt atnaujinimas — nauja atstovavimo valdymo sistema, vieši posėdžiai, gidų sistema, atnaujintas turinys ir daug daugiau.
---

<div class="blog-post-header">
  <div class="blog-post-tags">
    <span class="blog-post-tag">atnaujinimas</span>
    <span class="blog-post-tag">v1.0</span>
  </div>
  <h1 class="blog-post-title">mano.vusa.lt v1.0: Platformos modernizacija</h1>
  <div class="blog-post-meta">
    <span class="blog-post-author">
      <span class="author-initials">JK</span>
      Justinas Kavoliūnas
    </span>
    <span class="blog-post-date">2026 m. vasario 7 d.</span>
  </div>
</div>

Džiaugiamės pristatydami didžiausią mano.vusa.lt platformos atnaujinimą. Ši versija apima naują atstovavimo valdymo sistemą, viešus posėdžius, gidų sistemą ir daugybę kitų patobulinimų.

## 🧭 Atstovavimo valdymas

Visiškai atnaujintas atstovavimo skydelis (`/dashboard/atstovavimas`):

- **Gantt laiko juosta** — interaktyvi institucijų posėdžių laiko juosta su atostogų periodais, periodiškumu ir konfigūracija (išsaugoma naršyklėje)
- **Atstovų aktyvumas** — administratoriai mato studentų atstovų aktyvumą
- **Check-in sistema** — galimybė nurodyti, kad artimiausiu metu posėdžio nebus (iki 3 mėnesių į priekį), su pastabomis
- **Posėdžių kūrimas** — atnaujintas posėdžių kūrimo langas su darbotvarkės punktų perkėlimu iš ankstesnių posėdžių
- **Darbotvarkės punktų tvarkymas** — galimybė pertvarkyti darbotvarkės punktus ir nurodyti, ar klausimą iškėlė studentai

## 🌐 Vieši posėdžiai

Skaidrumo tikslais, dalis posėdžių dabar rodomi viešai:

- Institucijų puslapiuose rodomi posėdžiai, sugrupuoti pagal akademinius metus
- Atskiras viešas posėdžio puslapis su darbotvarke, balsavimais ir sprendimais
- Visų viešų posėdžių paieška su Typesense
- Administratorių nustatymai, kurios institucijos ir posėdžiai bus viešai matomi

### 🎓 Gidų sistema

Nauja interaktyvi gidų sistema padeda susipažinti su platforma:

- Automatiniai gidai pirmą kartą apsilankius puslapyje
- Pagalbos ikonėlė viršutinėje juostoje rodo galimus gidus
- Gidų eiga išsaugoma — nebereikia kartoti jau peržiūrėtų gidų
- Galimybė atstatyti gidus nustatymuose

### 👥 Naudotojų atnaujinimo vedlys

Naujas vedlys leidžia paprastai atnaujinti pareigybių naudotojus — pasirinkite instituciją, tada pareigybę, ir pridėkite naujus arba pašalinkite esamus naudotojus.

### 📰 Naujienų ir turinio atnaujinimai

- **Naujienų išdėstymai** — 4 skirtingi išdėstymo variantai naujienoms
- **Puslapių išdėstymai** — 3 variantai turinio puslapiams
- **Akcentai** — iki 3 akcentuojamų elementų naujienose
- **Socialiniai įterpiniai** — Facebook ir Instagram turinio įterpimas
- **Susijusios naujienos** — po naujienos rodomi 3 susiję straipsniai

### 📅 Kalendoriaus laiko juosta

Pakeistas senasis kalendoriaus komponentas nauja įvykių laiko juosta — aiškesnis ir paprastesnis naudoti.

### 📇 Kontaktų puslapis

Atnaujintas kontaktų puslapis naudoja Typesense paiešką — greita, su filtrais ir rasta per bendrą paiešką.

### 🔧 Kiti patobulinimai

- ✨ **Atnaujinta šoninė juosta** — animacijos, ryškesni greiti veiksmai, geresnis aktyvaus puslapio indikatorius
- 🔄 **Perėjimo animacijos** — sklandūs puslapių perėjimai visoje platformoje
- 📱 **PWA ir pranešimai** — platforma veikia kaip mobili aplikacija, atnaujinti pranešimai ir užduočių kūrimas atstovams
- 🔍 **Darbotvarkės punktų paieška** — administratoriai gali ieškoti darbotvarkės punktų
- 🔤 **Naujas šriftas** — Atkinson Hyperlegible Next geresniam skaitomumui
- 📋 **Registracijos forma** — studentų atstovavimo registracijos forma
- 🏨 **Atnaujinti rezervacijų rodiniai** — patobulintos rezervacijų lentelės
- 🔐 **Microsoft autentifikacija** — teisingas klaidos pranešimas nepavykus prisijungti
- ✏️ **Turinio redaktorius** — atnaujinta turinio redaktoriaus sąsaja
- 🔗 **Institucijų ryšiai** — platesnė ryšių sistema tarp institucijų su kryptiniais ir dvikrypčiais ryšiais

---

*Šis atnaujinimas apima 1384 pakeistus failus ir 195 pakeitimų. Dėkojame visiems prisidėjusiems!* 🚀

<style>
.blog-post-header {
  margin-bottom: 32px;
  padding-bottom: 24px;
  border-bottom: 1px solid var(--vp-c-divider);
}

.blog-post-header .blog-post-title {
  font-size: 32px !important;
  font-weight: 800 !important;
  line-height: 1.2 !important;
  margin: 8px 0 16px !important;
  padding: 0 !important;
  border: none !important;
}

.blog-post-tags {
  display: flex;
  gap: 6px;
}

.blog-post-tag {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--vp-c-brand-1);
  background: var(--vp-c-brand-soft);
  padding: 2px 10px;
  border-radius: 9999px;
}

.blog-post-meta {
  display: flex;
  align-items: center;
  gap: 16px;
  color: var(--vp-c-text-2);
  font-size: 14px;
}

.blog-post-author {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 500;
}

.author-initials {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: linear-gradient(135deg, oklch(0.5 0.12 25), oklch(0.75 0.12 65));
  color: white;
  font-size: 11px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.blog-post-date {
  color: var(--vp-c-text-3);
}
</style>
