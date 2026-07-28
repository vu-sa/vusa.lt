<template>
  <div
    class="group transition-all duration-200 border border-border/50 rounded-md bg-card hover:shadow-lg hover:bg-accent/20 hover:border-primary/30">
    <a :href="documentUrl" target="_blank" rel="noopener noreferrer"
      :title="isShortcut ? $t('search.document_link_hint') : undefined"
      class="block sm:flex sm:items-center gap-2 sm:gap-3 px-3 sm:px-4 py-2 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-ring rounded-md"
      @click="trackDocumentClick">

      <!-- Mobile Layout: Stacked -->
      <div class="sm:hidden space-y-2">
        <!-- Title Row -->
        <div class="flex items-center gap-2">
          <!-- Document Icon -->
          <div :class="getDocumentIconClasses()" class="flex-shrink-0">
            <Icon :icon="getDocumentIcon()" class="w-3.5 h-3.5" />
          </div>
          <!-- Title -->
          <div class="flex-1 min-w-0">
            <h3 class="text-xs font-medium text-card-foreground group-hover:text-primary transition-colors line-clamp-2 leading-tight"
              :title="document.title">
              {{ document.title }}
            </h3>
          </div>
          <!-- External Link / Shortcut Icon -->
          <component :is="isShortcut ? LinkIcon : ExternalLink"
            class="w-3 h-3 text-muted-foreground group-hover:text-primary transition-colors flex-shrink-0" />
        </div>

        <!-- Metadata Row - no margin, full width -->
        <div class="flex items-center gap-2 text-xs text-muted-foreground">
          <!-- Content Type Badge -->
          <Badge :class="getContentTypeBadgeClasses()" class="text-xs font-medium px-1.5 py-0.5 max-w-24 flex-shrink-0">
            <span class="truncate block" :title="getShortContentType()">
              {{ getShortContentType() }}
            </span>
          </Badge>

          <!-- Organization -->
          <span class="max-w-20 truncate flex-shrink-0 font-medium" :title="getTenantDisplayName()">
            {{ getTenantDisplayName() }}
          </span>

          <!-- Date -->
          <span class="whitespace-nowrap flex-shrink-0 font-medium">
            {{ formatCompactDate() }}
          </span>

          <!-- Unresolved shortcut warning -->
          <AlertTriangle v-if="isUnresolvedShortcut"
            class="w-3 h-3 text-amber-600 dark:text-amber-400 flex-shrink-0"
            :title="$t('search.document_link_unresolved')" />
        </div>
      </div>

      <!-- Desktop Layout: Horizontal -->
      <div class="hidden sm:flex sm:items-center sm:gap-3 sm:w-full">
        <!-- Document Icon -->
        <div :class="getDocumentIconClasses()" class="flex-shrink-0">
          <Icon :icon="getDocumentIcon()" class="w-4 h-4" />
        </div>

        <!-- Title -->
        <div class="flex-1 min-w-0">
          <h3 class="text-sm font-medium text-card-foreground group-hover:text-primary transition-colors line-clamp-1"
            :title="document.title">
            {{ document.title }}
          </h3>
        </div>

        <!-- Compact Metadata -->
        <div class="flex items-center gap-2 flex-shrink-0 min-w-0">
          <!-- Content Type Badge -->
          <Badge :class="getContentTypeBadgeClasses()" class="text-xs font-medium px-1.5 py-0.5 max-w-24 md:max-w-40 flex-shrink-0">
            <span class="truncate block" :title="getShortContentType()">
              {{ getShortContentType() }}
            </span>
          </Badge>

          <!-- Organization (abbreviated) - Hidden on very small screens -->
          <span class="text-xs text-muted-foreground font-medium max-w-16 md:max-w-28 truncate flex-shrink-0"
            :title="getTenantDisplayName()">
            {{ getTenantDisplayName() }}
          </span>

          <!-- Date -->
          <span class="text-xs text-muted-foreground whitespace-nowrap flex-shrink-0">
            {{ formatCompactDate() }}
          </span>

          <!-- Unresolved shortcut warning -->
          <AlertTriangle v-if="isUnresolvedShortcut"
            class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400 flex-shrink-0"
            :title="$t('search.document_link_unresolved')" />

          <!-- External Link / Shortcut Icon -->
          <component :is="isShortcut ? LinkIcon : ExternalLink"
            class="w-3.5 h-3.5 text-muted-foreground group-hover:text-primary transition-colors flex-shrink-0" />
        </div>
      </div>
    </a>
  </div>
</template>

<script setup lang="ts">
// ShadcnVue components

// Icons
import { AlertTriangle, ExternalLink, Link as LinkIcon } from 'lucide-vue-next';
import { Icon } from '@iconify/vue';
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { Badge } from '@/Components/ui/badge';

// Composables
import { useDocumentDisplay, getDocumentTargetUrl, parseDocumentDate, type DocumentDisplayItem } from '@/Composables/useDocumentDisplay';

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
  getContentTypeBadgeClasses,
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
      // Current year: show month abbreviated (e.g., "Vas 12" for February 12)
      return date.toLocaleDateString('lt-LT', {
        month: 'short',
        day: 'numeric',
      });
    }
    else {
      // Other years: show year only (e.g., "2023")
      return date.getFullYear().toString();
    }
  }
  catch {
    return props.document.document_date;
  }
};
</script>

<style scoped>
/* Custom breakpoint for very small screens */
@media (min-width: 375px) {
  .xs\:block {
    display: block;
  }
}</style>

<style scoped>
/* Line clamp utility */
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .group:hover {
    transform: none;
    /* Disable hover effects on mobile */
  }
}
</style>
