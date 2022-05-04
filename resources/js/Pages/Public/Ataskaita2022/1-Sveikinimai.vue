<template>
  <AtaskaitaLayout
    :backgroundImage="'/images/photos/observatorijos_kiemelis.jpg'"
    :title="'Sveikinimo žodžiai | Ataskaita 2022'"
  >
    <article class="grid grid-cols-4 justify-items-center px-8 lg:px-32 gap-y-4">
      <slot></slot>
    </article>
  </AtaskaitaLayout>
</template>

<script setup>
import AtaskaitaLayout from "@/Layouts/AtaskaitaLayout.vue";
</script>
