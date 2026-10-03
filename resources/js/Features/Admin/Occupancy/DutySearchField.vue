<template>
  <div class="space-y-1.5">
    <Label :for="inputId" class="text-sm font-medium">
      {{ $t('Pareigybė') }}
    </Label>

    <div
      v-if="modelValue"
      class="flex items-center justify-between gap-3 border border-border bg-card p-2.5"
      data-testid="duty-search-selected"
    >
      <div class="flex min-w-0 items-center gap-2.5">
        <EntityTypeMark :type="ModelEnum.DUTY" size="md" />
        <div class="min-w-0">
          <p class="truncate text-sm font-medium text-foreground">
            {{ modelValue.name }}
          </p>
          <p v-if="modelValue.institutionName" class="truncate text-xs text-muted-foreground">
            {{ modelValue.institutionName }}
          </p>
        </div>
      </div>
      <Button type="button" variant="ghost" size="sm" class="u-touch shrink-0" @click="clear">
        <X class="size-4" />
        <span class="sr-only">{{ $t('Pakeisti pareigybę') }}</span>
      </Button>
    </div>

    <template v-else>
      <div class="relative">
        <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
        <Input
          :id="inputId"
          v-model="query"
          role="combobox"
          class="pr-9 pl-9"
          autocomplete="off"
          aria-autocomplete="list"
          :aria-expanded="showList"
          :aria-controls="listId"
          :aria-activedescendant="activeOptionId"
          :placeholder="$t('Ieškok pagal pareigybės ar institucijos pavadinimą…')"
          @keydown.down.prevent="move(1)"
          @keydown.up.prevent="move(-1)"
          @keydown.enter.prevent="chooseActive"
        />
        <Loader2
          v-if="isSearching"
          class="absolute right-3 top-1/2 size-4 -translate-y-1/2 animate-spin text-muted-foreground"
        />
      </div>

      <!-- In the flow, not floating: the sheet body scrolls, so an absolute list would clip. -->
      <ul
        v-if="showList"
        :id="listId"
        role="listbox"
        :aria-label="$t('Paieškos rezultatai')"
        class="max-h-64 divide-y divide-border overflow-y-auto border border-border bg-card"
      >
        <li
          v-for="(hit, index) in hits"
          :id="optionId(index)"
          :key="hit.id"
          role="option"
          :aria-selected="index === activeIndex"
          :class="[
            'flex min-h-11 cursor-pointer items-center justify-between gap-3 px-3 py-2 hover:bg-accent',
            index === activeIndex && 'bg-accent',
          ]"
          tabindex="-1"
          @mousedown.prevent
          @click="choose(hit)"
          @keydown.enter.prevent="choose(hit)"
        >
          <div class="min-w-0">
            <p class="truncate text-sm font-medium text-foreground">
              {{ hit.name }}
            </p>
            <p v-if="hit.institutionName" class="truncate text-xs text-muted-foreground">
              {{ hit.institutionName }}
            </p>
          </div>
          <span
            v-if="hit.tenantShortname"
            class="shrink-0 border border-border bg-secondary px-1.5 py-0.5 text-xs font-medium text-muted-foreground"
          >
            {{ hit.tenantShortname }}
          </span>
        </li>
        <li v-if="hits.length === 0" class="px-3 py-4 text-center text-sm text-muted-foreground">
          {{ isSearching ? $t('Ieškoma…') : $t('Pareigybių nerasta') }}
        </li>
      </ul>
    </template>

    <p v-if="error" class="text-xs text-destructive">
      {{ error }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Loader2, Search, X } from 'lucide-vue-next';
import { computed, ref, useId, watch } from 'vue';

import EntityTypeMark from '@/Components/EntityTypeMark.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { useAdminCollectionSearch } from '@/Features/Admin/AdminSearch/Composables/useAdminCollectionSearch';
import { ModelEnum } from '@/Types/enums';

/** What the sheet needs from a `duties` search document. */
export interface DutyHit {
  id: string;
  name: string;
  institutionName?: string;
  tenantShortname?: string;
  /** The duty's home tenant, used to narrow the study-programme list. */
  homeTenantId?: number | null;
}

interface DutyDocument {
  id: string;
  name_lt?: string;
  institution_name_lt?: string;
  tenant_shortname?: string;
  home_tenant_id?: number | null;
}

defineProps<{
  modelValue: DutyHit | null;
  error?: string;
}>();

const emit = defineEmits<(e: 'update:modelValue', value: DutyHit | null) => void>();

const uid = useId();
const inputId = `duty-search-${uid}`;
const listId = `duty-search-list-${uid}`;
const optionId = (index: number) => `duty-search-option-${uid}-${index}`;

const activeIndex = ref(-1);

const collection = useAdminCollectionSearch({
  collection: 'duties',
  syncToUrl: false,
  loadFacetsOnMount: false,
  searchOnMount: false,
  perPage: 8,
});

const query = ref('');

const showList = computed(() => query.value.trim().length >= 2);
const isSearching = computed(() => collection.isSearching.value);
const activeOptionId = computed(() => (activeIndex.value >= 0 ? optionId(activeIndex.value) : undefined));

const hits = computed<DutyHit[]>(() => (showList.value
  ? (collection.results.value as DutyDocument[]).map(doc => ({
      id: String(doc.id),
      name: doc.name_lt ?? '',
      institutionName: doc.institution_name_lt ?? undefined,
      tenantShortname: doc.tenant_shortname ?? undefined,
      homeTenantId: doc.home_tenant_id ?? null,
    }))
  : []));

watch(query, (value) => {
  activeIndex.value = -1;

  if (value.trim().length >= 2) {
    collection.search(value);
  }
});

const move = (step: 1 | -1) => {
  if (hits.value.length === 0) {
    return;
  }

  activeIndex.value = (activeIndex.value + step + hits.value.length) % hits.value.length;
};

const choose = (hit: DutyHit) => {
  emit('update:modelValue', hit);
  query.value = '';
};

const chooseActive = () => {
  const hit = hits.value[activeIndex.value];

  if (hit) {
    choose(hit);
  }
};

const clear = () => emit('update:modelValue', null);
</script>
