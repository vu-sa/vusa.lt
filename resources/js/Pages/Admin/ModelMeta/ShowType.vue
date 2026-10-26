<template>
  <RecordPage
    v-model:section="section"
    :title
    :entity-type
    :facts
    :sections
    :primary-action
    :overflow-actions
    actions-beside-title
    @action="handleAction"
  >
    <template #overview>
      <div class="grid gap-10 xl:grid-cols-2 xl:gap-16">
        <OverviewSection variant="home" :title="$t('Aprašymas')" :icon="FileText">
          <p class="whitespace-pre-wrap text-sm text-foreground">
            {{ description || $t('Aprašymo nėra.') }}
          </p>
          <dl class="grid gap-4 pt-2 sm:grid-cols-2">
            <div>
              <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                {{ $t('Techninė žymė') }}
              </dt>
              <dd class="mt-1 text-sm font-medium">
                {{ contentType.slug || '—' }}
              </dd>
            </div>
            <div>
              <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                {{ $t('Tėvinis tipas') }}
              </dt>
              <dd class="mt-1 text-sm font-medium">
                {{ localized(contentType.parent?.title) || '—' }}
              </dd>
            </div>
          </dl>
        </OverviewSection>

        <OverviewSection
          v-if="responsibleDuties?.length"
          variant="home"
          :title="$t('responsibilities.label')"
          :icon="Compass"
          data-slot="type-responsible-duties"
        >
          <ul class="divide-y divide-border">
            <li v-for="item in responsibleDuties" :key="item.id">
              <Link :href="route('duties.show', item.duty_id)" class="flex min-h-11 flex-col justify-center py-2 hover:bg-secondary">
                <span class="text-sm font-medium"><InflectedDutyName :name="item.duty" /></span>
                <span class="text-xs text-muted-foreground">{{ item.label }}</span>
              </Link>
            </li>
          </ul>
        </OverviewSection>
      </div>
    </template>
    <template #models>
      <OverviewSection variant="home" :title="$t('Susieti įrašai')" :icon="Link2" :count="attachedModels.length" class="max-w-3xl">
        <template v-if="canManageAssignments" #actions>
          <Button variant="outline" size="sm" @click="openModels">
            {{ $t('Tvarkyti susietus įrašus') }}
          </Button>
        </template>
        <div class="divide-y divide-border">
          <div v-for="model in attachedModels" :key="model.id" class="py-3 text-sm font-medium">
            {{ model.name }}
          </div>
          <p v-if="!attachedModels.length" class="text-sm text-muted-foreground">
            {{ $t('Susietų įrašų nėra.') }}
          </p>
        </div>
      </OverviewSection>
    </template>
    <template #roles>
      <OverviewSection
        variant="home"
        :title="$t('responsibilities.label')"
        :icon="Shield"
        :count="responsibilityGrants.length"
        class="max-w-3xl"
        data-testid="type-responsibility-grants"
      >
        <template v-if="can.manageResponsibilities" #actions>
          <SpotlightPopover
            :title="$t('types.grants_spotlight_title')"
            :description="$t('types.grants_spotlight')"
            :is-dismissed="grantSpotlight.isDismissed.value"
            @dismiss="grantSpotlight.dismiss"
          >
            <Button variant="outline" size="sm" voice="sentence" as-child>
              <Link :href="route('responsibilities.index')" @click="grantSpotlight.dismiss">
                {{ $t('types.open_responsibilities') }}
              </Link>
            </Button>
          </SpotlightPopover>
        </template>
        <p class="text-xs text-muted-foreground">
          {{ $t('types.grants_hint') }}
        </p>
        <div class="divide-y divide-border">
          <div v-for="grant in responsibilityGrants" :key="grant.id" class="space-y-1 py-3 text-sm" data-testid="type-grant-row">
            <Link v-if="grant.href" :href="grant.href" class="font-medium hover:underline" @click="grantSpotlight.dismiss">
              {{ grant.title }}
            </Link>
            <span v-else class="font-medium">{{ grant.title }}</span>
            <p v-if="grant.inherited_from" class="text-xs text-muted-foreground" data-testid="type-grant-inherited">
              {{ $t('types.grant_inherited_from') }}
              <Link :href="grant.inherited_from.href" class="hover:underline">
                {{ grant.inherited_from.title }}
              </Link>
            </p>
            <p v-if="grant.scope_type === 'own_tenant'" class="text-xs text-muted-foreground">
              {{ $t('responsibilities.scopes.own_tenant') }}
            </p>
            <p v-else-if="grant.scope_name" class="text-xs text-muted-foreground">
              {{ grant.scope_type ? `${$t(`responsibilities.scopes.${grant.scope_type}`)} · ` : '' }}{{ grant.scope_name }}
            </p>
            <p v-if="grant.needs_scope" class="text-xs text-status-attention" data-testid="type-grant-needs-scope">
              {{ $t('types.grant_needs_scope') }}
            </p>
          </div>
          <p v-if="!responsibilityGrants.length" class="text-sm text-muted-foreground">
            {{ $t('types.no_responsibilities') }}
          </p>
        </div>
      </OverviewSection>
    </template>
    <template #relationships>
      <div class="max-w-3xl">
        <RelationshipLinkList
          subject="type"
          :record-id="String(contentType.id)"
          :record-name="title"
          :links="typeLinks ?? []"
          :can-manage="!!can.manageLinks"
          :kinds="relationKinds ?? []"
          :type-options="typeOptions ?? []"
        />
      </div>
    </template>
    <template #files>
      <div class="max-w-4xl">
        <Deferred data="files">
          <template #fallback>
            <p class="py-6 text-sm text-muted-foreground">
              {{ $t('Įkeliama…') }}
            </p>
          </template>
          <FileableFilesPanel
            :fileable="{ id: contentType.id, type: fileableType }"
            :files
            :can-upload="can.update && !!sharepointPath"
            :folder-url="sharepointFolderUrl"
            :can-delete="can.update"
          />
        </Deferred>
      </div>
    </template>
  </RecordPage>
  <SheetForm v-model:open="modelsOpen" :title="$t('Susieti įrašai')" :processing="modelsProcessing" :disabled="!modelOptions" @submit="saveModels">
    <p v-if="!modelOptions" class="text-sm text-muted-foreground">
      {{ $t('Įkeliama…') }}
    </p>
    <template v-else>
      <Input v-model="modelSearch" :placeholder="$t('Ieškoti')" />
      <div class="space-y-1">
        <label v-for="model in filteredModelOptions.filter(option => option.can_change !== false)" :key="model.id" class="flex min-h-11 items-center gap-3 border-b border-border py-2 text-sm">
          <Checkbox :model-value="modelIds.includes(model.id)" @update:model-value="checked => toggleModel(model.id, Boolean(checked))" />
          {{ localized(model.name ?? model.title) }}
        </label>
        <p v-for="model in filteredModelOptions.filter(option => option.can_change === false)" :key="model.id" class="py-2 text-sm text-muted-foreground">
          {{ localized(model.name ?? model.title) }} · {{ $t('types.assignment_readonly') }}
        </p>
      </div>
    </template>
  </SheetForm>
  <ConfirmDialog v-model:open="deleteOpen" :title="$t('Šalinti tipą?')" :description="$t('Tipas bus perkeltas į šiukšlinę.')" :confirm-label="$t('Šalinti')" destructive @confirm="router.delete(route(`${resource}.destroy`, contentType.id))" />
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Deferred, Link, router } from '@inertiajs/vue3';
import { getActiveLanguage, trans as $t } from 'laravel-vue-i18n';
import { Compass, Edit, FileText, Link2, Shield, Trash2 } from '@lucide/vue';

