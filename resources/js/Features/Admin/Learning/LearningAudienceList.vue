<template>
  <section id="step-audiences" class="space-y-3" data-testid="step-audiences">
    <h2 class="text-lg font-semibold">
      {{ $t('learning_library.fields.audience') }}
    </h2>
    <p v-if="kind === 'knowledge'" class="text-sm text-muted-foreground" data-slot="knowledge-audience-hint">
      {{ $t('learning_library.sections.path_description') }}
    </p>
    <p v-if="errors.audiences" class="text-sm text-destructive" role="alert">
      {{ errors.audiences }}
    </p>
    <ul class="divide-y divide-border border-y border-border">
      <li v-for="(audience, index) in audiences" :key="audience.id ?? index" class="flex flex-wrap items-start gap-2 py-3" data-slot="audience-row">
        <div class="min-w-0 flex-1 basis-52 space-y-1">
          <p class="break-words text-sm font-semibold">
            {{ audience.label }}
          </p>
          <p class="text-xs text-muted-foreground">
            {{ $t(`learning.importance.${audience.importance}`) }}
            <template v-if="audience.stage">
              · {{ $t(`learning.stages.${audience.stage}`) }}
            </template>
          </p>
          <p v-if="audience.description[locale]" class="line-clamp-2 text-sm text-muted-foreground">
            {{ audience.description[locale] }}
          </p>
          <p v-for="(error, key) in errorsFor(index)" :key class="text-sm text-destructive" role="alert">
            {{ error }}
          </p>
        </div>
        <div class="flex gap-1">
          <Button type="button" variant="ghost" voice="sentence" class="min-h-11" :aria-label="`${$t('Redaguoti')}: ${audience.label}`" @click="edit(index)">
            {{ $t('Redaguoti') }}
          </Button>
          <Button v-if="!isSystem" type="button" variant="ghost" voice="sentence" class="min-h-11" :aria-label="`${$t('Pašalinti')}: ${audience.label}`" @click="audiences.splice(index, 1)">
            {{ $t('Pašalinti') }}
          </Button>
        </div>
      </li>
    </ul>
    <Button v-if="!isSystem" type="button" variant="outline" voice="sentence" class="min-h-11" data-testid="add-audience" @click="edit(null)">
      <Plus class="size-4" aria-hidden="true" />{{ $t('learning_library.audiences.add') }}
    </Button>
  </section>

  <LearningAudienceSheet
    v-model:open="sheetOpen"
    :audience="editing === null ? null : audiences[editing]"
    :kind
    :is-system
    :audience-types
    :can-target-everywhere
    :tenants
    :institution-types
    :duty-types
    :responsibilities
    @save="save"
  />
</template>

<script setup lang="ts">
import { Plus } from '@lucide/vue';
import { ref } from 'vue';

import { Button } from '@/Components/ui/button';
import type { LearningStepKind } from '@/Types/enums';

import LearningAudienceSheet from './LearningAudienceSheet.vue';
import type { LearningAudienceDraft, LearningAudienceOptions } from './types';

const props = defineProps<LearningAudienceOptions & {
  kind: LearningStepKind | 'knowledge';
  isSystem?: boolean;
  locale: 'lt' | 'en';
  errors: Partial<Record<string, string>>;
}>();
const audiences = defineModel<LearningAudienceDraft[]>({ required: true });

const sheetOpen = ref(false);
const editing = ref<number | null>(null);

function edit(index: number | null): void {
  editing.value = index;
  sheetOpen.value = true;
}

function save(audience: LearningAudienceDraft): void {
  if (editing.value === null) {
    audiences.value.push({ ...audience, order: Math.max(-1, ...audiences.value.map(row => row.order)) + 1 });
  }
  else {
    audiences.value[editing.value] = audience;
  }
}

function errorsFor(index: number): Record<string, string | undefined> {
  return Object.fromEntries(Object.entries(props.errors).filter(([key]) => key === `audiences.${index}` || key.startsWith(`audiences.${index}.`)));
}
</script>
