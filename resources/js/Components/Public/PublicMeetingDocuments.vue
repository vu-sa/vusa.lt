<template>
  <ul class="divide-y divide-border border-y border-border">
    <li v-for="document in documents" :key="document.id">
      <a
        :href="documentUrl(document)"
        target="_blank"
        rel="noopener noreferrer"
        class="flex items-start gap-3 px-1 py-3 transition-colors hover:bg-secondary/60 sm:px-2"
      >
        <IFluentDocument20Regular class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
        <span class="min-w-0 flex-1">
          <span class="block text-sm font-medium text-foreground">
            {{ document.title }}
            <!-- Only when the file is not in the page's language: the list falls back to the
                 other locale rather than showing nothing, so say which one this is. -->
            <span
              v-if="isOtherLanguage(document)"
              class="ml-1.5 align-middle border border-border px-1 py-px font-mono text-[0.625rem] font-semibold uppercase tracking-wide text-muted-foreground"
            >{{ document.language_code }}</span>
          </span>
          <span class="block text-xs text-muted-foreground">
            {{ document.content_type }}
            <template v-if="document.document_date"> · {{ document.document_date }}</template>
          </span>
        </span>
        <IFluentOpen20Regular class="mt-0.5 size-3.5 shrink-0 text-muted-foreground/60" />
      </a>
    </li>
  </ul>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

import IFluentDocument20Regular from '~icons/fluent/document20-regular';
import IFluentOpen20Regular from '~icons/fluent/open-20-regular';

export interface PublicMeetingDocument {
  id: number;
  title: string;
  content_type: string | null;
  document_date: string | null;
  anonymous_url: string;
  language: string | null;
  /** Normalized `lt` / `en` / `unknown` — see Document::getLanguageCode(). */
  language_code?: string | null;
}

defineProps<{
  documents: PublicMeetingDocument[];
}>();

const page = usePage();

const isOtherLanguage = (document: PublicMeetingDocument): boolean =>
  !!document.language_code
  && document.language_code !== 'unknown'
  && document.language_code !== page.props.app.locale;

/**
 * `web=1` makes SharePoint open the file in the browser viewer rather than downloading it —
 * the same behaviour the public document search relies on (see useDocumentDisplay.ts).
 */
const documentUrl = (document: PublicMeetingDocument): string => {
  const url = document.anonymous_url;
  return url.includes('web=1') ? url : `${url}${url.includes('?') ? '&' : '?'}web=1`;
};
</script>
