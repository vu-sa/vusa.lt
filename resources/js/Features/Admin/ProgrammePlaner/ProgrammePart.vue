<template>
  <div class="bg-green-500">
    {{ part.name }} {{ partStartTime }}
    <slot />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const part = defineModel('element')

const { parent, sectionStartTime } = defineProps<{
  parent: any
  sectionStartTime?: Date;
}>();

const partStartTime = computed(() => {
  // get duration of all previous parts from order of the array
  if (parent.type === 'day') {
    const elapsedTime = parent.elements
      .slice(0, parent.elements.indexOf(part.value))
      .reduce((acc, element) => acc + element.duration, 0);

    return new Date(parent.start_time.getTime() + 1000 * 60 * elapsedTime);
  } else if (parent.type === 'block') {
    const elapsedTime = parent.parts
      .slice(0, parent.parts.indexOf(part.value))
      .reduce((acc, element) => acc + element.duration, 0);

    console.log(sectionStartTime?.getTime(), elapsedTime);

    return new Date(sectionStartTime?.getTime() + 1000 * 60 * elapsedTime);
  } else {
    return '';
  }
});
</script>
