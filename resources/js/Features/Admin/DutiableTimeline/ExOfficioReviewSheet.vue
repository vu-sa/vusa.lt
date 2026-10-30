<template>
  <div>
    <SheetForm :open="open" :title="$t('dutiables.ex_officio_review.title')"
      :description="$t('dutiables.ex_officio_review.description')" :required-note="false"
      :save-label="$t('dutiables.ex_officio_review.apply')" :processing="processing"
      :disabled="preview.isFetching.value || !!preview.error.value || failed || !selected.length" :dirty="selected.length > 0"
      @update:open="emit('update:open', $event)" @submit="submit">
      <p v-if="preview.isFetching.value" class="animate-pulse text-sm text-muted-foreground" role="status">{{ $t('dutiables.ex_officio_review.loading') }}</p>
      <div v-else-if="preview.error.value || failed" role="alert" class="space-y-2 text-sm text-destructive">
        <p>{{ $t('dutiables.ex_officio_review.error') }}</p>
        <Button variant="outline" voice="sentence" type="button" @click="load">{{ $t('dutiables.ex_officio_review.refresh') }}</Button>
      </div>
      <template v-else>
        <p v-if="!groups.length" class="text-sm text-muted-foreground">{{ $t('dutiables.ex_officio_review.empty') }}</p>
        <section v-for="group in groups" :key="group[0].holder_id" class="space-y-3">
          <h3 class="text-sm font-semibold">{{ group[0].holder_name }}</h3>
          <div v-for="item in group" :key="item.id" class="space-y-2 border-t border-border pt-3 text-sm">
            <label class="flex min-h-11 items-center gap-3">
              <Checkbox :model-value="selected.includes(item.id)" @update:model-value="toggle(item.id, $event)" />
              <span>{{ item.duty_name }} · {{ $t(`dutiables.ex_officio_review.${item.kind}`) }}</span>
            </label>
            <p>{{ item.before ? period(item.before) : '—' }} ⇒ {{ item.kind === 'remove_duplicate' ? item.survivors.map(period).join('; ') : period(item.after) }}</p>
            <p v-if="item.requires?.length">{{ $t('dutiables.ex_officio_review.requires') }}</p>
            <p v-if="item.historical" class="text-muted-foreground">{{ $t('dutiables.ex_officio_review.historical') }}</p>
            <p v-if="item.access_change" class="text-status-attention">{{ $t('dutiables.ex_officio_review.access_change') }}</p>
            <p v-if="item.quota_change" class="text-status-attention">{{ $t('dutiables.ex_officio_review.quota_change', { before: item.tenant_before_name ?? '—', after: item.tenant_after_name ?? '—' }) }}</p>
            <p v-if="item.remainders.length">{{ $t('dutiables.ex_officio_review.split') }}: {{ item.remainders.map(period).join('; ') }}</p>
            <fieldset v-if="item.orphan" class="space-y-2" :data-testid="`orphan-${item.id}`">
              <legend>{{ $t('dutiables.ex_officio_review.orphan') }}</legend>
              <RadioGroup v-model="choices[item.id]">
              <label class="flex min-h-11 items-center gap-2"><RadioGroupItem value="end" />{{ $t('dutiables.ex_officio_review.end') }}</label>
              <label class="flex min-h-11 items-center gap-2"><RadioGroupItem value="keep" />{{ $t('dutiables.ex_officio_review.keep') }}</label>
              </RadioGroup>
            </fieldset>
          </div>
        </section>
        <p v-for="blocked in preview.data.value?.blocked ?? []" :key="`${blocked.reason}-${blocked.source_id}-${blocked.target_duty_id}`" class="text-sm text-muted-foreground">
          {{ blocked.holder_name }} · {{ $t(`dutiables.ex_officio_review.blocked_${blocked.reason}`) }}
        </p>
      </template>
    </SheetForm>
    <AccessChangeWarningDialog :open="warningOpen" :report="report" @update:open="warningOpen = $event" @confirm="confirm" @cancel="cancel" />
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { SheetForm } from '@/Components/Patterns';
import { Checkbox } from '@/Components/ui/checkbox';
import { RadioGroup, RadioGroupItem } from '@/Components/ui/radio-group';
import { Button } from '@/Components/ui/button';
import AccessChangeWarningDialog from '@/Components/AdminForms/AccessChangeWarningDialog.vue';
import { useApi } from '@/Composables/useApi';
import { useAccessChangeGuard } from '@/Composables/useAccessChangeGuard';
import { formatDate } from '@/Utils/dateTime';

