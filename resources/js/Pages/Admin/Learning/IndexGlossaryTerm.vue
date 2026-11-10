<template>
  <CollectionPage
    collection="glossaryTerms"
    :eyebrow="$t('shell.workspaces.pradzia.title')"
    :title="$t('learning.pages.glossary')"
    :lead="$t('learning_library.glossary.lead')"
    :source
    :columns
    :item-key="termKey"
    :row-actions="rowActions"
    default-view="rows"
    :available-views="['rows']"
    table-fixed
    actions-width="w-36"
    table-min-width="min-w-[48rem]"
    :search-placeholder="$t('learning_library.glossary.search_placeholder')"
    @row-action="(item, key) => selectRowAction(key, item)"
  >
    <template #actions>
      <Button v-if="canManageTerms" variant="brand" data-testid="create-glossary-term" @click="openSheet()">
        <Plus aria-hidden="true" />
        {{ $t('learning_library.glossary.new_term') }}
      </Button>
    </template>

    <template #row="{ item, actions: recordActions, view }">
      <CollectionRow
        :id="`term-${item.id}`"
        :spotlight="item === source.items.value[0]"
        :actions="view === 'preview' ? [] : recordActions"
        class="px-3 py-3 sm:px-4"
        @select="key => selectRowAction(key, item)"
      >
        <div class="min-w-0 flex-1">
          <div class="flex flex-wrap items-baseline justify-between gap-2">
            <button
              v-if="canManageTerms"
              type="button"
              class="text-left font-medium text-foreground hover:text-brand"
              @click="openSheet(item)"
            >
              {{ item.term }}
            </button>
            <span v-else class="font-medium text-foreground">{{ item.term }}</span>
            <span class="text-xs tabular-nums text-muted-foreground" data-testid="glossary-term-steps-count">
              {{ $tChoice('learning_library.glossary.steps_usage', item.stepsCount, { count: String(item.stepsCount) }) }}
            </span>
          </div>
          <p class="mt-0.5 whitespace-pre-line text-sm text-muted-foreground">
            {{ item.definition }}
          </p>
          <LearningGlossarySteps :steps="item.steps" />
        </div>
      </CollectionRow>
    </template>

    <template #cell="{ item, column }">
      <template v-if="column.key === 'term'">
        <button
          v-if="canManageTerms"
          type="button"
          class="text-left font-medium text-foreground hover:text-brand"
          @click="openSheet(item)"
        >
          {{ item.term }}
        </button>
        <span v-else class="font-medium text-foreground">{{ item.term }}</span>
      </template>
      <div v-else-if="column.key === 'definition'" class="space-y-2">
        <p class="whitespace-pre-line text-sm text-muted-foreground">{{ item.definition }}</p>
        <LearningGlossarySteps :steps="item.steps" />
      </div>
      <span v-else-if="column.key === 'stepsCount'" class="tabular-nums text-sm text-muted-foreground" data-testid="glossary-term-steps-count">
        {{ item.stepsCount }}
      </span>
    </template>

    <template #empty>
      <EmptyState
        mode="empty"
        :icon="BookA"
        :title="$t('learning_library.glossary.empty_title')"
        :description="$t('learning_library.glossary.empty_description')"
        :action-label="canManageTerms ? $t('learning_library.glossary.new_term') : undefined"
        @action="openSheet()"
      />
    </template>
  </CollectionPage>

  <SheetForm
    v-if="canManageTerms"
    v-model:open="sheetOpen"
    required-note
    :title="editing ? $t('learning_library.glossary.edit_term') : $t('learning_library.glossary.new_term')"
    :description="$t('learning_library.glossary.sheet_description')"
    :processing="form.processing"
    :dirty="form.isDirty"
    @cancel="resetForm"
    @submit="submitForm"
  >
    <FormFieldWrapper
      id="glossary-term-input"
      :label="$t('learning_library.glossary.term')"
      :error="form.errors['term.lt'] || form.errors['term.en'] || form.errors.term"
      required
    >
      <MultiLocaleInput id="glossary-term-input" v-model:input="form.term" />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="glossary-definition-input"
      :label="$t('learning_library.glossary.definition')"
      :error="form.errors['definition.lt'] || form.errors['definition.en'] || form.errors.definition"
      required
    >
      <MultiLocaleInput
        id="glossary-definition-input"
        v-model:input="form.definition"
        input-type="textarea"
      />
    </FormFieldWrapper>
  </SheetForm>

  <ConfirmDialog
    v-if="canManageTerms"
    :open="toDelete !== null"
    :title="$t('learning_library.glossary.delete_title')"
    :description="$t('learning_library.glossary.delete_description')"
    :confirm-label="$t('learning_library.glossary.delete_confirm')"
    destructive
    @update:open="!$event && (toDelete = null)"
    @confirm="removeTerm"
  />
