<template>
  <div
    v-if="changes.length > 0 && !spotlight.isDismissed.value"
    class="flex flex-wrap items-center gap-x-4 gap-y-2 border-y border-border py-3"
    role="status"
    data-slot="access-change-band"
  >
    <KeyRound class="size-5 shrink-0 text-muted-foreground" aria-hidden="true" />
    <p class="min-w-0 flex-1 text-sm font-medium text-foreground" data-testid="access-change-text">
      {{ text }}
    </p>
    <div class="flex shrink-0 items-center gap-2">
      <Button as-child variant="brand-outline" size="sm" class="pointer-coarse:h-11">
        <Link :href="`${route('profile.roles')}#history`" @click="spotlight.dismiss()">{{ $t('access.band.view') }}</Link>
      </Button>
      <Button variant="ghost" size="sm" class="pointer-coarse:h-11" data-testid="access-change-dismiss" @click="spotlight.dismiss()">
        {{ $t('access.band.dismiss') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { KeyRound } from 'lucide-vue-next';
import { computed } from 'vue';

import type { HomeAccessChange } from './types';

import { Button } from '@/Components/ui/button';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';

const props = defineProps<{
  /** Newest first, as `GetRecentAccessChanges` returns them. */
  changes: HomeAccessChange[];
}>();

// Keyed by the newest change, so a later change brings the band back after it was dismissed.
const spotlight = useFeatureSpotlight(`access-change-band-${props.changes[0]?.effectiveOn ?? 'none'}`);

const text = computed(() => {
  const [only] = props.changes;

  if (props.changes.length !== 1) {
    return $t('access.band.many', { count: String(props.changes.length) });
  }

  return $t(`access.band.${only.kind}`, { date: only.effectiveOn, duty: only.dutyName });
});
</script>
