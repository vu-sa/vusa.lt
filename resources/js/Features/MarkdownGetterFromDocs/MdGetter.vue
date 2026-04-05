<template>
  <div v-if="error" class="subtle-error">
    {{ error }}
  </div>
  <component :is="refComponent" v-else-if="refComponent" />
  <div v-else class="loading">
    Loading content...
  </div>
</template>

<script setup lang="ts">
import { shallowRef, watch, ref } from 'vue';

const props = defineProps<{
  directory: string;
  locale: string;
  file: string;
}>();

const emit = defineEmits<{
  'content-loaded': [boolean];
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
    emit('content-loaded', true);
  }
  catch (e) {
    console.error(`Failed to load markdown file: ${directory}/${locale}/${file}.md`, e);
    error.value = `Failed to load content. Please try again later.`;
    emit('content-loaded', false);
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
.subtle-error {
  color: #718096;
  padding: 0.5rem;
  font-size: 0.875rem;
  font-style: italic;
  opacity: 0.7;
}

.loading {
  padding: 1rem;
  color: #718096;
  font-style: italic;
}

:deep ul {
  list-style-type: disc;
  margin: 1rem 0;
  padding-left: 1.5rem;
}

:deep ol {
  list-style-type: decimal;
  margin: 1rem 0;
  padding-left: 1.5rem;
}
</style>
