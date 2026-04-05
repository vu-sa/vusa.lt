<template>
  <CommandItem
    :value="itemValue"
    class="group cursor-pointer rounded-lg px-3 py-2.5 transition-colors hover:bg-accent data-[highlighted]:bg-accent"
    @select="handleSelect"
  >
    <div class="flex items-center gap-3 w-full min-w-0">
      <!-- Icon container with background -->
      <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400 group-hover:bg-indigo-500/15 dark:group-hover:bg-indigo-500/25 transition-colors">
        <Building2 class="size-4" />
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
          <span class="font-medium truncate text-sm group-hover:text-foreground transition-colors">
            {{ displayName }}
          </span>
          <span
            v-if="institution.alias"
            class="inline-flex items-center max-w-40 truncate whitespace-nowrap rounded-full px-1.5 py-0.5 text-[10px] font-medium ring-1 ring-inset bg-zinc-500/10 text-zinc-600 ring-zinc-500/20 dark:bg-zinc-500/20 dark:text-zinc-400"
            :title="institution.alias"
          >
            {{ institution.alias }}
          </span>
        </div>
        <div class="flex items-center gap-1.5 text-xs text-muted-foreground mt-0.5">
          <span v-if="institution.tenant_shortname" class="shrink-0 font-medium">{{ institution.tenant_shortname }}</span>
          <span v-if="institution.tenant_shortname && institution.email" class="text-muted-foreground/40">•</span>
          <span v-if="institution.email" class="truncate">{{ institution.email }}</span>
        </div>
      </div>

      <!-- Arrow indicator -->
      <ChevronRight class="size-4 text-muted-foreground/40 opacity-0 group-hover:opacity-100 transition-opacity shrink-0" />
    </div>
  </CommandItem>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t, getActiveLanguage } from 'laravel-vue-i18n';
import { ChevronRight, Building2 } from 'lucide-vue-next';

import { CommandItem } from '@/Components/ui/command';
import { useCommandPalette, type RecentItem } from '@/Composables/useCommandPalette';
import type { InstitutionSearchResult } from '@/Composables/useAdminSearch';

const props = defineProps<{
  institution: InstitutionSearchResult;
}>();

const { close, addRecentItem } = useCommandPalette();

const itemValue = computed(() => `institution-${props.institution.id}`);

const displayName = computed(() => {
  const lang = getActiveLanguage();
  if (lang === 'en' && props.institution.name_en) return props.institution.name_en;
  return props.institution.name_lt || props.institution.short_name_lt || $t('Be pavadinimo');
});

const handleSelect = () => {
  // Add to recent items
  addRecentItem({
    id: props.institution.id,
    type: 'institution',
    title: displayName.value,
    href: route('institutions.show', props.institution.id),
  } as Omit<RecentItem, 'timestamp'>);

  // Navigate to show page (institutions have a show page, not edit directly)
  close();
  router.visit(route('institutions.show', props.institution.id));
};
</script>
