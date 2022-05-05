<template>
  <Pradzia>
    <img
      class="my-4 md:w-2/3 col-span-4 rounded-sm shadow-md hover:shadow-lg duration-200 h-auto object-cover mx-auto mb-4 w-3/4"
      src="/images/ataskaita2022/kitos-nuotraukos/VUSAARK_EN-1-01.jpg"
    />
    <div class="flex gap-2 justify-center">
      <Link
        :href="
          route('main.ataskaita2022', {
            lang: $page.props.locale,
            permalink: 'sveikinimai',
          })
        "
        ><NButton strong round secondary>{{ __("Sveikinimai") }}</NButton></Link
      >

      <Link
        :href="
          route('main.ataskaita2022', {
            lang: $page.props.locale,
            permalink: 'vu-sa',
          })
        "
      >
        <NButton strong round secondary>{{ __("Apie VU SA") }}</NButton></Link
      >

      <Link
        :href="
          route('main.ataskaita2022', {
            lang: $page.props.locale,
            permalink: 'mvp',
          })
        "
        ><NButton strong round secondary>{{ __("Metų veiklos planas") }}</NButton></Link
      >
      <Link
        :href="
          route('main.ataskaita2022', {
            lang: $page.props.locale,
            permalink: 'studijos',
          })
        "
        ><NButton strong round secondary>{{ __("Strateginės kryptys") }}</NButton></Link
      >

      <Link
        :href="
          route('main.ataskaita2022', {
            lang: $page.props.locale,
            permalink: 'sritys',
          })
        "
        ><NButton strong round secondary>{{ __("Bendruomenė") }}</NButton></Link
      >
    </div>
  </Pradzia>
</template>

<script setup>
import Pradzia from "../0-Pradzia.vue";

import { Link } from "@inertiajs/inertia-vue3";
import { NButton } from "naive-ui";
</script>
