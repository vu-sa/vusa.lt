<template>
  <SectionCard
    :title="$t('Kitos pareigos institucijoje')"
    :icon="Users"
    :count="duties.length || undefined"
  >
    <div class="space-y-1">
      <Link
        v-for="duty in duties"
        :key="duty.id"
        :href="route('duties.show', duty.id)"
        :class="[
          'flex items-center gap-3 rounded-md border border-transparent px-2 py-2',
          interactiveCardClass,
        ]"
      >
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-medium text-foreground">
            {{ duty.name }}
          </p>
          <p class="text-xs text-muted-foreground">
            {{ (duty.current_users?.length ?? 0) }}<template v-if="duty.places_to_occupy">
              / {{ duty.places_to_occupy }}
            </template>
            {{ $t('užimta') }}
          </p>
        </div>
        <UsersAvatarGroup
          v-if="duty.current_users?.length"
          :users="duty.current_users"
          :max="3"
          :size="24"
          class="shrink-0"
        />
        <ChevronRight class="h-4 w-4 shrink-0 text-muted-foreground" />
      </Link>
    </div>
  </SectionCard>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Users, ChevronRight } from 'lucide-vue-next';

import { SectionCard } from '@/Components/ui/section-card';
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import { interactiveCardClass } from '@/Utils/interactiveCard';

export interface OtherDuty {
  id: string | number;
  name: string;
  places_to_occupy?: number | null;
  current_users?: App.Entities.User[];
}

defineProps<{
  duties: OtherDuty[];
}>();
</script>
