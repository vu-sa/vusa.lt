<template>
  <NCard
    class="h-fit rounded-2xl text-gray-900 shadow-md dark:text-zinc-100 lg:border-2"
    hoverable
    :segmented="{ footer: 'soft' }"
  >
    <template #cover>
      <img
        v-if="calendarEvent.images.length > 0"
        style="height: 100px"
        class="rounded-t-2xl object-cover object-center"
        :src="calendarEvent.images[0].original_url"
      />
    </template>
    <template #header
      ><strong class="line-clamp-2">{{ calendarEvent.title }}</strong></template
    >
    <div class="flex flex-col gap-2">
      <div class="inline-flex items-center gap-2">
        <NIcon :component="CalendarLtr24Regular" />
        <strong>
          {{
            formatStaticTime(
              new Date(calendarEvent.date),
              {
                weekday: "short",
                year: "numeric",
                month: "long",
                day: "numeric",
                hour: "numeric",
                minute: "numeric",
              },
              $page.props.app.locale
            )
          }}
        </strong>
      </div>
      <div v-if="calendarEvent.end_date" class="inline-flex items-center gap-2">
        <NIcon :component="Timer16Regular" />
        <span>
          {{ calculateEventDuration(calendarEvent) }}
        </span>
      </div>
      <div v-if="calendarEvent.location" class="inline-flex items-center gap-2">
        <NIcon :component="Location24Regular" />
        <a
          class="underline"
          target="_blank"
          :href="`https://www.google.com/maps/search/?api=1&query=${calendarEvent.location}`"
          >{{
            $page.props.app.locale === "en"
              ? calendarEvent.extra_attributes?.en?.location ??
                calendarEvent.location
              : calendarEvent.location
          }}</a
        >
      </div>
      <div class="inline-flex items-center gap-2">
        <NIcon :component="PeopleTeam28Regular" />
        <span>
          {{ $t("Organizuoja") }}:
          <strong>{{ eventOrganizer }}</strong>
        </span>
      </div>
    </div>

    <template #footer>
      <div class="flex flex-col justify-center">
        <p v-if="timeTillEvent.days >= 0" class="text-center">
          {{ $t("Iki renginio liko") }}:
        </p>

        <NGradientText
          v-if="timeTillEvent.days >= 0"
          type="error"
          class="mb-2 w-full text-center"
        >
          {{ `${timeTillEvent.days} ${$t("d.")}` }}
          <NCountdown
            :render="renderCountdown"
            :active="true"
            :duration="timeTillEvent.ms"
          ></NCountdown>
        </NGradientText>

        <NButton
          v-if="calendarEvent.url"
          strong
          tag="a"
          round
          type="primary"
          size="large"
          target="_blank"
          :href="calendarEvent.url"
          ><template #icon>
            <NIcon :component="HatGraduation20Regular"></NIcon>
          </template>
          {{ $t("Dalyvauk") }}!
        </NButton>
        <div class="mt-4 flex justify-center gap-2">
          <NButton
            v-if="calendarEvent.extra_attributes?.facebook_url"
            secondary
            tag="a"
            target="_blank"
            :href="calendarEvent.extra_attributes?.facebook_url"
            circle
            ><NIcon size="18" :component="FacebookF"></NIcon
          ></NButton>
          <NPopover>
            {{ $t("Įsidėk į Google kalendorių") }}
            <template #trigger>
              <NButton
                secondary
                circle
                tag="a"
                target="_blank"
                :href="googleLink"
              >
                <template #icon><NIcon :component="Google" /></template>
              </NButton>
            </template>
          </NPopover>
        </div>
      </div>
    </template>
  </NCard>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import {
  CalendarLtr24Regular,
  HatGraduation20Regular,
  Location24Regular,
  PeopleTeam28Regular,
  Timer16Regular,
} from "@vicons/fluent";
import {
  type CountdownProps,
  NButton,
  NCard,
  NCountdown,
  NGradientText,
  NIcon,
  NPopover,
} from "naive-ui";
import { FacebookF, Google } from "@vicons/fa";
import { computed } from "vue";
import { formatStaticTime } from "@/Utils/IntlTime";

const props = defineProps<{
  calendarEvent: App.Entities.Calendar;
  googleLink: string;
}>();

const eventOrganizer = computed((): string => {
  return (
    props.calendarEvent.extra_attributes?.organizer ??
    props.calendarEvent.padalinys.shortname
  );
});

const timeTillEvent = computed(() => {
  const date = new Date(props.calendarEvent.date.replace(/-/g, "/"));
  const now = new Date();
  // get full days till event
  const daysTillEvent = Math.floor(
    (date.getTime() - now.getTime()) / (1000 * 60 * 60 * 24)
  );
  // get ms till event minus full days
  const msTillEvent =
    date.getTime() - now.getTime() - daysTillEvent * 1000 * 60 * 60 * 24;
  return {
    days: daysTillEvent,
    ms: msTillEvent,
  };
});

const calculateEventDuration = (event: App.Entities.Calendar) => {
  if (!event.end_date) return undefined;

  const startDate = new Date(event.date);
  const endDate = new Date(event.end_date.replace(/-/g, "/"));
  const duration = endDate.getTime() - startDate.getTime();

  // if event is longer than 1 day, return days
  if (duration > 1000 * 60 * 60 * 24) {
    const days = Math.floor(duration / (1000 * 60 * 60 * 24));
    return `${days} ${$t("dienos")}`;
  }
  // if event is longer than 1 hour, return hours
  if (duration > 1000 * 60 * 60) {
    const hours = Math.floor(duration / (1000 * 60 * 60));
    return `${hours} ${$t("valandos")}`;
  }
  // if event is longer than 1 minute, return minutes
  if (duration > 1000 * 60) {
    const minutes = Math.floor(duration / (1000 * 60));
    return `${minutes} ${$t("minutės")}`;
  }
};

const renderCountdown: CountdownProps["render"] = ({
  hours,
  minutes,
  seconds,
}) => {
  return (
    <span>{`${hours} ${$t("val.")} ${minutes} ${$t("min.")} ${seconds} ${$t(
      "sek."
    )}`}</span>
  );
};
</script>
