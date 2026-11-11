<template>
  <FormPage
    :title="pageTitle"
    :bar-title="barTitle"
    :entity-type="ModelEnum.LEARNING_STEP"
    required-note
    :back-href="backHref"
    :back-label="$t('learning.pages.library')"
    :dirty="form.isDirty"
    :processing="form.processing"
    :errors="form.errors"
    :field-ids="fieldIds"
    :mode="isEdit ? 'edit' : 'create'"
    :locale="activeLocale"
    :available-locales="['lt', 'en']"
    :missing-locale-counts="missingLocaleCounts"
    @update:locale="activeLocale = $event"
    @submit="submit"
  >
    <template v-if="isSystem">
      <FormFieldWrapper
        id="system-title"
        :label="$t('learning_library.fields.title')"
        :hint="$t('learning_library.sections.system_description')"
      >
        <p data-testid="system-step-title" class="text-sm font-medium text-foreground">
          {{ systemTitle }}
        </p>
      </FormFieldWrapper>
    </template>

    <template v-else>
      <FormFieldWrapper
        id="step-title"
        :label="$t('learning_library.fields.title')"
        :hint="$t('learning_library.fields.title_hint')"
        :error="form.errors[`title.${activeLocale}`] || form.errors.title"
        :required="activeLocale === 'lt'"
      >
        <Input
          id="step-title"
          v-model="form.title[activeLocale]"
          :class="['h-11', fieldSurfaceClass]"
          data-testid="step-title"
        />
      </FormFieldWrapper>

      <FormFieldWrapper
        id="step-description"
        :label="$t('learning_library.fields.description')"
        :hint="$t('learning_library.fields.description_hint')"
        :error="form.errors[`description.${activeLocale}`] || form.errors.description"
      >
        <TiptapEditor :key="activeLocale" v-model="form.description[activeLocale]" tools="description" html :extensions="[createWikiLinkExtension()]" data-testid="step-description" />
      </FormFieldWrapper>

      <FormFieldWrapper
        id="step-kind"
        :label="$t('learning_library.fields.kind')"
        :hint="$t('learning_library.fields.kind_hint')"
        :error="form.errors.kind"
        required
      >
        <FormSegmentedControl
          v-model="form.kind"
          :options="kindOptions"
          :ariaLabel="$t('learning_library.fields.kind')"
          test-id-prefix="step-kind"
        />
      </FormFieldWrapper>

      <FormFieldWrapper
        v-if="form.kind === LearningStepKind.Action"
        id="step-action-label"
        :label="$t('learning_library.fields.action')"
        :hint="$t('learning_library.fields.action_hint')"
        :error="form.errors[`action_label.${activeLocale}`] || form.errors[`action_url.${activeLocale}`]"
      >
        <div class="grid gap-2 sm:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
          <Input
            id="step-action-label"
            v-model="form.action_label[activeLocale]"
            maxlength="60"
            :aria-label="$t('learning_library.fields.action_label')"
            :placeholder="$t('learning_library.fields.action_label')"
            :class="['h-11', fieldSurfaceClass]"
            data-testid="step-action-label"
          />
          <Input
            id="step-action-url"
            v-model="form.action_url[activeLocale]"
            inputmode="url"
            :aria-label="$t('learning_library.fields.action_url')"
            :placeholder="$t('learning_library.fields.action_url_hint')"
            :class="['h-11', fieldSurfaceClass]"
            data-testid="step-action-url"
          />
        </div>
      </FormFieldWrapper>

    </template>

    <LearningAudienceList
      v-model="form.audiences"
      :kind="form.kind"
      :is-system="isSystem"
      :locale="activeLocale"
      :errors="form.errors"
      :audience-types="audienceTypes"
      :can-target-everywhere="canTargetEverywhere"
      :tenants="tenants"
      :institution-types="institutionTypes"
      :duty-types="dutyTypes"
      :responsibilities="responsibilities"
    />

    <template #aside>
      <FormPanel
        :title="$t('learning_library.sections.additional')"
        :icon="SlidersHorizontal"
        data-testid="panel-additional"
      >
        <FormFieldWrapper
          id="step-order"
          :label="$t('learning_library.fields.order')"
          :error="form.errors.order"
        >
          <Input
            id="step-order"
            v-model.number="form.order"
            type="number"
            min="0"
            :class="['max-w-32', fieldSurfaceClass]"
            data-testid="step-order"
          />
        </FormFieldWrapper>

        <div v-if="!isSystem" class="flex items-start gap-2.5">
          <Checkbox id="step-per-term" v-model="form.per_term" data-testid="step-per-term" />
          <div class="space-y-0.5 leading-none">
            <Label for="step-per-term" class="text-sm font-medium">
              {{ $t('learning_library.fields.per_term') }}
            </Label>
            <p class="text-xs text-muted-foreground">
              {{ $t('learning_library.fields.per_term_hint') }}
            </p>
          </div>
        </div>

        <div class="flex items-start gap-2.5">
          <Checkbox id="step-is-active" v-model="form.is_active" data-testid="step-is-active" />
          <div class="space-y-0.5 leading-none">
            <Label for="step-is-active" class="text-sm font-medium">
              {{ $t('learning_library.fields.is_active') }}
            </Label>
            <p class="text-xs text-muted-foreground">
              {{ $t('learning_library.fields.is_active_hint') }}
            </p>
          </div>
        </div>
      </FormPanel>
    </template>

    <template v-if="isEdit && !isSystem && canDelete" #danger-zone>
      <Button
        type="button"
        variant="destructive"
        voice="sentence"
        data-testid="delete-learning-step"
        @click="deleteDialogOpen = true"
      >
        <Trash2 class="size-4" aria-hidden="true" />
        {{ $t('learning_library.delete.action') }}
      </Button>
    </template>
  </FormPage>

  <ConfirmDialog
    v-if="isEdit && !isSystem && canDelete"
    v-model:open="deleteDialogOpen"
    :title="$t('learning_library.delete.title')"
    :description="$t('learning_library.delete.description')"
    :confirm-label="$t('learning_library.delete.confirm')"
    destructive
    @confirm="destroyStep"
  />
