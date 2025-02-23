<template>
  <NButton size="small" :disabled="!activities" quaternary circle @click="showModal = true">
    <template #icon>
      <IFluentDocumentOnePage24Regular />
    </template>
  </NButton>
  <CardModal v-model:show="showModal" :segmented="{ content: 'soft' }" class="max-w-xl" title="Įrašo pokyčiai"
    @close="showModal = false">
    <div v-if="activities.length > 0" class="flex flex-col gap-4">
      <div v-for="activity in activities" :key="activity.id"
        class="border-b last:border-0 pb-4 last:pb-0 border-zinc-300 dark:border-zinc-700">
        <ActivityLogItem :activity="activity" />
      </div>
    </div>
    <div v-else>
      <p class="text-zinc-500">
        Įrašo pokyčių nėra.
      </p>
    </div>
  </CardModal>
</template>

<script setup lang="ts">
import { ref } from "vue";

import ActivityLogItem from "@/Features/Admin/ActivityLogViewer/ActivityLogItem.vue";
import CardModal from "@/Components/Modals/CardModal.vue";

defineProps<{
  activities: Record<string, any>;
}>();

const showModal = ref(false);
</script>
