<template>
  <FormPanel :title="$t('Paskelbimas')" :icon="Send" title-class="text-brand">
    <FormFieldWrapper :id="`${testIdPrefix}-status`" :label="$t('Būsena')">
      <FormSegmentedControl
        v-model="published"
        :options="statusOptions"
        :aria-label="$t('Būsena')"
        :test-id-prefix="`${testIdPrefix}-status`"
      />
    </FormFieldWrapper>

    <FormFieldWrapper
      v-if="!hidePublishTime"
      id="publish_time"
      :label="$t('Paskelbimo laikas')"
      :required="!clearable"
      :hint="timeHint"
      :error="publishTimeError"
    >
      <DateTimePicker
        v-model="publishTimeDate"
        variant="popover"
        :clearable
        :placeholder="$t('Pasirinkti paskelbimo laiką...')"
      />
    </FormFieldWrapper>

    <slot />

    <div
      class="flex items-start gap-2 border border-border bg-secondary/40 p-3 text-xs leading-relaxed text-muted-foreground"
      :data-testid="`${testIdPrefix}-status-callout`"
    >
      <Info class="mt-0.5 size-4 shrink-0 text-brand" aria-hidden="true" />
      <span>{{ callout }}</span>
    </div>
  </FormPanel>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Info, Send } from 'lucide-vue-next';

import FormFieldWrapper from './FormFieldWrapper.vue';

import { FormPanel, FormSegmentedControl, type FormSegmentOption } from '@/Components/Patterns';
import DateTimePicker from '@/Components/ui/date-picker/DateTimePicker.vue';
import { contentStatuses, statusRoleClasses, type StatusPresentation } from '@/Constants/statuses';

/** Draft ⇄ published, publish time and what that means for visitors, for a content editor's aside. */
withDefaults(defineProps<{
  /** What the current status means for visitors — each model gates visibility differently. */
  callout: string;
  /** Pages have no publish time; news does. */
  hidePublishTime?: boolean;
  timeHint?: string;
  /** Whether the publish time may be left empty. */
  clearable?: boolean;
  publishTimeError?: string;
  /** Prefixes `-status-draft`, `-status-published` and `-status-callout` test ids. */
  testIdPrefix: string;
}>(), {
  timeHint: undefined,
  publishTimeError: undefined,
});

const published = defineModel<boolean>('published', { required: true });

/** ISO instant, or null. */
const publishTime = defineModel<string | null | undefined>('publishTime', { default: null });

// Chosen status reads in the same colours as its tag in collections.
const statusOptions = computed<FormSegmentOption<boolean>[]>(() => [
  segmentFor(false, contentStatuses.draft, 'draft'),
  segmentFor(true, contentStatuses.published, 'published'),
]);

function segmentFor(value: boolean, status: StatusPresentation, testId: string): FormSegmentOption<boolean> {
  return {
    value,
    label: $t(status.label),
    icon: status.icon,
    activeClass: statusRoleClasses[status.role],
    testId,
  };
}

const publishTimeDate = computed({
  get: () => (publishTime.value ? new Date(publishTime.value) : undefined),
  set: (value: Date | null | undefined) => {
    publishTime.value = value ? value.toISOString() : null;
  },
});
</script>
