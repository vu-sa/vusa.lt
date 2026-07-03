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
      <DetailRow :label="$t('Padalinys')" :value="user.tenant_shortname || '—'" />
    </div>

    <!-- Current duties -->
    <div v-if="currentDuties.length" class="mt-6">
      <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
        {{ $t('Pareigos') }}
      </h3>
      <div class="flex flex-wrap gap-1.5">
        <component
          :is="duty.id ? Link : 'span'"
          v-for="(duty, i) in currentDuties"
          :key="i"
          :href="duty.id ? route('duties.show', duty.id) : undefined"
          class="rounded-full border bg-muted/40 px-2.5 py-1 text-xs transition-colors"
          :class="duty.id ? 'hover:border-primary/40 hover:bg-primary/5' : ''"
        >
          {{ duty.name }}
        </component>
      </div>
    </div>

    <!-- Previous duties -->
    <div v-if="previousDuties.length" class="mt-6">
      <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
        {{ $t('Buvusios pareigos') }}
      </h3>
      <div class="flex flex-wrap gap-1.5">
        <component
          :is="duty.id ? Link : 'span'"
          v-for="(duty, i) in previousDuties"
          :key="i"
          :href="duty.id ? route('duties.show', duty.id) : undefined"
          class="rounded-full bg-muted/40 px-2.5 py-1 text-xs text-muted-foreground transition-colors"
          :class="duty.id ? 'hover:bg-primary/5 hover:text-foreground' : ''"
        >
          {{ duty.name }}
        </component>
      </div>
    </div>
  </DetailLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Eye, Pencil } from 'lucide-vue-next';

import DetailLayout from './DetailLayout.vue';
import DetailRow from './DetailRow.vue';

import { UserIcon } from '@/Components/icons';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import type { UserSearchResult } from '@/Shared/Search/types';

interface DutyChip {
  name: string;
  id?: string;
}

const props = defineProps<{
  user: UserSearchResult;
}>();

const currentDuties = computed<DutyChip[]>(() =>
  (props.user.current_duty_names ?? []).map((name, i) => ({ name, id: props.user.current_duty_ids?.[i] })),
);

const previousDuties = computed<DutyChip[]>(() =>
  (props.user.previous_duty_names ?? []).map((name, i) => ({ name, id: props.user.previous_duty_ids?.[i] })),
);
</script>
