<template>
  <CollectionSelectDialog
    :open="open"
    collection="institutions"
    :multiple="false"
    :title="$t('Pasirinkti instituciją')"
    :description="$t('Ieškokite ir filtruokite institucijas pagal padalinį.')"
    :confirm-label="$t('Pasirinkti')"
    :base-filter-by="baseFilterBy"
    :initial-hits="initialHits"
    :allow-empty="allowEmpty"
    :empty-message="$t('Institucijų nerasta')"
    :search-placeholder="$t('Ieškoti institucijos pagal pavadinimą...')"
    @update:open="$emit('update:open', $event)"
    @confirm="$emit('confirm', $event)"
  >
    <template v-if="$slots.trigger" #trigger>
      <slot name="trigger" />
    </template>
  </CollectionSelectDialog>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import CollectionSelectDialog from './CollectionSelectDialog.vue';
import type { InstitutionOption } from './institutionOption';
import type { NormalizedSearchHit } from '../../Utils/searchHitMappers';

const props = defineProps<{
  open: boolean;
  institutions: InstitutionOption[];
  initialHits?: NormalizedSearchHit[];
  allowEmpty?: boolean;
}>();

defineEmits<{
  'update:open': [open: boolean];
  confirm: [hits: NormalizedSearchHit[]];
}>();

// The assignable set is scoped by whole tenants (DutyService), so filtering the
// institutions collection by those tenant ids reproduces it exactly while staying
// compact even for global-scope admins.
const baseFilterBy = computed(() => {
  const tenantIds = [
    ...new Set(props.institutions.map(i => i.tenant?.id).filter((id): id is number => id != null)),
  ];
  return tenantIds.length > 0 ? `tenant_ids:[${tenantIds.join(',')}]` : undefined;
});
</script>
