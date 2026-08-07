<template>
  <div v-if="matches.length" class="rounded-md border border-amber-300 bg-amber-50 p-3 text-xs dark:border-amber-800 dark:bg-amber-950">
    <p class="mb-2 flex items-center gap-1.5 font-medium text-amber-900 dark:text-amber-200">
      <TriangleAlert class="size-3.5 shrink-0" />
      {{ $t('users.duplicate_warning_title') }}
    </p>

    <ul class="space-y-1.5">
      <li v-for="match in matches" :key="match.id" class="flex flex-wrap items-center gap-x-2 gap-y-1">
        <span class="font-medium text-amber-900 dark:text-amber-100">{{ match.name }}</span>

        <span v-if="match.tenants.length" class="text-amber-700 dark:text-amber-300">
          {{ match.tenants.join(', ') }}
        </span>
        <span v-else class="text-amber-700 dark:text-amber-300">
          {{ $t('users.no_tenant') }}
        </span>

        <code class="rounded bg-amber-100 px-1 py-0.5 text-[10px] text-amber-900 dark:bg-amber-900 dark:text-amber-100">
          {{ match.email_masked }}
        </code>

        <Badge v-if="match.reason === 'email'" variant="destructive" class="text-[10px]">
          {{ $t('users.duplicate_reason_email') }}
        </Badge>

        <span class="ml-auto flex items-center gap-1">
          <Button v-if="showUseAction" size="xs" variant="secondary" @click="$emit('use', match)">
            {{ $t('users.duplicate_use_profile') }}
          </Button>
          <Button v-else-if="match.can_manage" size="xs" variant="outline" as="a"
            :href="route('users.edit', match.id)" target="_blank" rel="noopener noreferrer">
            {{ $t('users.duplicate_open_profile') }}
          </Button>
          <!-- Without can_manage there is nothing this admin can do to the record
               directly, so point them at the people who can rather than at a 403. -->
          <span v-else class="text-amber-700 dark:text-amber-300">
            {{ $t('users.duplicate_contact_admins') }}
          </span>
        </span>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { TriangleAlert } from 'lucide-vue-next';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';

/**
 * Warns that the person being created may already have an account, usually in a unit
 * this admin cannot see. Advisory only — names genuinely repeat, and a wrong guess
 * must never block a legitimate create.
 */
export interface DuplicateUserMatch {
  id: string;
  name: string;
  /** `name_variant` = one name is the other plus a middle name. */
  reason: 'email' | 'email_local_part' | 'name' | 'name_variant';
  tenants: string[];
  duties_count: number;
  email_masked: string;
  can_manage: boolean;
}

defineProps<{
  matches: DuplicateUserMatch[];
  /** Show "use this profile" instead of a link — only where the caller can switch to an existing user. */
  showUseAction?: boolean;
}>();

defineEmits<{
  (event: 'use', match: DuplicateUserMatch): void;
}>();
</script>
