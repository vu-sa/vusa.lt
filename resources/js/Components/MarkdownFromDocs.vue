<template>
  <Suspense>
    <VueComponent />
  </Suspense>
</template>

<script setup lang="ts">
const props = defineProps<{
  directory: string;
  locale: string;
  file: string;
}>();

// async render import { VueComponent } from "/docs/_parts/{directory}/{locale}/{file}.md";

const { VueComponent } = await import(`/docs/_parts/${props.directory}/${props.locale}/${props.file}`);

console.log(VueComponent);
</script>
