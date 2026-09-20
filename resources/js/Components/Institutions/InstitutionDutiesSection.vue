<template>
  <section data-slot="institution-duties-section">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-border pb-3">
      <h3 class="text-base font-semibold text-foreground">
        {{ $t('Pareigybės') }}
        <span class="ml-1 text-sm font-normal text-muted-foreground tabular-nums">{{ duties.length }}</span>
      </h3>

      <div v-if="canManage" class="flex flex-wrap items-center gap-2">
        <template v-if="reordering">
          <Button type="button" variant="ghost" size="sm" class="pointer-coarse:h-11" :disabled="saving" @click="cancelReorder">
            {{ $t('Atšaukti') }}
          </Button>
          <Button type="button" variant="brand" size="sm" class="pointer-coarse:h-11" :disabled="saving || !changed" @click="saveOrder">
            <Save class="size-4" aria-hidden="true" />
            {{ $t('Išsaugoti tvarką') }}
          </Button>
        </template>
        <template v-else>
          <Button
            v-if="duties.length > 1"
            type="button"
            variant="outline"
            size="sm"
            class="pointer-coarse:h-11"
            @click="startReorder"
          >
            <ArrowDownUp class="size-4" aria-hidden="true" />
            {{ $t('Keisti tvarką') }}
          </Button>
          <Button as-child variant="outline" size="sm" class="pointer-coarse:h-11">
            <Link :href="route('duties.create', { institution_id: institutionId })">
              <Plus class="size-4" aria-hidden="true" />
              {{ $t('Nauja pareigybė') }}
            </Link>
          </Button>
        </template>
      </div>
    </div>

    <EmptyState
      v-if="duties.length === 0"
      :title="$t('Nėra pareigybių')"
      :description="$t('Šiai institucijai dar nėra priskirta pareigybių.')"
      :icon="Briefcase"
      :action-label="canManage ? $t('Sukurti pirmą pareigybę') : undefined"
      :action-href="canManage ? route('duties.create', { institution_id: institutionId }) : undefined"
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

    <div v-else class="divide-y divide-border border-b border-border">
      <DutyCard
        v-for="duty in duties"
        :key="duty.id"
        :duty="(duty as DutyWithUsers)"
        :can-manage
        class="px-2 sm:px-3"
        @assign="$emit('assign', $event)"
        @edit-term="(target, user) => $emit('edit-term', target, user)"
      />
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowDown, ArrowDownUp, ArrowUp, Briefcase, Plus, Save } from 'lucide-vue-next';

import DutyCard, { type DutyWithUsers, type UserWithPivot } from '@/Components/AdminForms/DutyCard.vue';
import { EmptyState } from '@/Components/Patterns';
import SortableDutiesTable from '@/Components/Tables/SortableDutiesTable.vue';
import { Button } from '@/Components/ui/button';

const props = defineProps<{
  duties: DutyWithUsers[];
  institutionId: string | number;
  /** `institutions.update` on this record: reordering and assigning both hang off it. */
  canManage?: boolean;
}>();

defineEmits<{
  'assign': [duty: DutyWithUsers];
  'edit-term': [duty: DutyWithUsers, user: UserWithPivot];
}>();

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
  [next[index], next[target]] = [next[target], next[index]];
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
