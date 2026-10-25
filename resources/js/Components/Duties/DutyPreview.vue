<template>
  <PreviewPane
    entity-type="duty"
    :eyebrow="$t('Pareigybė')"
    :title="duty.name"
    :href="showHref"
    :subtitle="duty.institution?.name"
  >
    <template #title>
      <InflectedDutyName :name="duty.name" />
    </template>

    <template #badges>
      <InactiveInstitutionBadge :active="duty.institution?.isActive" />
    </template>

    <template v-if="$slots.status" #status>
      <slot name="status" />
    </template>

    <template #actions>
      <slot name="actions">
        <Button as-child variant="brand" size="sm">
          <Link :href="showHref">
            {{ $t('Atidaryti pareigybę') }}
          </Link>
        </Button>
      </slot>
    </template>

    <p v-if="isExternal" class="flex items-start gap-2.5 border-l-2 border-status-info bg-status-info-surface px-4 py-3 text-sm text-status-info">
      <Building2 class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
      <span>
        {{ $t('Ši pareigybė priklauso kitam padaliniui') }}<template v-if="duty.tenantShortname">
          ({{ duty.tenantShortname }})</template>{{ $t('. Gali valdyti tik savo padalinio atstovus.') }}
      </span>
    </p>

    <PreviewFacts>
      <PreviewFact v-if="duty.institution" :label="$t('Institucija')">
        <Link v-if="duty.institution.id" :href="route('institutions.show', duty.institution.id)" class="hover:text-brand">
          {{ duty.institution.name }}
        </Link>
        <template v-else>
          {{ duty.institution.name }}
        </template>
      </PreviewFact>
      <PreviewFact v-if="duty.tenantShortname" :label="$t('Padalinys')" :value="duty.tenantShortname" />
      <PreviewFact :label="$t('El. paštas')" :value="duty.email" />
      <PreviewFact v-if="duty.types.length" :label="$t('Tipai')" :value="duty.types.join(', ')" />
      <PreviewFact
        :label="$t('Nariai')"
        :value="duty.memberCount || $t('Neužimta')"
        :tone="duty.memberCount === 0 ? 'attention' : undefined"
        tabular
      />
    </PreviewFacts>

    <PreviewSection v-if="duty.currentMembers?.length" :title="$t('Nariai')">
      <PreviewChipList :items="memberChips(duty.currentMembers)" :max="MEMBER_LIMIT" :more-href="showHref" />
    </PreviewSection>

    <PreviewSection v-if="duty.previousMembers?.length" :title="$t('Buvę nariai')">
      <PreviewChipList :items="memberChips(duty.previousMembers)" :max="MEMBER_LIMIT" :more-href="showHref" />
    </PreviewSection>
  </PreviewPane>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Building2 } from '@lucide/vue';
import { computed } from 'vue';

import type { DutyPreviewModel } from './dutyPreview';
import InflectedDutyName from './InflectedDutyName.vue';

import InactiveInstitutionBadge from '@/Components/Institutions/InactiveInstitutionBadge.vue';
import { PreviewChipList, PreviewFact, PreviewFacts, PreviewPane, PreviewSection, type PreviewChipItem } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';

const MEMBER_LIMIT = 8;

const props = defineProps<{
  duty: DutyPreviewModel;
  /** The duty belongs to a unit outside the person's own scope. */
  isExternal?: boolean;
}>();

const showHref = computed(() => route('duties.show', props.duty.id));

function memberChips(members: { id?: string; name: string }[]): PreviewChipItem[] {
  return members.map(member => ({
    id: member.id,
    name: member.name,
    href: member.id ? route('users.show', member.id) : undefined,
  }));
}
</script>
