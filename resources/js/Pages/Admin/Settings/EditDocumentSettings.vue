<template>
  <FormPage
    :title="$t('settings.pages.documents.title')"
    :back-href="route('settings.index')"
    :back-label="$t('settings.title')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :available-locales="[]"
    @submit="handleFormSubmit"
  >
    <FormSection
      :title="$t('settings.document_settings.important_types_title')"
      :description="$t('settings.document_settings.important_types_description')"
    >
      <FormFieldWrapper
        id="important_content_types"
        :label="$t('settings.document_settings.important_types_label')"
        :error="form.errors.important_content_types"
      >
        <MultiSelect
          v-model="selectedTypes"
          :options="contentTypeOptions"
          label-field="label"
          value-field="value"
          :placeholder="$t('settings.document_settings.important_types_placeholder')"
          :empty-text="$t('settings.document_settings.no_types_found')"
        />
      </FormFieldWrapper>
    </FormSection>
    <FormSection
      :title="$t('settings.document_settings.recommendations_title')"
      :description="$t('settings.document_settings.recommendations_description')"
    >
      <ol v-if="form.recommendations.length" class="divide-y divide-border border-y border-border">
        <li
          v-for="(recommendation, index) in form.recommendations"
          :key="index"
          class="space-y-2 py-3"
          :class="{ 'opacity-60': !recommendation.enabled }"
          data-testid="document-recommendation"
        >
          <div class="flex items-center gap-1">
            <span class="w-6 shrink-0 text-sm tabular-nums text-muted-foreground">{{ index + 1 }}.</span>
            <button
              type="button"
              class="min-h-11 min-w-0 flex-1 truncate text-left text-sm font-medium hover:underline"
              @click="openPicker(index)"
            >
              {{ documentTitle(recommendation.document_id) }}
              <span class="sr-only">– {{ $t('settings.document_settings.change_document') }}</span>
            </button>
            <Button
              type="button" variant="ghost" size="icon" :disabled="index === 0"
              :aria-label="$t('settings.document_settings.move_up')" @click="move(index, -1)"
            >
              <ArrowUp class="size-4" />
            </Button>
            <Button
              type="button" variant="ghost" size="icon" :disabled="index === form.recommendations.length - 1"
              :aria-label="$t('settings.document_settings.move_down')" @click="move(index, 1)"
            >
              <ArrowDown class="size-4" />
            </Button>
            <Button
              type="button" variant="ghost" size="icon"
              :aria-label="$t('settings.document_settings.remove')" @click="form.recommendations.splice(index, 1)"
            >
              <Trash2 class="size-4" />
            </Button>
          </div>
          <div class="space-y-2 pl-6">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
              <label :for="`recommendation-phrases-${index}`" class="text-sm">
                {{ $t('settings.document_settings.phrases_label') }}
              </label>
              <Input
                :id="`recommendation-phrases-${index}`" v-model="recommendation.phrases_text" class="min-w-0 flex-1 basis-56"
                :placeholder="$t('settings.document_settings.phrases_placeholder')"
              />
            </div>
            <p v-if="recommendationError(index)" class="text-sm text-destructive">
              {{ recommendationError(index) }}
            </p>
            <div class="flex flex-wrap gap-x-6">
              <label class="flex min-h-11 items-center gap-2 text-sm">
                <Checkbox v-model="recommendation.show_without_query" />{{ $t('settings.document_settings.when_empty') }}
              </label>
              <label class="flex min-h-11 items-center gap-2 text-sm">
                <Checkbox
                  :model-value="!recommendation.enabled"
                  @update:model-value="value => recommendation.enabled = !value"
                />{{ $t('settings.document_settings.pause') }}
              </label>
            </div>
          </div>
        </li>
      </ol>
      <SpotlightPopover
        :is-dismissed="spotlight.isDismissed.value" :title="$t('settings.document_settings.recommendations_title')"
        :description="$t('settings.document_settings.recommendations_description')" @dismiss="spotlight.dismiss"
      >
        <Button v-if="form.recommendations.length < 20" type="button" variant="outline" data-testid="add-document-recommendation" @click="addRecommendation">
          <Plus class="size-4" />{{ $t('settings.document_settings.add_recommendation') }}
        </Button>
      </SpotlightPopover>
      <p v-if="form.errors.recommendations" class="text-sm text-destructive">
        {{ form.errors.recommendations }}
      </p>
    </FormSection>
    <CollectionSelectDialog
      :open="pickerIndex !== null" collection="documents" :title="$t('settings.document_settings.choose_document')"
      base-filter-by="is_active:=true" :disabled-ids="selectedDocumentIds"
      @update:open="value => { if (!value) pickerIndex = null; }" @confirm="selectDocument"
    />
  </FormPage>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch, computed, reactive } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowDown, ArrowUp, Plus, Trash2 } from 'lucide-vue-next';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import { MultiSelect } from '@/Components/ui/multi-select';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import { CollectionSelectDialog } from '@/Features/Admin/AdminSearch/Components/Select';
