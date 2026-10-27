<template>
  <span class="inline-flex w-fit shrink-0">
    <SpotlightPopover
      v-if="editable && spotlight"
      :is-dismissed="discovery.isDismissed.value"
      :title="$t('forms.activity_status.spotlight_title')"
      :description="$t('forms.activity_status.spotlight_description')"
      @dismiss="discovery.dismiss"
    >
      <CollectionStatusMenu :status :model-value="value" :options :editable :pending @update:model-value="choose" @open="discovery.dismiss" />
    </SpotlightPopover>
    <CollectionStatusMenu v-else :status :model-value="value" :options :editable :pending @update:model-value="choose" />
  </span>

  <ConfirmDialog
    :open="summary !== null"
    :title="$t('forms.activity_status.deactivate_title')"
    :description="consequence"
    :confirm-label="$t('forms.activity_status.deactivate')"
    :confirm-disabled="Boolean(summary?.blockers.length)"
    destructive
    @confirm="save(false)"
    @update:open="!$event && (summary = null)"
  >
    <template v-if="summary">
      <p class="text-sm text-muted-foreground">
        {{ $t('forms.helpers.institution_reactivation_hint') }}
      </p>
      <div v-if="summary.blockers.length" class="space-y-2 text-sm">
        <p role="alert">
          {{ $t('forms.helpers.institution_deactivation_blocked') }}
        </p>
        <ul class="list-disc pl-4">
          <li v-for="(blocker, index) in summary.blockers" :key="index">
            {{ blocker.duty }} · {{ formatDate(blocker.startDate) }}
          </li>
        </ul>
        <Button as-child variant="outline" voice="sentence">
          <Link :href="route('institutions.show', recordId)">
            {{ $t('forms.helpers.institution_resolve_terms') }}
          </Link>
        </Button>
      </div>
    </template>
  </ConfirmDialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import CollectionStatusMenu from './CollectionStatusMenu.vue';

import type { InstitutionDeactivationSummary } from '@/Components/AdminForms/InstitutionForm.vue';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { ConfirmDialog } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useApi, useApiMutation } from '@/Composables/useApi';
import { useDateFormatter } from '@/Composables/useDateFormatter';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import { operatingStatuses } from '@/Constants/statuses';

const props = defineProps<{
  entity: 'institutions' | 'duties';
  recordId: string | number;
  active: boolean | number;
  editable: boolean;
  spotlight?: boolean;
}>();
const emit = defineEmits<{ updated: [active: boolean] }>();
const current = ref(Boolean(props.active));
watch(() => props.active, (active) => {
  current.value = Boolean(active);
});
const pending = ref(false);
const summary = ref<InstitutionDeactivationSummary | null>(null);
const discovery = useFeatureSpotlight(`${props.entity}-activity-status-v1`);
const { formatDate } = useDateFormatter();
const value = computed(() => current.value ? 'active' : 'inactive');
const status = computed(() => operatingStatuses[value.value]);
const options = [
  { value: 'active', status: operatingStatuses.active },
  { value: 'inactive', status: operatingStatuses.inactive },
];
const consequence = computed(() => summary.value
  ? $t('forms.helpers.institution_deactivation_consequence', {
      count: summary.value.endingCount,
      date: formatDate(summary.value.effectiveEndDate),
    })
  : undefined);

async function choose(value: string): Promise<void> {
  if (pending.value || !props.editable) return;
  if (props.entity === 'institutions' && value === 'inactive') {
    pending.value = true;
    try {
      const api = useApi<{ deactivation: InstitutionDeactivationSummary }>(
        route('api.v1.admin.institutions.status', props.recordId), { immediate: false },
      );
      await api.execute();
      if (api.isSuccess.value && api.data.value) summary.value = api.data.value.deactivation;
    }
    finally {
      pending.value = false;
    }
    return;
  }
  await save(value === 'active');
}

async function save(active: boolean): Promise<void> {
  if (pending.value || !props.editable) return;
  const previous = current.value;
  current.value = active;
  summary.value = null;
  pending.value = true;
  try {
    const api = useApiMutation<{ is_active: boolean }>(
      route(`api.v1.admin.${props.entity}.status.update`, props.recordId),
      'PATCH', { is_active: active }, { showSuccessToast: true },
    );
    await api.execute();
    if (api.isSuccess.value && api.data.value) {
      current.value = api.data.value.is_active;
      emit('updated', current.value);
    }
    else current.value = previous;
  }
  catch {
    current.value = previous;
  }
  finally {
    pending.value = false;
  }
}
</script>
