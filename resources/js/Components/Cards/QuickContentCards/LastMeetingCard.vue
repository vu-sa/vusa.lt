<template>
  <QuickContentCard>
    <template v-if="lastMeeting">
      <div class="inline-flex items-center gap-2">
        <NIcon :size="36" :component="CalendarClock20Regular"></NIcon>

        <span class="text-3xl font-bold">
          prieš
          <NNumberAnimation
            :from="0"
            :to="getDaysDifference(lastMeeting.date)"
          ></NNumberAnimation>
          d.
        </span>
      </div>
      <p class="mt-4">
        Paskutinis posėdis vyko
        <Link :href="route('doings.show', lastMeeting.id)" class="font-bold">{{
          getMMMMDD(lastMeeting.date)
        }}</Link>
      </p>
      lastMeeting
    </template>
    <p v-else>Nėra užfiksuoto jokio posėdžio. 😢</p>
    <template #action-button>
      <NewMeetingButton :institution="institution" :doing-types="doingTypes" />
    </template>
  </QuickContentCard>
</template>

<script setup lang="tsx">
import { CalendarClock20Regular } from "@vicons/fluent";
import { Link } from "@inertiajs/inertia-vue3";
import { NIcon, NNumberAnimation } from "naive-ui";
import route from "ziggy-js";

import { getDaysDifference, getMMMMDD } from "@/Composables/getRelativeTime";
import NewMeetingButton from "@/Components/Buttons/NewMeetingButton.vue";
import QuickContentCard from "@/Components/Cards/QuickContentCards/QuickContentCard.vue";

defineProps<{
  doingTypes: any;
  institution: App.Models.Institution;
  lastMeeting?: App.Models.InstitutionMeeting;
}>();
</script>
