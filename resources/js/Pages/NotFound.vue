<template>
  <div class="typography m-4 rounded-xs p-4 leading-7">
    <h1>Oops!</h1>
    <p>
      Pasiekei puslapį, kurio neturėjai pasiekti. Wow, way to go! Šią žinutę
      turbūt yra matę vienetai žmonių. 🤷‍♂️ Ir geriau, kad taip liktų...
    </p>
    <p>
      Bet jeigu jau pamatei šį puslapį,
      <a :href="`mailto:${itEmail}`">parašyk {{ itEmail }}</a>, nurodydamas (-a), ką
      padarei, kad pamatytum šią žinutę.
    </p>
    <p class="text-xs">
      Dažniausiu atveju užteks nurodyti puslapį, kurį bandei aplankyti. Jis
      turėtų egzistuoti, bet šiuo metu bus nepasiekiamas.
    </p>
    <p>
      Geros dienos! 😉 Už parašytą žinutę, į priekį dovanoju fainą kačiuką! 😻
    </p>
    <p class="text-xs">
      p.s. galbūt nuėjus į <strong><a :href="$page.props.app.url">vusa.lt</a></strong
      >, galėsi toliau surasti, ko ieškojai...
    </p>
    <img src="https://cataas.com/cat/cute" alt="A cute cat to brighten your day" loading="lazy">
  </div>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

// The support address lives in config/vusa.php, shared through HandleInertiaRequests.
const itEmail = computed(() => usePage().props.organization.contacts.it);
</script>
