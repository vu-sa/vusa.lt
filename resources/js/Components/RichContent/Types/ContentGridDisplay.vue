<template>
  <div class="content-grid">
    <div class="flex flex-col" :class="processedOptions.gap || 'gap-4'">
      <div v-for="(row, rowIndex) in rows" :key="rowIndex" class="w-full">
        <!-- Responsive grid - stack columns on mobile if mobileStacking is true -->
        <div :class="[
          'grid',
          processedOptions.gap || 'gap-4',
          'grid-cols-12',
          processedOptions.mobileStacking ? 'max-md:grid-cols-1' : '',
        ]">
          <div v-for="(column, colIndex) in row.columns" :key="colIndex" :class="[
            column.width,
            processedOptions.equalHeight ? 'h-full' : '',
          ]">
            <!-- Render content based on type -->
            <div v-if="column.content.type === 'tiptap'" class="prose max-w-none dark:prose-invert">
              <RichContentTiptapHTML :json_content="column.content.value" />
            </div>
            <div v-else-if="column.content.type === 'image'" class="h-full">
              <img :src="column.content.value" class="rounded-lg w-full object-cover" :class="[
                processedOptions.equalHeight ? 'h-full object-cover' : 'aspect-video object-cover',
              ]" alt="">
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import RichContentTiptapHTML from '@/Components/RichContentTiptapHTML.vue';

const props = defineProps<{
  element: {
    json_content: any;
    options?: {
      gap?: string;
      mobileStacking?: boolean;
      equalHeight?: boolean;
    };
  };
}>();

// Parse the JSON content if it's a string (which often happens when coming from the backend)
const displayElement = computed(() => {
  // Create a copy of the element to avoid mutating props
  const result = { ...props.element };

  // Check if json_content is a string (from backend) and try to parse it
  if (typeof result.json_content === 'string') {
    try {
      result.json_content = JSON.parse(result.json_content);
    } catch (e) {
      console.error('Failed to parse grid JSON content', e);
      // Provide a fallback empty structure
      result.json_content = [];
    }
  }

  // Check if options is a string and try to parse it
  if (typeof result.options === 'string') {
    try {
      result.options = JSON.parse(result.options);
    } catch (e) {
      console.error('Failed to parse grid options', e);
      result.options = {
        gap: 'gap-4',
        mobileStacking: true,
        equalHeight: false
      };
    }
  }

  return result;
});

// Process options to ensure they're properly accessible
const processedOptions = computed(() => {
  const options = displayElement.value.options;
  
  // If the json_content has a nested options structure, use that instead
  if (displayElement.value.json_content && 
      displayElement.value.json_content.options && 
      typeof displayElement.value.json_content.options === 'object') {
    return displayElement.value.json_content.options;
  }
  
  // Otherwise use the top-level options
  return options || {
    gap: 'gap-4',
    mobileStacking: true,
    equalHeight: false
  };
});

// Directly use the content as rows, converting from the old nested format if needed
const rows = computed(() => {
  const content = displayElement.value.json_content;

  // If content is null or undefined, return empty array
  if (!content) {
    return [];
  }

  // Handle nested json_content structure from ContentGridEditor
  if (content.json_content && Array.isArray(content.json_content)) {
    return content.json_content;
  }

  // If content is already an array (direct format), use it directly
  if (Array.isArray(content)) {
    return content;
  }

  // If content has a rows property (old format), use that
  if (content && typeof content === 'object' && Array.isArray(content.rows)) {
    return content.rows;
  }

  // Fallback to empty array
  return [];
});

</script>

<style scoped>
.content-grid :deep(img) {
  max-width: 100%;
  height: auto;
}

.content-grid :deep(.prose) {
  width: 100%;
}
</style>
