<template>
  <ActionWindowScreen :title="draft.activityRequest.campaignType ? $t(`activity_requests.campaigns.${draft.activityRequest.campaignType}`) : $t('action_window.activity_request.review.title')" :subtitle="$t('action_window.activity_request.review.subtitle')">
    <div class="flex flex-wrap gap-2">
      <Button variant="outline" voice="sentence" size="sm" @click="editFromHere('activity.campaign')">
        {{ $t('activity_requests.change_campaign') }}
      </Button>
      <Button v-if="byPeople" variant="outline" voice="sentence" size="sm" @click="editFromHere('activity.people')">
        {{ $t('action_window.activity_request.review.change_people') }}
      </Button>
      <Button v-else variant="outline" voice="sentence" size="sm" @click="editFromHere('activity.institutions')">
        {{ $t('action_window.activity_request.review.change_institutions') }}
      </Button>
    </div>

    <div v-if="isFetching" class="mt-6 space-y-2">
      <Skeleton v-for="n in 3" :key="n" class="h-14 w-full" />
    </div>
    <p v-if="error" role="alert" class="mt-6 text-sm text-destructive">
      {{ $t('action_window.common.error') }}
    </p>

    <template v-if="preview">
      <section class="mt-6">
        <h2 class="flex items-center gap-2 text-sm font-bold uppercase tracking-[0.18em]">
          <Send class="size-4 shrink-0 text-brand" aria-hidden="true" />{{ $t('activity_requests.send_section') }}
        </h2>
        <p class="mt-1 text-xs text-muted-foreground">
          {{ $t('activity_requests.recipient_count', { count: String(recipientCount) }) }}
        </p>
        <div class="mt-3 divide-y divide-border border-y border-border" data-slot="activity-request-preview">
          <div v-for="group in sendGroups" :key="group.key" class="py-4">
            <p class="text-sm font-bold">
              {{ group.title }}
            </p>
            <ul class="mt-1">
              <li v-for="row in group.rows" :key="row.key">
                <label :for="`activity-row-${row.key}`" class="flex min-h-11 cursor-pointer items-start gap-3 py-2" data-slot="activity-request-row">
                  <Checkbox :id="`activity-row-${row.key}`" class="mt-0.5" :model-value="isChecked(row.key)" @update:model-value="setChecked(row.key, $event === true)" />
                  <span class="min-w-0">
                    <span class="block text-sm">{{ byPeople ? row.institution.name : row.recipient.name }}</span>
                    <span class="mt-0.5 block text-xs text-muted-foreground">
                      {{ dates.fullDay(row.recipient.period_start) }} – {{ dates.fullDay(row.recipient.period_end) }} · {{ $t(`activity_requests.delivery.${row.recipient.delivery_mode}`) }}
                    </span>
                    <span v-for="meeting in row.recipient.incomplete_meetings" :key="meeting.id" class="mt-0.5 block text-xs text-muted-foreground">
                      {{ dates.fullDay(meeting.date) }} · {{ $t(`activity_requests.meeting_status.${meeting.status}`) }}
                    </span>
                  </span>
                </label>
              </li>
            </ul>
          </div>
        </div>
      </section>

      <section v-if="skipGroups.length" class="mt-8">
        <h2 class="flex items-center gap-2 text-sm font-bold uppercase tracking-[0.18em]">
          <MailX class="size-4 shrink-0 text-brand" aria-hidden="true" />{{ $t('activity_requests.skip_section') }}
        </h2>
        <div class="mt-3 divide-y divide-border border-y border-border" data-slot="activity-request-exclusions">
          <div v-for="group in skipGroups" :key="group.key" class="py-4 text-sm">
            <p class="font-bold">
              {{ group.title }}
            </p>
            <p v-for="line in group.lines" :key="line" class="mt-1 text-xs text-muted-foreground">
              {{ line }}
            </p>
          </div>
        </div>
      </section>
    </template>

    <section class="mt-8 space-y-2">
      <Label for="activity-request-note">{{ $t('action_window.activity_request.review.note') }}</Label>
      <Textarea id="activity-request-note" :model-value="draft.activityRequest.note" :placeholder="$t('action_window.activity_request.review.note_placeholder')" maxlength="500" rows="3" @update:model-value="updateActivityRequest({ note: String($event) })" />
      <p class="text-xs text-muted-foreground">
        {{ draft.activityRequest.campaignType === 'missing_meetings' ? $t('activity_requests.review_missing_hint') : $t('action_window.activity_request.review.no_sign_in') }}
      </p>
    </section>
    <StagingNote topic="mail" class="mt-6" />
    <template #footer>
      <ActionWindowPrimaryButton :loading="submitting" :disabled="!ready" @click="submit">
        {{ $t('action_window.activity_request.review.submit', { count: String(checkedRows.length) }) }}
      </ActionWindowPrimaryButton>
    </template>
  </ActionWindowScreen>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Send, MailX } from 'lucide-vue-next';
import { trans as $t } from 'laravel-vue-i18n';

import ActionWindowPrimaryButton from '../ActionWindowPrimaryButton.vue';
import ActionWindowScreen from '../ActionWindowScreen.vue';
import { skipLabel, type ActivityPreviewEntry, type ActivityPreviewRecipient, type ActivityRecipientPair } from '../useActivityRequestAskability';
import { useWindowDates } from '../useWindowDates';

