<template>
  <NTag
    :bordered="false"
    size="small"
    :type="reservationResource.state_properties.tagType"
  >
    <div class="inline-flex items-center gap-1">
      <span>{{ doingStateDescriptions[reservationResource.state].title }}</span>
      <InfoPopover>
        {{ reservationResource.state_properties.description }}
      </InfoPopover>
    </div>
  </NTag>
</template>

<script setup lang="tsx">
import { NTag } from "naive-ui";
import InfoPopover from "../Buttons/InfoPopover.vue";

defineProps<{
  reservationResource: App.Entities.ReservationResource;
}>();

const doingStateDescriptions: Record<
  App.Entities.ReservationResource["state"],
  Partial<{
    title: string;
  }>
> = {
  created: {
    title: "Sukurtas",
  },
  reserved: {
    title: "Rezervuotas",
  },
  lent: {
    title: "Atsiimtas",
  },
  returned: {
    title: "Grąžintas",
  },
  rejected: {
    title: "Atmestas",
  },
  cancelled: {
    title: "Atšauktas",
  },
};
</script>
