<template>
  <Card class="hover:shadow-md transition-shadow">
    <CardContent class="p-4">
      <div class="flex items-center gap-3 min-w-0 overflow-hidden">
        <!-- Avatar -->
        <div class="flex-shrink-0">
          <UserPopover :user="member" :size="40" />
        </div>

        <!-- Member Name -->
        <h3 class="font-medium text-zinc-900 dark:text-zinc-100 truncate">
          {{ member.name }}
        </h3>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { Card, CardContent } from '@/Components/ui/card';
import UserPopover from '@/Components/Avatars/UserPopover.vue';

defineProps<{
  member: App.Entities.User;
}>();
</script>