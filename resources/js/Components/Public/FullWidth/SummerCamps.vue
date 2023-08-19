<template>
  <div class="group relative">
    <Head>
      <link rel="preload" href="/images/photos/VU SA.jpg" as="image" />
    </Head>

    <ShapeDivider1 class="absolute -top-1 z-[1]"></ShapeDivider1>
    <ShapeDivider1
      class="absolute -bottom-2 z-[1] rotate-180 lg:-bottom-1"
    ></ShapeDivider1>
    <div class="relative">
      <img
        src="/images/photos/stovykla.jpg"
        loading="lazy"
        class="mt-2 h-32 w-full object-cover brightness-50 lg:my-1 lg:h-64"
        style="object-position: 0% 45%"
      />
    </div>
  </div>
  <div
    class="mx-8 mt-4 flex flex-col justify-center gap-4 lg:mx-16 lg:flex-row lg:items-center lg:px-16"
  >
    <div class="prose prose-sm dark:prose-invert">
      <p class="text-2xl font-bold lg:w-4/5">
        VU SA
        <span class="font-extrabold text-vusa-red">pirmakursių stovyklos</span>
        jau artėja!
      </p>
      <p class="w-4/5">
        Daugiau informacijos apie tavo padalinio stovyklą – jau greitai. Sek
        naujienas mūsų
        <a href="https://www.facebook.com/VUstudentuatstovybe">Facebook</a>
        puslapyje 🔍
      </p>
    </div>
    <div class="flex flex-wrap items-center gap-4 lg:justify-center">
      <a :href="route('pirmakursiuStovyklos')">
        <NButton round size="large" strong type="primary"
          >Sužinok apie savo artėjančią stovyklą!<template #icon
            ><NIcon :component="Balloon20Regular"></NIcon></template
        ></NButton>
      </a>
      <a :href="route('pirmakursiuStovyklos2022')">
        <NButton round size="large" strong secondary
          >Kas vyko 2022 m.?<template #icon
            ><NIcon :component="HatGraduation20Regular"></NIcon></template
        ></NButton>
      </a>
      <a
        :href="
          route('contacts.category', {
            alias: 'padaliniai',
            lang: 'lt',
            subdomain: 'www',
          })
        "
      >
        <NButton round strong secondary
          >VU SA kontaktai tavo fakultete<template #icon
            ><NIcon :component="Person20Filled"></NIcon></template
        ></NButton>
      </a>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  Balloon20Regular,
  HatGraduation20Regular,
  Person20Filled,
} from "@vicons/fluent";
import { Head } from "@inertiajs/vue3";
import { NButton, NIcon } from "naive-ui";

import ShapeDivider1 from "@/Components/Public/ShapeDivider1.vue";
</script>
