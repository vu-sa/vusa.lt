<template>
  <p v-if="loading" class="text-sm text-muted-foreground" role="status">
    {{ $t('users.merge.loading_periods') }}
  </p>
  <div v-else-if="failed" class="space-y-2" role="alert">
    <p class="text-sm text-status-danger">
      {{ $t('users.merge.periods_failed') }}
    </p>
    <Button type="button" variant="outline" size="sm" @click="loadOptions">
      {{ $t('Bandyti dar kartą') }}
    </Button>
  </div>
  <fieldset v-else-if="options.length" class="min-w-0 space-y-2" data-slot="merge-duty-emails">
    <legend class="text-sm font-medium">
      {{ $t('users.merge.duty_emails') }}
    </legend>
    <p class="text-xs text-muted-foreground">
      {{ $t('users.merge.duty_emails_hint') }}
    </p>
    <label
      v-for="option in options"
      :key="option.id"
      :for="`merge-email-${option.id}`"
      class="flex min-h-11 cursor-pointer items-start gap-3 border border-border px-3 py-2 text-sm"
    >
      <Checkbox
        :id="`merge-email-${option.id}`"
        :model-value="selected.includes(option.id)"
        class="mt-0.5 shrink-0"
        @update:model-value="checked => select(option, checked === true)"
      />
      <span class="min-w-0 space-y-1">
        <span class="block font-medium">{{ option.duty }} · {{ option.institution }}</span>
        <span class="block text-xs text-muted-foreground">
          {{ formatDate(option.start_date) }} – {{ option.end_date ? formatDate(option.end_date) : $t('users.merge.open_ended') }}
        </span>
        <span class="block break-all">{{ option.email }}</span>
      </span>
    </label>
  </fieldset>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { useApiMutation } from '@/Composables/useApi';
import { formatDate } from '@/Utils/dateTime';

interface DutyEmailOption {
  id: string;
  source_user_id: string;
  duty: string;
  institution: string | null;
  email: string;
  start_date: string;
  end_date: string | null;
  overlap_group: string;
}

const props = defineProps<{
  records: { id: string | number }[];
  targetId: string | number;
  loginEmail: string | null;
}>();
const selected = defineModel<string[]>({ default: () => [] });
const emit = defineEmits<{ loading: [value: boolean] }>();
const options = ref<DutyEmailOption[]>([]);
const loading = ref(false);
const failed = ref(false);
const body = computed(() => ({
  kept_user_id: props.targetId,
  source_user_ids: props.records.filter(record => String(record.id) !== String(props.targetId)).map(record => record.id),
  login_email: props.loginEmail,
}));
const preview = useApiMutation<DutyEmailOption[]>(route('api.v1.admin.users.mergePreview'), 'POST', body, {
  showErrorToast: false,
  showSuccessToast: false,
});
let revision = 0;

async function loadOptions(): Promise<void> {
  const current = ++revision;
  preview.abort();
  selected.value = [];
  options.value = [];
  failed.value = false;
  if (!props.loginEmail) {
    loading.value = false;
    emit('loading', false);
    return;
  }

  loading.value = true;
  emit('loading', true);
  await preview.execute();
  if (current !== revision) return;

  failed.value = !preview.isSuccess.value;
  options.value = failed.value ? [] : preview.data.value ?? [];
  loading.value = false;
  emit('loading', false);
}

function select(option: DutyEmailOption, checked: boolean): void {
  const alternatives = options.value.filter(item => item.overlap_group === option.overlap_group).map(item => item.id);
  const remaining = selected.value.filter(id => !alternatives.includes(id));
  selected.value = checked ? [...remaining, option.id] : remaining;
}

watch(body, loadOptions, { immediate: true });
onBeforeUnmount(() => {
  revision++;
  preview.abort();
  emit('loading', false);
});
</script>
