<template>
  <component :is="refComponent" />
</template>

<script setup lang="ts">
import { shallowRef, watch } from 'vue';

const props = defineProps<{
  directory: string;
  locale: string;
  file: string;
}>();

const refComponent = shallowRef(null);

const { VueComponent } = await import(`../../../../docs/_parts/${props.directory}/${props.locale}/${props.file}.md`);

refComponent.value = VueComponent;

watch(() => props.locale, async (locale) => {
  const { VueComponent } = await import(`../../../../docs/_parts/${props.directory}/${locale}/${props.file}.md`);
  refComponent.value = VueComponent;
});
</script>
