<template>
  <div class="flex-1 flex items-center justify-center p-8">
    <div class="text-center max-w-md space-y-4">
      <div class="mx-auto w-20 h-20 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-6">
        <IconSearch class="w-10 h-10 text-zinc-400" />
      </div>
      <div>
        <h3 class="font-medium text-lg mb-2">
          {{ searchQuery ? $t('search.keep_typing_to_see_results') : $t('search.start_search') }}
        </h3>
        <p class="text-muted-foreground text-sm">
          {{ searchQuery ? $t('search.please_enter_at_least_3_characters') : $t('search.search_across_your_selected_content_types') }}
        </p>
        <!-- Show selected content types -->
        <div v-if="selectedTypes.length > 0" class="mt-3">
          <p class="text-xs text-muted-foreground mb-2">
            {{ $t('search.searching_in') }}:
          </p>
          <div class="flex flex-wrap gap-1 justify-center">
            <Badge
              v-for="type in selectedTypes"
              :key="type.id"
              variant="outline"
              class="text-xs"
            >
              {{ type.icon }} {{ $t(type.name) }}
            </Badge>
          </div>
        </div>
      </div>
      <div v-if="!searchQuery" class="space-y-2">
        <!-- Recent searches if available -->
        <div v-if="recentSearches.length > 0">
          <p class="text-xs text-muted-foreground">
            {{ $t('search.recent_searches') }}:
          </p>
          <div class="flex flex-wrap gap-2 justify-center">
            <Button
              v-for="search in recentSearches.slice(0, 3)"
              :key="search"
              variant="ghost"
              size="sm"
              class="text-xs"
              @click="$emit('selectSearch', search)"
            >
              {{ search }}
            </Button>
          </div>
        </div>
        <!-- Search suggestions -->
        <div v-else>
          <p class="text-xs text-muted-foreground">
            {{ $t('search.suggestions') }}:
          </p>
          <div class="flex flex-wrap gap-2 justify-center">
            <Button
              v-for="suggestion in searchSuggestions"
              :key="suggestion"
              variant="ghost"
              size="sm"
              class="text-xs"
              @click="$emit('selectSearch', suggestion)"
            >
              {{ suggestion }}
            </Button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import IconSearch from '~icons/fluent/search32-regular';

interface ContentType {
  id: string;
  name: string;
  icon: string;
}

interface SearchEmptyStateProps {
  searchQuery: string;
  selectedTypes: ContentType[];
  recentSearches: string[];
}

const props = defineProps<SearchEmptyStateProps>();

defineEmits<(e: 'selectSearch', search: string) => void>();

const page = usePage();

const searchSuggestions = computed(() => {
  const { locale } = page.props.app;

  if (locale === 'lt') {
    return ['Parlamentas', 'Stipendijos', 'VU SA'];
  }
  else {
    return ['Parliament', 'Scholarships', 'VU SR'];
  }
});
</script>
