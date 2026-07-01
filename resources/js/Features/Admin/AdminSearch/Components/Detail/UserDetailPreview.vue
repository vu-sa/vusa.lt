<template>
  <DetailLayout
    :icon="UserIcon"
    :kicker="$t('Narys')"
    :title="user.name || $t('Be pavadinimo')"
    :subtitle="user.email"
  >
    <template #badges>
      <Badge v-if="user.tenant_shortname" variant="secondary">
        {{ user.tenant_shortname }}
      </Badge>
      <Badge v-if="user.is_active === false" variant="destructive">
        {{ $t('Ištrintas') }}
      </Badge>
    </template>

    <template #actions>
      <Link :href="route('users.show', user.id)">
        <Button size="sm">
          <Eye class="mr-2 size-4" />
          {{ $t('Peržiūrėti') }}
        </Button>
      </Link>
      <Link :href="route('users.edit', user.id)">
        <Button size="sm" variant="outline">
          <Pencil class="mr-2 size-4" />
          {{ $t('Redaguoti') }}
        </Button>
      </Link>
    </template>

    <div class="divide-y rounded-lg border px-4">
      <DetailRow :label="$t('Vardas')" :value="user.name || '—'" />
      <DetailRow v-if="user.email" :label="$t('El. paštas')" :value="user.email" />
      <DetailRow v-if="user.phone" :label="$t('Telefonas')" :value="user.phone" />
      <DetailRow
        v-if="user.current_duty_names?.length"
        :label="$t('Pareigos')"
        :value="user.current_duty_names.join(', ')"
      />
      <DetailRow :label="$t('Padalinys')" :value="user.tenant_shortname || '—'" />
    </div>
  </DetailLayout>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Eye, Pencil } from 'lucide-vue-next';

import DetailLayout from './DetailLayout.vue';
import DetailRow from './DetailRow.vue';

import { UserIcon } from '@/Components/icons';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import type { UserSearchResult } from '@/Shared/Search/types';

defineProps<{
  user: UserSearchResult;
}>();
</script>
