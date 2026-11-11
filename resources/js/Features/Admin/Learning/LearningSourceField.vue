<template>
  <FormFieldWrapper
    id="step-source"
    :label="$t('learning_library.fields.source')"
    :hint="locale === 'en' ? $t('learning_library.source.en_optional_hint') : $t('learning_library.fields.source_hint')"
    :error="errors[`sources.${locale}`] || errors[`sources.${locale}.document_id`] || errors[`sources.${locale}.url`]"
    :required="locale === 'lt'"
  >
    <div class="space-y-3" :data-testid="`source-block-${locale}`">
      <FormSegmentedControl
        :model-value="modes[locale]"
        :options="modeOptions"
        :ariaLabel="$t('learning_library.fields.source')"
        :test-id-prefix="`source-mode-${locale}`"
        @update:model-value="mode => setMode(locale, mode)"
      />

      <div v-if="modes[locale] === 'document'" class="space-y-2">
        <div
          v-if="selected[locale]"
          class="flex items-center justify-between gap-2 border border-border bg-surface-subtle px-3 py-2"
          :data-testid="`selected-document-${locale}`"
        >
          <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
            <span class="truncate text-sm font-medium text-foreground">{{ selected[locale]!.title }}</span>
            <span v-if="selected[locale]!.date" class="text-xs tabular-nums text-muted-foreground">{{ selected[locale]!.date }}</span>
            <StatusBadge v-if="documentStatuses[selected[locale]!.status]" :status="documentStatuses[selected[locale]!.status]" voice="sentence" />
          </div>
          <Button
            type="button"
            variant="ghost"
            size="icon"
            class="size-11 shrink-0"
            :aria-label="$t('learning_library.source.clear')"
            :data-testid="`clear-document-${locale}`"
            @click="clearDocument(locale)"
          >
            <X class="size-4" aria-hidden="true" />
          </Button>
        </div>

        <div v-else class="relative">
          <div class="relative flex items-center">
            <Search class="pointer-events-none absolute left-3 size-4 text-muted-foreground" aria-hidden="true" />
            <Input
              :id="`source-doc-search-${locale}`"
              v-model="queries[locale]"
              type="text"
              :placeholder="$t('learning_library.source.search_placeholder')"
              :class="['pl-9', fieldSurfaceClass]"
              :data-testid="`document-search-input-${locale}`"
              @focus="search(locale)"
              @input="onInput(locale)"
            />
          </div>

          <div
            v-if="open[locale] && (searching[locale] || results[locale].length > 0 || queries[locale].trim().length > 0)"
            class="mt-1 max-h-60 overflow-y-auto border border-border bg-popover text-popover-foreground"
            role="listbox"
            :data-testid="`document-results-${locale}`"
          >
            <p v-if="searching[locale]" class="px-3 py-2 text-xs text-muted-foreground">{{ $t('learning_library.source.searching') }}</p>
            <p v-else-if="results[locale].length === 0" class="px-3 py-2 text-xs text-muted-foreground">{{ $t('learning_library.source.no_results') }}</p>
            <button
              v-for="doc in results[locale]"
              :key="doc.id"
              type="button"
              role="option"
              class="flex min-h-11 w-full items-center justify-between gap-2 border-b border-border px-3 py-2 text-left text-sm last:border-b-0 hover:bg-accent"
              :data-testid="`document-hit-${locale}-${doc.id}`"
              @click="selectDocument(locale, doc)"
            >
              <span class="min-w-0 flex-1 truncate font-medium">{{ doc.title }}</span>
              <span class="flex shrink-0 items-center gap-2">
                <span v-if="doc.date" class="text-xs tabular-nums text-muted-foreground">{{ doc.date }}</span>
                <StatusBadge v-if="documentStatuses[doc.status]" :status="documentStatuses[doc.status]" voice="sentence" />
              </span>
            </button>
          </div>
        </div>

        <p
          v-if="selected[locale] && selected[locale]!.status !== DocumentStatus.Published"
          :data-testid="`hidden-document-warning-${locale}`"
          data-slot="hidden-document-warning"
          class="text-xs text-status-attention"
        >
          {{ $t('learning_library.source.hidden_document_warning') }}
        </p>
      </div>

      <Input
        v-else
        :id="`source-url-${locale}`"
        v-model="sources[locale].url"
        type="url"
        inputmode="url"
        :class="fieldSurfaceClass"
        :placeholder="$t('learning_library.source.url_placeholder')"
        :data-testid="`source-url-${locale}`"
      />
    </div>
  </FormFieldWrapper>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Search, X } from '@lucide/vue';
