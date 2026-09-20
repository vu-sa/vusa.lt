<template>
  <div class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between" data-slot="member-term-row">
    <div class="flex min-w-0 items-start gap-3">
      <UserAvatar :user :size="40" class="shrink-0" />
      <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
          <Link
            :href="route('users.show', user.id)"
            class="truncate text-sm font-semibold text-foreground hover:underline"
          >
            {{ user.name }}
          </Link>
          <span v-if="user.pivot?.via_dutiable_id" :class="chipClass">{{ $t('Ex-officio') }}</span>
          <span v-if="user.pivot?.tenant_id" :class="chipClass">{{ $t('Deleguota') }}</span>
        </div>
        <p v-if="user.email" class="truncate text-xs text-muted-foreground">
          {{ user.pivot?.additional_email || user.email }}
        </p>
        <p class="mt-1 flex items-center gap-1 text-xs text-muted-foreground">
          <Calendar class="size-3 shrink-0" />
          <span>{{ tenure }}</span>
        </p>
      </div>
    </div>

    <div v-if="canManage" class="flex shrink-0 items-center gap-2">
      <Button variant="ghost" size="sm" class="u-touch" @click="emit('edit')">
        <Edit3 class="size-3.5" />
        {{ $t('Redaguoti') }}
      </Button>
      <Button v-if="canEnd" variant="ghost" size="sm" class="u-touch" @click="emit('end')">
        <CalendarCheck class="size-3.5" />
        {{ $t('Baigti kadenciją') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Calendar, CalendarCheck, Edit3 } from 'lucide-vue-next';
import { computed } from 'vue';

import { termStatus } from './occupancy';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import { Button } from '@/Components/ui/button';
import { formatStaticTime } from '@/Utils/IntlTime';

const props = defineProps<{
  user: App.Entities.User;
  canManage: boolean;
}>();

const emit = defineEmits<{
  (e: 'edit'): void;
  (e: 'end'): void;
}>();

const chipClass = 'border border-border bg-secondary px-1.5 py-0.5 text-xs font-medium text-muted-foreground';

const status = computed(() => termStatus(props.user.pivot ?? {}));

// Ex-officio terms follow their source, and a term that is over has nothing left to end.
const canEnd = computed(() => status.value === 'current' && !props.user.pivot?.via_dutiable_id);

const tenure = computed(() => {
  const start = props.user.pivot?.start_date;

  if (!start) {
    return '';
  }

  const label = (value: string) => formatStaticTime(new Date(value), { year: 'numeric', month: 'short' });
  const end = props.user.pivot?.end_date;

  return `${label(start)} – ${end ? label(end) : $t('dabar')}`;
});
</script>
