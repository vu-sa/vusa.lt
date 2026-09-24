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
      <div class="max-w-4xl divide-y divide-border border-y border-border">
        <div v-for="connection in relationship.relationshipables" :key="connection.id" class="grid gap-2 py-4 sm:grid-cols-[1fr_auto_1fr] sm:items-center">
          <p class="text-sm font-medium">
            {{ label(connection.source) }}
          </p>
          <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
            {{ connection.bidirectional ? '↔' : '→' }} {{ connection.scope ?? $t('Bendras') }}
          </p>
          <p class="text-sm font-medium sm:text-right">
            {{ label(connection.target) }}
          </p>
        </div>
        <p v-if="!relationship.relationshipables.length" class="py-6 text-sm text-muted-foreground">
          {{ $t('Ryšių tarp įrašų nėra.') }}
        </p>
      </div>
    </template>
  </RecordPage>
  <ConfirmDialog v-model:open="deleteOpen" :title="$t('Šalinti ryšį?')" :description="$t('Bus pašalinti ir visi su šiuo ryšiu susieti įrašai.')" :confirm-label="$t('Šalinti')" destructive @confirm="router.delete(route('relationships.destroy', relationship.id))" />
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Edit, Trash2 } from 'lucide-vue-next';

import RecordPage, { type RecordAction, type RecordFact, type RecordPageSection } from '@/Components/Layouts/RecordPage.vue';
import { ConfirmDialog } from '@/Components/Patterns';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { ModelEnum } from '@/Types/enums';

type Related = { name?: string; title?: string | { lt?: string; en?: string } } | null;
const props = defineProps<{ relationship: { id: string; name: string; slug: string; description?: string | null; relationshipables: Array<{ id: string; source: Related; target: Related; scope?: string | null; bidirectional: boolean }> }; can: { update: boolean; delete: boolean } }>();
const section = ref('overview');
const deleteOpen = ref(false);
const label = (model: Related): string => model?.name ?? (typeof model?.title === 'string' ? model.title : model?.title?.lt ?? model?.title?.en ?? '—');
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
