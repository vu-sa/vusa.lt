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
          <!-- Title + Actions row -->
          <div class="flex items-start justify-between gap-3 mb-1.5">
            <h3
              class="text-sm sm:text-base font-semibold text-card-foreground group-hover:text-primary transition-colors line-clamp-2 leading-tight sm:leading-normal">
              {{ document.title }}
            </h3>

            <!-- Actions -->
            <div class="flex-shrink-0">
              <TooltipProvider>
                <ButtonGroup class="opacity-50 group-hover:opacity-100 transition-opacity">
                  <Tooltip>
                    <TooltipTrigger as-child>
                      <Button
                        variant="outline"
                        size="icon"
                        class="h-7 w-8 text-muted-foreground hover:text-primary"
                        @click.prevent.stop="openDocument"
                      >
                        <ExternalLink class="w-3.5 h-3.5" />
                      </Button>
                    </TooltipTrigger>
                    <TooltipContent side="bottom">{{ $t('open') }}</TooltipContent>
                  </Tooltip>

                  <Tooltip v-if="downloadUrl">
                    <TooltipTrigger as-child>
                      <Button
                        variant="outline"
                        size="icon"
                        class="h-7 w-8 text-muted-foreground hover:text-primary"
                        @click.prevent.stop="downloadDocument"
                      >
                        <Download class="w-3.5 h-3.5" />
                      </Button>
                    </TooltipTrigger>
                    <TooltipContent side="bottom">{{ $t('download') }}</TooltipContent>
                  </Tooltip>

                  <Tooltip v-if="document.share_url">
                    <TooltipTrigger as-child>
                      <Button
                        variant="outline"
                        size="icon"
                        class="h-7 w-8 text-muted-foreground hover:text-primary"
                        @click.prevent.stop="copyShareUrl"
                      >
                        <LinkIcon class="w-3.5 h-3.5" />
                      </Button>
                    </TooltipTrigger>
                    <TooltipContent side="bottom">{{ $t('copy_link') }}</TooltipContent>
                  </Tooltip>
                </ButtonGroup>
              </TooltipProvider>
            </div>
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

          <!-- Desktop Layout: Badges and date row -->
          <div class="hidden sm:flex items-center gap-1.5 flex-wrap">
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

            <!-- Date — inline with badges -->
            <Badge variant="outline" class="text-xs flex-shrink-0">
              <Calendar class="w-3 h-3 mr-1" />
              {{ formatDocumentDate() }}
            </Badge>
          </div>

          <!-- Summary -->
          <div v-if="document.summary" class="mt-2 sm:mt-3">
            <p class="text-xs sm:text-sm text-muted-foreground line-clamp-2 leading-relaxed">
              {{ document.summary }}
            </p>
          </div>
        </div>
      </div>
    </a>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

// ShadcnVue components
import {
  Building2,
  Calendar,
  Download,
  ExternalLink,
  CheckCircle,
  Clock,
  FileText,
  Link as LinkIcon,
} from 'lucide-vue-next';
import { Icon } from '@iconify/vue';
import { trans as $t } from 'laravel-vue-i18n';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { ButtonGroup } from '@/Components/ui/button-group';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';

// Icons

// Icons

// Composables
import { useDocumentDisplay, type DocumentDisplayItem } from '@/Composables/useDocumentDisplay';
import { useToasts } from '@/Composables/useToasts';

// Props
interface Props {
  document: DocumentDisplayItem;
}

const props = defineProps<Props>();
const toasts = useToasts();

// Use shared document display logic - use simple date format for list view
const {
  formatDocumentDateSimple,
  getDocumentIcon,
  getDocumentIconClasses,
  getLanguageCode,
  getTenantDisplayName,
  trackDocumentClick,
} = useDocumentDisplay(props.document);

// For list view, use simple date format
const formatDocumentDate = formatDocumentDateSimple;

// Use share_url for linking, fallback to anonymous_url
const documentUrl = computed(() => props.document.share_url || props.document.anonymous_url);

// Download URL appends ?download=1 to the share/anonymous URL
const downloadUrl = computed(() => {
  const base = props.document.share_url || props.document.anonymous_url;
  if (!base) return undefined;
  const separator = base.includes('?') ? '&' : '?';
  return `${base}${separator}download=1`;
});

// Open document in new tab
const openDocument = () => {
  if (documentUrl.value) {
    trackDocumentClick();
    window.open(documentUrl.value, '_blank', 'noopener,noreferrer');
  }
};

// Download document
const downloadDocument = () => {
  if (downloadUrl.value) {
    trackDocumentClick();
    window.open(downloadUrl.value, '_blank', 'noopener,noreferrer');
  }
};

// Copy share URL to clipboard
const copyShareUrl = async () => {
  if (!props.document.share_url) {
    return;
  }

  try {
    await navigator.clipboard.writeText(props.document.share_url);
    toasts.success($t('copy_link_success'));
  }
  catch {
    toasts.error($t('copy_link_error'));
  }
};
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
