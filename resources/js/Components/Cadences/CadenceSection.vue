<template>
  <div data-slot="cadence-section" class="space-y-5">
    <!--
      The inherited ladder comes first and stays read-only: an editor should see what
      already applies before deciding to replace it.
    -->
    <div v-if="globalCadences.length > 0" class="space-y-2">
      <div class="flex items-baseline justify-between gap-2">
        <h4 class="text-sm font-medium">
          {{ $t('cadences.institution.inherited') }}
        </h4>
        <Badge v-if="ownCadences.length > 0" variant="outline" class="text-[10px]">
          {{ $t('cadences.institution.override_active') }}
        </Badge>
      </div>
      <p class="text-xs text-muted-foreground">
        {{ $t('cadences.institution.inherited_hint') }}
      </p>
      <div :class="ownCadences.length > 0 ? 'opacity-50' : undefined">
        <CadenceList
          readonly
          :cadences="globalCadences"
          :empty-message="$t('cadences.global.empty')"
        />
      </div>
    </div>

    <div class="space-y-2">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <h4 class="text-sm font-medium">
          {{ $t('cadences.institution.own') }}
        </h4>
        <Button type="button" size="xs" variant="outline" :disabled="crud.processing.value" @click="startAdding">
          <Plus class="size-3.5" />
          {{ $t('cadences.actions.add') }}
        </Button>
      </div>

      <Alert class="py-2">
        <AlertDescription class="text-xs">
          {{ $t('cadences.institution.override_warning') }}
        </AlertDescription>
      </Alert>

      <CadenceList
        :cadences="ownCadences"
        :empty-message="$t('cadences.overrides.empty')"
        :editing-id="crud.editingId.value"
        :adding="crud.adding.value"
        :processing="crud.processing.value"
        :prefill="prefill"
        @edit="crud.editingId.value = $event"
        @cancel-edit="crud.editingId.value = null"
        @cancel-add="crud.adding.value = false"
        @create="value => crud.create(value)"
        @update="crud.update"
        @delete="crud.destroy"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Plus } from 'lucide-vue-next';

import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';

import CadenceList from './CadenceList.vue';
import { prefillFrom, useCadenceCrud } from './useCadenceCrud';
import type { CadenceDraft, CadenceRow } from './index';

const props = defineProps<{
  institutionId: string;
  /** This institution's own overrides. */
  ownCadences: CadenceRow[];
  /** The shared ladder, shown for reference. */
  globalCadences: CadenceRow[];
  defaults: { default_start_month_day: string; default_end_month_day: string };
}>();

const crud = useCadenceCrud(props.institutionId);
const prefill = ref<CadenceDraft | null>(null);

/** A first override extrapolates from the shared ladder, so the dates start plausible. */
const source = computed(() => (props.ownCadences.length > 0 ? props.ownCadences : props.globalCadences));

function startAdding(): void {
  prefill.value = prefillFrom(source.value, props.defaults);
  crud.adding.value = true;
}
</script>
