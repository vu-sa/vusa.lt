<template>
  <InlineSearchField
    v-model:query="query"
    :label="$t('Pareigybė')"
    :placeholder="$t('Ieškok pagal pareigybės ar institucijos pavadinimą…')"
    :hits="showList ? rows : []"
    :selected
    selected-test-id="duty-search-selected"
    :clear-label="$t('Pakeisti pareigybę')"
    :empty-message="$t('Pareigybių nerasta')"
    :searching="isSearching"
    :loading-more="isLoadingMore"
    :has-more="collection.hasMoreResults.value"
    :total="collection.totalHits.value"
    :error
    @choose="choose"
    @load-more="collection.loadMore()"
    @clear="emit('update:modelValue', null)"
  />
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

import InlineSearchField from '@/Features/Admin/AdminSearch/Components/Select/InlineSearchField.vue';
import { useAdminCollectionSearch } from '@/Features/Admin/AdminSearch/Composables/useAdminCollectionSearch';
import { normalizeHit, type MapperContext, type NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';

/** What the sheet needs from a `duties` search document. */
export interface DutyHit {
  id: string;
  name: string;
  institutionName?: string;
  tenantShortname?: string;
  /** The duty's home tenant, used to narrow the study-programme list. */
  homeTenantId?: number | null;
  typeIds?: number[];
  isActive?: boolean;
}

interface DutyDocument {
  id: string;
  name_lt?: string;
  institution_name_lt?: string;
  tenant_shortname?: string;
  home_tenant_id?: number | null;
  type_ids?: number[];
  is_active?: boolean;
}

const props = defineProps<{
  modelValue: DutyHit | null;
  error?: string;
  /** Offer duties no longer staffed, e.g. when linking a predecessor. */
  includeInactive?: boolean;
  tenantIds?: number[];
}>();

const emit = defineEmits<(e: 'update:modelValue', value: DutyHit | null) => void>();

const collection = useAdminCollectionSearch({
  collection: 'duties',
  syncToUrl: false,
  loadFacetsOnMount: false,
  searchOnMount: false,
  perPage: 8,
  baseFilterBy: () => [
    props.includeInactive ? undefined : 'is_active:!=false',
    props.tenantIds ? `home_tenant_id:=[${props.tenantIds.length ? props.tenantIds.join(',') : '-1'}]` : undefined,
  ].filter(Boolean).join(' && ') || undefined,
});

const query = ref('');

const showList = computed(() => query.value.trim().length >= 2);
const isSearching = computed(() => collection.isSearching.value);
const isLoadingMore = computed(() => collection.status.value === 'loading-more');
const mapperContext = computed<MapperContext>(() => ({
  ownTenantIds: collection.adminSearch.getCollectionTenantIds('duties'),
  isSuperAdmin: collection.adminSearch.isSuperAdmin.value,
}));
const rows = computed(() => collection.results.value.map(doc => normalizeHit('duties', doc, mapperContext.value)));
const selected = computed(() => props.modelValue
  ? normalizeHit('duties', {
      id: props.modelValue.id,
      name_lt: props.modelValue.name,
      institution_name_lt: props.modelValue.institutionName,
    }, mapperContext.value)
  : null);

const hits = computed<DutyHit[]>(() => (showList.value
  ? (collection.results.value as DutyDocument[]).map(doc => ({
      id: String(doc.id),
      name: doc.name_lt ?? '',
      institutionName: doc.institution_name_lt ?? undefined,
      tenantShortname: doc.tenant_shortname ?? undefined,
      homeTenantId: doc.home_tenant_id ?? null,
      typeIds: doc.type_ids ?? [],
      isActive: doc.is_active !== false,
    }))
  : []));

watch(query, (value) => {
  if (value.trim().length >= 2) {
    collection.search(value);
  }
});

const choose = (row: NormalizedSearchHit) => {
  const hit = hits.value.find(hit => hit.id === row.recordId);
  if (!hit) {
    return;
  }
  emit('update:modelValue', hit);
  query.value = '';
};
</script>
