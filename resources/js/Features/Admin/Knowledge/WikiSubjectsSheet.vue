<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronsUpDown, X } from '@lucide/vue';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import { TypeTreeSelect } from '@/Components/AdminForms/TypeTree';
import { SheetForm } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { NativeSelect } from '@/Components/ui/native-select';
import { SingleSelect } from '@/Components/ui/single-select';
import InstitutionSelectDialog from '@/Features/Admin/AdminSearch/Components/Select/InstitutionSelectDialog.vue';
import type { NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { KnowledgeItemKind } from '@/Types/enums';
import type { LearningTypeOption, WikiSubject, WikiSubjectType } from '@/Types/learning';

const props = defineProps<{
  itemId: string;
  subjects: WikiSubject[];
  institutionTypes: LearningTypeOption[];
  dutyTypes: LearningTypeOption[];
  /** Material may be about a term; a term itself is only about institutions and types. */
  kind: KnowledgeItemKind;
  terms?: { id: string; title: string }[];
}>();
const open = defineModel<boolean>('open', { required: true });

type Draft = Pick<WikiSubject, 'id' | 'type' | 'title'>;
const form = useForm<{ subjects: Draft[] }>({ subjects: [] });
const adding = ref<WikiSubjectType>(props.kind === KnowledgeItemKind.Material ? 'knowledge_item' : 'institution');
const institutionDialogOpen = ref(false);
const typeId = ref<string | null>(null);
const subjectTypes = computed<WikiSubjectType[]>(() => props.kind === KnowledgeItemKind.Material
  ? ['knowledge_item', 'institution', 'institution_type', 'duty_type']
  : ['institution', 'institution_type', 'duty_type']);
const typeOptions = computed(() => subjectTypes.value.map(value => ({ value, label: $t(`learning.wiki.subject_types.${value}`) })));
const termOptions = computed(() => (props.terms ?? []).filter(term => term.id !== props.itemId));
const pickedTerm = computed<{ id: string; title: string } | null>({
  get: () => null,
  set: (term) => { if (term) add({ id: term.id, type: 'knowledge_item', title: term.title }); },
});
const termId = computed<string | null>({
  get: () => null,
  set: (id) => { pickedTerm.value = termOptions.value.find(term => term.id === id) ?? null; },
});
const treeTypes = computed(() => adding.value === 'duty_type' ? props.dutyTypes : props.institutionTypes);

watch(open, (value) => {
  if (!value) return;
  form.subjects = props.subjects.map(({ id, type, title }) => ({ id, type, title }));
  form.defaults();
  institutionDialogOpen.value = false;
  typeId.value = null;
});

function add(subject: Draft): void {
  if (!form.subjects.some(existing => existing.type === subject.type && existing.id === subject.id)) {
    form.subjects = [...form.subjects, subject];
  }
}

function pickInstitution(hits: NormalizedSearchHit[]): void {
  hits.forEach(hit => add({ id: String(hit.recordId), type: 'institution', title: hit.title }));
}

watch(typeId, (id) => {
  if (id === null || adding.value === 'institution' || adding.value === 'knowledge_item') return;
  const type = treeTypes.value.find(option => String(option.id) === id);
  if (type) add({ id, type: adding.value, title: type.title });
  typeId.value = null;
});

function remove(subject: Draft): void {
  form.subjects = form.subjects.filter(existing => existing !== subject);
}

function save(): void {
  form.transform(data => ({ subjects: data.subjects.map(({ id, type }) => ({ id, type })) }))
    .put(route('knowledgeItems.subjects.sync', props.itemId), {
      preserveScroll: true,
      onSuccess: () => {
        form.defaults();
        open.value = false;
      },
    });
}
</script>

<template>
  <SheetForm
    v-model:open="open"
    :title="$t('learning.wiki.subjects')"
    :description="$t('learning.wiki.subjects_hint')"
    :dirty="form.isDirty"
    :processing="form.processing"
    @submit="save"
  >
    <!-- A portaled dialog, as in DutyForm: an inline search inside the sheet's <form> would submit it. -->
    <div class="space-y-4">
      <ul v-if="form.subjects.length" class="divide-y divide-border border border-border" data-slot="wiki-subjects">
        <li v-for="subject in form.subjects" :key="`${subject.type}:${subject.id}`" class="flex items-center justify-between gap-3 py-1 pr-1 pl-3">
          <span class="min-w-0">
            <span class="block truncate text-sm font-semibold">{{ subject.title }}</span>
            <span class="block text-xs text-muted-foreground">{{ $t(`learning.wiki.subject_types.${subject.type}`) }}</span>
          </span>
          <Button type="button" variant="ghost" size="icon" :aria-label="`${$t('Pašalinti')}: ${subject.title}`" @click="remove(subject)">
            <X class="size-4" aria-hidden="true" />
          </Button>
        </li>
      </ul>
      <p v-else class="text-sm text-muted-foreground">{{ $t('learning.wiki.subjects_empty') }}</p>

      <FormFieldWrapper id="wiki-subject-type" :label="$t('learning.wiki.subjects_add')">
        <NativeSelect id="wiki-subject-type" v-model="adding" :options="typeOptions" />
      </FormFieldWrapper>
      <InstitutionSelectDialog v-if="adding === 'institution'" v-model:open="institutionDialogOpen" :institutions="[]" @confirm="pickInstitution">
        <template #trigger>
          <Button type="button" variant="outline" class="u-touch w-full justify-between font-normal" data-testid="wiki-subject-institution">
            <span class="truncate text-muted-foreground">{{ $t('Pasirinkti instituciją…') }}</span>
            <ChevronsUpDown class="size-4 opacity-50" aria-hidden="true" />
          </Button>
        </template>
      </InstitutionSelectDialog>
      <template v-else-if="adding === 'knowledge_item'">
        <SingleSelect v-if="termOptions.length > 16" v-model="pickedTerm" :options="termOptions" label-field="title" value-field="id" :placeholder="$t('Pasirinkti')" data-testid="wiki-subject-term" />
        <NativeSelect v-else id="wiki-subject-term" v-model="termId" :placeholder-value="null" :options="termOptions.map(term => ({ value: term.id, label: term.title }))" :placeholder="$t('Pasirinkti')" data-testid="wiki-subject-term" />
      </template>
      <TypeTreeSelect v-else id="wiki-subject-target" v-model="typeId" :types="treeTypes" as-string :placeholder="$t('Pasirinkti')" />
    </div>
  </SheetForm>
</template>
