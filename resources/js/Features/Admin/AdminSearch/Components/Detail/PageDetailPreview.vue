<template>
  <DetailLayout
    :icon="PageIcon"
    :kicker="$t('Puslapis')"
    :title="page.title || $t('Be pavadinimo')"
    :subtitle="page.meta_description"
  >
    <template #badges>
      <Badge v-if="page.lang" variant="outline" class="uppercase">
        {{ page.lang }}
      </Badge>
      <Badge v-if="page.category_name" variant="secondary">
        {{ page.category_name }}
      </Badge>
      <Badge v-if="page.tenant_name" variant="secondary">
        {{ page.tenant_name }}
      </Badge>
    </template>

    <template #actions>
      <a v-if="page.permalink" :href="page.permalink" target="_blank" rel="noopener noreferrer">
        <Button size="sm">
          <ExternalLink class="mr-2 size-4" />
          {{ $t('Atidaryti viešąjį puslapį') }}
        </Button>
      </a>
      <Link :href="route('pages.edit', page.id)">
        <Button size="sm" variant="outline">
          <Pencil class="mr-2 size-4" />
          {{ $t('Redaguoti') }}
        </Button>
      </Link>
    </template>

    <div class="divide-y rounded-lg border px-4">
      <DetailRow :label="$t('Pavadinimas')" :value="page.title || '—'" />
      <DetailRow v-if="page.meta_description" :label="$t('Meta aprašymas')" :value="page.meta_description" />
      <DetailRow :label="$t('Kalba')" :value="page.lang?.toUpperCase() || '—'" />
      <DetailRow v-if="page.category_name" :label="$t('Kategorija')" :value="page.category_name" />
      <DetailRow v-if="page.tenant_name" :label="$t('Padalinys')" :value="page.tenant_name" />
      <DetailRow v-if="page.permalink" :label="$t('Nuoroda')" :value="page.permalink" />
    </div>
  </DetailLayout>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ExternalLink, Pencil } from 'lucide-vue-next';

import DetailLayout from './DetailLayout.vue';
import DetailRow from './DetailRow.vue';

import { PageIcon } from '@/Components/icons';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import type { PageSearchResult } from '@/Shared/Search/types';

defineProps<{
  page: PageSearchResult;
}>();
</script>
