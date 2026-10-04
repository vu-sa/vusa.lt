<template>
  <ActionWindowScreen :title="draft.activityRequest.campaignType ? $t(`activity_requests.campaigns.${draft.activityRequest.campaignType}`) : $t('action_window.activity_request.review.title')" :subtitle="$t('action_window.activity_request.review.subtitle')">
    <div v-if="isFetching" class="space-y-2">
      <Skeleton v-for="n in 3" :key="n" class="h-14 w-full" />
    </div>
    <p v-if="error" role="alert" class="text-sm text-destructive">
      {{ $t('action_window.common.error') }}
    </p>
    <template v-if="preview">
      <h2 class="mb-2 flex items-center gap-2 text-sm font-bold uppercase tracking-[0.18em]">
        <Send class="size-4 shrink-0 text-brand" aria-hidden="true" />{{ $t('activity_requests.send_section') }}
      </h2>
      <ul class="divide-y divide-border border-y border-border" data-slot="activity-request-preview">
        <li v-for="entry in sendable" :key="entry.institution.id" class="py-3">
          <p class="text-sm font-bold">
            {{ entry.institution.name }}
          </p>
          <p v-for="recipient in entry.recipients" :key="recipient.id" class="mt-1 text-xs text-muted-foreground">
            {{ recipient.name }} · {{ dates.fullDay(recipient.period_start) }} – {{ dates.fullDay(recipient.period_end) }} · {{ $t(`activity_requests.delivery.${recipient.delivery_mode}`) }}
            <span v-for="meeting in recipient.incomplete_meetings" :key="meeting.id" class="mt-1 block">{{ dates.fullDay(meeting.date) }} · {{ $t(`activity_requests.meeting_status.${meeting.status}`) }}</span>
          </p>
        </li>
      </ul>
      <p class="mt-2 text-xs text-muted-foreground">
        {{ $t('activity_requests.recipient_count', { count: String(recipientCount) }) }}
      </p>
      <h2 v-if="excluded.length" class="mb-2 mt-5 flex items-center gap-2 text-sm font-bold uppercase tracking-[0.18em]">
        <MailX class="size-4 shrink-0 text-brand" aria-hidden="true" />{{ $t('activity_requests.skip_section') }}
      </h2>
      <ul v-if="excluded.length" class="divide-y divide-border border-y border-border" data-slot="activity-request-exclusions">
        <li v-for="entry in excluded" :key="entry.institution.id" class="py-3 text-sm">
          <p class="font-bold">
            {{ entry.institution.name }}
          </p>
          <p v-if="entry.skip_reason" class="text-xs text-muted-foreground">
            {{ skipLabel(entry.skip_reason) }}
          </p>
          <p v-for="recipient in excludedRecipients(entry)" :key="recipient.id" class="mt-1 text-xs text-muted-foreground">
            {{ recipient.name }} · {{ recipient.skip_reason ? skipLabel(recipient.skip_reason) : $t(`activity_requests.delivery.${recipient.delivery_mode}`) }}
          </p>
        </li>
      </ul>
    </template>
    <div class="mt-3 flex flex-wrap gap-2">
      <Button variant="outline" voice="sentence" @click="editFromHere('activity.campaign')">
        {{ $t('activity_requests.change_campaign') }}
      </Button>
      <Button variant="outline" voice="sentence" @click="editFromHere('activity.institutions')">
        {{ $t('action_window.activity_request.review.change_institutions') }}
      </Button>
    </div>
    <div class="mt-4 space-y-2">
      <Label for="activity-request-note">{{ $t('action_window.activity_request.review.note') }}</Label>
      <Textarea id="activity-request-note" :model-value="draft.activityRequest.note" :placeholder="$t('action_window.activity_request.review.note_placeholder')" maxlength="500" rows="3" @update:model-value="updateActivityRequest({ note: String($event) })" />
      <p class="text-xs text-muted-foreground">
        {{ draft.activityRequest.campaignType === 'missing_meetings' ? $t('activity_requests.review_missing_hint') : $t('action_window.activity_request.review.no_sign_in') }}
      </p>
    </div>
    <template #footer>
      <ActionWindowPrimaryButton :loading="submitting" :disabled="!ready" @click="submit">
        {{ $t('action_window.activity_request.review.submit', { count: String(sendable.length) }) }}
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
import { useWindowDates } from '../useWindowDates';

import { useActionWindow } from '@/Composables/useActionWindow';
import { useApiMutation } from '@/Composables/useApi';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Skeleton } from '@/Components/ui/skeleton';
import { Textarea } from '@/Components/ui/textarea';

interface Recipient { id: string; name: string; period_start: string; period_end: string; delivery_mode: string; skip_reason: string | null; incomplete_meetings?: Array<{ id: string; date: string; status: string }> }
interface PreviewEntry { institution: { id: string; name: string }; recipients: Recipient[]; excluded_recipients: Recipient[]; skip_reason: string | null }
const { draft, editFromHere, close, updateActivityRequest } = useActionWindow();
const dates = useWindowDates();
const submitting = ref(false);
const institutionIds = computed(() => draft.activityRequest.institutions.map(item => item.id));
const payload = computed(() => ({ institution_ids: institutionIds.value, campaign_type: draft.activityRequest.campaignType }));
const key = computed(() => JSON.stringify(payload.value));
const loadedKey = ref('');
const preview = ref<PreviewEntry[] | null>(null);
const { data, error, isFetching, execute, abort } = useApiMutation<PreviewEntry[], typeof payload.value>(route('api.v1.admin.activityRequests.preview'), 'POST', payload, { showSuccessToast: false });
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
const sendable = computed(() => (preview.value ?? []).filter(entry => !entry.skip_reason));
const excludedRecipients = (entry: PreviewEntry) => [...(entry.excluded_recipients ?? []), ...entry.recipients.filter(item => item.delivery_mode !== 'immediate')];
const excluded = computed(() => (preview.value ?? []).filter(entry => entry.skip_reason || excludedRecipients(entry).length));
const recipientCount = computed(() => new Set(sendable.value.flatMap(entry => entry.recipients.map(item => item.id))).size);
const ready = computed(() => !!draft.activityRequest.campaignType && loadedKey.value === key.value && !isFetching.value && !error.value && !submitting.value && sendable.value.length > 0 && institutionIds.value.length <= 100);
const skipLabel = (reason: string) => $t(`activity_requests.skip.${reason}`);
const submit = () => {
  if (!ready.value) return;
  submitting.value = true;
  router.post(route('institutions.activity-requests.store'), { ...payload.value, note: draft.activityRequest.note || undefined }, {
    preserveScroll: true, onSuccess: () => close(), onFinish: () => { submitting.value = false; },
  });
};
</script>
