<template>
  <OverviewSection
    variant="home"
    :title="$t('Pareigybės')"
    :icon="Briefcase"
    :count="duties.length"
    data-slot="institution-duties-section"
  >
    <template v-if="canManage || canCreate || preview" #actions>
      <SectionHeaderAction v-if="preview" :label="$t('Visos pareigybės')" trailing-chevron @click="$emit('view-all')" />
      <template v-if="reordering">
        <SectionHeaderAction tone="muted" :label="$t('Atšaukti')" :disabled="saving" @click="cancelReorder" />
        <SectionHeaderAction :icon="Save" :label="$t('Išsaugoti tvarką')" :disabled="saving || !changed" @click="saveOrder" />
      </template>
      <template v-else-if="!preview">
        <SectionHeaderAction v-if="canManage && duties.length > 1" tone="muted" :icon="ArrowDownUp" :label="$t('Keisti tvarką')" @click="startReorder" />
        <SectionHeaderAction v-if="canCreate" :icon="Plus" :label="$t('Nauja pareigybė')" :href="route('duties.create', { institution_id: institutionId })" />
      </template>
    </template>

    <EmptyState
      v-if="duties.length === 0"
      :title="$t('Nėra pareigybių')"
      :description="$t('Šiai institucijai dar nėra priskirta pareigybių.')"
      :icon="Briefcase"
      :action-label="canCreate ? $t('Sukurti pirmą pareigybę') : undefined"
      :action-href="canCreate ? route('duties.create', { institution_id: institutionId }) : undefined"
    />

    <SortableDutiesTable v-else-if="reordering" v-model="draft">
      <template #default="{ model, index }">
        <div class="flex items-start gap-2">
          <div class="min-w-0 flex-1">
            <DutyCard :duty="(model as DutyWithUsers)" compact />
          </div>
          <div class="flex shrink-0 items-center py-2">
            <Button
              type="button"
              variant="ghost"
              size="sm"
              class="size-8 p-0 pointer-coarse:size-11"
              :disabled="index === 0"
              :aria-label="$t('Perkelti aukščiau')"
              @click="move(index, -1)"
            >
              <ArrowUp class="size-4" aria-hidden="true" />
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              class="size-8 p-0 pointer-coarse:size-11"
              :disabled="index === draft.length - 1"
              :aria-label="$t('Perkelti žemiau')"
              @click="move(index, 1)"
            >
              <ArrowDown class="size-4" aria-hidden="true" />
            </Button>
          </div>
        </div>
      </template>
    </SortableDutiesTable>

    <CollectionRowList v-else frame="attached" :items="duties" :item-key="duty => String(duty.id)" :row-muted="duty => duty.is_active === false">
      <template #default="{ item: duty }">
        <CollectionRow :actions="rowActions(duty)" :class="ROW_PADDING_CLASS" data-slot="institution-duty-row" @select="key => selectRowAction(key, duty)">
          <div class="flex items-center gap-3">
            <CollectionPrimaryCell :title="dutyName(duty)" :href="route('duties.show', duty.id)" class="flex-1">
              <InflectedDutyName :name="dutyName(duty)" />
              <template #sub>
                <template v-for="(part, index) in rowFacts(duty)" :key="part.text">
                  <span v-if="index > 0" aria-hidden="true"> · </span>
                  <span :class="part.attention && 'text-status-attention'">{{ part.text }}</span>
                </template>
              </template>
            </CollectionPrimaryCell>
            <!-- Term editing used to sit beside each person; the first row says where it went. -->
            <SpotlightPopover
              v-if="canManage && duty === duties[0]"
              :title="$t('dutiables.holders_spotlight.title')"
              :description="$t('dutiables.holders_spotlight.description')"
              :is-dismissed="holdersSpotlight.isDismissed.value"
              align="end"
              @dismiss="holdersSpotlight.dismiss()"
            >
              <DutyHoldersPopover
                :holders="duty.current_users ?? []"
                can-manage
                @opened="holdersSpotlight.dismiss()"
                @assign="$emit('assign', duty)"
                @edit-term="user => $emit('edit-term', duty, user)"
              />
            </SpotlightPopover>
            <DutyHoldersPopover
              v-else
              :holders="duty.current_users ?? []"
              :can-manage
              @assign="$emit('assign', duty)"
              @edit-term="user => $emit('edit-term', duty, user)"
            />
          </div>
        </CollectionRow>
      </template>
    </CollectionRowList>
  </OverviewSection>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowDown, ArrowDownUp, ArrowUp, Briefcase, Pencil, Plus, Save, UserPlus } from '@lucide/vue';

