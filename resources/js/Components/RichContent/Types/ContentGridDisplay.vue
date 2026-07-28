<template>
  <RCSection
    :title="processedOptions.title" :subtitle="processedOptions.subtitle"
    :background="processedOptions.background ?? 'none'" :padding="processedOptions.padding ?? 'none'"
    :rounded="processedOptions.rounded ?? 'none'" :align="processedOptions.align ?? 'center'"
    :heading-level="processedOptions.headingLevel" :show-separator="processedOptions.showSeparator"
    inner="full" :id="anchorId ? `rc-${anchorId}` : undefined"
  >
    <div class="content-grid">
      <div class="flex flex-col" :class="processedOptions.gap || 'gap-4'">
        <div v-for="(row, rowIndex) in rows" :key="rowIndex" class="w-full">
          <!-- Responsive grid - stack columns on mobile if mobileStacking is true -->
          <div :class="[
            'grid',
            processedOptions.gap || 'gap-4',
            'grid-cols-12',
            processedOptions.mobileStacking ? 'max-md:grid-cols-1' : '',
            VERTICAL_ALIGN_CLASS[processedOptions.verticalAlign ?? 'stretch'],
          ]">
            <div v-for="(column, colIndex) in row.columns" :key="colIndex" :class="[
              column.width,
              processedOptions.equalHeight ? 'h-full' : '',
            ]">
              <!-- Render content based on type -->
              <div v-if="column.content.type === 'tiptap'" class="max-w-none tracking-normal">
                <RichContentTiptapHTML :json_content="column.content.value" />
              </div>
              <div v-else-if="column.content.type === 'image'" class="h-full">
                <ImageWithDecorations
                  :src="column.content.value"
                  :alt="column.content.alt || ''"
                  :height-class="processedOptions.equalHeight ? 'h-full' : 'aspect-video'"
                  :object-position="column.content.objectPosition"
                  :overlay-content="column.content.overlayContent"
                  :overlay-corner="column.content.overlayCorner"
                  :overlay-overhang="column.content.overlayOverhang"
                  :overlay-padding="column.content.overlayPadding"
                  :decorations="column.content.decorations"
                />
              </div>
              <RCFeatureCard
                v-else-if="column.content.type === 'card'"
                :title="column.content.value?.title || ''"
                :cover-image="column.content.value?.image || null"
                :cover-alt="column.content.value?.imageAlt || column.content.value?.title"
                :href="column.content.value?.href || null"
                :show-cover-fallback="false"
                :class="processedOptions.equalHeight ? 'h-full' : ''"
              >
                <p v-if="column.content.value?.description" class="text-sm text-zinc-600 dark:text-zinc-400">
                  {{ column.content.value.description }}
                </p>
              </RCFeatureCard>
            </div>
          </div>
        </div>
      </div>
    </div>
  </RCSection>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import RichContentTiptapHTML from '../RichContentTiptapHTML.vue';
import RCFeatureCard from '../RCFeatureCard.vue';
import RCSection from '../RCSection.vue';
import ImageWithDecorations from '@/Components/ui/ImageWithDecorations.vue';
import type { SectionBackground, SectionPadding, SectionRounded } from '../sectionClasses';

// The user's specific complaint was a short text column stretching to the row's
// height with its content pinned to the top — `grid` items default to `stretch` with
// nothing overriding it. `center` reproduces MembershipPage's `items-center` mascot
// layout (a text column beside a taller decorated image).
const VERTICAL_ALIGN_CLASS: Record<string, string> = {
  stretch: '',
  start: 'items-start',
  center: 'items-center',
  end: 'items-end',
};

const props = defineProps<{
  element: {
    json_content: any;
    options?: {
      title?: string;
      subtitle?: string;
      background?: SectionBackground;
      padding?: SectionPadding;
      rounded?: SectionRounded;
      /** Header alignment, forwarded to RCSection — grids default to centered like every other section block. */
      align?: 'center' | 'start';
      /** Semantic heading level for the title, forwarded to RCSection. */
      headingLevel?: 2 | 3 | 4;
      /** Whether to render the separator bar beneath the title. */
      showSeparator?: boolean;
      /** Vertical alignment of column content within each row. */
      verticalAlign?: 'stretch' | 'start' | 'center' | 'end';
      gap?: string;
      mobileStacking?: boolean;
      equalHeight?: boolean;
    };
  };
  /** Content-part id, used as the ToC scroll anchor when this block has a title (see tocAnchors.ts). */
  anchorId?: number | null;
}>();

// Parse the JSON content if it's a string (which often happens when coming from the backend)
const displayElement = computed(() => {
  // Create a copy of the element to avoid mutating props
  const result = { ...props.element };

  // Check if json_content is a string (from backend) and try to parse it
  if (typeof result.json_content === 'string') {
    try {
      result.json_content = JSON.parse(result.json_content);
    }
    catch (e) {
      console.error('Failed to parse grid JSON content', e);
      // Provide a fallback empty structure
      result.json_content = [];
    }
  }

  // Check if options is a string and try to parse it
  if (typeof result.options === 'string') {
    try {
      result.options = JSON.parse(result.options);
    }
    catch (e) {
      console.error('Failed to parse grid options', e);
      result.options = {
        gap: 'gap-4',
        mobileStacking: true,
        equalHeight: false,
      };
    }
  }

  return result;
});

// Process options to ensure they're properly accessible
const processedOptions = computed(() => {
  const { options } = displayElement.value;

  // If the json_content has a nested options structure, use that instead
  if (displayElement.value.json_content
    && displayElement.value.json_content.options
    && typeof displayElement.value.json_content.options === 'object') {
    return displayElement.value.json_content.options;
  }

  // Otherwise use the top-level options
  return options || {
    gap: 'gap-4',
    mobileStacking: true,
    equalHeight: false,
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
