<template>
  <NButton tertiary circle @click="showModal = true"
    ><template #icon
      ><NIcon :component="DocumentOnePage24Regular"></NIcon></template
  ></NButton>
  <NModal
    v-model:show="showModal"
    class="max-w-xl dark:prose-invert"
    title="Įrašo pokyčiai"
    :bordered="false"
    size="large"
    role="card"
    aria-modal="true"
    preset="card"
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
          {{ getRelativeTime(activity.created_at) }}
        </p>
      </div>
    </div>
    <p v-else>Jokių pokyčių nerasta.</p>
  </NModal>
</template>

<script setup lang="ts">
import { DocumentOnePage24Regular } from "@vicons/fluent";
import { NButton, NIcon, NModal } from "naive-ui";
import { ref } from "vue";

import UserAvatar from "@/Components/Avatars/UserAvatar.vue";
import getRelativeTime from "@/Composables/getRelativeTime";

defineProps<{
  activities: Record<string, any>;
}>();

const showModal = ref(false);
</script>
