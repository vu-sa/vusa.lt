<template>
  <div
    class="group transition-all duration-200 border border-border/50 rounded-md bg-card hover:shadow-lg hover:bg-accent/20 hover:border-primary/30">
    <a :href="document.anonymous_url" target="_blank" rel="noopener noreferrer"
      class="flex items-center gap-3 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-ring rounded-md min-h-[48px]"
      @click="trackDocumentClick">
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
      <div class="flex items-center gap-2 flex-shrink-0">
        <!-- Content Type Badge -->
        <Badge :class="getContentTypeBadgeClasses()" class="text-xs font-medium px-2 py-0.5 max-w-20 md:max-w-40">
          <span class="truncate block" :title="getShortContentType()">
            {{ getShortContentType() }}
          </span>
        </Badge>

        <!-- Organization (abbreviated) -->
        <span class="text-xs text-muted-foreground font-medium max-w-16 sm:max-w-28 truncate block"
          :title="getTenantDisplayName()">
          {{ getTenantDisplayName() }}
        </span>

        <!-- Date -->
        <span class="text-xs text-muted-foreground whitespace-nowrap">
          {{ formatCompactDate() }}
        </span>

        <!-- External Link Icon -->
        <ExternalLink class="w-3.5 h-3.5 text-muted-foreground group-hover:text-primary transition-colors" />
      </div>
    </a>
  </div>
</template>

<script setup lang="ts">
// ShadcnVue components
import { Badge } from '@/Components/ui/badge'

// Icons
import { ExternalLink } from 'lucide-vue-next'
import { Icon } from '@iconify/vue'

// Composables
import { useDocumentDisplay, type DocumentDisplayItem } from '@/Composables/useDocumentDisplay'

// Props
interface Props {
  document: DocumentDisplayItem
}

const props = defineProps<Props>()

// Use shared document display logic
const {
  getDocumentIcon,
  getDocumentIconClasses,
  getShortContentType,
  getContentTypeBadgeClasses,
  getTenantDisplayName,
  trackDocumentClick
} = useDocumentDisplay(props.document)

// Compact date formatting using the proper date parsing from useDocumentDisplay
const formatCompactDate = () => {
  if (!props.document.document_date) return ''

  try {
    // Use the same date parsing logic as useDocumentDisplay
    let date: Date
    if (typeof props.document.document_date === 'number' ||
      (typeof props.document.document_date === 'string' && /^\d+$/.test(props.document.document_date))) {
      const timestamp = Number(props.document.document_date)
      date = new Date(timestamp < 10000000000 ? timestamp * 1000 : timestamp)
    } else {
      date = new Date(props.document.document_date)
    }

    const now = new Date()
    const isCurrentYear = date.getFullYear() === now.getFullYear()

    if (isCurrentYear) {
      // Current year: show month abbreviated (e.g., "Vas 12" for February 12)
      return date.toLocaleDateString('lt-LT', {
        month: 'short',
        day: 'numeric'
      })
    } else {
      // Other years: show year only (e.g., "2023")
      return date.getFullYear().toString()
    }
  } catch {
    return props.document.document_date
  }
}
</script>

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