</template>

<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { SlidersHorizontal, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import { ConfirmDialog, FormPanel, FormSegmentedControl, type FormSegmentOption } from '@/Components/Patterns';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import { createWikiLinkExtension } from '@/Features/Admin/Knowledge/wikiLinkExtension';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { LearningAudienceType, LearningStage, LearningStepKind, ModelEnum } from '@/Types/enums';
import type { LearningStepEditData, LearningTenantOption, LearningTypeOption } from '@/Types/learning';

import LearningAudienceList from './LearningAudienceList.vue';
import type { LearningAudienceDraft } from './types';
import { hasRichText } from './composables/hasRichText';

type LocaleKey = 'lt' | 'en';

interface LearningStepFormState {
  kind: LearningStepKind;
  audiences: LearningAudienceDraft[];
  title: { lt: string; en: string };
  description: { lt: string; en: string };
  action_label: { lt: string; en: string };
  action_url: { lt: string; en: string };
  per_term: boolean;
  order: number;
  is_active: boolean;
}

const props = withDefaults(defineProps<{
  step?: LearningStepEditData | null;
  audienceTypes?: LearningAudienceType[];
  canTargetEverywhere?: boolean;
  tenants?: LearningTenantOption[];
  institutionTypes?: LearningTypeOption[];
  dutyTypes?: LearningTypeOption[];
  responsibilities?: { id: string; title: string }[];
  canDelete?: boolean;
}>(), {
  step: null,
  audienceTypes: () => Object.values(LearningAudienceType).filter(type => type !== LearningAudienceType.Purpose),
  canTargetEverywhere: false,
  tenants: () => [], institutionTypes: () => [], dutyTypes: () => [], responsibilities: () => [],
  canDelete: false,
});

const activeLocale = ref<LocaleKey>('lt');
const isEdit = computed(() => Boolean(props.step?.id));
const isSystem = computed(() => Boolean(props.step?.is_system));
const backHref = computed(() => (props.step?.id ? route('learningSteps.show', props.step.id) : route('learningSteps.index')));
const deleteDialogOpen = ref(false);

const form = useForm<LearningStepFormState>({
  kind: props.step?.kind ?? LearningStepKind.Action,
  audiences: props.step?.audiences.map(audience => ({ ...audience, description: { ...audience.description } })) ?? [],
  title: { lt: props.step?.title?.lt ?? '', en: props.step?.title?.en ?? '' },
  description: { lt: props.step?.description?.lt ?? '', en: props.step?.description?.en ?? '' },
  action_label: { lt: props.step?.action_label?.lt ?? '', en: props.step?.action_label?.en ?? '' },
  action_url: { lt: props.step?.action_url?.lt ?? '', en: props.step?.action_url?.en ?? '' },
  per_term: props.step?.per_term ?? false,
  order: props.step?.order ?? 0,
  is_active: props.step?.is_active ?? true,
});

const fieldIds: Record<string, string> = {
  'title': 'step-title', 'title.lt': 'step-title', 'title.en': 'step-title',
  'description': 'step-description', 'description.lt': 'step-description', 'description.en': 'step-description',
  'kind': 'step-kind',
  'action_label.lt': 'step-action-label', 'action_label.en': 'step-action-label',
  'action_url.lt': 'step-action-url', 'action_url.en': 'step-action-url',
  'audiences': 'step-audiences', 'order': 'step-order',
};

const missingLocaleCounts = computed<Record<LocaleKey, number>>(() => {
  if (isSystem.value) {
    return { lt: 0, en: 0 };
  }
  return {
    lt: form.title.lt.trim() === '' ? 1 : 0,
    en: (form.title.en.trim() === '' ? 1 : 0) + (hasRichText(form.description.lt) && !hasRichText(form.description.en) ? 1 : 0),
  };
});

const systemTitle = computed(() => props.step?.key ? $t(`learning.system_steps.${props.step.key}.title`) : '');

const pageTitle = computed(() => {
  if (isSystem.value) {
    return systemTitle.value || $t('learning.pages.edit');
  }
  const activeTitle = form.title[activeLocale.value].trim() || form.title.lt.trim() || form.title.en.trim();
  return isEdit.value ? activeTitle || $t('learning.pages.edit') : $t('learning.pages.create');
});

const barTitle = computed(() => {
  if (isSystem.value) {
    return systemTitle.value || $t('learning.pages.edit');
  }
  return isEdit.value ? props.step?.title?.lt || props.step?.title?.en || $t('learning.pages.edit') : $t('learning.pages.create');
});

const kindOptions = computed<FormSegmentOption<LearningStepKind>[]>(() => [
  { value: LearningStepKind.Action, label: $t('learning.kinds.action') },
  { value: LearningStepKind.MeetingPrep, label: $t('learning.kinds.meeting_prep') },
]);

watch(() => form.kind, (kind) => {
  for (const audience of form.audiences) {
    audience.stage = kind === LearningStepKind.Action ? audience.stage ?? LearningStage.BeforeStart : null;
  }
});

function buildPayload(data: LearningStepFormState): Record<string, unknown> {
  if (isSystem.value) {
    return {
      audiences: data.audiences.map(({ id, stage, order }) => ({ id, stage, order })),
      order: data.order,
      is_active: data.is_active,
    };
  }

  return {
    kind: data.kind,
    audiences: data.audiences.map(({ label, stage, ...audience }) => ({ ...audience, stage: data.kind === LearningStepKind.Action ? stage : null })),
    title: data.title,
    description: data.description,
    action_label: { lt: data.action_label.lt.trim(), en: data.action_label.en.trim() },
    action_url: { lt: data.action_url.lt.trim(), en: data.action_url.en.trim() },
    per_term: data.per_term,
    order: data.order,
    is_active: data.is_active,
  };
}

function submit(): void {
  form.transform(data => buildPayload(data));
  form.defaults();
  const options = { preserveScroll: true, onSuccess: () => form.defaults() };
  if (props.step?.id) {
    form.put(route('learningSteps.update', props.step.id), options);
  }
  else {
    form.post(route('learningSteps.store'), options);
  }
}

function destroyStep(): void {
  if (!props.step?.id) {
    return;
  }
  deleteDialogOpen.value = false;
  router.delete(route('learningSteps.destroy', props.step.id));
}
</script>
