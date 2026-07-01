<template>
  <DetailLayout
    :icon="ResourceIcon"
    :kicker="$t('Išteklius')"
    :title="resource.name_lt || resource.name_en || $t('Be pavadinimo')"
    :subtitle="resource.category_name"
  >
    <template #badges>
      <Badge v-if="resource.tenant_shortname" variant="outline">{{ resource.tenant_shortname }}</Badge>
      <Badge :class="toneClass(resource.is_reservable ? 'success' : 'neutral')">
        {{ resource.is_reservable ? $t('Skolinamas') : $t('Neskolinamas') }}
      </Badge>
    </template>

    <template v-if="resource.image_url" #media>
      <Dialog>
        <DialogTrigger as-child>
          <button
            type="button"
            class="group relative size-20 overflow-hidden rounded-lg border bg-muted transition-shadow hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary/40"
            :aria-label="$t('Padidinti nuotrauką')"
          >
            <img :src="resource.image_url" :alt="imageAlt" class="size-full object-cover">
            <span class="absolute inset-0 flex items-center justify-center bg-black/0 opacity-0 transition-all group-hover:bg-black/30 group-hover:opacity-100">
              <Expand class="size-5 text-white" />
            </span>
          </button>
        </DialogTrigger>
        <DialogContent class="max-w-3xl p-2">
          <DialogTitle class="sr-only">{{ imageAlt || $t('Ištekliaus nuotrauka') }}</DialogTitle>
          <img :src="resource.image_url" :alt="imageAlt" class="max-h-[80vh] w-full rounded-md object-contain">
        </DialogContent>
      </Dialog>
    </template>

    <template #actions>
      <Link :href="route('resources.edit', resource.id)">
        <Button size="sm">
          <Pencil class="mr-2 size-4" />
          {{ $t('Redaguoti') }}
        </Button>
      </Link>
    </template>

    <div class="divide-y rounded-lg border px-4">
      <DetailRow v-if="resource.category_name" :label="$t('Kategorija')" :value="resource.category_name" />
      <DetailRow v-if="resource.location" :label="$t('Vieta')" :value="resource.location" />
      <DetailRow v-if="resource.capacity != null" :label="$t('Kiekis')" :value="resource.capacity" />
      <DetailRow :label="$t('Padalinys')" :value="resource.tenant_shortname || '—'" />
    </div>

    <div v-if="description" class="mt-6">
      <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
        {{ $t('Aprašymas') }}
      </h3>
      <p class="whitespace-pre-line text-sm text-muted-foreground">{{ description }}</p>
    </div>
  </DetailLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Expand, Pencil } from 'lucide-vue-next';

import DetailLayout from './DetailLayout.vue';
import DetailRow from './DetailRow.vue';
import { toneClass } from '../../Utils/searchBadges';

import { ResourceIcon } from '@/Components/icons';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogTitle, DialogTrigger } from '@/Components/ui/dialog';
import type { ResourceSearchResult } from '@/Shared/Search/types';

const props = defineProps<{
  resource: ResourceSearchResult;
}>();

const description = computed(() => props.resource.description_lt || props.resource.description_en);
const imageAlt = computed(() => props.resource.name_lt || props.resource.name_en || '');
</script>
