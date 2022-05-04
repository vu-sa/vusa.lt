<template>
  <AtaskaitaLayout
    :backgroundImage="'/images/photos/observatorijos_kiemelis.jpg'"
    :title="'Pradžia | Ataskaita 2022'"
  >
    <article class="grid grid-cols-4 justify-items-center px-8 lg:px-32 gap-y-4">
      <div class="min-w-[1/2] max-w-[3/4] w-auto col-span-4">
        <slot></slot>
      </div>
    </article>
  </AtaskaitaLayout>
</template>

<script setup>
import AtaskaitaLayout from "@/Layouts/AtaskaitaLayout.vue";
</script>
