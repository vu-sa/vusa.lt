<template>
  <AtaskaitaLayout
    :backgroundImage="'/images/photos/observatorijos_kiemelis.jpg'"
    :title="'VU SA | Ataskaita 2022'"
  >
    <article class="grid grid-cols-4 justify-items-center px-8 lg:px-32 gap-y-4">
      <div class="py-6 px-12 rounded-lg col-span-4">
        <slot></slot>
      </div>
    </article>
  </AtaskaitaLayout>
</template>

<script setup>
import AtaskaitaLayout from "@/Layouts/AtaskaitaLayout.vue";
</script>
