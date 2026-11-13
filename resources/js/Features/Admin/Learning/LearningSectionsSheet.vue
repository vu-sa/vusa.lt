<template>
  <SheetForm
    v-model:open="open"
    :title="$t('learning_library.section_manager.title')"
    :description="sheetDescription"
    :dirty="(view === 'edit' && form.isDirty) || (view === 'reorder' && orderChanged)"
    :processing="form.processing || saving"
    :disabled
    :save-label="view === 'reorder' ? $t('Išsaugoti tvarką') : undefined"
    @submit="submit"
  >
    <template v-if="view === 'list'" #footer>
      <Button type="button" variant="outline" voice="sentence" class="min-h-11" @click="open = false">
        {{ $t('Uždaryti') }}
      </Button>
    </template>

    <!-- List -->
    <template v-if="view === 'list'">
      <Button v-if="canCreate" type="button" variant="outline" voice="sentence" class="self-start" data-testid="create-section" @click="edit(null)">
        <Plus class="size-4" aria-hidden="true" />{{ $t('learning.sections.create') }}
      </Button>
      <section v-for="group in groups" :key="group.kind" class="space-y-2" :data-slot="`sections-${group.kind}`">
        <div class="flex items-center justify-between gap-2">
          <h3 class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">
            {{ group.title }}
          </h3>
          <Button v-if="canReorder(group.sections)" type="button" variant="ghost" voice="sentence" size="sm" class="-my-1" :data-testid="`reorder-sections-${group.kind}`" @click="startReorder(group.kind)">
            <ArrowDownUp class="size-4" aria-hidden="true" />{{ $t('Keisti tvarką') }}
          </Button>
        </div>
        <CollectionRowList :items="group.sections" frame="box" :item-key="(section: LearningSectionOption) => section.id">
          <template #default="{ item: section }">
            <div class="flex items-start gap-2 px-3 py-2.5" data-slot="managed-section">
              <div class="min-w-0 flex-1">
                <p class="break-words font-semibold leading-snug text-foreground">
                  {{ titleOf(section) }}
                </p>
                <p v-if="descriptionOf(section)" class="mt-0.5 line-clamp-1 text-sm text-muted-foreground">
                  {{ descriptionOf(section) }}
                </p>
                <p class="mt-1 flex flex-wrap gap-x-2 text-xs text-muted-foreground">
                  <template v-if="section.date">
                    <span>{{ section.date }}</span><span aria-hidden="true">·</span>
                  </template>
                  <span class="tabular-nums">{{ transChoice('learning_library.section_manager.articles', section.itemsCount ?? 0, { count: String(section.itemsCount ?? 0) }) }}</span>
                  <template v-if="section.tenant">
                    <span aria-hidden="true">·</span><span>{{ section.tenant.shortname }}</span>
                  </template>
                  <span v-if="!section.title.en" class="text-status-attention">· {{ $t('learning_library.section_manager.no_english') }}</span>
                </p>
              </div>
              <Button v-if="section.canUpdate" type="button" variant="ghost" size="icon" :aria-label="`${$t('Redaguoti')}: ${titleOf(section)}`" @click="edit(section)">
                <Pencil class="size-4" aria-hidden="true" />
              </Button>
              <Button v-if="section.canDelete" type="button" variant="ghost" size="icon" :aria-label="`${$t('Ištrinti')}: ${titleOf(section)}`" @click="deleting = section; confirmOpen = true">
                <Trash2 class="size-4" aria-hidden="true" />
              </Button>
            </div>
          </template>
        </CollectionRowList>
      </section>
      <p v-if="!sections.length" class="text-sm text-muted-foreground">
        {{ $t('learning_library.section_manager.empty') }}
      </p>
    </template>

    <!-- Reorder: drag by the handle or move with the arrows, as on an institution's duties. -->
    <SortableRowList
      v-else-if="view === 'reorder'"
      v-model:items="draft"
      frame="box"
      :item-key="(section: LearningSectionOption) => section.id"
      :item-label="titleOf"
      :disabled="saving"
      data-slot="sections-reorder"
    >
      <template #default="{ item: section, index }">
        <p class="flex min-w-0 items-baseline gap-2 py-2.5">
          <span class="w-5 shrink-0 text-right text-xs tabular-nums text-muted-foreground">{{ index + 1 }}</span>
          <span class="truncate font-semibold">{{ titleOf(section) }}</span>
        </p>
      </template>
    </SortableRowList>

    <!-- Edit -->
    <div v-else class="space-y-5" data-slot="section-editor">
      <div class="flex flex-wrap items-center gap-3">
        <div :class="segmentGroupClass" role="group" :aria-label="$t('Kalba')">
          <button
            v-for="loc in LOCALES"
            :key="loc"
            type="button"
            :class="segmentVariants({ active: editLocale === loc })"
            :aria-pressed="editLocale === loc"
            :data-testid="`section-locale-${loc}`"
            @click="editLocale = loc"
          >
            <LocaleFlag :locale="loc" />
            <span>{{ loc.toUpperCase() }}</span>
            <span v-if="loc === 'en' && missingEnglish" class="size-1.5 bg-status-attention" aria-hidden="true" />
          </button>
        </div>
        <p v-if="editLocale === 'en'" class="flex items-center gap-1.5 text-xs font-medium text-status-attention">
          <Languages class="size-3.5 shrink-0" aria-hidden="true" />{{ $t('learning_library.section_manager.editing_english') }}
        </p>
      </div>
      <FormFieldWrapper id="section-kind" :label="$t('learning_library.section_manager.kind')" :hint="kindLocked ? $t('learning_library.section_manager.kind_locked') : $t('learning_library.section_manager.kind_hint')" :error="form.errors.kind">
        <FormSegmentedControl v-model="kindModel" :options="kindOptions" :label="$t('learning_library.section_manager.kind')" :disabled="kindLocked" test-id-prefix="section-kind" />
      </FormFieldWrapper>
      <FormFieldWrapper id="section-title" :label="$t('learning_library.section_manager.name')" :required="editLocale === 'lt'" :error="form.errors[`title.${editLocale}`]">
        <Input id="section-title" v-model="form.title[editLocale]" :class="fieldSurfaceClass" data-testid="section-title" />
      </FormFieldWrapper>
      <FormFieldWrapper id="section-description" :label="$t('learning_library.section_manager.description_label')" :hint="$t('learning_library.section_manager.description_hint')" :error="form.errors[`description.${editLocale}`]">
        <Textarea id="section-description" v-model="form.description[editLocale]" rows="3" :class="fieldSurfaceClass" data-testid="section-description" />
      </FormFieldWrapper>
      <FormFieldWrapper v-if="form.kind === KnowledgeSectionKind.Collection" id="section-date" :label="$t('learning_library.section_manager.date')" :hint="$t('learning_library.section_manager.date_hint')" :error="form.errors.date">
        <DatePicker :model-value="form.date" clearable data-testid="section-date" @update:model-value="(value?: Date) => form.date = isoDate(value)" />
      </FormFieldWrapper>
      <TenantSelectField v-if="!scope.canTargetEverywhere" id="section-tenant" v-model="form.tenant_id" :tenants="scope.tenants" :error="form.errors.tenant_id" />
      <Button v-if="!editOnly" type="button" variant="ghost" voice="sentence" class="-ml-3" @click="view = 'list'">
        {{ $t('learning_library.section_manager.back') }}
      </Button>
    </div>
  </SheetForm>
  <ConfirmDialog v-model:open="confirmOpen" :title="$t('learning_library.section_manager.delete_title')" :description="$t('learning_library.section_manager.delete_description')" :confirm-label="$t('Ištrinti')" destructive @confirm="destroy" />
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { trans as $t, transChoice } from 'laravel-vue-i18n';
import { ArrowDownUp, Languages, Pencil, Plus, Trash2 } from '@lucide/vue';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import TenantSelectField from '@/Components/AdminForms/TenantSelectField.vue';
import CollectionRowList from '@/Components/Collection/CollectionRowList.vue';
import SortableRowList from '@/Components/Collection/SortableRowList.vue';
import { ConfirmDialog, FormSegmentedControl, SheetForm } from '@/Components/Patterns';
import LocaleFlag from '@/Components/Public/Nav/LocaleFlag.vue';
import { Button } from '@/Components/ui/button';
import { fieldSurfaceClass, segmentGroupClass, segmentVariants } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { DatePicker } from '@/Components/ui/date-picker';
import { KnowledgeSectionKind } from '@/Types/enums';

