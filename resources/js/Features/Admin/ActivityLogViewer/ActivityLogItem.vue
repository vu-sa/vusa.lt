<template>
  <div class="flex items-center justify-between">
    <span v-if="activity.description === 'created'"> Sukurtas įvykis.</span>
    <span v-else-if="activity.description === 'updated'"
      >Atnaujintas įvykis.</span
    >
    <div class="w-fit">
      <UserPopover :size="24" show-name :user="activity.causer" />
    </div>
  </div>
  <pre>{{ activity.properties }}</pre>
  <p :title="activity.created_at" class="mt-0 text-sm text-gray-500">
    {{ formatRelativeTime(activity.created_at) }}
  </p>
</template>

<script setup lang="tsx">
import { formatRelativeTime } from "@/Utils/IntlTime";
import UserPopover from "@/Components/Avatars/UserPopover.vue";

defineProps<{
  activity: Record<string, any>;
}>();
</script>
