<template>
  <div
    class="group transition-all duration-200 border rounded-lg bg-card hover:shadow-lg hover:bg-accent/20">
    <!-- Main clickable content -->
    <a :href="documentUrl" target="_blank" rel="noopener noreferrer"
      class="block p-3 sm:p-4 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ring rounded-lg"
      @click="trackDocumentClick">
      <div class="flex items-start gap-3 sm:gap-4">
        <!-- Document Icon -->
        <div :class="getDocumentIconClasses()" class="flex-shrink-0 mt-0.5">
          <Icon :icon="getDocumentIcon()" class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>

        <!-- Main Content -->
        <div class="flex-1 min-w-0">
          <!-- Title -->
          <div class="mb-2 sm:mb-3">
            <h3
              class="text-sm sm:text-base font-semibold text-card-foreground group-hover:text-primary transition-colors line-clamp-2 leading-tight sm:leading-normal">
              {{ document.title }}
            </h3>
          </div>

          <!-- Mobile Layout: Stack everything -->
          <div class="sm:hidden space-y-2">
            <!-- Primary badges row -->
            <div class="flex items-center gap-1 flex-wrap">
              <!-- Organization -->
              <Badge variant="outline" class="text-xs px-1.5 py-0.5 max-w-40">
                <Building2 class="w-3 h-3 mr-1 flex-shrink-0" />
                <span class="truncate">{{ getTenantDisplayName() }}</span>
              </Badge>

              <!-- Date Badge -->
              <Badge variant="outline" class="text-xs px-1.5 py-0.5 flex-shrink-0">
                <Calendar class="w-3 h-3 mr-1" />
                {{ formatDocumentDate() }}
              </Badge>
            </div>

            <!-- Secondary badges row -->
            <div class="flex items-center gap-1 flex-wrap">
              <!-- Content Type -->
              <Badge v-if="document.content_type" variant="outline" class="text-xs px-1.5 py-0.5 max-w-36">
                <FileText class="w-3 h-3 mr-1 flex-shrink-0" />
                <span class="truncate">{{ document.content_type }}</span>
              </Badge>

              <!-- Language -->
              <Badge v-if="document.language" variant="secondary" class="text-xs px-1.5 py-0.5 flex-shrink-0">
                {{ getLanguageCode() }}
              </Badge>

              <!-- Status -->
              <Badge v-if="'is_in_effect' in document && document.is_in_effect !== null" 
                :variant="document.is_in_effect ? 'default' : 'secondary'"
                class="text-xs px-1.5 py-0.5 flex-shrink-0">
                <component :is="document.is_in_effect ? CheckCircle : Clock" class="w-3 h-3 mr-1" />
                <span class="hidden xs:inline">{{ document.is_in_effect ? 'Galioja' : 'Negalioja' }}</span>
                <span class="xs:hidden">{{ document.is_in_effect ? '✓' : '○' }}</span>
              </Badge>
            </div>
          </div>

          <!-- Desktop Layout: Horizontal with date on right -->
          <div class="hidden sm:block">
            <!-- Badges and Date Row -->
            <div class="flex items-start justify-between gap-4 mb-2">
              <div class="flex-1 min-w-0">
                <!-- Badges Row -->
                <div class="flex items-center gap-1.5 flex-wrap">
                  <!-- Organization -->
                  <Badge variant="outline" class="text-xs max-w-48">
                    <Building2 class="w-3 h-3 mr-1 flex-shrink-0" />
                    <span class="truncate">{{ getTenantDisplayName() }}</span>
                  </Badge>

                  <!-- Content Type -->
                  <Badge v-if="document.content_type" variant="outline" class="text-xs max-w-52">
                    <FileText class="w-3 h-3 mr-1 flex-shrink-0" />
                    <span class="truncate">{{ document.content_type }}</span>
                  </Badge>

                  <!-- Language -->
                  <Badge v-if="document.language" variant="secondary" class="text-xs flex-shrink-0">
                    {{ getLanguageCode() }}
                  </Badge>

                  <!-- Status -->
                  <Badge v-if="'is_in_effect' in document && document.is_in_effect !== null" 
                    :variant="document.is_in_effect ? 'default' : 'secondary'"
                    class="text-xs flex-shrink-0">
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

                <!-- Copy Link Button (only if share_url available) -->
                <Button
                  v-if="document.share_url"
                  variant="ghost"
                  size="icon"
                  class="h-7 w-7 text-muted-foreground hover:text-primary"
                  :title="$t('copy_link')"
                  @click.prevent.stop="copyShareUrl"
                >
                  <LinkIcon class="w-4 h-4" />
                </Button>

                <!-- External Link Icon -->
                <ExternalLink class="w-4 h-4 text-muted-foreground group-hover:text-primary transition-colors" />
              </div>
            </div>
          </div>

          <!-- Summary -->
          <div v-if="document.summary" class="mt-2 sm:mt-3">
            <p class="text-xs sm:text-sm text-muted-foreground line-clamp-2 leading-relaxed">
              {{ document.summary }}
            </p>
          </div>
        </div>

        <!-- Mobile External Link Icon -->
        <div class="sm:hidden flex-shrink-0 mt-1">
          <ExternalLink class="w-4 h-4 text-muted-foreground group-hover:text-primary transition-colors" />
        </div>
      </div>
    </a>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

// ShadcnVue components
import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'

// Icons
import {
  Building2,
  Calendar,
  ExternalLink,
  CheckCircle,
  Clock,
  FileText,
  Link as LinkIcon,
} from 'lucide-vue-next'
import { Icon } from '@iconify/vue'

// Composables
import { useDocumentDisplay, type DocumentDisplayItem } from '@/Composables/useDocumentDisplay'
import { useToasts } from '@/Composables/useToasts'
import { trans as $t } from 'laravel-vue-i18n'

// Props
interface Props {
  document: DocumentDisplayItem
}

const props = defineProps<Props>()
const toasts = useToasts()

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

// Use share_url for linking, fallback to anonymous_url
const documentUrl = computed(() => props.document.share_url || props.document.anonymous_url)

// Copy share URL to clipboard
const copyShareUrl = async () => {
  if (!props.document.share_url) {
    return
  }
  
  try {
    await navigator.clipboard.writeText(props.document.share_url)
    toasts.success($t('copy_link_success'))
  } catch {
    toasts.error($t('copy_link_error'))
  }
}
</script>

<style scoped>
/* Custom breakpoint for very small screens */
@media (min-width: 375px) {
  .xs\:inline {
    display: inline;
  }
  .xs\:hidden {
    display: none;
  }
}</style>
