<template>
  <section class="lg:px-2 px-1.5 isolate my-12">
    <div class="max-w-6xl mx-auto">
      <p class="mx-auto w-fit text-4xl font-bold">
        {{ element.options.title }}
      </p>
      <div class="flex flex-col md:flex-row my-12 mx-auto font-bold text-xl leading-tight justify-center gap-8">
        <NumberStatistic v-for="numberStat in element.json_content" :key="numberStat.label" :color-class
          :end-number="numberStat.endNumber" :show-plus="numberStat.showPlus">
          {{ numberStat.label }}
        </NumberStatistic>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from "vue";

import NumberStatistic from "./RCNumberStatistic.vue";

import type { NumberStatSection } from "@/Types/contentParts"

const { element } = defineProps<{
  element: NumberStatSection
}>();

const colorClass = computed(() => {
  if (element.options.color === 'zinc' || element.options.color === undefined) {
    return "text-zinc-800 dark:text-zinc-50"
  } else {
    return `text-vusa-${element.options.color}`
  }
})

</script>
