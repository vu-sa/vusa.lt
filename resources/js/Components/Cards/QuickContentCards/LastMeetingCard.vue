<template>
  <QuickContentCard>
    <template v-if="lastMeeting">
      <div class="inline-flex items-center gap-2">
        <NIcon :size="36" :component="CalendarClock20Regular"></NIcon>
        <span v-if="daysDifference === 0" class="text-3xl font-bold"
          >šiandien</span
        >
        <span v-else class="text-3xl font-bold">
          {{ lastMeetinginFuture ? "po" : "prieš" }}
          <NNumberAnimation
            :from="0"
            :to="Math.abs(daysDifference ?? 0)"
          ></NNumberAnimation>
          d.
        </span>
      </div>
      <p class="mt-4">
        {{
          lastMeetinginFuture ? "Kitas posėdis vyks" : "Paskutinis posėdis vyko"
        }}
        <Link
          :href="route('meetings.show', lastMeeting.id)"
          class="font-bold"
          >{{
            formatStaticTime(new Date(lastMeeting.start_time), {
              year: "numeric",
              month: "long",
              day: "2-digit",
            })
          }}</Link
        >
      </p>
    </template>
    <p v-else>Nėra užfiksuoto jokio posėdžio. 😢</p>
    <template #action-button>
      <NMessageProvider
        ><NewMeetingButton :institution="institution"
      /></NMessageProvider>
    </template>
  </QuickContentCard>
</template>

<script setup lang="tsx">
import { CalendarClock20Regular } from "@vicons/fluent";
import { Link } from "@inertiajs/inertia-vue3";
import { NIcon, NMessageProvider, NNumberAnimation } from "naive-ui";
import { computed } from "vue";

import { formatStaticTime, getDaysDifference } from "@/Utils/IntlTime";
import NewMeetingButton from "@/Components/Buttons/NewMeetingButton.vue";
import QuickContentCard from "@/Components/Cards/QuickContentCards/QuickContentCard.vue";

const props = defineProps<{
  doingTypes: any;
  institution: App.Entities.Institution;
  lastMeeting?: App.Entities.Meeting;
}>();

const daysDifference = computed(() => {
  return props.lastMeeting
    ? getDaysDifference(props.lastMeeting.start_time)
    : undefined;
});

console.log(daysDifference.value);

// check if daysDifference is in future
const lastMeetinginFuture = computed(() => {
  if (daysDifference.value === undefined) return undefined;
  return daysDifference.value < 0;
});
</script>
