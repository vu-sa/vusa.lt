<template>
  <div>
    <div class="section-handle p-4 text-2xl font-bold">
      {{ day.name }}
    </div>
    {{ day.start_time }}
    <div ref="elementsEl">
      <component :is="element.type === 'block' ? ProgrammeSection : ProgrammePart"
        v-for="(element, index) in day.elements" :key="element.id" v-model:element="day.elements[index]" :parent="day"
        class="element-handle" />
      <!-- calculate current time from previous parts and blocks -->
    </div>
    <NButton @click="createProgrammePart">
      Pridėti dalį
    </NButton>
    <NButton @click="createProgrammeSection">
      Pridėti sekciją
    </NButton>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useSortable } from '@vueuse/integrations/useSortable';

import ProgrammePart from './ProgrammePart.vue';
import ProgrammeSection from './ProgrammeSection.vue';

const day = defineModel('day')

const elementsEl = ref<HTMLDivElement | null>('elementsEl');

useSortable<HTMLDivElement | null>(elementsEl, day.value?.elements, {
  handle: '.element-handle',
  group: 'day-' + day.value.id
});

function createProgrammeSection() {
  day.value.elements?.push({
    id: 'programme-section-' + day.value.elements.length,
    name: 'Programme Section ' + (day.value.elements.length + 1),
    type: 'block',
    duration: 60,
    blocks: []
  });
}

function createProgrammePart() {
  day.value.elements?.push({
    id: 'programme-part-' + 100 + day.value.elements.length,
    name: 'Programme Part ' + (day.value.elements.length + 1),
    type: 'part',
    duration: 45,
  });
}
</script>
