<template>
  <div class="flex h-full min-h-0 flex-col overflow-hidden" :data-testid="`translation-pane-${context.form.lang}`" :data-save-state="context.form.processing ? 'saving' : context.form.isDirty ? 'dirty' : 'saved'">
    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 border-b border-border px-3 py-2">
      <span class="u-eyebrow">{{ context.form.lang }}</span>
      <Input v-model="context.form.title" :aria-label="$t('forms.fields.title')" class="min-h-11 min-w-0 flex-1" />
      <label class="flex min-h-11 items-center gap-2 text-sm">
        <Checkbox :model-value="published" @update:model-value="setPublished(Boolean($event))" />{{ $t('editor.published') }}
      </label>
      <DateTimePicker v-if="kind === 'news'" v-model="publishTimeDate" variant="popover" clearable :placeholder="$t('editor.publish_time')" />
      <p v-if="Object.keys(context.form.errors).length" class="text-sm text-destructive" role="alert">{{ Object.values(context.form.errors).join(' · ') }}</p>
    </div>
    <ContentRecoveryPanel v-if="recovery" :editor="recovery" hide-status />
    <div
      v-if="form && source && !form.id && !copied"
      class="flex shrink-0 flex-wrap items-center justify-between gap-x-3 border-b border-border px-3 text-xs text-muted-foreground"
      data-testid="translation-copy"
    >
      <span>{{ $t('editor.translation_blank') }}</span>
      <Button type="button" variant="ghost" size="sm" class="min-h-11" @click="copySource">
        <Copy class="size-3.5" aria-hidden="true" />
        {{ $t('editor.copy_source', { lang: String(source.form.lang).toUpperCase() }) }}
      </Button>
    </div>
    <div class="shrink-0 border-b border-border">
      <div class="flex items-center justify-between gap-3 px-3">
        <button
          type="button"
          class="flex min-h-11 min-w-0 items-center gap-1.5 text-left text-sm text-muted-foreground"
          :aria-expanded="detailsOpen"
          :aria-controls="detailsId"
          @click="detailsOpen = !detailsOpen"
        >
          <ChevronRight :class="['size-4 shrink-0 transition-transform', detailsOpen && 'rotate-90']" aria-hidden="true" />
          <span class="truncate">{{ $t('editor.translation_details') }}</span>
        </button>
        <ContentRecoveryStatus v-if="recovery" :editor="recovery" />
      </div>
      <div v-show="detailsOpen" :id="detailsId" class="max-h-56 space-y-3 overflow-y-auto px-3 pb-3">
        <FormFieldWrapper v-if="kind === 'news'" :id="`short-${context.form.lang}`" :label="$t('Įvadinis tekstas')" :error="context.form.errors.short" :max-length="200">
          <TiptapEditor v-model="context.form.short" preset="marks" disable-links :max-characters="200" html framed />
        </FormFieldWrapper>
        <FormFieldWrapper v-else :id="`description-${context.form.lang}`" :label="$t('Meta aprašymas')" :error="context.form.errors.meta_description">
          <Textarea v-model="context.form.meta_description" />
        </FormFieldWrapper>
        <FormFieldWrapper :id="`highlights-${context.form.lang}`" :label="$t('Svarbiausi punktai')">
          <OrderedListInput v-model="context.form.highlights" :max="3" input-type="textarea" :placeholder="$t('Įveskite svarbų punktą...')" :empty-text="$t('Dar nepridėta jokių punktų')" :add-first-text="$t('Pridėti pirmą punktą')" :add-text="$t('Pridėti punktą')" />
        </FormFieldWrapper>
      </div>
    </div>
    <RCFullscreenEditor v-model:contents="parts" embedded :tenant-id="context.form.tenant_id" :history="{ commit, undo, redo, canUndo, canRedo }" />
  </div>
</template>

<script setup lang="ts">
import { computed, provide, ref, useId, watch } from 'vue';
import { ChevronRight, Copy } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';
import { useManualRefHistory } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { cloneTranslation, TRANSLATION_TEXT_FIELDS, useContentEditor, type ContentEditorData, type ContentKind } from '@/Composables/useContentEditor';
import { CONTENT_EDITOR_CONTEXT, type ContentEditorContext } from '@/Components/RichContent/contentEditorContext';
import type { ContentPart } from '@/Components/RichContent/Types';
import RCFullscreenEditor from '@/Components/RichContent/Editor/Fullscreen/RCFullscreenEditor.vue';
import ContentRecoveryPanel from './ContentRecoveryPanel.vue';
import ContentRecoveryStatus from './ContentRecoveryStatus.vue';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { OrderedListInput } from '@/Components/ui/ordered-list-input';
import DateTimePicker from '@/Components/ui/date-picker/DateTimePicker.vue';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
const props = defineProps<{ kind: ContentKind; initial?: ContentEditorData; existing?: ContentEditorContext; source?: ContentEditorContext; draftIdentity?: string; afterSave?: (data: ContentEditorData) => Promise<void> }>();
const form = props.existing ? null : useForm<ContentEditorData>({ id: undefined, content_version: undefined, permalink: '', pairing_confirmation: undefined, ...props.initial });
const editor = form ? useContentEditor(props.kind, form, { stay: true, identity: props.draftIdentity, onSaved: props.afterSave }) : null;
const context: ContentEditorContext = props.existing
  ? { ...props.existing, save: () => props.existing!.save(true) }
  : {
      kind: props.kind, form: form!, ready: editor!.ready, error: editor!.saveError,
      save: async () => {
        if (!form!.id && props.source && !props.source.form.id) {
          editor!.saveError.value = $t('editor.save_source_first');
          return;
        }
        await editor!.save();
      },
    };
const recovery = editor ?? props.existing?.recovery;
const detailsOpen = ref(false);
const detailsId = `translation-details-${useId()}`;
provide(CONTENT_EDITOR_CONTEXT, context);
watch(() => props.source?.form.id, (id) => {
  if (form && id && !form.id) form.other_lang_id = id;
});
const parts = computed({ get: () => context.form.content?.parts as ContentPart[] ?? [], set: (value) => {
  context.form.content = { parts: value };
} });
const { commit, undo, redo, canUndo, canRedo } = useManualRefHistory(parts, { clone: true, capacity: 30 });
const published = computed(() => props.kind === 'news' ? !context.form.draft : Boolean(context.form.is_active));
const publishTimeDate = computed({
  get: () => context.form.publish_time ? new Date(context.form.publish_time as string) : undefined,
  set: (value: Date | null | undefined) => { context.form.publish_time = value?.toISOString() ?? null; },
});
const copied = ref(false);
function copySource() {
  if (!props.source) return;
  const copy = cloneTranslation(props.source.form);
  commit();
  Object.assign(context.form, Object.fromEntries(TRANSLATION_TEXT_FIELDS.filter(field => field in context.form && field in copy).map(field => [field, copy[field]])));
  commit();
  copied.value = true;
}
function setPublished(value: boolean) {
  context.form[props.kind === 'news' ? 'draft' : 'is_active'] = props.kind === 'news' ? !value : value;
}
</script>
