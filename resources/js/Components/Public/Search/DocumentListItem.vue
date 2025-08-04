<template>
  <div
    class="group transition-all duration-200 border rounded-lg bg-card hover:shadow-lg hover:bg-accent/20 min-h-[88px]">
    <!-- Main clickable content -->
    <a :href="document.anonymous_url" target="_blank" rel="noopener noreferrer"
      class="block p-4 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ring rounded-lg"
      @click="trackDocumentClick">
      <div class="flex items-start gap-4">
        <!-- Document Icon -->
        <div :class="getDocumentIconClasses()" class="flex-shrink-0">
          <Icon :icon="getDocumentIcon()" class="w-5 h-5" />
        </div>

        <!-- Main Content -->
        <div class="flex-1 min-w-0">
          <!-- Title and Badges Row -->
          <div class="flex items-start justify-between gap-4 mb-2">
            <div class="flex-1 min-w-0">
              <h3
                class="text-base font-semibold text-card-foreground group-hover:text-primary transition-colors line-clamp-1 mb-2">
                {{ document.title }}
              </h3>

              <!-- Badges Row -->
              <div class="flex items-center gap-2 flex-wrap">
                <!-- Organization -->
                <Badge variant="outline" class="text-xs">
                  <Building2 class="w-3 h-3 mr-1" />
                  {{ getTenantDisplayName() }}
                </Badge>

                <!-- Content Type -->
                <Badge v-if="document.content_type" variant="outline" class="text-xs">
                  <FileText class="w-3 h-3 mr-1" />
                  {{ document.content_type }}
                </Badge>

                <!-- Language -->
                <Badge v-if="document.language" variant="secondary" class="text-xs">
                  {{ getLanguageCode() }}
                </Badge>

                <!-- Status -->
                <Badge v-if="'is_in_effect' in document && document.is_in_effect !== null" :variant="document.is_in_effect ? 'default' : 'secondary'"
                  class="text-xs">
                  <component :is="document.is_in_effect ? CheckCircle : Clock" class="w-3 h-3 mr-1" />
                  {{ document.is_in_effect ? 'Galioja' : 'Negalioja' }}
                </Badge>
              </div>
            </div>

            <!-- Date and Actions -->
            <div class="flex items-center gap-3 flex-shrink-0">
              <!-- Date Badge -->
              <Badge variant="outline" class="text-xs">
                <Calendar class="w-3 h-3 mr-1" />
                {{ formatDocumentDate() }}
              </Badge>

              <!-- External Link Icon -->
              <ExternalLink class="w-4 h-4 text-muted-foreground group-hover:text-primary transition-colors" />
            </div>
          </div>

          <!-- Summary -->
          <div v-if="document.summary" class="mt-3">
            <p class="text-sm text-muted-foreground line-clamp-2 leading-relaxed">
              {{ document.summary }}
            </p>
          </div>
        </div>
      </div>
    </a>
  </div>
</template>

<script setup lang="ts">
// ShadcnVue components
import { Badge } from '@/Components/ui/badge'

// Icons
import {
  Building2,
  Calendar,
  ExternalLink,
  CheckCircle,
  Clock,
  FileText,
} from 'lucide-vue-next'
import { Icon } from '@iconify/vue'

// Composables
import { useDocumentDisplay, type DocumentDisplayItem } from '@/Composables/useDocumentDisplay'

// Props
interface Props {
  document: DocumentDisplayItem
}

const props = defineProps<Props>()

// Use shared document display logic - use simple date format for list view
const {
  formatDocumentDateSimple,
  getDocumentIcon,
  getDocumentIconClasses,
  getLanguageCode,
  getTenantDisplayName,
  trackDocumentClick
} = useDocumentDisplay(props.document)

// For list view, use simple date format
const formatDocumentDate = formatDocumentDateSimple
</script>
