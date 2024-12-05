<template>
  <div>
    <div class="p-4 text-2xl font-bold">
      {{ block.name }}
    </div>
    <ProgrammePart v-for="(part, index) in block.parts" :key="part.id" v-model:element="block.parts[index]"
      :section-start-time :parent="block">
      <NButton @click="deleteProgrammePart(index)">
        Ištrinti
      </NButton>
    </ProgrammePart>
    <NButton @click="createProgrammePart">
      Pridėti dalį
    </NButton>
  </div>
</template>

<script setup lang="ts">
import ProgrammePart from './ProgrammePart.vue';

defineProps<{
  sectionStartTime: Date;
}>();

const block = defineModel('block')

function createProgrammePart() {
  block.value.parts?.push({
    id: 'programme-part-' + 100 + block.value.parts.length,
    name: 'Programme Part ' + block.value.parts.length,
    type: 'part',
    duration: 15,
  });
}

function deleteProgrammePart(index: number) {
  block.value.parts?.splice(index, 1);
}
</script>
