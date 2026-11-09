<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { FileText, ListChecks } from '@lucide/vue';

import type { GlossaryTermData } from '@/Types/learning';
import { safeHref } from '@/Utils/safeHref';

defineProps<{ steps: GlossaryTermData['steps'] }>();
</script>

<template>
  <div v-if="steps?.length" class="mt-3">
    <h3 class="text-xs font-semibold text-muted-foreground">{{ $t('learning.glossary.where_used') }}</h3>
    <ul class="flex flex-wrap gap-x-4">
      <li v-for="step in steps" :key="step.id" class="min-w-0">
        <a v-if="step.isDocument && safeHref(step.url)" :href="safeHref(step.url)" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center gap-2 text-sm underline underline-offset-4">
          <FileText class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />{{ step.title }}
        </a>
        <Link v-else :href="route('learningSteps.show', step.id)" class="inline-flex min-h-11 items-center gap-2 text-sm underline underline-offset-4">
          <ListChecks class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />{{ step.title }}
        </Link>
      </li>
    </ul>
  </div>
</template>
