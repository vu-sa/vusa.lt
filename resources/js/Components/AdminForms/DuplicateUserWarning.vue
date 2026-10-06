<template>
  <Alert v-if="matches.length" class="border-status-attention-border bg-status-attention-surface text-status-attention">
    <TriangleAlert class="size-4 shrink-0" aria-hidden="true" />
    <AlertTitle>
      {{ $t('users.duplicate_warning_title') }}
    </AlertTitle>

    <AlertDescription class="text-foreground">
      <ul class="space-y-2">
        <li v-for="match in matches" :key="match.id" class="flex flex-wrap items-center gap-x-2 gap-y-1">
          <span class="font-medium">{{ match.name }}</span>

          <span class="text-muted-foreground">
            {{ match.tenants.length ? match.tenants.join(', ') : $t('users.no_tenant') }}
          </span>

          <code class="bg-secondary px-1 py-0.5 text-xs text-foreground">{{ match.email_masked }}</code>

          <Badge v-if="match.reason === 'email'" variant="destructive" class="text-xs">
            {{ $t('users.duplicate_reason_email') }}
          </Badge>

          <span class="ml-auto flex flex-wrap items-center gap-1">
            <Button v-if="showUseAction" size="sm" voice="sentence" variant="secondary" @click="$emit('use', match)">
              {{ $t('users.duplicate_use_profile') }}
            </Button>
            <Button
              v-else-if="match.can_manage"
              size="sm" voice="sentence" variant="outline" as="a"
              :href="route('users.edit', match.id)" target="_blank" rel="noopener noreferrer"
            >
              {{ $t('users.duplicate_open_profile') }}
            </Button>
            <span v-else class="text-muted-foreground">{{ $t('users.duplicate_contact_admins') }}</span>
          </span>
        </li>
      </ul>
    </AlertDescription>
  </Alert>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { TriangleAlert } from 'lucide-vue-next';

import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';

/** Advisory only: names repeat, so a possible match must never block creation. */
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

defineEmits<(event: 'use', match: DuplicateUserMatch) => void>();
</script>
