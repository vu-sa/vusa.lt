<template>
  <li class="group">
    <!-- Main clickable content. Bleeds slightly past the list's own edge on hover
         (-mx/px, equal and opposite) rather than boxing the row — the house idiom
         for full-width list rows, see .ai/rules/public.md. -->
    <a
      :href="documentUrl"
      target="_blank"
      rel="noopener noreferrer"
      class="-mx-3 block px-3 py-4 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:-mx-4 sm:px-4 sm:py-5 hover:bg-secondary/50"
      @click="trackDocumentClick"
    >
      <div class="flex items-start gap-3 sm:gap-4">
        <!-- Document Icon -->
        <div :class="getDocumentIconClasses()" class="shrink-0 border border-border">
          <Icon :icon="getDocumentIcon()" class="size-4 sm:size-5" />
        </div>

        <!-- Main Content -->
        <div class="flex-1 min-w-0">
          <!-- Title + Actions row -->
          <div class="flex items-start justify-between gap-3 mb-2">
            <h3
              class="text-pretty text-base font-bold leading-snug text-foreground transition-colors group-hover:text-brand sm:text-lg"
            >
              {{ document.title }}
            </h3>

            <!-- Actions. A plain flex row with a small gap, not ButtonGroup — connected
                 buttons share a border pixel with their neighbour, so hovering the middle
                 button couldn't recolour its left edge (that edge belongs to the button
                 beside it). A gap gives every button its own complete border. -->
            <div class="shrink-0">
              <TooltipProvider>
                <div class="flex items-center gap-1 opacity-70 group-hover:opacity-100 transition-opacity">
                  <Tooltip>
                    <TooltipTrigger as-child>
                      <Button
                        variant="ghost"
                        size="icon"
                        class="h-7 w-8 border border-border text-muted-foreground hover:border-brand hover:text-brand hover:bg-brand/5"
                        @click.prevent.stop="openDocument"
                      >
                        <IFluentOpen20Regular class="size-3.5" />
                      </Button>
                    </TooltipTrigger>
                    <TooltipContent side="bottom">{{ $t('open') }}</TooltipContent>
                  </Tooltip>

                  <Tooltip v-if="downloadUrl">
                    <TooltipTrigger as-child>
                      <Button
                        variant="ghost"
                        size="icon"
                        class="h-7 w-8 border border-border text-muted-foreground hover:border-brand hover:text-brand hover:bg-brand/5"
                        @click.prevent.stop="downloadDocument"
                      >
                        <IFluentArrowDownload20Regular class="size-3.5" />
                      </Button>
                    </TooltipTrigger>
                    <TooltipContent side="bottom">{{ $t('download') }}</TooltipContent>
                  </Tooltip>

                  <Tooltip v-if="document.link_url || document.share_url">
                    <TooltipTrigger as-child>
                      <Button
                        variant="ghost"
                        size="icon"
                        class="h-7 w-8 border border-border text-muted-foreground hover:border-brand hover:text-brand hover:bg-brand/5"
                        @click.prevent.stop="copyShareUrl"
                      >
                        <IFluentLink20Regular class="size-3.5" />
                      </Button>
                    </TooltipTrigger>
                    <TooltipContent side="bottom">{{ $t('copy_link') }}</TooltipContent>
                  </Tooltip>
                </div>
              </TooltipProvider>
            </div>
          </div>

          <!-- Badges row: one flow that wraps, instead of a separate mobile/desktop pair —
               TagChip is compact enough to not need the split. -->
          <div class="flex items-center gap-1.5 flex-wrap">
            <!-- Organization -->
            <TagChip variant="muted" class="max-w-40 sm:max-w-48">
              <IFluentBuilding20Regular class="size-3 mr-1 shrink-0" />
              <span class="truncate normal-case tracking-normal font-medium">{{ getTenantDisplayName() }}</span>
            </TagChip>

            <!-- Link (shortcut) -->
            <TagChip v-if="isShortcut" variant="outline" class="shrink-0">
              <IFluentLink20Regular class="size-3 mr-1" />
              {{ $t('search.document_link_badge') }}
            </TagChip>

            <!-- Content Type -->
            <TagChip v-if="document.content_type" variant="muted" class="max-w-36 sm:max-w-52">
              <IFluentDocument20Regular class="size-3 mr-1 shrink-0" />
              <span class="truncate normal-case tracking-normal font-medium">{{ document.content_type }}</span>
            </TagChip>

            <!-- Language -->
            <TagChip v-if="document.language" variant="muted" class="shrink-0">
              <span class="inline-flex items-center gap-1.5">
                <LocaleFlag v-if="languageFlagLocale" :locale="languageFlagLocale" />
                <span class="normal-case tracking-normal">{{ getLanguageCode() }}</span>
              </span>
            </TagChip>

            <!-- Status -->
            <TagChip
              v-if="'is_in_effect' in document && document.is_in_effect !== null"
              variant="muted"
              class="shrink-0"
            >
              <span
                class="mr-1.5 size-1.5 shrink-0"
                :class="document.is_in_effect ? 'bg-status-success' : 'bg-status-neutral'"
              />
              <span class="normal-case tracking-normal font-medium">
                {{ document.is_in_effect ? $t('Galioja') : $t('Negalioja') }}
              </span>
            </TagChip>

            <!-- Date -->
            <TagChip variant="muted" class="shrink-0">
              <IFluentCalendarLtr20Regular class="size-3 mr-1" />
              <span class="normal-case tracking-normal">{{ formatDocumentDate() }}</span>
            </TagChip>
          </div>

          <!-- Unresolved shortcut warning -->
          <div
            v-if="isUnresolvedShortcut"
            class="mt-2 flex items-start gap-1.5 text-xs text-brand"
          >
            <IFluentWarning20Regular class="size-3.5 mt-0.5 shrink-0" />
            <span>{{ $t('search.document_link_unresolved') }}</span>
          </div>

          <!-- Summary -->
          <div v-if="document.summary" class="mt-2.5">
            <p class="text-sm text-muted-foreground line-clamp-2 leading-relaxed">
              {{ document.summary }}
            </p>
          </div>
        </div>
      </div>
    </a>
  </li>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Icon } from '@iconify/vue';