import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import RecordPage, { type RecordAction, type RecordFact, type RecordPageSection } from '@/Components/Layouts/RecordPage.vue';
import { FileableFilesPanel, type FileableFileItem } from '@/Components/Files';
import { ConfirmDialog, OverviewSection, SheetForm } from '@/Components/Patterns';
import { RelationshipLinkList, type RelationKindOption, type RelationshipLinkRow, type RelationshipTypeOption } from '@/Components/Relationships';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { ModelEnum } from '@/Types/enums';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';

type Translation = string | { lt?: string; en?: string } | null | undefined;
const props = withDefaults(defineProps<{
  typeKind: 'institutionType' | 'dutyType';
  contentType: (App.Entities.InstitutionType | App.Entities.DutyType) & { parent?: { title?: Translation } };
  attachedModels: Array<{ id: string; name: string }>;
  modelOptions?: Array<{ id: string; name?: string; title?: Translation; can_change?: boolean }>;
  responsibilityGrants?: Array<{
    id: string;
    title: string;
    href: string | null;
    inherited_from?: { title: string; href: string } | null;
    scope_type?: string | null;
    scope_name: string | null;
    needs_scope: boolean;
  }>;
  responsibleDuties?: Array<{ id: string; duty_id: string; duty: string; label: string }>;
  sharepointPath?: string | null;
  sharepointFolderUrl?: string | null;
  /** Deferred (`files`). */
  files?: FileableFileItem[];
  /** Institution types only. */
  typeLinks?: RelationshipLinkRow[];
  relationKinds?: RelationKindOption[];
  typeOptions?: RelationshipTypeOption[];
  can: { update: boolean; delete: boolean; manageLinks?: boolean; manageAssignments?: boolean; manageResponsibilities?: boolean };
}>(), {
  responsibilityGrants: () => [],
  modelOptions: undefined,
  responsibleDuties: () => [],
  sharepointPath: null,
  sharepointFolderUrl: null,
  files: undefined,
  typeLinks: undefined,
  relationKinds: undefined,
  typeOptions: undefined,
});
const grantSpotlight = useFeatureSpotlight('type-responsibility-grants-v1');
const canManageAssignments = computed(() => props.typeKind === 'dutyType' ? props.can.manageAssignments === true : props.can.update);
const resource = props.typeKind === 'institutionType' ? 'institutionTypes' : 'dutyTypes';
const entityType = props.typeKind === 'institutionType' ? ModelEnum.INSTITUTION_TYPE : ModelEnum.DUTY_TYPE;
const fileableType = props.typeKind === 'institutionType' ? 'InstitutionType' : 'DutyType';
const section = ref('overview');
const deleteOpen = ref(false);
const modelsOpen = ref(false);
const modelsProcessing = ref(false);
const modelSearch = ref('');
const modelIds = ref(props.attachedModels.map(model => model.id));
const localized = (value: Translation): string => typeof value === 'string' ? value : value?.[getActiveLanguage() as 'lt' | 'en'] ?? value?.lt ?? value?.en ?? '';
const title = computed(() => localized(props.contentType.title));
const description = computed(() => localized(props.contentType.description));
const filteredModelOptions = computed(() => (props.modelOptions ?? []).filter(model =>
  localized(model.name ?? model.title).toLocaleLowerCase().includes(modelSearch.value.toLocaleLowerCase()),
));
function toggleModel(id: string, checked: boolean): void {
  modelIds.value = checked ? [...new Set([...modelIds.value, id])] : modelIds.value.filter(value => value !== id);
}
function openModels(): void {
  modelIds.value = props.attachedModels.map(model => model.id);
  modelsOpen.value = true;
  router.reload({ only: ['modelOptions'] });
}
function saveModels(): void {
  modelsProcessing.value = true;
  router.put(route(`${resource}.models.sync`, props.contentType.id), { models: modelIds.value }, {
    preserveScroll: true,
    except: ['files'],
    onSuccess: () => { modelsOpen.value = false; },
    onFinish: () => { modelsProcessing.value = false; },
  });
}
const facts = computed<RecordFact[]>(() => [
  { key: 'models', label: $t('Susieti įrašai'), value: String(props.attachedModels.length) },
  ...(props.typeKind === 'dutyType' ? [{ key: 'roles', label: $t('responsibilities.label'), value: String(props.responsibilityGrants.length) }] : []),
]);
const sections = computed<RecordPageSection[]>(() => [
  { value: 'overview', label: $t('Apžvalga') },
  { value: 'models', label: $t('Susieti įrašai'), count: props.attachedModels.length },
  ...(props.typeKind === 'dutyType' ? [{ value: 'roles', label: $t('responsibilities.label'), count: props.responsibilityGrants.length }] : []),
  ...(props.typeKind === 'institutionType' && (props.typeLinks?.length || props.can.manageLinks)
    ? [{ value: 'relationships', label: $t('Ryšiai'), count: props.typeLinks?.length ?? 0 }]
    : []),
  // Type files are reference documents for every duty or institution of the type, so anyone who can
  // see the type reads them; only uploading needs update and a folder.
  { value: 'files', label: $t('Failai') },
]);
const primaryAction = computed<RecordAction | undefined>(() => props.can.update ? { key: 'edit', label: $t('Redaguoti'), icon: Edit } : undefined);
const overflowActions = computed<RecordAction[]>(() => props.can.delete ? [{ key: 'delete', label: $t('Šalinti'), icon: Trash2, destructive: true }] : []);
function handleAction(action: string): void {
  if (action === 'edit') router.visit(route(`${resource}.edit`, props.contentType.id));
  if (action === 'delete') deleteOpen.value = true;
}
</script>
