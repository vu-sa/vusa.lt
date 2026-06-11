<template>
  <Link
    :href="route('institutions.show', institution.id)"
    class="group flex items-center gap-2.5 rounded-lg border border-border bg-card px-2.5 py-2 transition-colors hover:bg-accent/50"
  >
    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-muted">
      <InstitutionIconFilled class="h-5 w-5 text-muted-foreground" />
    </div>
    <div class="min-w-0 flex-1 leading-tight">
      <p class="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">
        {{ $t('Institucija') }}
      </p>
      <p class="truncate text-sm font-medium text-foreground group-hover:text-primary">
        {{ institution.name }}
      </p>
    </div>
    <ChevronRight class="h-4 w-4 shrink-0 text-muted-foreground group-hover:text-primary" />
  </Link>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronRight } from 'lucide-vue-next';

import { InstitutionIconFilled } from '@/Components/icons';

defineProps<{
  institution: { id: string | number; name: string; short_name?: string | null };
}>();
</script>
