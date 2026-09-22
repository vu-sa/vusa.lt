<template>
  <RecordPage
    v-model:section="section"
    :title="title"
    :entity-type="ModelEnum.TYPE"
    :facts="facts"
    :sections="sections"
    :primary-action="primaryAction"
    :overflow-actions="overflowActions"
    @action="handleAction"
  >
    <template #overview>
      <div class="max-w-3xl space-y-6">
        <div>
          <h2 class="mb-2 text-base font-semibold">{{ $t('Aprašymas') }}</h2>
          <p class="whitespace-pre-wrap text-sm text-foreground">{{ description || $t('Aprašymo nėra.') }}</p>
        </div>
        <dl class="grid border border-border sm:grid-cols-2">
          <div class="border-b border-border p-4 sm:border-b-0 sm:border-r">
            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ $t('Techninė žymė') }}</dt>
            <dd class="mt-1 text-sm font-medium">{{ contentType.slug || '—' }}</dd>
          </div>
          <div class="p-4">
            <dt class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ $t('Tėvinis tipas') }}</dt>
            <dd class="mt-1 text-sm font-medium">{{ localized(contentType.parent?.title) || '—' }}</dd>
          </div>
        </dl>
      </div>
    </template>
    <template #models>
      <div class="max-w-3xl divide-y divide-border border-y border-border">
        <div v-for="model in attachedModels" :key="model.id" class="py-3 text-sm font-medium">{{ model.name }}</div>
        <p v-if="!attachedModels.length" class="py-6 text-sm text-muted-foreground">{{ $t('Susietų įrašų nėra.') }}</p>
      </div>
    </template>
    <template #roles>
      <div class="max-w-3xl divide-y divide-border border-y border-border">
        <div v-for="role in contentType.roles ?? []" :key="role.id" class="py-3 text-sm font-medium">{{ role.name }}</div>
        <p v-if="!contentType.roles?.length" class="py-6 text-sm text-muted-foreground">{{ $t('Rolių nepriskirta.') }}</p>
      </div>
    </template>
  </RecordPage>
  <ConfirmDialog v-model:open="deleteOpen" :title="$t('Šalinti tipą?')" :description="$t('Tipas bus perkeltas į šiukšlinę.')" :confirm-label="$t('Šalinti')" destructive @confirm="router.delete(route('types.destroy', contentType.id))" />
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { getActiveLanguage, trans as $t } from 'laravel-vue-i18n';
import { Edit, Trash2 } from 'lucide-vue-next';

import RecordPage, { type RecordAction, type RecordFact, type RecordPageSection } from '@/Components/Layouts/RecordPage.vue';
import { ConfirmDialog } from '@/Components/Patterns';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { ModelEnum } from '@/Types/enums';

type Translation = string | { lt?: string; en?: string } | null | undefined;
const props = defineProps<{
  contentType: App.Entities.Type & { parent?: { title?: Translation }; roles?: Array<{ id: string; name: string }> };
  attachedModels: Array<{ id: string; name: string }>;
  can: { update: boolean; delete: boolean };
}>();
const section = ref('overview');
const deleteOpen = ref(false);
const localized = (value: Translation): string => typeof value === 'string' ? value : value?.[getActiveLanguage() as 'lt' | 'en'] ?? value?.lt ?? value?.en ?? '';
const title = computed(() => localized(props.contentType.title));
const description = computed(() => localized(props.contentType.description));
const facts = computed<RecordFact[]>(() => [
  { key: 'model_type', label: $t('Modelis'), value: props.contentType.model_type ?? '—' },
  { key: 'models', label: $t('Susieti įrašai'), value: String(props.attachedModels.length) },
  { key: 'roles', label: $t('Rolės'), value: String(props.contentType.roles?.length ?? 0) },
]);
const sections = computed<RecordPageSection[]>(() => [
  { value: 'overview', label: $t('Apžvalga') },
  { value: 'models', label: $t('Susieti įrašai'), count: props.attachedModels.length },
  { value: 'roles', label: $t('Rolės'), count: props.contentType.roles?.length },
]);
const primaryAction = computed<RecordAction | undefined>(() => props.can.update ? { key: 'edit', label: $t('Redaguoti'), icon: Edit } : undefined);
const overflowActions = computed<RecordAction[]>(() => props.can.delete ? [{ key: 'delete', label: $t('Šalinti'), icon: Trash2, destructive: true }] : []);
function handleAction(action: string): void {
  if (action === 'edit') router.visit(route('types.edit', props.contentType.id));
  if (action === 'delete') deleteOpen.value = true;
}
usePageBreadcrumbs(BreadcrumbHelpers.adminShow($t('Tipai'), route('types.index'), title.value));
</script>
