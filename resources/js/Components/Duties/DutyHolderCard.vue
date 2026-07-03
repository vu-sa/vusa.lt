<template>
  <Link
    :href="route('users.show', member.id)"
    :class="['flex items-center gap-2.5 rounded-lg border border-border bg-card px-2.5 py-2 text-left', interactiveCardClass]"
  >
    <UserPopover :user="member" :size="32" class="shrink-0" />

    <div class="min-w-0 flex-1 leading-tight">
      <div class="flex min-w-0 items-center gap-1.5">
        <span class="truncate text-sm font-medium text-foreground">{{ member.name }}</span>
        <Badge v-if="isDelegated" variant="outline" class="shrink-0 text-[10px]">
          {{ $t('Deleguota') }}
        </Badge>
      </div>
      <div class="mt-0.5 flex min-w-0 items-center gap-1 text-xs text-muted-foreground">
        <Calendar class="h-3 w-3 shrink-0" />
        <span class="truncate">{{ tenureLabel }}</span>
      </div>
    </div>
  </Link>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Calendar } from 'lucide-vue-next';

import UserPopover from '@/Components/Avatars/UserPopover.vue';
import { Badge } from '@/Components/ui/badge';
import { formatStaticTime } from '@/Utils/IntlTime';
import { interactiveCardClass } from '@/Utils/interactiveCard';

const props = defineProps<{
  member: App.Entities.User;
}>();

/** Cross-tenant representatives carry a non-null pivot tenant_id. */
const isDelegated = computed(() => props.member.pivot?.tenant_id != null);

const tenureLabel = computed(() => {
  const start = props.member.pivot?.start_date;
  if (!start) {
    return '';
  }
  const startLabel = formatStaticTime(new Date(start), { year: 'numeric', month: 'short' });
  const end = props.member.pivot?.end_date;
  if (!end || new Date(end) >= new Date()) {
    return `${startLabel} – ${$t('dabar')}`;
  }
  return `${startLabel} – ${formatStaticTime(new Date(end), { year: 'numeric', month: 'short' })}`;
});
</script>
