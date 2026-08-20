<template>
  <div data-slot="cadence-row-form" class="grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
    <div class="space-y-1">
      <Label :for="`${uid}-start`" class="text-xs text-muted-foreground">
        {{ $t('cadences.fields.start_date') }}
      </Label>
      <Input :id="`${uid}-start`" v-model="draft.start_date" type="date" />
    </div>

    <div class="space-y-1">
      <Label :for="`${uid}-end`" class="text-xs text-muted-foreground">
        {{ $t('cadences.fields.end_date') }}
      </Label>
      <Input :id="`${uid}-end`" v-model="draft.end_date" type="date" />
    </div>

    <div class="flex items-center gap-2">
      <Button type="button" size="sm" :disabled="!isComplete || processing" @click="emit('save', { ...draft })">
        {{ $t('cadences.actions.save') }}
      </Button>
      <Button type="button" size="sm" variant="ghost" :disabled="processing" @click="emit('cancel')">
        {{ $t('cadences.actions.cancel') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, watch } from 'vue';
import { useId } from 'vue';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

export interface CadenceDraft {
  start_date: string;
  end_date: string;
}

const props = withDefaults(defineProps<{
  modelValue?: CadenceDraft | null;
  processing?: boolean;
}>(), {
  modelValue: null,
  processing: false,
});

const emit = defineEmits<{
  save: [value: CadenceDraft];
  cancel: [];
}>();

const uid = useId();

const draft = reactive<CadenceDraft>({
  start_date: props.modelValue?.start_date ?? '',
  end_date: props.modelValue?.end_date ?? '',
});

// The list reuses one form instance per section, so switching which row is being edited
// changes `modelValue` without remounting — without this the previous row's dates stay.
watch(() => props.modelValue, (next) => {
  draft.start_date = next?.start_date ?? '';
  draft.end_date = next?.end_date ?? '';
});

// The server also enforces `end_date` after `start_date`; this only keeps the button
// from submitting an obviously empty or inverted row.
const isComplete = computed(() => Boolean(draft.start_date && draft.end_date)
  && draft.end_date > draft.start_date);
</script>
