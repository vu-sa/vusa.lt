<template>
  <Card
    class="group hover:shadow-md transition-all duration-200 cursor-pointer"
    @click="navigateToEdit"
  >
    <CardContent class="p-4">
      <div class="flex items-start gap-3">
        <!-- Image/Icon -->
        <div
          class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400 group-hover:bg-amber-500/15 transition-colors overflow-hidden"
        >
          <img
            v-if="resource.image_url"
            :src="resource.image_url"
            :alt="displayName"
            class="size-full object-cover"
          >
          <Package v-else class="size-6" />
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
          <!-- Title and Reservable Status -->
          <div class="flex items-start justify-between gap-2">
            <h3 class="font-medium text-foreground truncate group-hover:text-primary transition-colors">
              {{ displayName }}
            </h3>
            <Badge
              :variant="resource.is_reservable ? 'default' : 'secondary'"
              :class="[
                resource.is_reservable
                  ? 'bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500/20'
                  : 'bg-zinc-500/10 text-zinc-600 hover:bg-zinc-500/20'
              ]"
            >
              {{ resource.is_reservable ? $t('Skolinamas') : $t('Neskolinamas') }}
            </Badge>
          </div>

          <!-- Description -->
          <p
            v-if="displayDescription"
            class="text-sm text-muted-foreground line-clamp-2 mt-1"
          >
            {{ displayDescription }}
          </p>

          <!-- Meta info -->
          <div class="flex flex-wrap items-center gap-2 mt-2">
            <Badge v-if="resource.tenant_shortname" variant="outline" class="text-xs">
              <Building2 class="size-3 mr-1" />
              {{ resource.tenant_shortname }}
            </Badge>
            <Badge v-if="resource.category_name" variant="secondary" class="text-xs">
              <Tag class="size-3 mr-1" />
              {{ resource.category_name }}
            </Badge>
            <Badge v-if="resource.capacity" variant="outline" class="text-xs">
              <Hash class="size-3 mr-1" />
              {{ resource.capacity }} {{ $t('vnt.') }}
            </Badge>
            <Badge v-if="resource.location" variant="outline" class="text-xs">
              <MapPin class="size-3 mr-1" />
              {{ resource.location }}
            </Badge>
          </div>
        </div>
      </div>
    </CardContent>

    <!-- Actions Footer -->
    <CardFooter class="px-4 py-3 bg-muted/30 border-t justify-end gap-2">
      <Button
        variant="ghost"
        size="sm"
        @click.stop="navigateToEdit"
      >
        <Pencil class="size-4 mr-1" />
        {{ $t('Redaguoti') }}
      </Button>
    </CardFooter>
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import {
  Building2,
  Tag,
  Hash,
  MapPin,
  Pencil,
  Package,
} from 'lucide-vue-next';

import { Card, CardContent, CardFooter } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import type { ResourceSearchResult } from '@/Shared/Search/types';

interface Props {
  resource: ResourceSearchResult;
}

const props = defineProps<Props>();

// Get localized display name
const displayName = computed(() => {
  const locale = document.documentElement.lang || 'lt';
  return locale === 'en'
    ? (props.resource.name_en || props.resource.name_lt || $t('Be pavadinimo'))
    : (props.resource.name_lt || props.resource.name_en || $t('Be pavadinimo'));
});

// Get localized description
const displayDescription = computed(() => {
  const locale = document.documentElement.lang || 'lt';
  return locale === 'en'
    ? (props.resource.description_en || props.resource.description_lt)
    : (props.resource.description_lt || props.resource.description_en);
});

// Navigate to edit page
const navigateToEdit = () => {
  router.visit(route('resources.edit', props.resource.id));
};
</script>
