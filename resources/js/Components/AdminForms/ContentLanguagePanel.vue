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
        v-model:open="dialogOpen"
        :collection
        allow-empty
        :clear-label="$t('editor.unlink')"
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
              {{ selected ? selected.title : `-- ${$t('Nepasirinkta')} --` }}
            </span>
            <ChevronDown class="size-4 opacity-50" />
          </Button>
        </template>
      </CollectionSelectDialog>
    </FormFieldWrapper>
    <p v-if="pairError" class="text-sm text-destructive" role="alert">
      {{ pairError }}
    </p>
    <div v-if="otherLangId || (context && canCreate)" class="flex gap-2">
      <SpotlightPopover
        v-if="context && (otherLangId || canCreate)"
        class="min-w-0 flex-1"
        :title="$t('editor.translation_workspace')"
        :description="$t('editor.translation_hint')"
        :is-dismissed="spotlight.isDismissed.value"
        @dismiss="spotlight.dismiss"
      >
        <Button type="button" variant="outline" class="w-full" @click="openWorkspace">
          <component :is="otherLangId ? Columns2 : Plus" class="size-4" aria-hidden="true" />
          {{ $t(otherLangId ? 'editor.compare' : 'editor.create_translation') }}
        </Button>
      </SpotlightPopover>
      <Button v-if="otherLangId" as-child variant="outline" class="shrink-0">
        <a
          :href="route(`${collection}.edit`, otherLangId)"
          target="_blank"
          rel="noopener noreferrer"
          :aria-label="$t('editor.open_other')"
        >
          <ArrowUpRight class="size-4" aria-hidden="true" />
          {{ $t('editor.open_other_short') }}
        </a>
      </Button>
    </div>
  </FormPanel>
  <ConfirmDialog
    v-model:open="confirmOpen"
    :title="$t('editor.replace_pairing')"
    :description="pairDescription"
    :confirm-label="$t('editor.confirm_pairing')"
    @confirm="applySelection"
  />
  <ContentTranslationWorkspace v-if="workspaceOpen" v-model:open="workspaceOpen" :counterpart-id="otherLangId" />
</template>

<script setup lang="ts">
import { computed, inject, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowUpRight, ChevronDown, Columns2, Languages, Plus } from 'lucide-vue-next';

import ContentTranslationWorkspace from './ContentTranslationWorkspace.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';

import { contentEditorHttp as http } from '@/Composables/contentEditorHttp';
import { CONTENT_EDITOR_CONTEXT } from '@/Components/RichContent/contentEditorContext';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { ConfirmDialog } from '@/Components/Patterns';
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
  tenantId?: number | null;
  recordId?: number;
  isCreate: boolean;
  labels: {
    lang: string;
    otherLangLt: string;
    otherLangEn: string;
    createHint: string;
    editHint: string;
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

const selected = ref<OtherLangCandidate | null>(null);
const context = inject(CONTENT_EDITOR_CONTEXT, null);
const page = usePage();
const canCreate = computed(() => Boolean(page.props.auth?.can?.create?.[props.collection === 'pages' ? 'page' : 'news']));
const workspaceOpen = ref(false);
const spotlight = useFeatureSpotlight('content-editor-translation-v1');
const confirmOpen = ref(false);
const pairError = ref('');
const pairDescription = ref('');
const pairingConfirmation = defineModel<string | undefined>('pairingConfirmation');
let pendingId: number | null = null;
let pendingToken: string | undefined;
watch(otherLangId, async (id) => {
  if (!id) { selected.value = null; return; }
  try {
    const { data } = await http.get(route('api.v1.admin.contentEditor.show', { kind: props.collection, record: id }));
    if (otherLangId.value === id) selected.value = data.data;
  }
  catch { pairError.value = $t('editor.counterpart_unavailable'); }
}, { immediate: true });

// Tenant filtering is a convenience; the save endpoint checks every affected record.
const baseFilterBy = computed(() => `${props.tenantId ? `tenant_ids:[${props.tenantId}] && ` : ''}lang:=${otherLang.value}`);

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

async function onConfirm(hits: NormalizedSearchHit[]) {
  pendingId = hits[0] ? Number(hits[0].recordId) : null;
  pairError.value = '';
  try {
    const { data } = await http.post(route('api.v1.admin.contentEditor.pairing', { kind: props.collection }), { record_id: props.recordId, target_id: pendingId, lang: lang.value });
    pendingToken = data.data.token;
    if (data.data.confirmation_required) {
      pairDescription.value = `${$t('editor.pairing_changes')}\n${data.data.records.map((record: { title: string; lang: string; other_lang_id?: number; id: number }) => `${record.title} (${record.lang}) → ${data.data.records.find((candidate: { id: number }) => candidate.id === record.other_lang_id)?.title ?? $t('editor.unpaired')}`).join('\n')}`;
      confirmOpen.value = true;
    }
    else applySelection();
  }
  catch (error) { pairError.value = http.isError(error) ? error.response?.data?.message ?? error.message : String(error); }
}
function openWorkspace() {
  workspaceOpen.value = true;
  spotlight.dismiss();
}

function applySelection() {
  otherLangId.value = pendingId;
  pairingConfirmation.value = pendingToken;
  confirmOpen.value = false;
}
</script>
