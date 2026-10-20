<template>
  <OverviewSection variant="home" :title="$t('responsibilities.people.title')" :icon="Users" :count="people.length" data-slot="responsibility-people">
    <p class="text-xs text-muted-foreground">
      {{ $t('responsibilities.people.description') }}
    </p>
    <ul v-if="people.length" class="divide-y divide-border">
      <li v-for="person in people" :key="person.id" class="flex min-h-14 items-center gap-3 py-2" data-testid="responsibility-person">
        <UserAvatar :user="(person as unknown as App.Entities.User)" :size="32" />
        <span class="min-w-0">
          <Link :href="route('users.show', person.id)" class="block text-sm font-medium text-foreground hover:underline">{{ person.name }}</Link>
          <span class="block text-xs text-muted-foreground">
            <template v-for="(duty, index) in person.duties" :key="duty.id">
              <template v-if="index">; </template>
              <InflectedDutyName :name="duty.name" /><template v-if="duty.institution"> · {{ duty.institution }}</template>
            </template>
          </span>
        </span>
      </li>
    </ul>
    <p v-else class="text-sm text-muted-foreground">
      {{ $t('responsibilities.people.empty') }}
    </p>
  </OverviewSection>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Users } from '@lucide/vue';

import type { ResponsibilityPerson } from './types';

import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';
import { OverviewSection } from '@/Components/Patterns';
import UserAvatar from '@/Components/Avatars/UserAvatar.vue';

defineProps<{
  people: ResponsibilityPerson[];
}>();
</script>
