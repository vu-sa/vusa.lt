<template>
  <AtaskaitaLayout
    :background-image="'/images/photos/observatorijos_kiemelis.jpg'"
    :title="'Pradžia | Ataskaita 2022'"
  >
    <article
      class="grid grid-cols-4 justify-items-center gap-y-4 px-8 lg:px-32"
    >
      <slot></slot>
    </article>
  </AtaskaitaLayout>
</template>

<script setup lang="ts">
import AtaskaitaLayout from "@/components/Public/Layouts/AtaskaitaLayout.vue";
</script>
