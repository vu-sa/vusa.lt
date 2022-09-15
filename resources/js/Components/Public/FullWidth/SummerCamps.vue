<template>
  <div class="group relative">
    <Head>
      <link
        rel="preload"
        href="/images/ataskaita2022/kitos-nuotraukos/VU SA.jpg"
        as="image"
      />
    </Head>

    <ShapeDivider1
      :is-theme-dark="isThemeDark"
      class="absolute -top-1 z-10"
    ></ShapeDivider1>
    <ShapeDivider1
      :is-theme-dark="isThemeDark"
      class="absolute -bottom-2 z-10 rotate-180 lg:-bottom-1"
    ></ShapeDivider1>
    <div class="relative">
      <img
        src="/images/photos/stovykla.jpg"
        class="mt-2 h-32 w-full object-cover brightness-50 lg:my-1 lg:h-64"
        style="object-position: 0% 45%"
      />
    </div>
  </div>
  <div
    class="mx-8 mt-4 flex flex-col justify-center gap-4 lg:mx-16 lg:flex-row lg:items-center lg:px-16"
  >
    <div class="prose-sm dark:prose-invert sm:prose">
      <p class="text-2xl font-bold lg:w-4/5">
        VU SA organizuojamos
        <span class="font-extrabold text-vusa-red">pirmakursių stovyklos</span>
        jau pasibaigė!
      </p>
      <p class="w-4/5">
        Tačiau integracijos renginiai dar tik prasideda! Sek naujienas mūsų
        <a href="https://www.facebook.com/VUstudentuatstovybe">Facebook</a>
        puslapyje 🔍
      </p>
    </div>
    <div class="flex flex-wrap items-center gap-4 lg:justify-center">
      <NButton
        round
        size="large"
        strong
        type="primary"
        @click="Inertia.visit(route('pirmakursiuStovyklos'))"
        >Kas vyko?<template #icon
          ><NIcon :component="HatGraduation20Regular"></NIcon></template
      ></NButton>
      <NButton
        round
        strong
        secondary
        @click="
          Inertia.visit(
            route('main.contacts.category', {
              alias: 'padaliniai',
              lang: 'lt',
            })
          )
        "
        >Tavo kuratorius tavęs dar laukia...<template #icon
          ><NIcon :component="Person20Filled"></NIcon></template
      ></NButton>
    </div>
  </div>
  <NDivider />
</template>

<script setup lang="ts">
import { HatGraduation20Regular, Person20Filled } from "@vicons/fluent";
import { Head } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";
import { NButton, NDivider, NIcon } from "naive-ui";
import route from "ziggy-js";

import ShapeDivider1 from "@/Components/Public/ShapeDivider1.vue";

defineProps<{
  isThemeDark: boolean;
}>();
</script>