import { useActionWindow } from '@/Composables/useActionWindow';
import { useApiMutation } from '@/Composables/useApi';
import StagingNote from '@/Components/StagingNote.vue';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Label } from '@/Components/ui/label';
import { Skeleton } from '@/Components/ui/skeleton';
import { Textarea } from '@/Components/ui/textarea';

interface Row { key: string; institution: { id: string; name: string }; recipient: ActivityPreviewRecipient }

const { draft, editFromHere, close, updateActivityRequest } = useActionWindow();
const dates = useWindowDates();
const submitting = ref(false);
const byPeople = computed(() => draft.activityRequest.mode === 'people');

const institutionIds = computed(() => byPeople.value
  ? [...new Set(draft.activityRequest.people.flatMap(person => person.institutions.map(institution => institution.id)))]
  : draft.activityRequest.institutions.map(item => item.id));
const pickedPairs = computed<ActivityRecipientPair[] | null>(() => byPeople.value
  ? draft.activityRequest.people.flatMap(person => person.institutions.map(institution => ({ institution_id: institution.id, user_id: person.id })))
  : null);
const payload = computed(() => ({
  institution_ids: institutionIds.value,
  campaign_type: draft.activityRequest.campaignType,
  ...(pickedPairs.value ? { recipients: pickedPairs.value } : {}),
}));
const key = computed(() => JSON.stringify(payload.value));
const loadedKey = ref('');
const preview = ref<ActivityPreviewEntry[] | null>(null);
const { data, error, isFetching, execute, abort } = useApiMutation<ActivityPreviewEntry[], typeof payload.value>(route('api.v1.admin.activityRequests.preview'), 'POST', payload, { showSuccessToast: false });
let generation = 0;
watch(key, async (requestedKey) => {
  const current = ++generation;
  loadedKey.value = '';
  abort();
  await execute();
  if (current === generation && !error.value && data.value && requestedKey === key.value) {
    preview.value = data.value;
    loadedKey.value = requestedKey;
  }
}, { immediate: true, flush: 'sync' });

const rows = computed<Row[]>(() => (preview.value ?? []).filter(entry => !entry.skip_reason).flatMap(entry => entry.recipients.map(recipient => ({
  key: `${entry.institution.id}:${recipient.id}`, institution: entry.institution, recipient,
}))));

/** Groups follow what was picked: institutions with their recipients, or people with their institutions. */
const sendGroups = computed(() => {
  const groups = new Map<string, { key: string; title: string; rows: Row[] }>();
  rows.value.forEach((row) => {
    const [groupKey, title] = byPeople.value ? [row.recipient.id, row.recipient.name] : [row.institution.id, row.institution.name];
    const group = groups.get(groupKey) ?? { key: groupKey, title, rows: [] };
    group.rows.push(row);
    groups.set(groupKey, group);
  });
  return [...groups.values()];
});

const skipGroups = computed(() => {
  const groups = new Map<string, { key: string; title: string; lines: string[] }>();
  const add = (groupKey: string, title: string, line: string) => {
    const group = groups.get(groupKey) ?? { key: groupKey, title, lines: [] };
    group.lines.push(line);
    groups.set(groupKey, group);
  };
  const recipientLine = (recipient: ActivityPreviewRecipient) => recipient.skip_reason ? skipLabel(recipient.skip_reason) : $t(`activity_requests.delivery.${recipient.delivery_mode}`);
  (preview.value ?? []).forEach((entry) => {
    const left = [...(entry.excluded_recipients ?? []), ...(entry.skip_reason ? [] : entry.recipients.filter(item => item.delivery_mode !== 'immediate'))];
    if (entry.skip_reason && left.length === 0) {
      add(entry.institution.id, entry.institution.name, skipLabel(entry.skip_reason));
    }
    left.forEach(recipient => byPeople.value
      ? add(recipient.id, recipient.name, `${entry.institution.name} · ${recipientLine(recipient)}`)
      : add(entry.institution.id, entry.institution.name, `${recipient.name} · ${recipientLine(recipient)}`));
  });
  return [...groups.values()];
});

const isChecked = (rowKey: string) => !draft.activityRequest.unchecked.includes(rowKey);
const setChecked = (rowKey: string, checked: boolean) => updateActivityRequest({
  unchecked: checked ? draft.activityRequest.unchecked.filter(item => item !== rowKey) : [...draft.activityRequest.unchecked, rowKey],
});
const checkedRows = computed(() => rows.value.filter(row => isChecked(row.key)));
const recipientCount = computed(() => new Set(checkedRows.value.map(row => row.recipient.id)).size);
const ready = computed(() => !!draft.activityRequest.campaignType && loadedKey.value === key.value && !isFetching.value && !error.value && !submitting.value && checkedRows.value.length > 0 && institutionIds.value.length <= 100);

const submit = () => {
  if (!ready.value) return;
  submitting.value = true;
  // Unticked rows narrow the send to the ticked pairs; otherwise each institution asks its usual people.
  const narrowed = byPeople.value || checkedRows.value.length < rows.value.length;
  router.post(route('institutions.activity-requests.store'), {
    institution_ids: institutionIds.value,
    campaign_type: draft.activityRequest.campaignType,
    ...(narrowed ? { recipients: checkedRows.value.map(row => ({ institution_id: row.institution.id, user_id: row.recipient.id })) } : {}),
    note: draft.activityRequest.note || undefined,
  }, {
    preserveScroll: true, onSuccess: () => close(), onFinish: () => { submitting.value = false; },
  });
};
</script>
