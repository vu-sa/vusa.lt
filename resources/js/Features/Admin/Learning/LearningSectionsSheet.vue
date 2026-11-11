<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { trans as $t, transChoice } from 'laravel-vue-i18n';
import { ArrowDown, ArrowDownUp, ArrowUp, GripVertical, Languages, Pencil, Plus, Trash2 } from '@lucide/vue';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import TenantSelectField from '@/Components/AdminForms/TenantSelectField.vue';
import CollectionRowList from '@/Components/Collection/CollectionRowList.vue';
import { ConfirmDialog, SheetForm } from '@/Components/Patterns';
import LocaleFlag from '@/Components/Public/Nav/LocaleFlag.vue';
import SortableDutiesTable from '@/Components/Tables/SortableDutiesTable.vue';
import { Button } from '@/Components/ui/button';
import { fieldSurfaceClass, segmentGroupClass, segmentVariants } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';

import type { LearningSectionOption, LearningSectionScopes } from './types';

const props = defineProps<{
  sections: LearningSectionOption[];
  scopes: LearningSectionScopes;
  canCreate: boolean;
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
const saving = ref(false);
const form = useForm({ title: { lt: '', en: '' }, description: { lt: '', en: '' }, tenant_id: null as number | null });

const titleOf = (section: LearningSectionOption) => section.title[props.locale] || section.title.lt || section.title.en || '';
const descriptionOf = (section: LearningSectionOption) => section.description[props.locale] || section.description.lt || '';
// A partial reorder would leave others' sections in odd places, so it needs rights over the whole list.
const canReorder = computed(() => props.sections.length > 1 && props.sections.every(section => section.canUpdate));
const orderChanged = computed(() => draft.value.some((section, index) => section.id !== props.sections[index]?.id));
const missingEnglish = computed(() => form.title.lt.trim() !== '' && form.title.en.trim() === '');

watch(open, (value) => {
  if (!value) view.value = 'list';
});

function edit(section: LearningSectionOption | null): void {
  editingId.value = section?.id ?? null;
  form.title = { lt: section?.title.lt ?? '', en: section?.title.en ?? '' };
  form.description = { lt: section?.description.lt ?? '', en: section?.description.en ?? '' };
  form.tenant_id = section ? section.tenant_id : (!scope.value.canTargetEverywhere && scope.value.tenants.length === 1 ? scope.value.tenants[0]!.id : null);
  form.clearErrors();
  form.defaults();
  editLocale.value = 'lt';
  view.value = 'edit';
}

function startReorder(): void {
  draft.value = [...props.sections];
  view.value = 'reorder';
}

function move(index: number, step: -1 | 1): void {
  const target = index + step;
  if (target < 0 || target >= draft.value.length) return;
  const next = [...draft.value];
  [next[index], next[target]] = [next[target]!, next[index]!];
  draft.value = next;
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
  const options = { preserveScroll: true, onSuccess: () => { form.defaults(); view.value = 'list'; } };
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
      <Button type="button" variant="outline" voice="sentence" class="min-h-11" @click="open = false">{{ $t('Uždaryti') }}</Button>
    </template>

    <!-- List -->
    <template v-if="view === 'list'">
      <div v-if="canCreate || canReorder" class="flex flex-wrap gap-2">
        <Button v-if="canCreate" type="button" variant="outline" voice="sentence" data-testid="create-section" @click="edit(null)">
          <Plus class="size-4" aria-hidden="true" />{{ $t('learning.sections.create') }}
        </Button>
        <Button v-if="canReorder" type="button" variant="ghost" voice="sentence" data-testid="reorder-sections" @click="startReorder">
          <ArrowDownUp class="size-4" aria-hidden="true" />{{ $t('Keisti tvarką') }}
        </Button>
      </div>
      <CollectionRowList v-if="sections.length" :items="sections" frame="box" :item-key="(section: LearningSectionOption) => section.id">
        <template #default="{ item: section }">
          <div class="flex items-start gap-2 px-3 py-2.5" data-slot="managed-section">
            <div class="min-w-0 flex-1">
              <p class="break-words font-semibold leading-snug text-foreground">{{ titleOf(section) }}</p>
              <p v-if="descriptionOf(section)" class="mt-0.5 line-clamp-1 text-sm text-muted-foreground">{{ descriptionOf(section) }}</p>
              <p class="mt-1 flex flex-wrap gap-x-2 text-xs text-muted-foreground">
                <span class="tabular-nums">{{ transChoice('learning_library.section_manager.articles', section.itemsCount ?? 0, { count: String(section.itemsCount ?? 0) }) }}</span>
                <template v-if="section.tenant"><span aria-hidden="true">·</span><span>{{ section.tenant.shortname }}</span></template>
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
      <p v-else class="text-sm text-muted-foreground">{{ $t('learning_library.section_manager.empty') }}</p>
    </template>

    <!-- Reorder: drag by the handle or move with the arrows, as on an institution's duties. -->
    <SortableDutiesTable
      v-else-if="view === 'reorder'"
      v-model:items="draft"
      frame="box"
      :item-key="(section: LearningSectionOption) => section.id"
      data-slot="sections-reorder"
    >
      <template #default="{ item: section, index }">
        <div class="flex items-center gap-2 py-1 pr-1 pl-1">
          <button type="button" class="handle flex min-h-11 w-8 shrink-0 cursor-grab items-center justify-center text-muted-foreground active:cursor-grabbing" :aria-label="$t('Tempti, kad pakeistum tvarką')" tabindex="-1" :disabled="saving">
            <GripVertical class="size-4" aria-hidden="true" />
          </button>
          <span class="w-5 shrink-0 text-right text-xs tabular-nums text-muted-foreground">{{ index + 1 }}</span>
          <span class="min-w-0 flex-1 truncate font-semibold">{{ titleOf(section) }}</span>
          <Button type="button" variant="ghost" size="icon" :disabled="saving || index === 0" :aria-label="`${$t('Perkelti aukščiau')}: ${titleOf(section)}`" @click="move(index, -1)"><ArrowUp class="size-4" aria-hidden="true" /></Button>
          <Button type="button" variant="ghost" size="icon" :disabled="saving || index === draft.length - 1" :aria-label="`${$t('Perkelti žemiau')}: ${titleOf(section)}`" @click="move(index, 1)"><ArrowDown class="size-4" aria-hidden="true" /></Button>
        </div>
      </template>
    </SortableDutiesTable>

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
      <FormFieldWrapper id="section-title" :label="$t('learning_library.section_manager.name')" :required="editLocale === 'lt'" :error="form.errors[`title.${editLocale}`]">
        <Input id="section-title" v-model="form.title[editLocale]" :class="fieldSurfaceClass" data-testid="section-title" />
      </FormFieldWrapper>
      <FormFieldWrapper id="section-description" :label="$t('learning_library.section_manager.description_label')" :hint="$t('learning_library.section_manager.description_hint')" :error="form.errors[`description.${editLocale}`]">
        <Textarea id="section-description" v-model="form.description[editLocale]" rows="3" :class="fieldSurfaceClass" data-testid="section-description" />
      </FormFieldWrapper>
      <TenantSelectField v-if="!scope.canTargetEverywhere" id="section-tenant" v-model="form.tenant_id" :tenants="scope.tenants" :error="form.errors.tenant_id" />
      <Button type="button" variant="ghost" voice="sentence" class="-ml-3" @click="view = 'list'">{{ $t('learning_library.section_manager.back') }}</Button>
    </div>
  </SheetForm>
  <ConfirmDialog v-model:open="confirmOpen" :title="$t('learning_library.section_manager.delete_title')" :description="$t('learning_library.section_manager.delete_description')" :confirm-label="$t('Ištrinti')" destructive @confirm="destroy" />
</template>