import DutyCard from '@/Components/AdminForms/DutyCard.vue';
import CollectionPrimaryCell from '@/Components/Collection/CollectionPrimaryCell.vue';
import CollectionRow from '@/Components/Collection/CollectionRow.vue';
import CollectionRowList from '@/Components/Collection/CollectionRowList.vue';
import { ROW_PADDING_CLASS } from '@/Components/Collection/rowList';
import DutyHoldersPopover from '@/Components/Duties/DutyHoldersPopover.vue';
import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import type { AdminRecordAction } from '@/Types/AdminRecordAction';
import type { DutyWithUsers, UserWithPivot } from '@/Types/DutyAssignment';
import { EmptyState, OverviewSection, SectionHeaderAction } from '@/Components/Patterns';
import SortableDutiesTable from '@/Components/Tables/SortableDutiesTable.vue';
import { Button } from '@/Components/ui/button';

const props = defineProps<{
  duties: DutyWithUsers[];
  institutionId: string | number;
  /** `institutions.update` on this record: reordering and assigning both hang off it. */
  canManage?: boolean;
  canCreate?: boolean;
  preview?: boolean;
}>();

const emit = defineEmits<{
  'view-all': [];
  'assign': [duty: DutyWithUsers];
  'edit-term': [duty: DutyWithUsers, user: UserWithPivot];
}>();

const holdersSpotlight = useFeatureSpotlight('duty-holders-popover-v1');

const dutyName = (duty: DutyWithUsers) => getTranslatedValue(duty.name, undefined, '—');

/** The institution is the page itself, so a row says only how full the duty is and where to write. */
const rowFacts = (duty: DutyWithUsers) => {
  const holders = duty.current_users?.length ?? 0;
  const occupancy = holders === 0
    ? { text: $t('Neužimta'), attention: true }
    : duty.places_to_occupy
      ? { text: $t('Užimta :count iš :places', { count: String(holders), places: String(duty.places_to_occupy) }), attention: false }
      : null;

  return [occupancy, duty.email ? { text: duty.email, attention: false } : null]
    .filter((part): part is { text: string; attention: boolean } => part !== null);
};

// The same set as a duty's row in the collection, with assigning as the step a manager takes here.
const rowActions = (duty: DutyWithUsers): AdminRecordAction[] => props.canManage
  ? [
      { key: 'assign', label: $t('Pridėti asmenį'), icon: UserPlus, primary: true },
      { key: 'edit', label: $t('Redaguoti'), icon: Pencil, role: 'edit', menuOnly: true, href: route('duties.edit', duty.id) },
    ]
  : [];

const selectRowAction = (key: string, duty: DutyWithUsers) => {
  if (key === 'assign') {
    emit('assign', duty);
  }
};

const reordering = ref(false);
const saving = ref(false);
const draft = ref<DutyWithUsers[]>([]);

const changed = computed(() =>
  draft.value.some((duty, index) => String(duty.id) !== String(props.duties[index]?.id)));

const startReorder = () => {
  draft.value = props.duties.map(duty => ({ ...duty }));
  reordering.value = true;
};

const cancelReorder = () => {
  reordering.value = false;
  draft.value = [];
};

const move = (index: number, step: -1 | 1) => {
  const target = index + step;

  if (target < 0 || target >= draft.value.length) {
    return;
  }

  const next = [...draft.value];
  const currentDuty = next[index];
  const targetDuty = next[target];

  if (!currentDuty || !targetDuty) return;

  next[index] = targetDuty;
  next[target] = currentDuty;
  draft.value = next;
};

const saveOrder = () => {
  saving.value = true;

  router.post(
    route('institutions.reorderDuties'),
    { duties: draft.value.map((duty, order) => ({ id: duty.id, order })) },
    {
      preserveScroll: true,
      onSuccess: () => cancelReorder(),
      onFinish: () => {
        saving.value = false;
      },
    },
  );
};
</script>
