<template>
  <RecordPage
    v-model:section="section"
    :title
    :entity-type="ModelEnum.TYPE"
    :facts
    :sections
    :primary-action
    :overflow-actions
    @action="handleAction"
  >
    <template #overview>
      <div class="max-w-3xl space-y-6">
        <div>
          <h2 class="mb-2 text-base font-semibold">
            {{ $t('Aprašymas') }}
          </h2>
          <p class="whitespace-pre-wrap text-sm text-foreground">
            {{ description || $t('Aprašymo nėra.') }}
          </p>
        </div>
        <dl class="grid border border-border sm:grid-cols-2">
          <div class="border-b border-border p-4 sm:border-b-0 sm:border-r">
            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
              {{ $t('Techninė žymė') }}
            </dt>
            <dd class="mt-1 text-sm font-medium">
              {{ contentType.slug || '—' }}
            </dd>
          </div>
          <div class="p-4">
            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
              {{ $t('Tėvinis tipas') }}
            </dt>
            <dd class="mt-1 text-sm font-medium">
              {{ localized(contentType.parent?.title) || '—' }}
            </dd>
          </div>
        </dl>
      </div>
    </template>
    <template #models>
      <div class="max-w-3xl">
        <SpotlightPopover
          v-if="can.update"
          :title="$t('Susieti įrašai')"
          :description="$t('Susietus įrašus dabar tvarkyk šio tipo puslapyje.')"
          :is-dismissed="modelsSpotlight.isDismissed.value"
          @dismiss="modelsSpotlight.dismiss"
        >
          <Button variant="outline" class="mb-4" @click="openModels">
            {{ $t('Tvarkyti susietus įrašus') }}
          </Button>
        </SpotlightPopover>
        <div class="divide-y divide-border border-y border-border">
          <div v-for="model in attachedModels" :key="model.id" class="py-3 text-sm font-medium">
            {{ model.name }}
          </div>
          <p v-if="!attachedModels.length" class="py-6 text-sm text-muted-foreground">
            {{ $t('Susietų įrašų nėra.') }}
          </p>
        </div>
      </div>
    </template>
    <template #roles>
      <div class="max-w-3xl">
        <Button v-if="can.update && contentType.model_type === 'duty'" variant="outline" class="mb-4" @click="openRoles">
          {{ $t('Tvarkyti roles') }}
        </Button>
        <div class="divide-y divide-border border-y border-border">
          <div v-for="role in contentType.roles ?? []" :key="role.id" class="py-3 text-sm font-medium">
            {{ role.name }}
          </div>
          <p v-if="!contentType.roles?.length" class="py-6 text-sm text-muted-foreground">
            {{ $t('Rolių nepriskirta.') }}
          </p>
        </div>
      </div>
    </template>
    <template v-if="sharepointPath && can.update" #files>
      <div class="max-w-4xl">
        <FileManager :starting-path="sharepointPath" :fileable="{ id: contentType.id, type: 'Type' }" />
      </div>
    </template>
  </RecordPage>
  <SheetForm v-model:open="modelsOpen" :title="$t('Susieti įrašai')" :processing="modelsProcessing" :disabled="!modelOptions" @submit="saveModels">
    <p v-if="!modelOptions" class="text-sm text-muted-foreground">{{ $t('Įkeliama…') }}</p>
    <template v-else>
      <Input v-model="modelSearch" :placeholder="$t('Ieškoti')" />
      <div class="space-y-1">
        <label v-for="model in filteredModelOptions" :key="model.id" class="flex min-h-11 items-center gap-3 border-b border-border py-2 text-sm">
          <Checkbox :model-value="modelIds.includes(model.id)" @update:model-value="checked => toggleModel(model.id, Boolean(checked))" />
          {{ localized(model.name ?? model.title) }}
        </label>
      </div>
    </template>
  </SheetForm>
  <SheetForm v-model:open="rolesOpen" :title="$t('Rolės')" :processing="rolesProcessing" :disabled="!roleOptions" @submit="saveRoles">
    <p v-if="!roleOptions" class="text-sm text-muted-foreground">{{ $t('Įkeliama…') }}</p>
    <div v-else class="space-y-1">
      <label v-for="role in roleOptions" :key="role.id" class="flex min-h-11 items-center gap-3 border-b border-border py-2 text-sm">
        <Checkbox :model-value="roleIds.includes(role.id)" @update:model-value="checked => toggleRole(role.id, Boolean(checked))" />
        {{ role.name }}
      </label>
    </div>
  </SheetForm>
  <ConfirmDialog v-model:open="deleteOpen" :title="$t('Šalinti tipą?')" :description="$t('Tipas bus perkeltas į šiukšlinę.')" :confirm-label="$t('Šalinti')" destructive @confirm="router.delete(route('types.destroy', contentType.id))" />
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { getActiveLanguage, trans as $t } from 'laravel-vue-i18n';
import { Edit, Trash2 } from 'lucide-vue-next';