import type { NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';

interface ContentTypeOption {
  value: string;
  label: string;
}

interface Recommendation {
  document_id: string;
  phrases: string[];
  enabled: boolean;
  show_without_query: boolean;
}

const props = defineProps<{
  selected_content_types: string[];
  available_content_types: string[];
  recommendations: Recommendation[];
  selected_documents: { id: string; title: string; is_active: boolean }[];
}>();

const contentTypeOptions = computed<ContentTypeOption[]>(() => {
  return props.available_content_types.map(type => ({
    value: type,
    label: type,
  }));
});

const selectedTypes = ref<ContentTypeOption[]>(
  props.selected_content_types.map(type => ({
    value: type,
    label: type,
  })),
);

// Phrases are edited as one comma-separated line and split back into the saved array on submit.
const form = useForm({
  important_content_types: props.selected_content_types,
  recommendations: props.recommendations.map(({ phrases, ...rule }) => ({ ...rule, phrases_text: phrases.join(', ') })),
});

const spotlight = useFeatureSpotlight('document-recommendations-v1');
const selectedDocuments = reactive(Object.fromEntries(props.selected_documents.map(document => [document.id, document.title])));
const pickerIndex = ref<number | null>(null);
const selectedDocumentIds = computed(() => new Set(form.recommendations
  .filter((_, index) => index !== pickerIndex.value)
  .map(rule => `documents-${rule.document_id}`)));
const documentTitle = (id: string) => selectedDocuments[id] ?? $t('settings.document_settings.choose_document');
const recommendationError = (index: number) => Object.entries(form.errors)
  .find(([key]) => key.startsWith(`recommendations.${index}.`))?.[1];
function openPicker(index: number): void {
  pickerIndex.value = index;
}
function addRecommendation(): void {
  spotlight.dismiss();
  form.recommendations.push({ document_id: '', phrases_text: '', enabled: true, show_without_query: true });
  openPicker(form.recommendations.length - 1);
}
function selectDocument(hits: NormalizedSearchHit[]): void {
  const hit = hits[0];
  if (pickerIndex.value === null || !hit) return;
  form.recommendations[pickerIndex.value].document_id = hit.recordId;
  selectedDocuments[hit.recordId] = hit.title;
  pickerIndex.value = null;
}
function move(index: number, direction: number): void {
  const [rule] = form.recommendations.splice(index, 1);
  form.recommendations.splice(index + direction, 0, rule);
}

watch(selectedTypes, (newTypes) => {
  form.important_content_types = newTypes.map(type => type.value);
}, { deep: true });

const handleFormSubmit = () => {
  form.transform(data => ({
    ...data,
    recommendations: data.recommendations.map(({ phrases_text, ...rule }) => ({
      ...rule,
      phrases: phrases_text.split(',').map(phrase => phrase.trim()).filter(Boolean),
    })),
  }));
  form.defaults();
  form.post(route('settings.documents.update'));
};
</script>
