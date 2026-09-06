<template>
  <li class="group">
    <a
      :href="documentUrl"
      target="_blank"
      rel="noopener noreferrer"
      :title="isShortcut ? $t('search.document_link_hint') : undefined"
      :class="[
        '-mx-3 block sm:flex sm:items-center gap-2 sm:gap-3 px-3 sm:px-4 py-2.5',
        'transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:-mx-4 hover:bg-secondary/50',
      ]"
      @click="trackDocumentClick"
    >
      <!-- Mobile Layout: Stacked -->
      <div class="sm:hidden space-y-2">
        <!-- Title Row -->
        <div class="flex items-center gap-2">
          <!-- Document Icon -->
          <div :class="getDocumentIconClasses()" class="shrink-0 border border-border">
            <Icon :icon="getDocumentIcon()" class="size-3.5" />
          </div>
          <!-- Title -->
          <div class="flex-1 min-w-0">
            <h3
              class="text-sm font-semibold text-foreground group-hover:text-brand transition-colors line-clamp-2 leading-tight"
              :title="document.title"
            >
              {{ document.title }}
            </h3>
          </div>
          <!-- External Link / Shortcut Icon -->
          <component
            :is="isShortcut ? IFluentLink20Regular : IFluentOpen20Regular"
            class="size-3.5 text-muted-foreground group-hover:text-brand transition-colors shrink-0"
          />
        </div>

        <!-- Metadata Row - no margin, full width -->
        <div class="flex items-center gap-1.5 flex-wrap text-xs text-muted-foreground">
          <!-- Content Type -->
          <TagChip variant="muted" class="max-w-24 shrink-0">
            <span class="truncate block normal-case tracking-normal font-medium" :title="getShortContentType()">
              {{ getShortContentType() }}
            </span>
          </TagChip>

          <!-- Organization -->
          <span class="max-w-24 truncate shrink-0 font-medium" :title="getTenantDisplayName()">
            {{ getTenantDisplayName() }}
          </span>

          <!-- Date -->
          <span class="whitespace-nowrap shrink-0">
            {{ formatCompactDate() }}
          </span>

          <!-- Unresolved shortcut warning -->
          <IFluentWarning20Regular
            v-if="isUnresolvedShortcut"
            class="size-3.5 text-brand shrink-0"
            :title="$t('search.document_link_unresolved')"
          />
        </div>
      </div>

      <!-- Desktop Layout: Horizontal -->
      <div class="hidden sm:flex sm:items-center sm:gap-3 sm:w-full">
        <!-- Document Icon -->
        <div :class="getDocumentIconClasses()" class="shrink-0 border border-border">
          <Icon :icon="getDocumentIcon()" class="size-4" />
        </div>

        <!-- Title -->
        <div class="flex-1 min-w-0">
          <h3
            class="text-base font-semibold text-foreground group-hover:text-brand transition-colors line-clamp-1"
            :title="document.title"
          >
            {{ document.title }}
          </h3>
        </div>

        <!-- Compact Metadata -->
        <div class="flex items-center gap-2.5 shrink-0 min-w-0">
          <!-- Content Type -->
          <TagChip variant="muted" class="max-w-24 md:max-w-40 shrink-0">
            <span class="truncate block normal-case tracking-normal font-medium" :title="getShortContentType()">
              {{ getShortContentType() }}
            </span>
          </TagChip>

          <!-- Organization (abbreviated) -->
          <span
            class="text-xs text-muted-foreground font-medium max-w-20 md:max-w-32 truncate shrink-0"
            :title="getTenantDisplayName()"
          >
            {{ getTenantDisplayName() }}
          </span>

          <!-- Date -->
          <span class="text-xs text-muted-foreground whitespace-nowrap shrink-0">
            {{ formatCompactDate() }}
          </span>

          <!-- Unresolved shortcut warning -->
          <IFluentWarning20Regular
            v-if="isUnresolvedShortcut"
            class="size-3.5 text-brand shrink-0"
            :title="$t('search.document_link_unresolved')"
          />

          <!-- External Link / Shortcut Icon -->
          <component
            :is="isShortcut ? IFluentLink20Regular : IFluentOpen20Regular"
            class="size-3.5 text-muted-foreground group-hover:text-brand transition-colors shrink-0"
          />
        </div>
      </div>
    </a>
  </li>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Icon } from '@iconify/vue';
import { trans as $t } from 'laravel-vue-i18n';

import { useDocumentDisplay, getDocumentTargetUrl, parseDocumentDate, type DocumentDisplayItem } from '@/Composables/useDocumentDisplay';
import { TagChip } from '@/Components/Public/Base';
import IFluentLink20Regular from '~icons/fluent/link-20-regular';
import IFluentOpen20Regular from '~icons/fluent/open-20-regular';
import IFluentWarning20Regular from '~icons/fluent/warning-20-regular';

// Props
interface Props {
  document: DocumentDisplayItem;
}

const props = defineProps<Props>();

// Use shared document display logic
const {
  getDocumentIcon,
  getDocumentIconClasses,
  getShortContentType,
  getTenantDisplayName,
  isShortcut,
  isUnresolvedShortcut,
  trackDocumentClick,
} = useDocumentDisplay(props.document);

// .url shortcuts link straight to their resolved target; otherwise share_url
// goes through DocumentRedirectController which appends web=1 server-side,
// falling back to the raw anonymous_url with web=1 added client-side.
const documentUrl = computed(() => getDocumentTargetUrl(props.document));

// Compact date formatting using the proper date parsing from useDocumentDisplay
const formatCompactDate = () => {
  const date = parseDocumentDate(props.document.document_date);
  if (!date) return '';

  try {
    const now = new Date();
    const isCurrentYear = date.getFullYear() === now.getFullYear();

    if (isCurrentYear) {
      return date.toLocaleDateString('lt-LT', {
        month: 'short',
        day: 'numeric',
      });
    }
    else {
      return date.getFullYear().toString();
    }
  }
  catch {
    return props.document.document_date;
  }
};
</script>
