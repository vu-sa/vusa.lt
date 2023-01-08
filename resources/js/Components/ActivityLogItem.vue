<template>
  <UserAvatar class="not-prose" show-name :user="activity.causer" />

  <p class="my-1 flex flex-row items-center gap-1">
    <template v-if="activity.description === 'created'"
      ><span> sukūrė įvykį.</span>
    </template>
    <template v-else-if="activity.description === 'updated'"
      ><span>atnaujino įvykį.</span></template
    >
  </p>
  <pre>{{ activity.properties }}</pre>
  <p :title="activity.created_at" class="mt-0 text-sm text-gray-500">
    {{ formatRelativeTime(activity.created_at) }}
  </p>
</template>

<script setup lang="tsx">
import { formatRelativeTime } from "@/Utils/IntlTime";
import UserAvatar from "@/Components/Avatars/UserAvatar.vue";

defineProps<{
  activity: Record<string, any>;
}>();
</script>
