<template>
  <Popover v-if="holders.length || canManage" v-model:open="open">
    <PopoverTrigger as-child>
      <button
        type="button"
        :class="[
          'flex min-w-0 shrink-0 items-center gap-2 px-1 py-1 text-left text-sm text-muted-foreground transition-colors',
          'hover:bg-secondary hover:text-foreground pointer-coarse:min-h-11',
          'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring',
        ]"
        :aria-label="triggerLabel"
        data-slot="duty-holders-trigger"
      >
        <span v-if="holders.length" class="flex items-center">
          <UserAvatar
            v-for="(user, index) in visibleHolders"
            :key="user.id"
            :user
            size="xs"
            :class="index > 0 ? '-ml-2 [&_[data-slot=avatar]]:ring-2 [&_[data-slot=avatar]]:ring-background' : undefined"
          />
          <Avatar v-if="hiddenCount" size="xs" class="-ml-2 ring-2 ring-background">
            <AvatarFallback class="text-[11px] font-semibold">+{{ hiddenCount }}</AvatarFallback>
          </Avatar>
        </span>
        <span v-else class="flex size-6 items-center justify-center border border-dashed border-muted-foreground/50">
          <UserPlus class="size-3.5" aria-hidden="true" />
        </span>
        <span v-if="holders.length === 1" class="hidden max-w-40 truncate sm:inline">{{ holders[0]!.name }}</span>
        <ChevronDown class="size-3.5 shrink-0" aria-hidden="true" />
      </button>
    </PopoverTrigger>

    <PopoverContent align="end" class="w-72 p-0" data-slot="duty-holders-popover">
      <p class="border-b border-border px-3 py-2 text-[11px] font-bold uppercase tracking-[0.18em] text-muted-foreground">
        {{ $t('Užima') }} · {{ holders.length }}
      </p>
      <ul v-if="holders.length" class="max-h-72 divide-y divide-border/60 overflow-y-auto">
        <li v-for="user in holders" :key="user.id" class="flex min-h-11 items-center gap-2 py-1.5 pr-1 pl-3">
          <Link :href="route('users.show', user.id)" class="flex min-w-0 flex-1 items-center gap-2 hover:text-brand">
            <UserAvatar :user size="xs" />
            <span class="min-w-0">
              <span class="block truncate text-sm font-medium">{{ user.name }}</span>
              <span v-if="user.pivot?.start_date" class="block text-xs text-muted-foreground tabular-nums">
                {{ $t('nuo') }} {{ formatDate(user.pivot.start_date) }}
              </span>
            </span>
          </Link>
          <Button
            v-if="canManage && user.pivot?.id"
            type="button"
            variant="ghost"
            size="icon-sm"
            class="shrink-0 pointer-coarse:size-11"
            :aria-label="`${$t('Redaguoti pareigybės laikotarpį')}: ${user.name}`"
            :title="$t('Redaguoti pareigybės laikotarpį')"
            @click="editTerm(user)"
          >
            <CalendarCog class="size-4 text-muted-foreground" aria-hidden="true" />
          </Button>
        </li>
      </ul>
      <p v-else class="px-3 py-3 text-sm text-status-attention">
        {{ $t('Neužimta') }}
      </p>
      <!-- The popover's own footer action, as the date-time picker closes its popover. -->
      <button
        v-if="canManage"
        type="button"
        :class="[
          'flex h-10 w-full items-center justify-center gap-2 border-t border-border bg-secondary/20 pointer-coarse:h-11',
          'text-xs font-bold uppercase tracking-wide text-brand transition-colors hover:bg-secondary/60',
          'focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring',
        ]"
        data-slot="duty-holders-assign"
        @click="assign"
      >
        <UserPlus class="size-4" aria-hidden="true" />
        {{ $t('Pridėti asmenį') }}
      </button>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CalendarCog, ChevronDown, UserPlus } from '@lucide/vue';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import { Button } from '@/Components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import type { UserWithPivot } from '@/Types/DutyAssignment';
import { formatDate } from '@/Utils/dateTime';

/** Who holds a duty, behind its avatars: each holder's term can be edited from here. */
const props = withDefaults(defineProps<{
  holders: UserWithPivot[];
  canManage?: boolean;
  max?: number;
}>(), {
  max: 4,
});

const emit = defineEmits<{
  'assign': [];
  'edit-term': [user: UserWithPivot];
}>();

const open = ref(false);

const visibleHolders = computed(() => props.holders.slice(0, props.max));
const hiddenCount = computed(() => Math.max(0, props.holders.length - props.max));
const triggerLabel = computed(() => props.holders.length
  ? `${$t('Užima')}: ${props.holders.map(user => user.name).join(', ')}`
  : $t('Neužimta'));

const editTerm = (user: UserWithPivot) => {
  open.value = false;
  emit('edit-term', user);
};

const assign = () => {
  open.value = false;
  emit('assign');
};
</script>