interface Period { start_date: string; end_date: string | null }
interface ReviewItem {
  id: string;
  kind: 'link' | 'create' | 'resync' | 'remove_duplicate';
  holder_id: string;
  holder_name: string;
  duty_name: string;
  before: Period | null;
  after: Period;
  selected: boolean;
  orphan: boolean;
  historical: boolean;
  access_change: boolean;
  quota_change: boolean;
  tenant_before: number | null;
  tenant_after: number | null;
  tenant_before_name: string | null;
  tenant_after_name: string | null;
  survivors: Period[];
  remainders: Period[];
  requires: string[];
}
interface ReviewPlan {
  fingerprint: string;
  items: ReviewItem[];
  blocked: { source_id: string; target_duty_id: string; holder_name: string; reason: string }[];
}
const props = defineProps<{ open: boolean; dutyId: string }>();
const emit = defineEmits<{ 'update:open': [value: boolean] }>();
const preview = useApi<ReviewPlan>(computed(() => route('duties.exOfficioReview.preview', props.dutyId)), { immediate: false });
const selected = ref<string[]>([]);
const choices = ref<Record<string, 'end' | 'keep'>>({});
const processing = ref(false);
const failed = ref(false);
const guard = useAccessChangeGuard();
const { open: warningOpen, report, confirm, cancel } = guard;
const groups = computed(() => {
  const result = new Map<string, ReviewItem[]>();
  for (const item of preview.data.value?.items ?? []) {
    result.set(item.holder_id, [...(result.get(item.holder_id) ?? []), item]);
  }
  return [...result.values()];
});
async function load(): Promise<void> {
  failed.value = false;
  await preview.execute();
  selected.value = (preview.data.value?.items ?? []).filter(item => item.selected).map(item => item.id);
  choices.value = Object.fromEntries((preview.data.value?.items ?? []).filter(item => item.orphan).map(item => [item.id, 'end']));
}
watch(() => props.open, open => { if (open) void load(); }, { immediate: true });
function toggle(id: string, value: boolean | 'indeterminate'): void {
  if (value === true) {
    const pending = [id];
    const next = new Set(selected.value);
    while (pending.length) {
      const itemId = pending.pop()!;
      if (next.has(itemId)) continue;
      next.add(itemId);
      pending.push(...(preview.data.value?.items.find(item => item.id === itemId)?.requires ?? []));
    }
    selected.value = [...next];
  } else {
    selected.value = selected.value.filter(entry => entry !== id);
    let changed = true;
    while (changed) {
      const before = selected.value.length;
      selected.value = selected.value.filter(entry => (preview.data.value?.items.find(item => item.id === entry)?.requires ?? []).every(required => selected.value.includes(required)));
      changed = before !== selected.value.length;
    }
  }
}
function period(value: Period): string {
  return `${formatDate(value.start_date)} → ${value.end_date === null ? $t('dutiables.ex_officio_review.open_end') : formatDate(value.end_date)}`;
}
function submit(): void {
  if (!preview.data.value) return;
  guard.guardedSubmit(acknowledge => {
    processing.value = true;
    router.post(route('duties.exOfficioReview.apply', props.dutyId), {
      fingerprint: preview.data.value!.fingerprint,
      item_ids: selected.value,
      remainder_choices: Object.fromEntries(Object.entries(choices.value).filter(([id]) => selected.value.includes(id))),
      acknowledge_access_change: acknowledge,
    }, {
      preserveScroll: true,
      except: ['otherDuties', 'studyPrograms', 'lineage', 'quotaTenants', 'responsibilities', 'files', 'typeFiles'],
      onSuccess: page => {
        if (guard.open.value || (page.props.flash as { access_change_warning?: unknown } | undefined)?.access_change_warning) return;
        emit('update:open', false);
        router.reload();
      },
      onError: () => { failed.value = true; },
      onHttpException: () => { failed.value = true; return false; },
      onFinish: () => { processing.value = false; },
    });
  });
}
</script>
