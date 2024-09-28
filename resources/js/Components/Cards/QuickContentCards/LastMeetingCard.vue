<template>
  <QuickContentCard>
    <template v-if="lastMeeting">
      <div class="inline-flex items-center gap-2">
        <IFluentCalendarClock24Filled />
        <span v-if="daysDifference === 0" class="text-3xl font-bold">{{ $t('šiandien') }}</span>
        <span v-else class="text-3xl font-bold">
          {{ lastMeetinginFuture ? $t("po") : $t("prieš") }}
          <NNumberAnimation :from="0" :to="Math.abs(daysDifference ?? 0)" />
          d.
        </span>
      </div>
      <p class="mt-4">
        {{
          lastMeetinginFuture ? $t("Kitas posėdis vyks") : $t("Paskutinis posėdis vyko")
        }}
        <Link :href="route('meetings.show', lastMeeting.id)" class="font-bold">
        {{
          formatStaticTime(new Date(lastMeeting.start_time), {
        year: "numeric",
        month: "long",
        day: "2-digit",
        }, $page.props.app.locale)
        }}
        </Link>
      </p>
    </template>
    <p v-else>
      <template v-if="$page.props.app.locale === 'lt'">
        Nėra įrašytų posėdžių. Paspausk mygtuką žemiau, kad pridėtum pirmąjį! 🎉
      </template>
      <template v-if="$page.props.app.locale === 'en'">
        No meeting has been recorded. Use the button below to add one! 🎉
      </template>
    </p>
    <template #action-button>
      <NewMeetingButton @click="showModal = true" />
      <NewMeetingModal :institution="institution" :show-modal="showModal" @close="showModal = false" />
    </template>
  </QuickContentCard>
</template>

<script setup lang="tsx">
import { Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";

import { formatStaticTime, getDaysDifference } from "@/Utils/IntlTime";
import NewMeetingButton from "@/Components/Buttons/NewMeetingButton.vue";
import NewMeetingModal from "@/Components/Modals/NewMeetingModal.vue";
import QuickContentCard from "@/Components/Cards/QuickContentCards/QuickContentCard.vue";

const props = defineProps<{
  doingTypes: any;
  institution: App.Entities.Institution;
  lastMeeting?: App.Entities.Meeting;
}>();

const showModal = ref(false);

const daysDifference = computed(() => {
  return props.lastMeeting
    ? getDaysDifference(props.lastMeeting.start_time)
    : undefined;
});

// check if daysDifference is in future
const lastMeetinginFuture = computed(() => {
  if (daysDifference.value === undefined) return undefined;
  return daysDifference.value < 0;
});
</script>
