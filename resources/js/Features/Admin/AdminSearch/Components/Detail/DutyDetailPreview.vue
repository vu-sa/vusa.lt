<template>
  <DetailLayout
    :icon="DutyIcon"
    :kicker="$t('Pareigybė')"
    :title="duty.name_lt || duty.name_en || $t('Be pavadinimo')"
    :subtitle="institutionName"
  >
    <template #badges>
      <Badge v-if="duty.tenant_shortname" :variant="isExternal ? 'default' : 'outline'">
        {{ duty.tenant_shortname }}
      </Badge>
    </template>

    <template #actions>
      <Link :href="route('duties.show', duty.id)">
        <Button size="sm">
          <Eye class="mr-2 size-4" />
          {{ $t('Peržiūrėti') }}
        </Button>
      </Link>
      <Link :href="route('duties.edit', duty.id)">
        <Button size="sm" variant="outline">
          <Pencil class="mr-2 size-4" />
          {{ $t('Redaguoti') }}
        </Button>
      </Link>
    </template>

    <!-- Clear cross-tenant notice -->
    <div
      v-if="isExternal"
      class="mb-4 flex items-start gap-2.5 rounded-lg border border-blue-200 bg-blue-50 px-3.5 py-2.5 text-sm text-blue-800 dark:border-blue-900 dark:bg-blue-950/50 dark:text-blue-200"
    >
      <Building2 class="mt-0.5 size-4 shrink-0" />
      <span>
        {{ $t('Ši pareigybė priklauso kitam padaliniui') }}<template v-if="duty.tenant_shortname"> ({{ duty.tenant_shortname }})</template>{{ $t('. Galite valdyti tik savo padalinio atstovus.') }}
      </span>
    </div>

    <div class="divide-y rounded-lg border px-4">
      <DetailRow v-if="institutionName" :label="$t('Institucija')" :value="institutionName" />
      <DetailRow :label="$t('Padalinys')" :value="duty.tenant_shortname || '—'" />
      <DetailRow v-if="duty.email" :label="$t('El. paštas')" :value="duty.email" />
    </div>

    <!-- Types -->
    <div v-if="duty.type_titles?.length" class="mt-6">
      <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
        {{ $t('Tipai') }}
      </h3>
      <div class="flex flex-wrap gap-1.5">
        <Badge v-for="(title, i) in duty.type_titles" :key="i" variant="secondary">{{ title }}</Badge>
      </div>
    </div>

    <!-- Current members -->
    <div class="mt-6">
      <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
        {{ $t('Nariai') }}
      </h3>
      <div v-if="currentMembers.length" class="flex flex-wrap items-center gap-1.5">
        <span
          v-for="(name, i) in currentMembers"
          :key="i"
          class="inline-flex items-center gap-1.5 rounded-full border bg-muted/40 py-1 pl-1 pr-2.5 text-xs"
        >
          <Avatar class="size-5">
            <AvatarFallback class="text-[9px]">{{ initials(name) }}</AvatarFallback>
          </Avatar>
          {{ name }}
        </span>
        <span v-if="hiddenCurrentCount > 0" class="text-xs font-medium text-muted-foreground">
          +{{ hiddenCurrentCount }}
        </span>
      </div>
      <p v-else class="text-sm text-muted-foreground">
        {{ $t('Šiuo metu neužimta') }}
      </p>
    </div>

    <!-- A few previous members -->
    <div v-if="duty.previous_user_names?.length" class="mt-6">
      <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
        {{ $t('Buvę nariai') }}
      </h3>
      <div class="flex flex-wrap gap-1.5">
        <span
          v-for="(name, i) in duty.previous_user_names"
          :key="i"
          class="rounded-full bg-muted/40 px-2.5 py-1 text-xs text-muted-foreground"
        >
          {{ name }}
        </span>
      </div>
    </div>
  </DetailLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Building2, Eye, Pencil } from 'lucide-vue-next';

import DetailLayout from './DetailLayout.vue';
import DetailRow from './DetailRow.vue';

import { DutyIcon } from '@/Components/icons';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
import type { DutySearchResult } from '@/Shared/Search/types';

/** Soft cap on member chips before collapsing into a "+N" indicator. */
const MEMBER_LIMIT = 8;

const props = defineProps<{
  duty: DutySearchResult;
  /** Whether the duty belongs to a tenant outside the user's own scope. */
  isExternal?: boolean;
}>();

const institutionName = computed(() => props.duty.institution_name_lt || props.duty.institution_name_en);

const currentMembers = computed(() => (props.duty.current_user_names ?? []).slice(0, MEMBER_LIMIT));
const hiddenCurrentCount = computed(() => {
  const total = props.duty.current_users_count ?? props.duty.current_user_names?.length ?? 0;
  return Math.max(0, total - currentMembers.value.length);
});

const initials = (name: string): string => name.split(' ').map(p => p[0]).slice(0, 2).join('').toUpperCase();
</script>
