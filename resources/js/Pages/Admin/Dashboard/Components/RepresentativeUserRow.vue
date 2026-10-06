<template>
  <div class="flex items-center gap-3 py-3">
    <div class="relative shrink-0">
      <UserAvatar :user="(user as unknown as App.Entities.User)" :size="32" />
      <span
        :class="['absolute -right-0.5 -bottom-0.5 size-2.5 ring-2 ring-background', statusDotClasses]"
        :title="$t(statusLabel)"
      >
        <span class="sr-only">{{ $t(statusLabel) }}</span>
      </span>
    </div>

    <div class="min-w-0 flex-1">
      <div class="truncate text-sm font-medium text-foreground">
        {{ user.name }}
      </div>
      <div class="truncate text-xs text-muted-foreground">
        {{ user.duties && user.duties.length > 0 ? user.duties[0]?.institution_name : user.email }}
      </div>
    </div>

    <div class="shrink-0 text-right">
      <div :class="['text-xs font-medium', lastActivityClasses]">
        {{ lastActivityText }}
      </div>
      <div v-if="user.duties.length > 1" class="text-xs text-muted-foreground">
        +{{ user.duties.length - 1 }} {{ $t('pareigos') }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import type { RepresentativeUser } from '../types';
import { getActivityDotClasses, getActivityTextClasses, getActivityLabel } from '../Composables/useActivityStatus';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import { formatNearDate } from '@/Utils/dateTime';

interface Props {
  user: RepresentativeUser;
}

const props = defineProps<Props>();

const statusDotClasses = computed(() => getActivityDotClasses(props.user.category));

const statusLabel = computed(() => getActivityLabel(props.user.category));

const lastActivityText = computed(() => {
  if (props.user.category === 'never' || !props.user.last_action) {
    return $t('Niekada');
  }
  return formatNearDate(props.user.last_action, { thresholdDays: 30, fallbackFormat: 'iso' });
});

const lastActivityClasses = computed(() => getActivityTextClasses(props.user.category));
</script>
