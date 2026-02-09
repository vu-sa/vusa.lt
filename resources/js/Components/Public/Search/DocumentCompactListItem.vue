<template>
  <div
    class="group transition-all duration-200 border border-border/50 rounded-md bg-card hover:shadow-lg hover:bg-accent/20 hover:border-primary/30">
    <a :href="document.anonymous_url" target="_blank" rel="noopener noreferrer"
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
          <!-- External Link Icon -->
          <ExternalLink class="w-3 h-3 text-muted-foreground group-hover:text-primary transition-colors flex-shrink-0" />
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

          <!-- External Link Icon -->
          <ExternalLink class="w-3.5 h-3.5 text-muted-foreground group-hover:text-primary transition-colors flex-shrink-0" />
        </div>
      </div>
    </a>
  </div>
</template>

<script setup lang="ts">
// ShadcnVue components

// Icons
import { ExternalLink } from 'lucide-vue-next';
import { Icon } from '@iconify/vue';

import { Badge } from '@/Components/ui/badge';

// Composables
import { useDocumentDisplay, type DocumentDisplayItem } from '@/Composables/useDocumentDisplay';

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
  trackDocumentClick,
} = useDocumentDisplay(props.document);

// Compact date formatting using the proper date parsing from useDocumentDisplay
const formatCompactDate = () => {
  if (!props.document.document_date) return '';

  try {
    // Use the same date parsing logic as useDocumentDisplay
    let date: Date;
    if (typeof props.document.document_date === 'number'
      || (typeof props.document.document_date === 'string' && /^\d+$/.test(props.document.document_date))) {
      const timestamp = Number(props.document.document_date);
      date = new Date(timestamp < 10000000000 ? timestamp * 1000 : timestamp);
    }
    else {
      date = new Date(props.document.document_date);
    }

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
