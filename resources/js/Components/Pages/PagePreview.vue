<template>
  <PreviewPane
    entity-type="page"
    :eyebrow="$t('Puslapis')"
    :title="page.title || $t('Be pavadinimo')"
    :href="trashed ? undefined : editHref"
  >
    <template #status>
      <slot name="status">
        <StatusBadge :status="pageStatus(page)" />
      </slot>
    </template>

    <template #actions>
      <slot name="actions">
        <Button as-child variant="brand" size="sm">
          <Link :href="editHref">
            {{ $t('Redaguoti puslapį') }}
          </Link>
        </Button>
        <Button v-if="publicUrl" as-child variant="outline" size="sm">
          <a :href="publicUrl" target="_blank" rel="noopener noreferrer">
            <ExternalLink aria-hidden="true" />
            {{ $t('Atidaryti viešąjį puslapį') }}
          </a>
        </Button>
      </slot>
    </template>

    <p v-if="description" class="text-sm text-muted-foreground">
      {{ description }}
    </p>

    <PreviewFacts>
      <PreviewFact :label="$t('Padalinys')" :value="page.tenant_shortname ?? page.tenant_name" />
      <PreviewFact :label="$t('Kalba')" :value="page.lang === 'en' ? 'English' : 'Lietuvių'" />
      <PreviewFact v-if="page.permalink" :label="$t('Nuoroda')" :value="page.permalink" />
      <PreviewFact v-if="date" :label="dateLabel ?? $t('Sukurta')" :value="date" tabular />
    </PreviewFacts>
  </PreviewPane>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ExternalLink } from '@lucide/vue';
import { computed } from 'vue';

import { pageStatus } from './pageStatus';

import { PreviewFact, PreviewFacts, PreviewPane, StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { resolveTenantSubdomain } from '@/Composables/useTenantSubdomain';
import { stripHtml } from '@/Features/Admin/AdminSearch/Utils/html';
import type { PageSearchResult } from '@/Shared/Search/types';

const props = defineProps<{
  page: PageSearchResult;
  /** A trashed page has no editor to open. */
  trashed?: boolean;
  dateLabel?: string;
  date?: string;
}>();

const editHref = computed(() => route('pages.edit', props.page.id));
const description = computed(() => stripHtml(props.page.meta_description));

const publicUrl = computed(() => {
  if (!props.page.permalink || props.page.is_active === false) {
    return undefined;
  }

  return route('page', {
    subdomain: resolveTenantSubdomain(props.page.tenant_id),
    lang: props.page.lang ?? 'lt',
    permalink: props.page.permalink,
  });
});
</script>
