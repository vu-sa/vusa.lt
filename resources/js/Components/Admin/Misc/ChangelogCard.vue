<template>
  <NCard
    style="width: 600px"
    title="vusa.lt pasikeitimų sąrašas"
    :bordered="false"
    size="huge"
    role="dialog"
    aria-modal="true"
    class="prose"
  >
    <section>
      <h4>
        <a
          class="transition hover:text-vusa-red"
          target="_blank"
          href="https://github.com/vu-sa/vusa.lt/pull/90"
          >v0.2.0 (2022-07-23)</a
        >
      </h4>
      <p>
        Didžiausias atnaujinimas po vusa.lt atnaujinimo. Daugiausiai dėmesio
        skirta platformos techniniam atnaujinimui ir administravimo funkcijoms.
      </p>
      <ul>
        <li>
          🌟 Sutvarkyta Sąžiningai registracija, pridėtas dienų suskaičiavimas
          iki galimos registracijos datos
        </li>
        <li>🌟 Šiek tiek pakeistas breadcrumbs stilius</li>
        <li>
          🛡 Visi masiniai modeliai dabar yra puslapiuoti (nerodomi viename
          puslapyje)
        </li>
        <li>
          🛡 Generuojant nuorodą, lietuviškos raidės konvertuojamos į lotyniškas
        </li>
        <li>
          🛡 Sukurti <em>preview</em>, <em>update</em> ir
          <em>destroy</em> veiksmai beveik visiems duomenų modeliams. Jie
          pasiekiami ir iš lentelės puslapio.
        </li>
        <li>🛡 Kai kur pridėtos papildomos modelių tikrinimo taisyklės</li>
        <li>🛡 Atnaujintas dizainas (kortelės, spalvos)</li>
        <li>🛡 Institucijos padarytos pagrindiniu kontaktų puslapiu</li>
        <li>🛡 Atnaujintas teksto redaktoriaus stilius</li>
      </ul>
    </section>
    <NDivider></NDivider>
    <NCollapse>
      <NCollapseItem title="v0.1">
        <section>
          <h4>v0.1.2 (2022-07-08)</h4>
          <ul class="mb-2">
            <li>
              🔧 Šis atnaujinimas labiausiai skirtas puslapio vidui ir kūrimo
              procesui atnaujinti.
            </li>
            <li>🛡 Administracinių ikonų suvienodinimas.</li>
            <li>
              🌟 Pridėta galimybė įsidėti VU SA naujienų <em>feed</em> į Atom
              tipo naršyklę.
            </li>
          </ul>
        </section>
        <section>
          <h4>v0.1.1 (2022-06-12)</h4>
          <ul class="mb-2">
            <li>
              🛡 Adminstratoriams, dėl aiškumo, nerodomi veiksmai, kurie
              negalimi.
            </li>
            <li>
              🪄 Galima ištrinti paveikslėlį iš kontakto ir kitų vietų, kur
              prašo įkelti paveikslėlį.
            </li>
            <li>
              🪄 Galima
              <NTooltip
                ><template #trigger><strong>atsieti</strong></template
                >Kontaktas nebebus priskiriamas pareigybei, bet nebus
                ištrintas.</NTooltip
              >
              kontaktus nuo pareigybės, pareigybės puslapyje.
            </li>
            <li>
              🐛 Galima redaguoti pirmininkų informaciją padalinių
              koordinatoriams.
            </li>
          </ul>
        </section>

        <section>
          <h4>v0.1.0 (2022-06-11)</h4>
          <ul>
            <li>
              🪄 Padalinius galima surasti puslapyje:
              <a
                class="text-vusa-red"
                target="_blank"
                :href="'/kontaktai/kategorija/padaliniai'"
                >Padaliniai</a
              >
            </li>
          </ul>
        </section>
      </NCollapseItem>
    </NCollapse>
  </NCard>
</template>

<script setup lang="ts">
import { NCard, NCollapse, NCollapseItem, NDivider, NTooltip } from "naive-ui";
</script>
