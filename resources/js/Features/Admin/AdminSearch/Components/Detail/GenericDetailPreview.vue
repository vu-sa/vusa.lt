<template>
  <DetailLayout
    :icon="hit.icon"
    :kicker="$t(COLLECTION_META[hit.collection].label)"
    :title="hit.title"
    :subtitle="hit.subtitle"
  >
    <template v-if="hit.badge" #badges>
      <Badge variant="outline">{{ hit.badge }}</Badge>
    </template>

    <template v-if="hit.href" #actions>
      <a v-if="isExternal" :href="hit.href" target="_blank" rel="noopener noreferrer">
        <Button size="sm">
          <ExternalLink class="mr-2 size-4" />
          {{ $t('Atidaryti') }}
        </Button>
      </a>
      <Link v-else :href="hit.href">
        <Button size="sm">
          <Eye class="mr-2 size-4" />
          {{ $t('Atidaryti') }}
        </Button>
      </Link>
    </template>

    <div v-if="hit.meta" class="divide-y rounded-lg border px-4">
      <DetailRow :label="$t('Data')" :value="hit.meta" />
    </div>
  </DetailLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Eye, ExternalLink } from 'lucide-vue-next';

import DetailLayout from './DetailLayout.vue';
import DetailRow from './DetailRow.vue';
import { COLLECTION_META, type NormalizedSearchHit } from '../../Utils/searchHitMappers';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';

const props = defineProps<{
  hit: NormalizedSearchHit;
}>();

const isExternal = computed(() => /^https?:\/\//.test(props.hit.href ?? ''));
</script>
