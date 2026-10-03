<template>
  <div data-slot="cadence-section" class="space-y-3">
    <!-- Terms are set up once and rarely touched, so the default is one line saying which apply. -->
    <div class="flex flex-wrap items-center justify-between gap-2">
      <p class="text-sm" data-slot="cadence-summary">
        {{ hasOwn ? $t('cadences.institution.summary_own') : $t('cadences.institution.summary_global') }}
        <span class="text-muted-foreground">
          · {{ current ? $t('cadences.institution.now', { label: current.label }) : $t('cadences.institution.none_now') }}
        </span>
      </p>

      <Button v-if="!editing" type="button" size="xs" variant="ghost" voice="sentence" @click="startEditing">
        <Pencil v-if="hasOwn" class="size-3.5" />
        <Plus v-else class="size-3.5" />
        {{ hasOwn ? $t('cadences.institution.manage') : $t('cadences.institution.customize') }}
      </Button>
      <Button v-else type="button" size="xs" variant="ghost" voice="sentence" @click="stopEditing">
        {{ $t('cadences.institution.done') }}
      </Button>
    </div>

    <div v-if="editing" class="space-y-3">
      <p v-if="!hasOwn" class="text-xs text-muted-foreground">
        {{ $t('cadences.institution.override_warning') }}
      </p>

      <CadenceList
        :cadences="ownCadences"
        :institution-id
        :empty-message="$t('cadences.overrides.empty')"
        :editing-id="crud.editingId.value"
        :adding="crud.adding.value"
        :processing="crud.processing.value"
        :prefill
        @edit="crud.editingId.value = $event"
        @cancel-edit="crud.editingId.value = null"
        @cancel-add="onCancelAdd"
        @create="value => crud.create(value)"
        @update="crud.update"
        @delete="crud.destroy"
      />

      <Button
        v-if="hasOwn && !crud.adding.value"
        type="button"
        size="xs"
        variant="outline"
        voice="sentence"
        :disabled="crud.processing.value"
        @click="startAdding"
      >
        <Plus class="size-3.5" />
        {{ $t('cadences.actions.add') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Pencil, Plus } from 'lucide-vue-next';

import CadenceList from './CadenceList.vue';
import { prefillFrom, useCadenceCrud } from './useCadenceCrud';

import type { CadenceDraft, CadenceRow } from './index';

import { Button } from '@/Components/ui/button';

const props = defineProps<{
  institutionId: string;
  /** This institution's own overrides. */
  ownCadences: CadenceRow[];
  /** The shared ladder, which applies while there are no overrides. */
  globalCadences: CadenceRow[];
  defaults: { default_start_month_day: string; default_end_month_day: string };
}>();

const crud = useCadenceCrud(props.institutionId);
const prefill = ref<CadenceDraft | null>(null);
const editing = ref(false);

const hasOwn = computed(() => props.ownCadences.length > 0);

/** Own terms replace the shared ladder outright — see ResolveCadenceForInstitution. */
const applicable = computed(() => (hasOwn.value ? props.ownCadences : props.globalCadences));

const current = computed(() => {
  const today = localToday();

  return applicable.value.find(cadence => cadence.start_date <= today && cadence.end_date >= today) ?? null;
});

function startAdding(): void {
  // A first override extrapolates from the shared ladder, so the dates start plausible.
  prefill.value = prefillFrom(applicable.value, props.defaults);
  crud.adding.value = true;
}

function startEditing(): void {
  editing.value = true;

  // Without own terms there is nothing to list — go straight to the first one.
  if (!hasOwn.value) {
    startAdding();
  }
}

function stopEditing(): void {
  crud.reset();
  editing.value = false;
}

function onCancelAdd(): void {
  crud.adding.value = false;

  if (!hasOwn.value) {
    editing.value = false;
  }
}

function localToday(): string {
  const now = new Date();

  return [now.getFullYear(), now.getMonth() + 1, now.getDate()].map(part => String(part).padStart(2, '0')).join('-');
}
</script>
