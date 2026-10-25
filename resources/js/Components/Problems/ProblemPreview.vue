<template>
  <PreviewPane
    entity-type="problem"
    :eyebrow="$t('Problema')"
    :title="title"
    :href="trashed ? undefined : showHref"
    :subtitle="problem.tenant?.shortname"
  >
    <template v-if="status" #status>
      <StatusBadge :status />
    </template>

    <template #actions>
      <slot name="actions">
        <Button as-child variant="brand" size="sm">
          <Link :href="showHref">
            {{ $t('Atidaryti problemą') }}
          </Link>
        </Button>
        <Button v-if="problem.can_update" as-child variant="outline" size="sm">
          <Link :href="route('problems.edit', problem.id)">
            {{ $t('Redaguoti') }}
          </Link>
        </Button>
      </slot>
    </template>

    <PreviewFacts>
      <PreviewFact :label="$t('Atsakingas')" :value="problem.responsible_user?.name" />
      <PreviewFact :label="$t('Įvykio data')" :value="problem.occurred_at ? formatDate(problem.occurred_at) : null" tabular />
      <PreviewFact v-if="problem.resolved_at" :label="$t('Išspręsta')" :value="formatDate(problem.resolved_at)" tabular />
      <PreviewFact v-if="problem.categories?.length" :label="$t('Kategorijos')" :value="problem.categories.map(category => getTranslatedValue(category.name)).join(', ')" />
    </PreviewFacts>

    <PreviewSection v-if="problem.institutions?.length" :title="$tChoice('entities.institution.model', 2)">
      <PreviewChipList :items="institutions" />
    </PreviewSection>

    <PreviewSection v-if="description" :title="$t('Aprašymas')">
      <!-- Sanitized on write (HtmlSanitizerService via the model). -->
      <!-- eslint-disable-next-line vue/no-v-html -->
      <div class="line-clamp-6 text-sm text-foreground/90" v-html="description" />
    </PreviewSection>
  </PreviewPane>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { computed } from 'vue';

import { PreviewChipList, PreviewFact, PreviewFacts, PreviewPane, PreviewSection, StatusBadge, type PreviewChipItem } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { problemStatuses, statusFor } from '@/Constants/statuses';
import { formatDate } from '@/Utils/dateTime';

const props = defineProps<{
  problem: App.Entities.Problem & { can_update?: boolean };
  /** A trashed problem has no page to open. */
  trashed?: boolean;
}>();

const showHref = computed(() => route('problems.show', props.problem.id));
const title = computed(() => getTranslatedValue(props.problem.title) || $t('Be pavadinimo'));
const description = computed(() => getTranslatedValue(props.problem.description));
const status = computed(() => statusFor(problemStatuses, props.problem.status));

const institutions = computed<PreviewChipItem[]>(() => (props.problem.institutions ?? []).map(institution => ({
  id: institution.id,
  name: getTranslatedValue(institution.name),
  href: route('institutions.show', institution.id),
})));
</script>
