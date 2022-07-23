<template>
  <div
    class="mx-8 mb-4 rounded-lg bg-white p-4 text-gray-800 shadow-lg lg:mx-16 lg:mb-8 lg:px-8"
  >
    <h1 class="lg:mb-0">Naujienos</h1>
    <div class="mb-4 flex flex-wrap justify-between gap-1">
      <p>Karščiausios universiteto naujienos tiesiai iš studentų lūpų!</p>
      <Link :href="route('main.newsArchive', { lang: 'lt' })">Daugiau →</Link>
    </div>
    <div class="grid gap-8 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-4">
      <slot></slot>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";
</script>
