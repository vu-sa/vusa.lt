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
        <p class="my-1 flex flex-row items-center gap-1">
          <UserAvatar show-name :user="activity.causer" />
          <template v-if="activity.description === 'created'"
            ><span> sukūrė įvykį.</span>
          </template>
          <template v-else-if="activity.description === 'updated'"
            ><span>atnaujino įvykį.</span></template
          >
        </p>
        <p :title="activity.created_at" class="mt-0 text-sm text-gray-500">
          {{ formatRelativeTime(activity.created_at) }}
        </p>
      </div>
    </div>
    <p v-else>Jokių pokyčių nerasta.</p>
  </CardModal>
</template>

<script setup lang="ts">
import { DocumentOnePage24Regular } from "@vicons/fluent";
import { NButton, NIcon } from "naive-ui";
import { ref } from "vue";

import { formatRelativeTime } from "@/Utils/IntlTime";
import CardModal from "@/Components/Modals/CardModal.vue";
import UserAvatar from "@/Components/Avatars/UserAvatar.vue";

defineProps<{
  activities: Record<string, any>;
}>();

const showModal = ref(false);
</script>
