<template>
  <NTag
    :bordered="false"
    size="small"
    :type="doingStateDescriptions[reservationResource.state].tagType"
  >
    <div class="inline-flex items-center gap-1">
      <span>{{ doingStateDescriptions[reservationResource.state].title }}</span>
      <InfoPopover>
        <component
          :is="doingStateDescriptions[reservationResource.state].description"
        ></component>
      </InfoPopover>
    </div>
  </NTag>
</template>

<script setup lang="tsx">
import { NTag } from "naive-ui";
import InfoPopover from "../Buttons/InfoPopover.vue";
import type { Component } from "vue";

defineProps<{
  reservationResource: App.Entities.ReservationResource;
}>();

const doingStateDescriptions: Record<
  App.Entities.ReservationResource["state"],
  Partial<{
    title: string;
    description: Component;
    tagType: "success" | "info" | "error";
  }>
> = {
  created: {
    title: "Sukurtas",
    description: (
      <span>
        Daikto rezervacijos užklausa yra sukurta! Laukiama, kol administratorius
        patvirtins rezervaciją.
      </span>
    ),
    tagType: "info",
  },
  updated: {
    title: "Atnaujintas",
    description: (
      <span>
        <strong>Rezervacija atnaujinta!</strong> Laukiama, kol administratorius
        patvirtins rezervaciją.
      </span>
    ),
    tagType: "info",
  },
  reserved: {
    title: "Rezervuotas",
    description: (
      <span>
        <strong>Daiktas rezervuotas!</strong> Rezervuotą daiktą galima atsiimti
        nurodytu laiku.
      </span>
    ),
    tagType: "success",
  },
  lent: {
    title: "Atsiimtas",
    description: (
      <span>
        <strong>Daiktas paimtas!</strong> Daiktas sėkmingai paimtas rezervacijos
        organizatorių ir įpareigotas grąžinti nurodytu laiku.
      </span>
    ),
    tagType: "success",
  },
  returned: {
    title: "Grąžintas",
    description: (
      <span>
        <strong>Daiktas grąžintas!</strong> Daiktas sėkmingai grąžintas
        rezervacijos organizatorių.
      </span>
    ),
    tagType: "success",
  },
  rejected: {
    title: "Atmestas",
    description: <span>Daikto rezervacija atmesta.</span>,
    tagType: "error",
  },
  cancelled: {
    title: "Atšauktas",
    description: <span>Daikto rezervacija atšaukta.</span>,
    tagType: "error",
  },
};
</script>
