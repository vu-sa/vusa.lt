<template>
  <div class="space-y-3" data-slot="learning-step-glossary-editor">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
      <div class="min-w-0 flex-1">
        <MultiSelect
          v-model="selectedItems"
          :options="labelledOptions"
          label-field="label"
          value-field="id"
          :placeholder="$t('learning_library.step_glossary.placeholder')"
          :search-placeholder="$t('learning_library.step_glossary.search_placeholder')"
          :empty-text="$t('learning_library.step_glossary.empty')"
          data-testid="glossary-multiselect"
        />
      </div>
      <Button
        type="button"
        variant="outline"
        voice="sentence"
        :disabled="form.processing"
        data-testid="save-glossary-terms"
        @click="save"
      >
        {{ $t('learning_library.step_glossary.save') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

import { Button } from '@/Components/ui/button';
import { MultiSelect } from '@/Components/ui/multi-select';

export interface GlossaryTermOption {
  id: string;
  term: string;
  abbreviation?: string;
}

const props = defineProps<{
  stepId: string;
  selected: GlossaryTermOption[];
  options: GlossaryTermOption[];
}>();

const emit = defineEmits<{
  'save': [payload: { term_ids: string[] }];
  'update:selected': [items: GlossaryTermOption[]];
}>();

// Pickers search the abbreviation too, since that is how a term usually appears in documents.
const withLabel = (item: GlossaryTermOption) => ({ ...item, label: item.abbreviation ? `${item.term} (${item.abbreviation})` : item.term });
const labelledOptions = computed(() => props.options.map(withLabel));
const selectedItems = ref<GlossaryTermOption[]>(props.selected.map(withLabel));

const form = useForm<{ term_ids: string[] }>({
  term_ids: props.selected.map(item => item.id),
});

watch(() => props.selected, (next) => {
  selectedItems.value = next.map(withLabel);
  form.term_ids = next.map(item => item.id);
}, { deep: true });

watch(selectedItems, (next) => {
  form.term_ids = next.map(item => item.id);
  emit('update:selected', next);
}, { deep: true });

function save(): void {
  const termIds = selectedItems.value.map(item => item.id);
  form.term_ids = termIds;
  emit('save', { term_ids: termIds });

  form.put(route('learningSteps.glossary.sync', props.stepId), {
    preserveScroll: true,
    onSuccess: () => {
      form.defaults();
    },
  });
}
</script>
