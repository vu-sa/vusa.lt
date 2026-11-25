<template>
  <OverviewSection
    :title="$t('Sekamos institucijos')"
    :icon="Eye"
    variant="home"
    content-ruled
    :href="route('institutions.index', { followed: 1 })"
    :href-label="$t('Visos sekamos (:count)', { count: String(followed.total) })"
    data-slot="followed-institutions"
  >
    <CollectionRowList
      :items="followed.items"
      frame="box"
      :item-key="institution => institution.id"
      :row-muted="institution => institution.is_active === false"
    >
      <template #default="{ item: institution }">
        <Link :href="route('institutions.show', institution.id)" prefetch :class="cn(ROW_LINK_CLASS, 'flex-col gap-0.5')">
          <span class="truncate font-semibold text-foreground">{{ institution.name }}</span>
          <StatusText
            :status="institution.is_active === false ? inactiveInstitutionStatus : institutionActivityStatuses[institution.activity_status as InstitutionActivityStatus]"
            class="text-xs"
          />
        </Link>
      </template>
    </CollectionRowList>
  </OverviewSection>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Eye } from '@lucide/vue';

import CollectionRowList from '@/Components/Collection/CollectionRowList.vue';
import { ROW_LINK_CLASS } from '@/Components/Collection/rowList';
import { StatusText } from '@/Components/Patterns';
import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import { inactiveInstitutionStatus, institutionActivityStatuses } from '@/Constants/statusIcons';
import { cn } from '@/Utils/Shadcn/utils';
import type { InstitutionActivityStatus } from '@/Types/enums';

import type { HomeFollowedInstitutions } from './types';

/** Only rendered when something is followed; following is discovered on the institution pages. */
defineProps<{
  followed: HomeFollowedInstitutions;
}>();
</script>
