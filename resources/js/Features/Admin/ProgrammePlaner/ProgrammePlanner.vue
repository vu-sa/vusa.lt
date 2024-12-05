<template>
  <!-- <CardModal :show class="max-w-7xl" display-directive="show" @close="$emit('close')">-->
  <div class="flex flex-col gap-4">
    <div ref="programmeEl" class="flex flex-col border">
      <ProgrammeDay v-for="(day, index) in programme" :key="day.id"
        v-model:day="programme[index]" :data-id="programme.id" class="border" />
    </div>
  </div>
  <NButton @click="createDay">
    Pridėti programos dieną
  </NButton>
  <!-- </CardModal> -->
</template>

<script setup lang="ts">
import { ref, useTemplateRef } from 'vue';
import { useSortable } from '@vueuse/integrations/useSortable'

import ProgrammeDay from './ProgrammeDay.vue';

defineEmits<{
  (e: "close"): void;
}>();

const props = defineProps<{
  show: boolean;
  startTime: Date;
}>();

const programmeEl = useTemplateRef<HTMLDivElement | null>('programmeEl')

const programme = ref([]);

useSortable<HTMLDivElement | null>(programmeEl, programme, {
  handle: '.section-handle',
});

function createDay() {
  programme.value.push({
    id: 'programme-day-' + String(programme.value.length + 1),
    name: 'Programme Day ' + (programme.value.length + 1),
    type: 'day',
    elements: [],
    start_time: new Date(props.startTime.getTime() + 1000 * 60 * 60 * 24 * programme.value.length),
  });
}
</script>