import RecordPage, { type RecordAction, type RecordFact, type RecordPageSection } from '@/Components/Layouts/RecordPage.vue';
import FileManager from '@/Features/Admin/SharepointFileManager/SharepointFileManager.vue';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { ConfirmDialog, SheetForm } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import { ModelEnum } from '@/Types/enums';

type Translation = string | { lt?: string; en?: string } | null | undefined;
const props = defineProps<{
  contentType: App.Entities.Type & { parent?: { title?: Translation }; roles?: Array<{ id: string; name: string }> };
  attachedModels: Array<{ id: string; name: string }>;
  modelOptions?: Array<{ id: string; name?: string; title?: Translation }>;
  roleOptions?: Array<{ id: string; name: string }>;
  sharepointPath?: string;
  can: { update: boolean; delete: boolean };
}>();
const section = ref('overview');
const deleteOpen = ref(false);
const modelsOpen = ref(false);
const rolesOpen = ref(false);
const modelsProcessing = ref(false);
const rolesProcessing = ref(false);
const modelSearch = ref('');
const modelIds = ref(props.attachedModels.map(model => model.id));
const roleIds = ref(props.contentType.roles?.map(role => role.id) ?? []);
const modelsSpotlight = useFeatureSpotlight('type-relations-v1');
const localized = (value: Translation): string => typeof value === 'string' ? value : value?.[getActiveLanguage() as 'lt' | 'en'] ?? value?.lt ?? value?.en ?? '';
const title = computed(() => localized(props.contentType.title));
const description = computed(() => localized(props.contentType.description));
const filteredModelOptions = computed(() => (props.modelOptions ?? []).filter(model =>
  localized(model.name ?? model.title).toLocaleLowerCase().includes(modelSearch.value.toLocaleLowerCase()),
));
function toggleModel(id: string, checked: boolean): void {
  modelIds.value = checked ? [...new Set([...modelIds.value, id])] : modelIds.value.filter(value => value !== id);
}
function toggleRole(id: string, checked: boolean): void {
  roleIds.value = checked ? [...new Set([...roleIds.value, id])] : roleIds.value.filter(value => value !== id);
}
function openModels(): void {
  modelsSpotlight.dismiss();
  modelIds.value = props.attachedModels.map(model => model.id);
  modelsOpen.value = true;
  router.reload({ only: ['modelOptions'] });
}
function openRoles(): void {
  roleIds.value = props.contentType.roles?.map(role => role.id) ?? [];
  rolesOpen.value = true;
  router.reload({ only: ['roleOptions'] });
}
function saveModels(): void {
  modelsProcessing.value = true;
  router.put(route('types.models.sync', props.contentType.id), { models: modelIds.value }, {
    preserveScroll: true,
    onSuccess: () => { modelsOpen.value = false; },
    onFinish: () => { modelsProcessing.value = false; },
  });
}
function saveRoles(): void {
  rolesProcessing.value = true;
  router.put(route('types.roles.sync', props.contentType.id), { roles: roleIds.value }, {
    preserveScroll: true,
    onSuccess: () => { rolesOpen.value = false; },
    onFinish: () => { rolesProcessing.value = false; },
  });
}
const facts = computed<RecordFact[]>(() => [
  { key: 'model_type', label: $t('Modelis'), value: props.contentType.model_type ?? '—' },
  { key: 'models', label: $t('Susieti įrašai'), value: String(props.attachedModels.length) },
  { key: 'roles', label: $t('Rolės'), value: String(props.contentType.roles?.length ?? 0) },
]);
const sections = computed<RecordPageSection[]>(() => [
  { value: 'overview', label: $t('Apžvalga') },
  { value: 'models', label: $t('Susieti įrašai'), count: props.attachedModels.length },
  { value: 'roles', label: $t('Rolės'), count: props.contentType.roles?.length },
  ...(props.sharepointPath && props.can.update ? [{ value: 'files', label: $t('Failai') }] : []),
]);
const primaryAction = computed<RecordAction | undefined>(() => props.can.update ? { key: 'edit', label: $t('Redaguoti'), icon: Edit } : undefined);
const overflowActions = computed<RecordAction[]>(() => props.can.delete ? [{ key: 'delete', label: $t('Šalinti'), icon: Trash2, destructive: true }] : []);
function handleAction(action: string): void {
  if (action === 'edit') router.visit(route('types.edit', props.contentType.id));
  if (action === 'delete') deleteOpen.value = true;
}
usePageBreadcrumbs(BreadcrumbHelpers.adminShow($t('Tipai'), route('types.index'), title.value));
</script>
