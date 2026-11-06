<template>
  <MultiSelect
    v-model="selected"
    :options="users"
    label-field="name"
    value-field="id"
    :placeholder="`${$t('Pasirinkite')}...`"
    :empty-text="$t('No users found.')"
  >
    <template #selected-item="{ item: user }">
      <div class="flex items-center gap-1">
        <UserAvatar :user="(user as unknown as App.Entities.User)" :size="16" />
        <span class="max-w-[120px] truncate">{{ user.name }}</span>
      </div>
    </template>
    <template #option="{ item: user }">
      <UserAvatar :user="(user as unknown as App.Entities.User)" :size="24" class="shrink-0" />
      <span class="flex min-w-0 flex-col">
        <span class="truncate">{{ user.name }}</span>
        <span v-if="user.email" class="truncate text-xs text-muted-foreground">{{ user.email }}</span>
      </span>
    </template>
  </MultiSelect>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import { MultiSelect } from '@/Components/ui/multi-select';
import type { SupportRequestUser } from '@/Types/supportRequests';

defineProps<{
  users: SupportRequestUser[];
}>();

const selected = defineModel<SupportRequestUser[]>({ required: true });
</script>
