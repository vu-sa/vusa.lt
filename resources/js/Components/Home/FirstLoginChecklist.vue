<template>
  <section
    v-if="!spotlight.isDismissed.value"
    class="flex flex-col gap-2"
    data-slot="first-login-checklist"
    :aria-labelledby="headingId"
  >
    <div class="flex items-center justify-between gap-4 border-b border-border pb-3">
      <h2 :id="headingId" class="flex items-center gap-2 text-sm font-bold uppercase tracking-[0.18em] text-foreground">
        <ListChecks class="size-4 shrink-0 text-brand" aria-hidden="true" />
        {{ $t('onboarding.title') }}
      </h2>
      <div class="flex items-center gap-2">
        <span class="text-xs text-muted-foreground" data-testid="checklist-progress">
          {{ $t('onboarding.progress', { done: String(checklist.doneCount), total: String(checklist.items.length) }) }}
        </span>
        <Button variant="ghost" size="sm" class="pointer-coarse:h-11" data-testid="checklist-dismiss" @click="spotlight.dismiss()">
          {{ $t('onboarding.dismiss') }}
        </Button>
      </div>
    </div>

    <ul class="divide-y divide-border border-y border-border">
      <li
        v-for="item in checklist.items"
        :key="item.key"
        class="flex items-center gap-3 px-1 py-3 pointer-coarse:py-4"
        :data-item="item.key"
        :data-done="item.done"
      >
        <CircleCheck v-if="item.done" class="size-5 shrink-0 text-status-success" aria-hidden="true" />
        <Circle v-else class="size-5 shrink-0 text-muted-foreground" aria-hidden="true" />

        <span class="min-w-0 flex-1">
          <span :class="['block font-medium', item.done ? 'text-muted-foreground' : 'text-foreground']">
            {{ $t(`onboarding.items.${item.key}.label`) }}
          </span>
          <span v-if="!item.done" class="block text-sm text-muted-foreground">
            {{ $t(`onboarding.items.${item.key}.hint`) }}
          </span>
        </span>

        <span v-if="item.done" class="shrink-0 text-sm text-muted-foreground">{{ $t('onboarding.done') }}</span>
        <Button v-else-if="item.href === null" variant="outline" size="sm" class="shrink-0 pointer-coarse:h-11" @click="emit('record-meeting')">
          {{ $t(`onboarding.items.${item.key}.action`) }}
        </Button>
        <Button v-else as-child variant="outline" size="sm" class="shrink-0 pointer-coarse:h-11">
          <Link :href="item.href">
            {{ $t(`onboarding.items.${item.key}.action`) }}
          </Link>
        </Button>
      </li>
    </ul>
  </section>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Circle, CircleCheck, ListChecks } from 'lucide-vue-next';
import { useId } from 'vue';

import type { HomeChecklist } from './types';

import { Button } from '@/Components/ui/button';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';

defineProps<{
  checklist: HomeChecklist;
}>();

const emit = defineEmits<{
  'record-meeting': [];
}>();

const headingId = useId();

// A checklist is dismissed the way a spotlight is: once, per person, remembered on the server.
const spotlight = useFeatureSpotlight('checklist-first-login-v1');
</script>