</template>

<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { BookA, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref, toRaw, toRef } from 'vue';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import type { CollectionColumn } from '@/Components/Collection/types';
import CollectionRow from '@/Components/Collection/CollectionRow.vue';
import type { CollectionRowAction } from '@/Components/Collection/CollectionRowActions.vue';
import MultiLocaleInput from '@/Components/FormItems/MultiLocaleInput.vue';
import CollectionPage from '@/Components/Layouts/CollectionPage.vue';
import { ConfirmDialog, EmptyState, SheetForm } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useLocalCollectionSource } from '@/Composables/useCollectionSource';
import LearningGlossarySteps from '@/Features/Admin/Learning/LearningGlossarySteps.vue';
import type { GlossaryTermData } from '@/Types/learning';

interface GlossaryFormState {
  term: { lt: string; en: string };
  definition: { lt: string; en: string };
}

const props = defineProps<{
  terms: GlossaryTermData[];
  canManage?: boolean;
}>();

const canManageTerms = computed(() => Boolean(props.canManage));
const termKey = (item: GlossaryTermData) => item.id;

const source = useLocalCollectionSource<GlossaryTermData>({
  items: toRef(props, 'terms'),
  searchText: item => [
    item.term,
    item.definition,
    item.translations?.term?.lt,
    item.translations?.term?.en,
    item.translations?.definition?.lt,
    item.translations?.definition?.en,
  ],
  defaultSort: 'term:asc',
  sortOptions: () => [
    { value: 'term:asc', label: $t('learning_library.library.sort.title_asc'), by: item => item.term },
    { value: 'term:desc', label: $t('learning_library.library.sort.title_desc'), by: item => item.term },
  ],
});

const columns = computed<CollectionColumn[]>(() => [
  { key: 'term', label: $t('learning_library.glossary.term'), class: 'w-56', sortField: 'term' },
  { key: 'definition', label: $t('learning_library.glossary.definition') },
  { key: 'stepsCount', label: $t('learning_library.glossary.steps_count'), class: 'w-28' },
]);

const rowActions = (_item: GlossaryTermData): CollectionRowAction[] => {
  if (!canManageTerms.value) {
    return [];
  }
  return [
    { key: 'edit', role: 'edit' as const, label: $t('Redaguoti'), icon: Pencil },
    { key: 'delete', label: $t('Ištrinti'), icon: Trash2, destructive: true },
  ];
};

const sheetOpen = ref(false);
const editing = ref<GlossaryTermData | null>(null);
const toDelete = ref<GlossaryTermData | null>(null);

const blank = (): GlossaryFormState => ({
  term: { lt: '', en: '' },
  definition: { lt: '', en: '' },
});

const form = useForm<GlossaryFormState>(blank());

function openSheet(item: GlossaryTermData | null = null): void {
  editing.value = item;
  const next: GlossaryFormState = item
    ? {
        term: structuredClone(toRaw(item.translations?.term ?? { lt: item.term, en: '' })),
        definition: structuredClone(toRaw(item.translations?.definition ?? { lt: item.definition, en: '' })),
      }
    : blank();
  form.defaults(next);
  form.reset();
  form.clearErrors();
  sheetOpen.value = true;
}

function resetForm(): void {
  form.reset();
  form.clearErrors();
}

function submitForm(): void {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      form.defaults();
      sheetOpen.value = false;
      editing.value = null;
    },
  };

  if (editing.value) {
    form.put(route('glossaryTerms.update', editing.value.id), options);
  }
  else {
    form.post(route('glossaryTerms.store'), options);
  }
}

function selectRowAction(key: string, item: GlossaryTermData): void {
  if (key === 'edit') {
    openSheet(item);
  }
  else if (key === 'delete') {
    toDelete.value = item;
  }
}

function removeTerm(): void {
  if (!toDelete.value) {
    return;
  }
  const { id } = toDelete.value;
  toDelete.value = null;
  router.delete(route('glossaryTerms.destroy', id), { preserveScroll: true });
}
</script>
