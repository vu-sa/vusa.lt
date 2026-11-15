<template>
  <FormFieldWrapper
    id="article-structure"
    :label="$t('learning_library.fields.structure_graph')"
    :hint="$t('learning_library.fields.structure_hint')"
    :error="errors.structure"
  >
    <div class="space-y-3" data-slot="structure-field">
      <LearningStructurePlace
        v-for="(place, index) in places"
        :key="keyOf(place)"
        v-model:graph="place.graph"
        v-model:node="place.node"
        :index
        :locale
        :taken="places.map(other => other.graph)"
        :error="errors[`structure.${index}.node`] || errors[`structure.${index}.graph`]"
        @remove="places.splice(index, 1)"
      />
      <Button
        v-if="free.length"
        type="button"
        variant="outline"
        voice="sentence"
        data-testid="structure-add"
        @click="places.push({ graph: free[0]!, node: null })"
      >
        <Plus class="size-4" aria-hidden="true" />
        {{ $t('learning_library.fields.structure_add') }}
      </Button>
    </div>
  </FormFieldWrapper>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Plus } from '@lucide/vue';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import { Button } from '@/Components/ui/button';
import { graphRegistry } from '@/Features/StructureGraph/registry';

import LearningStructurePlace from './LearningStructurePlace.vue';

export interface LearningStructureDraft {
  graph: string;
  node: string | null;
}

defineProps<{
  locale: 'lt' | 'en';
  errors: Partial<Record<string, string>>;
}>();

const places = defineModel<LearningStructureDraft[]>({ required: true });
// Rows keep their own state (the loaded graph) when an earlier row is removed.
const keys = new WeakMap<LearningStructureDraft, number>();
let nextKey = 0;
const keyOf = (place: LearningStructureDraft): number => {
  if (!keys.has(place)) keys.set(place, nextKey++);
  return keys.get(place)!;
};
const free = computed(() => graphRegistry.map(entry => entry.key).filter(key => !places.value.some(place => place.graph === key)));
</script>
