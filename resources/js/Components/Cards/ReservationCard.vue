<template>
  <!-- Needs to show start and end date -->
  <!-- Needs to show state summary of resources -->
  <Link :href="route('reservations.show', reservation.id)">
    <NCard :title="reservation.name" hoverable>
      <p>
        <strong>Pradžia</strong>:
        {{
          formatStaticTime(
            new Date(reservation.start_time),
            RESERVATION_DATE_TIME_FORMAT
          )
        }}
      </p>
      <p>
        <strong>Pabaiga</strong>:
        {{
          formatStaticTime(
            new Date(reservation.end_time),
            RESERVATION_DATE_TIME_FORMAT
          )
        }}
      </p>
    </NCard>
  </Link>
</template>

<script setup lang="tsx">
import { Link } from "@inertiajs/vue3";
import { NCard } from "naive-ui";
import { RESERVATION_DATE_TIME_FORMAT } from "@/Constants/DateTimeFormats";
import { formatStaticTime } from "@/Utils/IntlTime";

defineProps<{
  reservation: App.Entities.Reservation;
}>();
</script>
