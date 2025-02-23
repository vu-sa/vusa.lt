<template>
  <div class="flex items-center justify-between">
    <strong v-if="activity.description === 'created'"> Sukurtas įvykis</strong>
    <div v-else-if="activity.description === 'updated'">
      <strong>Atnaujintas įvykis</strong>
    </div>
    <div class="w-fit">
      <UserPopover :size="18" show-name :user="activity.causer" />
    </div>
  </div>
  <div v-if="activity.description === 'updated'">
    <div v-for="key in Object.keys(activity.properties.attributes)" :key="key" class="mt-1">
      <pre>{{ key }}:</pre>
      <span class="text-red-500 dark:text-red-300">{{ activity.properties.old[key] }}</span> ->
      <span class="text-green-600 dark:text-green-300">{{ activity.properties.attributes[key] }}</span>
    </div>
  </div>
  <!-- <pre>{{ activity.properties }}</pre> -->
  <p :title="activity.created_at" class="mt-1 text-xs text-zinc-600">
    {{ formatRelativeTime(activity.created_at) }}
  </p>
</template>

<script setup lang="ts">
import { formatRelativeTime } from "@/Utils/IntlTime";
import UserPopover from "@/Components/Avatars/UserPopover.vue";

defineProps<{
  activity: Record<string, any>;
}>();
</script>
