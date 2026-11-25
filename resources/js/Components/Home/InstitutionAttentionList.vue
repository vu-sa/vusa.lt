<template>
  <CollectionRowList
    :items="institutions"
    :frame
    :item-key="(institution: InstitutionActivityInsight) => String(institution.id)"
    :row-muted="(institution: InstitutionActivityInsight) => institution.is_active === false"
    data-slot="institutions-needing-attention"
  >
    <template #default="{ item: institution }">
      <!-- Not CollectionRow: it folds a narrow row's action into ⋯, and this row's one job is that action. -->
      <div :class="['flex items-center gap-3 sm:gap-4', ROW_PADDING_CLASS]" data-slot="institution-attention-row">
        <CollectionPrimaryCell :title="institution.name" :href="route('institutions.show', institution.id)" class="flex-1">
          <template #sub>
            <span class="flex flex-wrap items-center gap-x-1.5 gap-y-0.5">
              <template v-for="(part, index) in subParts(institution)" :key="part">
                <span v-if="index > 0" aria-hidden="true">·</span>
                <span
                  v-if="part === 'secretary'"
                  class="inline-flex items-center gap-1 text-foreground"
                  :title="$t('secretaries.dashboard.secretary_hint')"
                  data-slot="institution-secretary-badge"
                >
                  <NotebookPen class="size-3.5" aria-hidden="true" />
                  {{ $t('secretaries.dashboard.badge') }}
                </span>
                <span v-else-if="part === 'tenant'">{{ institution.tenant_name }}</span>
                <StatusText v-else-if="part === 'status'" :status="statusOf(institution)" />
                <span v-else>{{ $t('Paskutinė veikla prieš :days d.', { days: String(institution.effective_days_since_activity) }) }}</span>
              </template>
            </span>
          </template>
        </CollectionPrimaryCell>
        <CollectionRowActions :actions="[recordAction]" stacked @select="emit('record', institution)" />
      </div>
    </template>
  </CollectionRowList>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { NotebookPen, NotebookText } from '@lucide/vue';
import { computed } from 'vue';

import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import CollectionRowList from '@/Components/Collection/CollectionRowList.vue';
import { ROW_PADDING_CLASS, type RowListFrame } from '@/Components/Collection/rowList';
import { StatusText } from '@/Components/Patterns';
import { inactiveInstitutionStatus, institutionActivityStatuses } from '@/Constants/statusIcons';
import type { AdminRecordAction } from '@/Types/AdminRecordAction';
import type { InstitutionActivityStatus } from '@/Types/enums';

import type { InstitutionActivityInsight } from './types';

withDefaults(defineProps<{
  institutions: InstitutionActivityInsight[];
  frame?: RowListFrame;
}>(), {
  frame: 'box',
});

const emit = defineEmits<{
  record: [institution: InstitutionActivityInsight];
}>();

const recordAction = computed<AdminRecordAction>(() => ({
  key: 'record',
  label: $t('Fiksuoti veiklą'),
  icon: NotebookText,
  primary: true,
}));

type SubPart = 'secretary' | 'tenant' | 'status' | 'days';

// Status sits in the sub-line, so the row's right side holds only its one action.
function subParts(institution: InstitutionActivityInsight): SubPart[] {
  return [
    (institution.is_secretary ?? institution.is_administered) ? 'secretary' : null,
    institution.tenant_name ? 'tenant' : null,
    'status',
    institution.effective_days_since_activity !== null ? 'days' : null,
  ].filter((part): part is SubPart => part !== null);
}

function statusOf(institution: InstitutionActivityInsight) {
  return institution.is_active === false
    ? inactiveInstitutionStatus
    : institutionActivityStatuses[institution.status as InstitutionActivityStatus];
}
</script>
