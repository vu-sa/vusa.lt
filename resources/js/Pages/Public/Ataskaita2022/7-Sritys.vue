<template>
  <AtaskaitaLayout
    :backgroundImage="'/images/ataskaita2022/kitos-nuotraukos/VU SA.jpg'"
    :title="'Sritys | Ataskaita 2022'"
  >
    <article
      class="grid md:grid-cols-2 justify-items-center lg:px-32 gap-6 py-6 px-12 rounded-lg"
    >
      <slot></slot>
    </article>
  </AtaskaitaLayout>
</template>

<script setup>
import AtaskaitaLayout from "@/Layouts/AtaskaitaLayout.vue";
</script>