import type { LearningSectionOption, LearningSectionScopes } from './types';

const props = defineProps<{
  sections: LearningSectionOption[];
  scopes: LearningSectionScopes;
  canCreate: boolean;
  /** Opens straight into this section's editor and closes on save, as from the section's own page. */
  editOnly?: string | null;
  locale: 'lt' | 'en';
}>();
const open = defineModel<boolean>('open', { required: true });

const LOCALES = ['lt', 'en'] as const;
type View = 'list' | 'reorder' | 'edit';
const view = ref<View>('list');
const editingId = ref<string | null>(null);
const editLocale = ref<'lt' | 'en'>('lt');
const scope = computed(() => props.scopes[editingId.value ? 'update' : 'create']);
const deleting = ref<LearningSectionOption | null>(null);
const confirmOpen = ref(false);
const draft = ref<LearningSectionOption[]>([]);
const reorderKind = ref<KnowledgeSectionKind>(KnowledgeSectionKind.Topic);
const saving = ref(false);
const form = useForm({ kind: KnowledgeSectionKind.Topic as KnowledgeSectionKind, date: null as string | null, title: { lt: '', en: '' }, description: { lt: '', en: '' }, tenant_id: null as number | null });
const kindOptions = computed<{ value: string; label: string }[]>(() => [
  { value: KnowledgeSectionKind.Topic, label: $t('learning.section_kinds.topic') },
  { value: KnowledgeSectionKind.Collection, label: $t('learning.section_kinds.collection') },
]);
const kindModel = computed<string>({
  get: () => form.kind,
  set: (value) => {
    form.kind = value as KnowledgeSectionKind;
  },
});
const editingSection = computed(() => props.sections.find(section => section.id === editingId.value) ?? null);
const kindLocked = computed(() => (editingSection.value?.itemsCount ?? 0) > 0);
const groups = computed(() => ([KnowledgeSectionKind.Topic, KnowledgeSectionKind.Collection] as const).map(kind => ({
  kind,
  title: $t(kind === KnowledgeSectionKind.Topic ? 'learning_library.section_manager.topics' : 'learning_library.section_manager.collections'),
  sections: props.sections.filter(section => section.kind === kind),
})).filter(group => group.sections.length));
const isoDate = (value?: Date) => value ? `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}` : null;

