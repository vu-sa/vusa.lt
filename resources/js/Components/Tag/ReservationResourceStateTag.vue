<template>
  <NTag :bordered="false" size="small" :type="state_properties.tagType">
    <div class="inline-flex items-center gap-1">
      <span>{{ capitalize($t(`state.status.${state}`)) }}</span>
      <InfoPopover v-if="state_properties">
        {{ state_properties.description }}
      </InfoPopover>
    </div>
  </NTag>
</template>

<script setup lang="ts">
import { NTag } from "naive-ui";

import { capitalize } from "@/Utils/String";
import InfoPopover from "../Buttons/InfoPopover.vue";

defineProps<{
  state: App.Entities.ReservationResource["state"];
  state_properties?: App.Entities.ReservationResource["state_properties"];
}>();
</script>
