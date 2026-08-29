<template>
  <ActionWindowScreen
    :title="$t('action_window.check_in.review.title')"
    :subtitle="$t('action_window.check_in.review.subtitle')"
  >
    <dl class="divide-y divide-border overflow-hidden rounded-xl border border-border">
      <ReviewRow
        :label="$t('action_window.meeting.review.institution')"
        :value="draft.institution?.name"
        :editable="isOnStack('checkin.institution')"
        @edit="editFromHere('checkin.institution')"
      />
      <ReviewRow
        :label="$t('action_window.check_in.review.period')"
        :value="periodLabel"
        @edit="editFromHere('checkin.until')"
      />
    </dl>

    <div class="mt-4 space-y-2">
      <Label>{{ $t('action_window.check_in.review.note') }}</Label>
      <Textarea
        :model-value="draft.checkIn.note"
        :placeholder="$t('action_window.check_in.review.note_placeholder')"
        rows="3"
        @update:model-value="(value) => updateCheckIn({ note: String(value) })"
      />
    </div>

    <template #footer>
      <Button class="w-full" size="lg" :disabled="submitting" @click="submit">
        <Loader2 v-if="submitting" class="mr-2 size-4 animate-spin" />
        {{ submitting ? $t('action_window.check_in.review.submitting') : $t('action_window.check_in.review.submit') }}
      </Button>
    </template>
  </ActionWindowScreen>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';

import ActionWindowScreen from '../ActionWindowScreen.vue';
import ReviewRow from '../ReviewRow.vue';
import { useWindowDates } from '../useWindowDates';

import { useActionWindow, type ScreenId } from '@/Composables/useActionWindow';
import { invalidateActionWindowData } from '@/Composables/useActionWindowData';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';

const { draft, stack, editFromHere, close, updateCheckIn } = useActionWindow();

const dates = useWindowDates();
const submitting = ref(false);

const isOnStack = (id: ScreenId) => stack.some(frame => frame.id === id);

const periodLabel = computed(() => {
  const { startDate, endDate } = draft.checkIn;
  if (!startDate || !endDate) {
    return undefined;
  }

  return `${dates.day(startDate)} – ${dates.day(endDate)}`;
});

const submit = () => {
  if (submitting.value || !draft.institution) {
    return;
  }

  submitting.value = true;

  router.post(route('institutions.check-ins.store', draft.institution.id), {
    start_date: draft.checkIn.startDate,
    end_date: draft.checkIn.endDate,
    note: draft.checkIn.note || undefined,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      invalidateActionWindowData();
      close();
      // The check-in changes what the page behind the window is showing.
      router.reload();
    },
    onFinish: () => {
      submitting.value = false;
    },
  });
};
</script>
