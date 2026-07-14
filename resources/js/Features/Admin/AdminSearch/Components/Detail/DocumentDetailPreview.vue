<template>
  <DetailLayout
    :icon="DocumentIcon"
    :kicker="$t('Dokumentas')"
    :title="document.title || $t('Be pavadinimo')"
    :subtitle="document.content_type"
  >
    <template #badges>
      <Badge v-if="document.tenant_shortname" variant="outline">
        {{ document.tenant_shortname }}
      </Badge>
      <Badge :class="toneClass(document.is_active ? 'success' : 'neutral')">
        {{ document.is_active ? $t('Aktyvus') : $t('Neaktyvus') }}
      </Badge>
      <Badge v-if="document.language" variant="secondary">
        {{ document.language }}
      </Badge>
    </template>

    <template #actions>
      <Link :href="route('documents.show', document.id)">
        <Button size="sm">
          <Eye class="mr-2 size-4" />
          {{ $t('Peržiūrėti') }}
        </Button>
      </Link>
      <a
        v-if="document.anonymous_url"
        :href="document.anonymous_url"
        target="_blank"
        rel="noopener noreferrer"
      >
        <Button size="sm" variant="outline">
          <ExternalLink class="mr-2 size-4" />
          {{ $t('Atidaryti') }}
        </Button>
      </a>
    </template>

    <div class="divide-y rounded-lg border px-4">
      <DetailRow v-if="institutionName" :label="$t('Institucija')" :value="institutionName" />
      <DetailRow :label="$t('Padalinys')" :value="document.tenant_shortname || '—'" />
      <DetailRow v-if="document.content_type" :label="$t('Tipas')" :value="document.content_type" />
      <DetailRow v-if="document.language" :label="$t('Kalba')" :value="document.language" />
      <DetailRow :label="$t('Data')" :value="formatSearchDate(document.document_date) || '—'" />
    </div>

    <div v-if="document.summary" class="mt-6">
      <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
        {{ $t('Santrauka') }}
      </h3>
      <p class="whitespace-pre-line text-sm text-muted-foreground">
        {{ document.summary }}
      </p>
    </div>
  </DetailLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Eye, ExternalLink } from 'lucide-vue-next';

import { toneClass } from '../../Utils/searchBadges';
import { formatSearchDate } from '../../Utils/searchHitMappers';

import DetailLayout from './DetailLayout.vue';
import DetailRow from './DetailRow.vue';

import { DocumentIcon } from '@/Components/icons';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import type { DocumentSearchResult } from '@/Shared/Search/types';

const props = defineProps<{
  document: DocumentSearchResult;
}>();

const institutionName = computed(() => props.document.institution_name_lt || props.document.institution_name_en);
</script>
