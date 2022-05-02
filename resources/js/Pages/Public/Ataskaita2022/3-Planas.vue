<template>
  <AtaskaitaLayout
    :backgroundImage="'/images/photos/vu.jpg'"
    :title="'Metų veiklos planas | Ataskaita 2022'"
  >
    <article class="grid grid-cols-4 justify-items-center px-8 lg:px-32 gap-y-4">
      <div class="prose md:prose-sm col-span-4">
        <div class="bg-white py-6 px-12 rounded-lg">
          <slot></slot>
        </div>
      </div>
    </article>
  </AtaskaitaLayout>
</template>

<script setup>
import AtaskaitaLayout from "@/Layouts/AtaskaitaLayout.vue";
</script>
