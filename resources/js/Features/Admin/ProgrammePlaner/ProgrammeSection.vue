<template>
  <div>
    <div class="bg-red-500">
      {{ section?.name }}
      {{ sectionStartTime }}
    </div>
    <div class="grid grid-cols-3 gap-4">
      <ProgrammeBlock v-for="(block, index) in section.blocks" :key="block.id" v-model:block="section.blocks[index]" :section-start-time
        class="mb-2 bg-gray-200 p-4">
        {{ block.name }}
      </ProgrammeBlock>
    </div>
    <NButton @click="createProgrammeBlock">
      Pridėti programos bloką
    </NButton>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import ProgrammeBlock from './ProgrammeBlock.vue';

const section = defineModel('element')

const { parent } = defineProps<{
  parent: App.Entities.ProgrammeDay;
}>();

function createProgrammeBlock() {
  section.value?.blocks?.push({
    id: 'programme-block-' + section.value.blocks.length,
    name: 'Programme Block ' + section.value.blocks.length,
    type: 'block',
    parts: []
  });
}

const sectionStartTime = computed(() => {
  // get duration of all previous blocks from order of the array
  const elapsedTime = parent.elements
    .slice(0, parent.elements.indexOf(section.value))
    .reduce((acc, section) => acc + section.duration, 0);

  return new Date(parent.start_time.getTime() + 1000 * 60 * elapsedTime);
});
</script>
