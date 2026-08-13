<template>
  <div class="flex min-w-0 items-center gap-2">
    <UserAvatar :size="24" :user="option.user" />
    <span class="min-w-0">
      <span class="flex items-center gap-1.5">
        <span class="truncate">{{ option.label }}</span>
        <Badge variant="outline" class="shrink-0 text-[10px] font-normal">
          {{ $t('forms.fields.ex_officio_badge') }}
        </Badge>
      </span>
      <span v-if="option.sourceDutyName" class="block truncate text-xs text-muted-foreground">
        {{ $t('forms.fields.ex_officio_source', { duty: option.sourceDutyName }) }}
      </span>
    </span>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import { Badge } from '@/Components/ui/badge';

/**
 * A member whose seat was granted by another duty. Rendered inside the locked
 * slot of a TransferList, where the seat is shown but cannot be revoked.
 */
defineProps<{
  option: {
    label: string;
    user?: { id: string; name: string; profile_photo_path?: string | null };
    sourceDutyName?: string | null;
  };
}>();
</script>
