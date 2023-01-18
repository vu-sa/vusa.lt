<template>
  <NButton
    size="small"
    :disabled="!activities"
    quaternary
    circle
    @click="showModal = true"
    ><template #icon
      ><NIcon :component="DocumentOnePage24Regular"></NIcon></template
  ></NButton>
  <CardModal
    v-model:show="showModal"
    :segmented="{ content: 'soft' }"
    title="Įrašo pokyčiai"
    @close="showModal = false"
  >
    <div v-if="activities.length > 0" class="flex flex-col gap-4">
      <div v-for="activity in activities" :key="activity.id">
        <ActivityLogItem :activity="activity" />
        <NDivider class="last:hidden" />
      </div>
    </div>
  </CardModal>
</template>

<script setup lang="ts">
import { DocumentOnePage24Regular } from "@vicons/fluent";
import { NButton, NDivider, NIcon } from "naive-ui";
import { ref } from "vue";

import ActivityLogItem from "@/Features/Admin/ActivityLogViewer/ActivityLogItem.vue";
import CardModal from "@/Components/Modals/CardModal.vue";

defineProps<{
  activities: Record<string, any>;
}>();

const showModal = ref(false);
</script>
