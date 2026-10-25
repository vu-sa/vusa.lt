<template>
  <PreviewPane
    entity-type="document"
    :eyebrow="$t('Dokumentas')"
    :title="document.title || $t('Be pavadinimo')"
    :href="showHref"
    :subtitle="document.content_type"
  >
    <template v-if="!document.is_active" #badges>
      <StatusBadge :status="INACTIVE" />
    </template>

    <template #actions>
      <slot name="actions">
        <Button as-child variant="brand" size="sm">
          <Link :href="showHref">
            {{ $t('Atidaryti dokumentą') }}
          </Link>
        </Button>
        <Button v-if="document.anonymous_url" as-child variant="outline" size="sm">
          <a :href="document.anonymous_url" target="_blank" rel="noopener noreferrer">
            <ExternalLink aria-hidden="true" />
            {{ $t('Atidaryti failą') }}
          </a>
        </Button>
      </slot>
    </template>

    <PreviewFacts>
      <PreviewFact v-if="institutionName" :label="$t('Institucija')" :value="institutionName" />
      <PreviewFact :label="$t('Padalinys')" :value="document.tenant_shortname" />
      <PreviewFact v-if="document.language" :label="$t('Kalba')" :value="document.language" />
      <PreviewFact :label="$t('Data')" :value="documentDate" tabular />
    </PreviewFacts>

    <PreviewSection v-if="document.summary" :title="$t('Santrauka')">
      <p class="whitespace-pre-line text-sm text-muted-foreground">
        {{ document.summary }}
      </p>
    </PreviewSection>
  </PreviewPane>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { CircleSlash, ExternalLink } from '@lucide/vue';
import { computed } from 'vue';

import { PreviewFact, PreviewFacts, PreviewPane, PreviewSection, StatusBadge } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import type { StatusPresentation } from '@/Constants/statuses';
import type { DocumentSearchResult } from '@/Shared/Search/types';
import { formatDate } from '@/Utils/dateTime';

// An active document is the healthy default and gets no badge.
const INACTIVE: StatusPresentation = { label: 'Neaktyvus', role: 'neutral', icon: CircleSlash };

const props = defineProps<{
  document: DocumentSearchResult;
}>();

const showHref = computed(() => route('documents.show', props.document.id));
const institutionName = computed(() => props.document.institution_name_lt || props.document.institution_name_en);
// Search documents store Unix seconds.
const documentDate = computed(() => (props.document.document_date ? formatDate(props.document.document_date * 1000) : null));
</script>