import { trans as $t } from 'laravel-vue-i18n';

import { useDocumentDisplay, getDocumentTargetUrl, type DocumentDisplayItem } from '@/Composables/useDocumentDisplay';
import { useToasts } from '@/Composables/useToasts';
import { Button } from '@/Components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { TagChip } from '@/Components/Public/Base';
import LocaleFlag from '@/Components/Public/Nav/LocaleFlag.vue';
import IFluentArrowDownload20Regular from '~icons/fluent/arrow-download-20-regular';
import IFluentBuilding20Regular from '~icons/fluent/building-20-regular';
import IFluentCalendarLtr20Regular from '~icons/fluent/calendar-ltr-20-regular';
import IFluentDocument20Regular from '~icons/fluent/document-20-regular';
import IFluentLink20Regular from '~icons/fluent/link-20-regular';
import IFluentOpen20Regular from '~icons/fluent/open-20-regular';
import IFluentWarning20Regular from '~icons/fluent/warning-20-regular';

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
  isShortcut,
  isUnresolvedShortcut,
  trackDocumentClick,
} = useDocumentDisplay(props.document);

// For list view, use simple date format
const formatDocumentDate = formatDocumentDateSimple;

// The flag is purely decorative (LocaleFlag is aria-hidden) and only means something for the
// two locales it can actually draw — an "OTHER" language keeps the plain text label alone.
const languageFlagLocale = computed(() => {
  const code = getLanguageCode();
  if (code === 'LT') return 'lt';
  if (code === 'EN') return 'en';
  return null;
});

// .url shortcuts link straight to their resolved target; otherwise share_url
// goes through DocumentRedirectController which appends web=1 server-side,
// falling back to the raw anonymous_url with web=1 added client-side.
const documentUrl = computed(() => getDocumentTargetUrl(props.document));

// Download URL appends ?download=1 to the share/anonymous URL.
// A .url shortcut has nothing meaningful to download.
const downloadUrl = computed(() => {
  if (isShortcut.value) return undefined;
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
  const url = props.document.link_url || props.document.share_url;
  if (!url) {
    return;
  }

  try {
    await navigator.clipboard.writeText(url);
    toasts.success($t('copy_link_success'));
  }
  catch {
    toasts.error($t('copy_link_error'));
  }
};
</script>
