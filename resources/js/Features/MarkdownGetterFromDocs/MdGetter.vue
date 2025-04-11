<template>
  <div v-if="error" class="error-message">
    {{ error }}
  </div>
  <component v-else-if="refComponent" :is="refComponent" />
  <div v-else class="loading">Loading content...</div>
</template>

<script setup lang="ts">
import { shallowRef, watch, ref } from 'vue';

const props = defineProps<{
  directory: string;
  locale: string;
  file: string;
}>();

const refComponent = shallowRef(null);
const error = ref<string | null>(null);

/**
 * Loads a markdown component from the specified path
 */
async function loadComponent(directory: string, locale: string, file: string) {
  error.value = null;
  try {
    const { default: VueComponent } = await import(`../../../../docs/_parts/${directory}/${locale}/${file}.md`);
    refComponent.value = VueComponent;
  } catch (e) {
    console.error(`Failed to load markdown file: ${directory}/${locale}/${file}.md`, e);
    error.value = `Failed to load content. Please try again later.`;
  }
}

// Load initial component
await loadComponent(props.directory, props.locale, props.file);

// Watch for locale changes and reload component when needed
watch(() => props.locale, async (locale) => {
  await loadComponent(props.directory, locale, props.file);
});
</script>

<style scoped>
.error-message {
  color: #e53e3e;
  padding: 1rem;
  border-left: 4px solid #e53e3e;
  background-color: #fff5f5;
  margin: 1rem 0;
}

.loading {
  padding: 1rem;
  color: #718096;
  font-style: italic;
}
</style>