import { computed, reactive } from 'vue';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import { FormSegmentedControl, StatusBadge, type FormSegmentOption } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';
import { documentStatuses } from '@/Constants/statusIcons';
import { DocumentStatus } from '@/Types/enums';
import type { LearningDocumentSearchHit } from '@/Types/learning';

type LocaleKey = 'lt' | 'en';
type SourceMode = 'document' | 'url';
export type LearningSourceDraft = Record<LocaleKey, { document_id: number | null; url: string }>;

const props = defineProps<{
  locale: LocaleKey;
  errors: Partial<Record<string, string>>;
  documents?: Partial<Record<string, LearningDocumentSearchHit>>;
}>();
const sources = defineModel<LearningSourceDraft>({ required: true });

const modeFor = (locale: LocaleKey): SourceMode => sources.value[locale].url && !sources.value[locale].document_id ? 'url' : 'document';
const modes = reactive<Record<LocaleKey, SourceMode>>({ lt: modeFor('lt'), en: modeFor('en') });
const selected = reactive<Record<LocaleKey, LearningDocumentSearchHit | null>>({ lt: props.documents?.lt ?? null, en: props.documents?.en ?? null });
const queries = reactive<Record<LocaleKey, string>>({ lt: '', en: '' });
const results = reactive<Record<LocaleKey, LearningDocumentSearchHit[]>>({ lt: [], en: [] });
const searching = reactive<Record<LocaleKey, boolean>>({ lt: false, en: false });
const open = reactive<Record<LocaleKey, boolean>>({ lt: false, en: false });
const timers: Record<LocaleKey, ReturnType<typeof setTimeout> | null> = { lt: null, en: null };

const modeOptions = computed<FormSegmentOption<SourceMode>[]>(() => [
  { value: 'document', label: $t('learning_library.source.mode_document') },
  { value: 'url', label: $t('learning_library.source.mode_url') },
]);

// Switching mode clears the other half, so the payload never carries a document and a URL at once.
function setMode(locale: LocaleKey, mode: SourceMode): void {
  modes[locale] = mode;
  if (mode === 'url') {
    sources.value[locale].document_id = null;
    selected[locale] = null;
  }
  else {
    sources.value[locale].url = '';
  }
}

function selectDocument(locale: LocaleKey, doc: LearningDocumentSearchHit): void {
  selected[locale] = doc;
  sources.value[locale].document_id = doc.id;
  sources.value[locale].url = '';
  open[locale] = false;
}

function clearDocument(locale: LocaleKey): void {
  selected[locale] = null;
  sources.value[locale].document_id = null;
}

function onInput(locale: LocaleKey): void {
  if (timers[locale]) clearTimeout(timers[locale]!);
  timers[locale] = setTimeout(() => void search(locale), 150);
}

async function search(locale: LocaleKey): Promise<void> {
  open[locale] = true;
  searching[locale] = true;
  try {
    const response = await fetch(route('knowledgeItems.documentSearch', { q: queries[locale].trim() }), { headers: { Accept: 'application/json' } });
    if (response.ok) {
      const data = await response.json();
      results[locale] = Array.isArray(data) ? data : (Array.isArray(data?.data) ? data.data : []);
    }
  }
  finally {
    searching[locale] = false;
  }
}
</script>
