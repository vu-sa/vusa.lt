<template>
  <div class="space-y-4">
    <div class="grid grid-flow-row-dense grid-cols-6 gap-4">
      <div v-for="(image, index) in element.json_content" :key="index"
        :class="getClassesForImage(image.colspan)">
        <img
          :src="image.image"
          class="size-full rounded-md shadow-xs object-cover"
          :alt="image.alt || image.title || `Image ${index + 1}`"
          :title="image.title || image.alt || `Image ${index + 1}`"
        >
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  element: App.Entities.ContentPart;
}>();

const getClassesForImage = (colspan: string) => {
  if (colspan === 'col-span-full') {
    return `h-48 md:h-60 ${colspan}`;
  }
  return `md:h-40 ${colspan}`;
};
</script>
