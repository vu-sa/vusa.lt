<template>
  <div
    class="py-4 px-4 lg:px-8 lg:mx-16 mx-8 lg:mb-8 mb-4 rounded-lg text-gray-800 bg-white shadow-lg"
  >
    <h1 class="lg:mb-0">Naujienos</h1>
    <div class="flex flex-wrap justify-between mb-4 gap-1">
      <p>Karščiausios universiteto naujienos tiesiai iš studentų lūpų!</p>
      <Link :href="route('main.newsArchive', { lang: 'lt' })">Daugiau →</Link>
    </div>
    <div class="grid sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
      <slot></slot>
    </div>
  </div>
</template>

<script setup>
import { Link } from "@inertiajs/inertia-vue3";
</script>
