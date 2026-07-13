<template>
  <SpotlightPopover
    :title="$t('Nauja: paieška iš pradžios puslapio')"
    :description="$t('Ieškokite posėdžių, institucijų, dokumentų ir kitų įrašų tiesiai iš pradžios puslapio arba bet kur paspaudę Ctrl+K.')"
    :is-dismissed="searchSpotlight.isDismissed.value"
    position="bottom"
    style="display: block; width: 100%;"
    @dismiss="searchSpotlight.dismiss"
  >
    <div class="relative w-full" data-tour="home-search">
      <Search class="pointer-events-none absolute left-4 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
      <input
        ref="inputRef"
        v-model="localValue"
        type="text"
        :placeholder="$t('Ieškoti visur...')"
        :aria-label="$t('Paieška')"
        class="h-11 w-full rounded-xl border bg-background/80 pl-11 pr-16 text-sm shadow-sm outline-none transition-shadow placeholder:text-muted-foreground/60 focus:ring-2 focus:ring-primary/20"
        @click="openSearch()"
        @input="handleInput"
        @keydown.enter.prevent="openSearch(localValue)"
      >
      <kbd
        class="pointer-events-none absolute right-3 top-1/2 hidden h-5 -translate-y-1/2 items-center gap-0.5 rounded border bg-muted px-1.5 font-mono text-[10px] font-medium text-muted-foreground sm:inline-flex"
      >
        {{ isMac ? '⌘' : 'Ctrl' }} K
      </kbd>
    </div>
  </SpotlightPopover>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Search } from 'lucide-vue-next';

import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';

const searchSpotlight = useFeatureSpotlight('home-search-v1', { position: 'bottom' });

const inputRef = ref<HTMLInputElement | null>(null);
const localValue = ref('');

const isMac = computed(() => typeof navigator !== 'undefined' && /Mac/i.test(navigator.platform));

/**
 * Navigate to the full search page, passing any typed text as a query param.
 * The search page reads ?q= from the URL and auto-focuses + searches.
 */
const openSearch = (initialQuery?: string) => {
  searchSpotlight.dismiss();
  const query = initialQuery?.trim() || undefined;
  router.visit(route('search.index', query ? { q: query } : undefined));
  localValue.value = '';
  inputRef.value?.blur();
};

const handleInput = () => {
  if (localValue.value.trim()) {
    openSearch(localValue.value);
  }
};
</script>
