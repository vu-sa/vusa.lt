<template>
  <UsersAvatarGroup
    v-if="holders.length || canManage"
    v-model:open="open"
    :users="holders"
    :max
    size="xs"
    expandable
    show-when-empty
    hide-avatars-on-mobile
    :popover-title="$t('Užima')"
    :trigger-label
    trigger-slot="duty-holders-trigger"
    popover-slot="duty-holders-popover"
  >
    <template #trigger-prefix>
      <span>{{ $t('Nariai') }} · {{ holders.length }}</span>
    </template>
    <template #trigger-empty>
      <span class="flex size-6 items-center justify-center border border-dashed border-muted-foreground/50">
        <UserPlus class="size-3.5" aria-hidden="true" />
      </span>
    </template>
    <template #trigger-suffix>
      <span v-if="holders.length === 1" class="hidden max-w-40 truncate sm:inline">{{ holders[0]!.name }}</span>
    </template>

    <template #user-additional-info="{ user }">
      <span v-if="user.pivot?.start_date" class="block text-xs text-muted-foreground tabular-nums">
        {{ $t('nuo') }} {{ formatDate(user.pivot.start_date) }}
      </span>
    </template>

    <template #user-actions="{ user }">
      <Button
        v-if="canManage && user.pivot?.id"
        type="button"
        variant="ghost"
        size="sm"
        class="shrink-0 pointer-coarse:min-h-11"
        :aria-label="`${$t('Redaguoti pareigybės laikotarpį')}: ${user.name}`"
        :title="$t('Redaguoti pareigybės laikotarpį')"
        @click="editTerm(user)"
      >
        <CalendarCog class="size-4 text-muted-foreground" aria-hidden="true" />
        {{ $t('Keisti laikotarpį') }}
      </Button>
    </template>

    <template #popover-empty>
      <p class="px-3 py-3 text-sm text-status-attention">
        {{ $t('Neužimta') }}
      </p>
    </template>

    <template #popover-footer>
      <PopoverFooterAction v-if="canManage" voice="sentence" class="border-t border-border" data-slot="duty-holders-assign" @click="assign">
        <UserPlus class="size-4" aria-hidden="true" />
        {{ $t('Pridėti asmenį') }}
      </PopoverFooterAction>
    </template>
  </UsersAvatarGroup>
</template>

<script setup lang="ts">
import { CalendarCog, UserPlus } from '@lucide/vue';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import { Button } from '@/Components/ui/button';
import { PopoverFooterAction } from '@/Components/ui/popover';
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
  'opened': [];
}>();

const open = ref(false);
watch(open, (value) => {
  if (value) {
    emit('opened');
  }
});

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
