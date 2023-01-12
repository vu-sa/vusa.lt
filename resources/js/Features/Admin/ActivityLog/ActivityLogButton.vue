<template>
  <NButton tertiary circle @click="showModal = true"
    ><template #icon
      ><NIcon :component="DocumentOnePage24Regular"></NIcon></template
  ></NButton>
  <CardModal
    v-model:show="showModal"
    title="Įrašo pokyčiai"
    @close="showModal = false"
  >
    <div v-if="activities.length > 0" class="flex flex-col gap-4">
      <div v-for="activity in activities" :key="activity.id">
        <ActivityLogItem :activity="activity" />
        <NDivider class="last:hidden" />
      </div>
    </div>
    <p v-else>Jokių pokyčių nerasta.</p>
  </CardModal>
</template>

<script setup lang="ts">
import { DocumentOnePage24Regular } from "@vicons/fluent";
import { NButton, NDivider, NIcon } from "naive-ui";
import { ref } from "vue";

import { formatRelativeTime } from "@/Utils/IntlTime";
import ActivityLogItem from "@/Features/Admin/ActivityLog/ActivityLogItem.vue";
import CardModal from "@/Components/Modals/CardModal.vue";
import UserAvatar from "@/Components/Avatars/UserAvatar.vue";

defineProps<{
  activities: Record<string, any>;
}>();

const showModal = ref(false);
</script>
