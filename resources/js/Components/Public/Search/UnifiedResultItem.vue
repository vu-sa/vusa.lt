<template>
  <SmartLink
    :href
    :target="isExternal ? '_blank' : undefined"
    :rel="isExternal ? 'noopener' : undefined"
    :class="[
      'group flex items-center gap-3 rounded-md border border-border bg-card',
      'px-3 py-2.5 transition-all duration-200',
      'hover:bg-accent/20 hover:border-primary/30',
    ]"
  >
    <!-- Thumbnail (news with image) or type icon -->
    <div class="flex size-9 flex-shrink-0 items-center justify-center overflow-hidden rounded-lg bg-muted">
      <img
        v-if="thumbnailUrl && !imageFailed"
        :src="thumbnailUrl"
        :alt="title"
        class="size-full object-cover"
        @error="imageFailed = true"
      >
      <component :is="icon" v-else class="size-4 text-muted-foreground" />
    </div>

    <!-- Content -->
    <div class="min-w-0 flex-1">
      <p class="truncate text-sm font-medium text-foreground">
        {{ title }}
      </p>
      <div v-if="subtitle || formattedDate" class="mt-0.5 flex items-center gap-2">
        <span v-if="subtitle" class="truncate text-xs text-muted-foreground">
          {{ subtitle }}
        </span>
        <span v-if="subtitle && formattedDate" class="text-muted-foreground/40">·</span>
        <span v-if="formattedDate" class="flex-shrink-0 text-xs text-muted-foreground">
          {{ formattedDate }}
        </span>
      </div>
    </div>

    <!-- External indicator -->
    <ArrowUpRight v-if="isExternal" class="size-4 flex-shrink-0 text-muted-foreground/60 transition-colors group-hover:text-foreground" />
    <ArrowRight v-else class="size-4 flex-shrink-0 text-muted-foreground/60 transition-colors group-hover:text-foreground" />
  </SmartLink>
</template>

<script setup lang="ts">
import { localizedRoute } from '@/Utils/LocalizedRoutes';
import { computed, ref, type Component } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { ArrowRight, ArrowUpRight } from 'lucide-vue-next';

import SmartLink from '@/Components/Public/SmartLink.vue';
import { getDocumentTargetUrl, type DocumentDisplayItem } from '@/Composables/useDocumentDisplay';
import type { SearchCollectionId } from '@/Composables/usePublicMultiSearch';

interface Props {
  collection: SearchCollectionId;
  doc: Record<string, any>;
  icon: Component;
}

const props = defineProps<Props>();

const page = usePage();

const locale = computed(() => (page.props.app as { locale?: string })?.locale || 'lt');
const subdomain = computed(() => (page.props.tenant as { subdomain?: string })?.subdomain ?? 'www');
const appUrl = computed(() => (page.props.app as { url?: string })?.url || '');

// -- Title (locale-aware) -----------------------------------------------------

const title = computed(() => {
  const { doc } = props;
  switch (props.collection) {
    case 'institutions':
      return doc.name_lt || doc.name_en || doc.short_name_lt || doc.short_name_en || '';
    case 'calendar':
      return doc.title_lt || doc.title_en || doc.title || '';
    default:
      return doc.title || doc.name || '';
  }
});

// -- Subtitle (snippet / type label) -----------------------------------------

const stripHtml = (html: string): string => {
  if (!html) return '';
  const tmp = document.createElement('div');
  tmp.innerHTML = html;
  return tmp.textContent || tmp.innerText || '';
};

const subtitle = computed(() => {
  const { doc } = props;
  let text = '';
  switch (props.collection) {
    case 'news':
      text = doc.short || doc.summary || '';
      break;
    case 'documents':
      text = doc.summary || '';
      break;
    case 'institutions':
      return doc.type_titles?.[0] || doc.alias || '';
    case 'meetings':
      return doc.institution_name_lt || doc.institution_name_en || '';
    case 'pages':
      text = doc.meta_description || doc.category_name || '';
      break;
    default:
      return '';
  }
  return stripHtml(text);
});

// -- Thumbnail (news only — the only public collection with indexed images) --

const imageFailed = ref(false);
const thumbnailUrl = computed<string | null>(() => {
  if (props.collection !== 'news') return null;
  const { image } = props.doc;
  if (!image || typeof image !== 'string') return null;
  return image.startsWith('http') ? image : `${appUrl.value}/uploads/${image}`;
});

// -- Date --------------------------------------------------------------------

const timestamp = computed<number | null>(() => {
  const { doc } = props;
  const raw = doc.start_time ?? doc.document_date ?? doc.publish_time ?? doc.date;
  if (!raw) return null;
  return typeof raw === 'number' ? raw : null;
});

const formattedDate = computed(() => {
  if (!timestamp.value) return '';
  return new Date(timestamp.value * 1000).toLocaleDateString(locale.value === 'en' ? 'en-GB' : 'lt-LT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
});

// -- URL / routing -----------------------------------------------------------

const isExternal = computed(() => props.collection === 'documents');

const href = computed(() => {
  const { doc } = props;
  const base = { lang: locale.value, subdomain: subdomain.value };

  try {
    switch (props.collection) {
      case 'meetings':
        return route('publicMeetings.show', {
          meeting: doc.id,
          ...(subdomain.value ? { subdomain: subdomain.value } : {}),
        });
      case 'documents':
        return getDocumentTargetUrl(doc as DocumentDisplayItem) || '#';
      case 'institutions':
        if (doc.alias) {
          return route('contacts.alias', { ...base, institution: doc.alias });
        }
        return route('contacts.institution', { ...base, institution: doc.id });
      case 'news':
        return localizedRoute('news', { subdomain: base.subdomain, news: doc.permalink }, base.lang);
      case 'pages':
        return route('page', { ...base, permalink: doc.permalink });
      case 'calendar':
        return route('calendar.event', { ...base, calendar: doc.id });
      default:
        return '#';
    }
  }
  catch {
    return '#';
  }
});
</script>
