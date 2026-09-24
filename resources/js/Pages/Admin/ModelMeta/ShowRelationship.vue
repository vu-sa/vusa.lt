<template>
  <RecordPage v-model:section="section" :title="relationship.name" :entity-type="ModelEnum.RELATIONSHIP" :facts :sections :primary-action :overflow-actions @action="handleAction">
    <template #overview>
      <div class="max-w-3xl space-y-5">
        <div>
          <h2 class="mb-2 text-base font-semibold">
            {{ $t('Aprašymas') }}
          </h2>
          <p class="whitespace-pre-wrap text-sm">
            {{ relationship.description || $t('Aprašymo nėra.') }}
          </p>
        </div>
        <div class="border border-border p-4">
          <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ $t('Techninė žymė') }}</span><p class="mt-1 text-sm font-medium">
            {{ relationship.slug }}
          </p>
        </div>
      </div>
    </template>
    <template #connections>
      <RelationshipConnections :relationship :related-models :can-update="can.update" />
    </template>
  </RecordPage>
  <ConfirmDialog v-model:open="deleteOpen" :title="$t('Šalinti ryšį?')" :description="$t('Bus pašalinti ir visi su šiuo ryšiu susieti įrašai.')" :confirm-label="$t('Šalinti')" destructive @confirm="router.delete(route('relationships.destroy', relationship.id))" />
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Edit, Trash2 } from 'lucide-vue-next';

import RelationshipConnections from './RelationshipConnections.vue';

import RecordPage, { type RecordAction, type RecordFact, type RecordPageSection } from '@/Components/Layouts/RecordPage.vue';
import { ConfirmDialog } from '@/Components/Patterns';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { ModelEnum } from '@/Types/enums';

const props = defineProps<{
  relationship: App.Entities.Relationship & { relationshipables: Array<Record<string, unknown>> };
  relatedModels?: Record<string, unknown>[];
  can: { update: boolean; delete: boolean };
}>();
const section = ref('overview');
const deleteOpen = ref(false);
const facts = computed<RecordFact[]>(() => [{ key: 'connections', label: $t('Ryšiai'), value: String(props.relationship.relationshipables.length) }]);
const sections = computed<RecordPageSection[]>(() => [{ value: 'overview', label: $t('Apžvalga') }, { value: 'connections', label: $t('Susieti įrašai'), count: props.relationship.relationshipables.length }]);
const primaryAction = computed<RecordAction | undefined>(() => props.can.update ? { key: 'edit', label: $t('Redaguoti'), icon: Edit } : undefined);
const overflowActions = computed<RecordAction[]>(() => props.can.delete ? [{ key: 'delete', label: $t('Šalinti'), icon: Trash2, destructive: true }] : []);
function handleAction(action: string): void {
  if (action === 'edit') {
    router.visit(route('relationships.edit', props.relationship.id));
  }

  if (action === 'delete') {
    deleteOpen.value = true;
  }
}
usePageBreadcrumbs(BreadcrumbHelpers.adminShow($t('Ryšiai'), route('relationships.index'), props.relationship.name));
</script>
