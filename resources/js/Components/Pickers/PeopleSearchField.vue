<template>
  <div class="space-y-2">
    <ul v-if="modelValue.length > 0" class="flex flex-wrap gap-1.5" data-testid="people-search-chips">
      <li
        v-for="person in modelValue"
        :key="person.id"
        class="flex min-h-9 items-center gap-1.5 border border-border bg-secondary/40 pl-1.5 text-sm"
      >
        <UserAvatar :user="person" :size="20" class="shrink-0" />
        <span class="max-w-40 truncate">{{ person.name }}</span>
        <button
          type="button"
          class="u-touch flex size-9 items-center justify-center text-muted-foreground hover:text-foreground focus-visible:outline-2 focus-visible:outline-ring"
          :aria-label="$t('Pašalinti :name', { name: person.name })"
          @click="remove(person.id)"
        >
          <X class="size-3.5" />
        </button>
      </li>
    </ul>
    <UserSearchField
      :model-value="null"
      :purpose
      :input-id
      :aria-label
      :placeholder="placeholder ?? $t('Ieškok pagal vardą…')"
      :taken-ids="modelValue.map(person => person.id)"
      :taken-label="$t('Jau pasirinktas')"
      @update:model-value="add"
    />
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { X } from '@lucide/vue';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';

import UserSearchField, { type MemberHit, type UserSearchPurpose } from './UserSearchField.vue';

/** Several people, searched as you type: nothing is preloaded, so no page ships the users table. */
withDefaults(defineProps<{
  purpose?: UserSearchPurpose;
  inputId?: string;
  ariaLabel?: string;
  placeholder?: string;
}>(), {
  purpose: 'involve',
});

const modelValue = defineModel<MemberHit[]>({ required: true });

const add = (person: MemberHit | null) => {
  if (person && !modelValue.value.some(picked => picked.id === person.id)) {
    modelValue.value = [...modelValue.value, person];
  }
};

const remove = (id: string) => {
  modelValue.value = modelValue.value.filter(person => person.id !== id);
};
</script>
