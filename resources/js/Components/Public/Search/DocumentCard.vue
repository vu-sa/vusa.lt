<template>
  <Card class="group transition-all duration-300 cursor-pointer border border-border hover:border-primary/50 hover:shadow-lg hover:scale-[1.01] bg-card">
    <a 
      :href="document.anonymous_url" 
      target="_blank" 
      rel="noopener noreferrer"
      class="block p-6 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ring rounded-lg"
      @click="trackDocumentClick"
    >
      <!-- Header Section -->
      <div class="flex items-start gap-4 mb-4">
        <!-- Document Type Icon -->
        <div :class="getDocumentIconClasses()">
          <Icon :icon="getDocumentIcon()" class="w-5 h-5" />
        </div>
        
        <!-- Title and Metadata -->
        <div class="flex-1 min-w-0">
          <h3 class="text-lg font-semibold text-card-foreground mb-2 line-clamp-2 group-hover:text-primary transition-colors">
            {{ document.title }}
          </h3>
          
          <!-- Document Type Badge -->
          <div class="flex items-center gap-2 mb-3">
            <Badge :class="getContentTypeBadgeClasses()" class="text-xs font-medium">
              {{ getShortContentType() }}
            </Badge>
            
            <!-- Language Flag -->
            <Badge v-if="document.language" variant="outline" class="text-xs">
              {{ getLanguageCode() }}
            </Badge>
            
            <!-- File Extension -->
            <Badge v-if="getFileExtension()" variant="secondary" class="text-xs">
              {{ getFileExtension().toUpperCase() }}
            </Badge>
          </div>
        </div>
        
        <!-- Status Indicators -->
        <div class="flex flex-col items-end gap-2">
          <!-- Effective Status -->
          <div v-if="document.is_in_effect !== null" class="flex items-center">
            <Badge 
              :variant="document.is_in_effect ? 'default' : 'secondary'"
              class="text-xs"
            >
              <component 
                :is="document.is_in_effect ? CheckCircle : Clock" 
                class="w-3 h-3 mr-1" 
              />
              {{ document.is_in_effect ? 'Galioja' : 'Negalioja' }}
            </Badge>
          </div>
          
          <!-- External Link Indicator -->
          <ExternalLink class="w-4 h-4 text-muted-foreground group-hover:text-primary transition-colors" />
        </div>
      </div>
      
      <!-- Summary/Description -->
      <div v-if="document.summary" class="mb-4">
        <p class="text-sm text-muted-foreground line-clamp-3 leading-relaxed">
          {{ document.summary }}
        </p>
      </div>
      
      <!-- Metadata Footer -->
      <div class="flex items-center justify-between pt-4 border-t border-border/50">
        <!-- Organization Info -->
        <div class="flex items-center gap-2">
          <div class="flex items-center gap-1">
            <Building2 class="w-4 h-4 text-muted-foreground" />
            <span class="text-sm font-medium text-card-foreground">
              {{ getTenantDisplayName() }}
            </span>
          </div>
        </div>
        
        <!-- Date -->
        <div class="flex items-center gap-1 text-sm text-muted-foreground">
          <Calendar class="w-4 h-4" />
          <time :datetime="document.document_date">
            {{ formatDocumentDate() }}
          </time>
        </div>
      </div>
      
      <!-- Additional Metadata (Expandable) -->
      <Collapsible v-if="hasAdditionalMetadata" @click.stop>
        <CollapsibleTrigger 
          class="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground transition-colors"
          @click.stop
        >
          <Info class="w-3 h-3" />
          <span>Daugiau informacijos</span>
          <ChevronDown class="w-3 h-3" />
        </CollapsibleTrigger>
        <CollapsibleContent class="pt-2">
          <div class="text-xs text-muted-foreground space-y-1">
            <div v-if="document.effective_date" class="flex items-center gap-2">
              <span class="font-medium">{{ $t('search.effective_date') }}</span>
              <span>{{ formatDate(document.effective_date) }}</span>
            </div>
            <div v-if="document.expiration_date" class="flex items-center gap-2">
              <span class="font-medium">Baigia galioti:</span>
              <span>{{ formatDate(document.expiration_date) }}</span>
            </div>
            <div v-if="document.institution_name_lt" class="flex items-center gap-2">
              <span class="font-medium">Institucija:</span>
              <span>{{ document.institution_name_lt }}</span>
            </div>
          </div>
        </CollapsibleContent>
      </Collapsible>
    </a>
  </Card>
</template>

<script setup lang="ts">
// ShadcnVue components
import { Card } from '@/Components/ui/card'
import { Badge } from '@/Components/ui/badge'
import { 
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/Components/ui/collapsible'

// Icons
import { 
  Building2,
  Calendar,
  ExternalLink,
  CheckCircle,
  Clock,
  Info,
  ChevronDown,
} from 'lucide-vue-next'
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
  formatDate,
  formatDocumentDate,
  getFileExtension,
  getDocumentIcon,
  getDocumentIconClasses,
  getShortContentType,
  getContentTypeBadgeClasses,
  getLanguageCode,
  getTenantDisplayName,
  hasAdditionalMetadata,
  trackDocumentClick
} = useDocumentDisplay(props.document)
</script>

<style scoped>
/* Line clamp utilities */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .group:hover {
    transform: none; /* Disable scaling on mobile */
  }
}
</style>