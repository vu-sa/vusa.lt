<template>
  <div class="space-y-4">
    <NImageGroup :show-toolbar="false">
      <div class="grid grid-flow-row-dense grid-cols-6 gap-4">
        <div v-for="(image, index) in element.json_content" :key="index" 
             :class="getClassesForImage(image.colspan)">
          <NImage :src="image.image" width="100%" class="size-full rounded-md shadow-xs" 
                 object-fit="cover" />
        </div>
      </div>
    </NImageGroup>
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

