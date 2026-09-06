<template>
  <HairlineRow
    :href
    :target="isExternal ? '_blank' : undefined"
    :rel="isExternal ? 'noopener' : undefined"
    :title
    :meta="metaText"
    class="px-4"
  >
    <template #leading>
      <div class="flex size-9 items-center justify-center overflow-hidden bg-secondary">
        <img
          v-if="thumbnailUrl && !imageFailed"
          :src="thumbnailUrl"
          :alt="title"
          class="size-full object-cover"
          @error="imageFailed = true"
        >
        <component :is="icon" v-else class="size-4 text-muted-foreground" />
      </div>
    </template>

    <template #trailing>
      <component
        :is="isExternal ? IFluentArrowUpRight16Regular : IFluentArrowRight16Regular"
        class="size-4 text-muted-foreground/60 transition-colors group-hover:text-brand"
      />
    </template>
  </HairlineRow>
</template>

<script setup lang="ts">
import { computed, ref, type Component } from 'vue';
import { usePage } from '@inertiajs/vue3';

import HairlineRow from '@/Components/Public/Base/HairlineRow.vue';
import { getDocumentTargetUrl, type DocumentDisplayItem } from '@/Composables/useDocumentDisplay';
import type { SearchCollectionId } from '@/Composables/usePublicMultiSearch';
import { localizedRoute } from '@/Utils/LocalizedRoutes';
import IFluentArrowRight16Regular from '~icons/fluent/arrow-right-16-regular';
import IFluentArrowUpRight16Regular from '~icons/fluent/arrow-up-right-16-regular';

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
      // Not the alias/slug — it reads as noise, not helpful context, next to the name.
      return doc.type_titles?.[0] || '';
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

const metaText = computed(() => [subtitle.value, formattedDate.value].filter(Boolean).join(' · '));

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
