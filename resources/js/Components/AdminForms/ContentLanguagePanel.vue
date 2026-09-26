<template>
  <FormPanel :title="$t('Kalba')" :icon="Languages" title-class="text-brand">
    <FormFieldWrapper
      id="lang"
      :label="labels.lang"
      required
      :error="langError"
      :valid="langValid"
      :invalid="langInvalid"
    >
      <FormSegmentedControl
        v-model="lang"
        :options="langOptions"
        :aria-label="labels.lang"
        :test-id-prefix="`${testIdPrefix}-lang`"
      >
        <template #option="{ option }">
          <LocaleFlag :locale="option.value" />
        </template>
      </FormSegmentedControl>
    </FormFieldWrapper>

    <FormFieldWrapper
      id="other_lang"
      :label="lang === 'lt' ? labels.otherLangEn : labels.otherLangLt"
      :hint="isCreate ? labels.createHint : labels.editHint"
    >
      <CollectionSelectDialog
        v-if="!isCreate"
        v-model:open="dialogOpen"
        :collection
        allow-empty
        :base-filter-by
        :initial-hits
        :title="labels.dialogTitle"
        :confirm-label="$t('Pasirinkti')"
        :search-placeholder="labels.searchPlaceholder"
        :empty-message="labels.emptyMessage"
        @confirm="onConfirm"
      >
        <template #trigger>
          <Button
            id="other_lang"
            type="button"
            variant="outline"
            voice="plain"
            :class="['h-11 w-full justify-between font-normal hover:bg-secondary/80', fieldSurfaceClass]"
          >
            <span class="truncate" :class="{ 'text-muted-foreground': !selected }">
              {{ selected ? `${selected.title} (${selected.tenant?.shortname})` : `-- ${$t('Nepasirinkta')} --` }}
            </span>
            <ChevronDown class="size-4 opacity-50" />
          </Button>
        </template>
      </CollectionSelectDialog>
      <Button
        v-else
        id="other_lang"
        type="button"
        variant="outline"
        voice="plain"
        class="h-11 w-full justify-between border-border bg-secondary/30 font-normal"
        disabled
      >
        <span class="text-muted-foreground">{{ labels.createPlaceholder }}</span>
        <ChevronDown class="size-4 opacity-50" />
      </Button>
    </FormFieldWrapper>
  </FormPanel>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { ChevronDown, Languages } from 'lucide-vue-next';

import FormFieldWrapper from './FormFieldWrapper.vue';

import { FormPanel, FormSegmentedControl, type FormSegmentOption } from '@/Components/Patterns';
import LocaleFlag from '@/Components/Public/Nav/LocaleFlag.vue';
import { Button } from '@/Components/ui/button';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { CollectionSelectDialog } from '@/Features/Admin/AdminSearch/Components/Select';
import { normalizeHit, type NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';

type ContentLang = 'lt' | 'en';

interface OtherLangCandidate {
  id: number;
  title: string;
  tenant?: { id: number; shortname?: string } | null;
}

/** A single-language record's language, and the same content in the other language. */
const props = defineProps<{
  collection: 'pages' | 'news';
  /** The opposite-language records the server allows linking to. */
  candidates: OtherLangCandidate[];
  isCreate: boolean;
  labels: {
    lang: string;
    otherLangLt: string;
    otherLangEn: string;
    createHint: string;
    editHint: string;
    createPlaceholder: string;
    dialogTitle: string;
    searchPlaceholder: string;
    emptyMessage: string;
  };
  langError?: string;
  langValid?: boolean;
  langInvalid?: boolean;
  testIdPrefix: string;
}>();

const lang = defineModel<ContentLang>('lang', { required: true });
const otherLangId = defineModel<number | null | undefined>('otherLangId', { default: null });

const langOptions: FormSegmentOption<ContentLang>[] = [
  { value: 'lt', label: 'Lietuvių' },
  { value: 'en', label: 'English' },
];

const dialogOpen = ref(false);

const otherLang = computed<ContentLang>(() => (lang.value === 'lt' ? 'en' : 'lt'));

const selected = computed(() => props.candidates.find(candidate => String(candidate.id) === String(otherLangId.value)));

// Search only the opposite-language records of the candidates' tenants — the same set the server offers.
const baseFilterBy = computed(() => {
  const tenantIds = [
    ...new Set(props.candidates.map(candidate => candidate.tenant?.id).filter((id): id is number => id != null)),
  ];
  const parts: string[] = [];
  if (tenantIds.length > 0) {
    parts.push(`tenant_ids:[${tenantIds.join(',')}]`);
  }
  parts.push(`lang:=${otherLang.value}`);
  return parts.join(' && ');
});

const initialHits = computed<NormalizedSearchHit[]>(() => {
  if (!selected.value) {
    return [];
  }
  return [normalizeHit(props.collection, {
    id: selected.value.id,
    title: selected.value.title,
    tenant_name: selected.value.tenant?.shortname,
    lang: otherLang.value,
  })];
});

function onConfirm(hits: NormalizedSearchHit[]) {
  otherLangId.value = hits[0] ? Number(hits[0].recordId) : null;
}
</script>
