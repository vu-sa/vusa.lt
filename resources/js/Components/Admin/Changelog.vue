<template>
  <NCard
    style="width: 600px"
    title="vusa.lt pasikeitimų sąrašas"
    :bordered="false"
    size="huge"
    role="dialog"
    aria-modal="true"
  >
    <section>
      <h4>v0.1.1 (2022-06-12)</h4>
      <ul class="list-inside mb-2">
        <li>🛡 Adminstratoriams, dėl aiškumo, nerodomi veiksmai, kurie negalimi.</li>
        <li>
          🪄 Galima ištrinti paveikslėlį iš kontakto ir kitų vietų, kur prašo įkelti
          paveikslėlį.
        </li>
        <li>
          🪄 Galima
          <NTooltip
            ><template #trigger><strong>atsieti</strong></template
            >Kontaktas nebebus priskiriamas pareigybei, bet nebus ištrintas.</NTooltip
          >
          kontaktus nuo pareigybės, pareigybės puslapyje.
        </li>
        <li>🐛 Galima redaguoti pirmininkų informaciją padalinių koordinatoriams.</li>
      </ul>
    </section>
    <section>
      <h4>v0.1.0 (2022-06-11)</h4>
      <ul class="list-inside">
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
  </NCard>
</template>

<script setup>
import { NCard, NTooltip } from "naive-ui";
</script>