const titleOf = (section: LearningSectionOption) => section.title[props.locale] || section.title.lt || section.title.en || '';
const descriptionOf = (section: LearningSectionOption) => section.description[props.locale] || section.description.lt || '';
// A partial reorder would leave others' sections in odd places, so it needs rights over the whole list.
const canReorder = (sections: LearningSectionOption[]) => sections.length > 1 && sections.every(section => section.canUpdate);
const reorderSource = computed(() => props.sections.filter(section => section.kind === reorderKind.value));
const orderChanged = computed(() => draft.value.some((section, index) => section.id !== reorderSource.value[index]?.id));
const missingEnglish = computed(() => form.title.lt.trim() !== '' && form.title.en.trim() === '');

watch(open, (value) => {
  if (!value) {
    view.value = 'list';
    return;
  }
  const only = props.editOnly ? props.sections.find(section => section.id === props.editOnly) : null;
  if (only) edit(only);
}, { immediate: true });

function edit(section: LearningSectionOption | null): void {
  editingId.value = section?.id ?? null;
  form.kind = section?.kind ?? KnowledgeSectionKind.Topic;
  form.date = section?.date ?? null;
  form.title = { lt: section?.title.lt ?? '', en: section?.title.en ?? '' };
  form.description = { lt: section?.description.lt ?? '', en: section?.description.en ?? '' };
  form.tenant_id = section ? section.tenant_id : (!scope.value.canTargetEverywhere && scope.value.tenants.length === 1 ? scope.value.tenants[0]!.id : null);
  form.clearErrors();
  form.defaults();
  editLocale.value = 'lt';
  view.value = 'edit';
}

function startReorder(kind: KnowledgeSectionKind): void {
  reorderKind.value = kind;
  draft.value = props.sections.filter(section => section.kind === kind);
  view.value = 'reorder';
}

function submit(): void {
  if (view.value === 'reorder') {
    saving.value = true;
    router.post(route('knowledgeSections.reorder'), { sections: draft.value.map((section, order) => ({ id: section.id, order })) }, {
      preserveScroll: true,
      onSuccess: () => { view.value = 'list'; },
      onFinish: () => { saving.value = false; },
    });
    return;
  }
  if (view.value !== 'edit') return;
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      form.defaults();
      if (props.editOnly) open.value = false;
      else view.value = 'list';
    },
  };
  form.transform(data => ({ ...data, date: data.kind === KnowledgeSectionKind.Collection ? data.date : null }));
  if (editingId.value) form.put(route('knowledgeSections.update', editingId.value), options);
  else form.post(route('knowledgeSections.store'), options);
}

function destroy(): void {
  if (!deleting.value) return;
  router.delete(route('knowledgeSections.destroy', deleting.value.id), { preserveScroll: true, onSuccess: () => { confirmOpen.value = false; deleting.value = null; } });
}

const sheetDescription = computed(() => {
  if (view.value === 'reorder') return $t('learning_library.section_manager.reorder_hint');
  if (view.value === 'edit') return editingId.value ? $t('learning_library.section_manager.edit_title') : $t('learning.sections.create');
  return $t('learning_library.section_manager.description');
});
const disabled = computed(() => {
  if (view.value === 'reorder') return saving.value || !orderChanged.value;
  if (view.value === 'edit') return !scope.value.canTargetEverywhere && form.tenant_id === null;
  return true;
});
</script>
