<template>
  <div class="mt-4 grid grid-cols-1 items-start justify-center gap-6 md:grid-cols-[minmax(220px,_25%),_1fr] lg:gap-12">
    <NForm
      class="flex flex-col gap-3 rounded-lg border border-zinc-200 bg-gradient-to-b from-white to-zinc-50 p-4 shadow-sm dark:border-zinc-900 dark:from-zinc-800 dark:to-zinc-900">
      <p class="mb-3 text-xl font-bold">
        Filtrai
      </p>
      <div class="rounded-lg border p-3">
        <NCollapse v-model:expanded-names="expandedNames" accordion>
          <NCollapseItem title="VU SA" name="VU SA">
            <NCheckboxGroup v-model:value="form.contentTypes" @update:value="handleSearch">
              <template v-for="contentType in contentTypeOptions" :key="contentType.value">
                <NCheckbox v-if="contentType.value?.startsWith('VU SA ') && !contentType.value.startsWith('VU SA P ')"
                  :value="contentType.value">
                  {{ contentType.label }}
                </NCheckbox>
              </template>
            </NCheckboxGroup>
          </NCollapseItem>
          <NCollapseItem title="VU SA P" name="VU SA P">
            <NCheckboxGroup v-model:value="form.contentTypes" @update:value="handleSearch">
              <template v-for="contentType in contentTypeOptions" :key="contentType.value">
                <NCheckbox v-if="contentType.value?.startsWith('VU SA P ') === true" :value="contentType.value">
                  {{ contentType.label }}
                </NCheckbox>
              </template>
            </NCheckboxGroup>
          </NCollapseItem>
          <NCollapseItem title="Kiti VU SA dokumentai" name="Kita">
            <NCheckboxGroup v-model:value="form.contentTypes" @update:value="handleSearch">
              <template v-for="contentType in contentTypeOptions" :key="contentType.value">
                <NCheckbox
                  v-if="(contentType.value?.startsWith('VU SA ') === false) && (contentType.value?.startsWith('VU SA P ') === false)"
                  :value="contentType.value">
                  {{ contentType.label }}
                </NCheckbox>
              </template>
            </NCheckboxGroup>
          </NCollapseItem>
        </NCollapse>
      </div>
      <NFormItem label="Padalinys" :show-feedback="false">
        <NSelect v-model:value="form.tenants" :consistent-menu-width="false" clearable multiple :options="tenantOptions"
          placeholder="VU SA" max-tag-count="responsive" @update:value="handleSearch" />
      </NFormItem>
      <NFormItem label="Kalba" :show-feedback="false">
        <NCheckboxGroup v-model:value="form.language" @update:value="handleSearch">
          <NCheckbox value="Lietuvių">
            🇱🇹 LT
          </NCheckbox>
          <NCheckbox value="Anglų">
            🇬🇧 EN
          </NCheckbox>
        </NCheckboxGroup>
      </NFormItem>
      <NDivider />
      <NFormItem label="Nuo..." :show-feedback="false">
        <NDatePicker v-model:value="form.dateFrom" clearable placeholder="2023-05-01"
          :is-date-disabled="isStartDateDisabled" @update:value="handleSearch" />
      </NFormItem>
      <NFormItem label="Iki..." :show-feedback="false">
        <NDatePicker v-model:value="form.dateTo" clearable placeholder="2024-05-01"
          :is-date-disabled="isEndDateDisabled" @update:value="handleSearch" />
      </NFormItem>
    </NForm>
    <div>
      <h1 class="mt-0">
        Dokumentai
      </h1>

      <NInputGroup>
        <NInput v-model:value="form.q" clearable type="text" placeholder="Ieškoti pagal pavadinimą..."
          @keyup.enter="handleSearch" />
        <NButton type="primary" :loading="searchLoading" @click="handleSearch">
          <template #icon>
            <IFluentSearch20Filled />
          </template>
          Ieškoti
        </NButton>
      </NInputGroup>
      <div class="my-4">
        Iš viso rezultatų: <strong>{{ documents.total }}</strong>
      </div>
      <NPagination v-if="documents.total > 20" :page-slot="7" class="mt-4" :item-count="documents.total"
        :page="documents.current_page" :page-size="20" @update:page="handlePageChange" />
      <div v-if="documents.data.length" class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2 2xl:grid-cols-3">
        <SmartLink v-for="documentItem in documents.data" :key="documentItem.id" :href="documentItem.anonymous_url">
          <DocumentCard :document-item />
        </SmartLink>
      </div>
      <p v-else class="mt-8 self-start font-bold text-zinc-500">
        Dokumentų pagal užklausą nerasta.
      </p>
      <NPagination v-if="documents.total > 20" :page-slot="7" class="mt-6" :item-count="documents.total"
        :page="documents.current_page" :page-size="20" @update:page="handlePageChange" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

import SmartLink from '@/Components/Public/SmartLink.vue';
import DocumentCard from '@/Components/Cards/DocumentCard.vue';
import { useStorage } from '@vueuse/core';

const props = defineProps<{
  //documents: PaginatedModels<App.Entities.Document>;
  documents: PaginatedModels<App.Entities.Document>;
  allContentTypes: App.Entities.Document['content_type'][];
}>();

const expandedNames = useStorage('showDocuments-expandedNames', 'VU SA');

const params = new URLSearchParams(window.location.search);

// parse tenants and contentTypes array params (in form of 'tenants[0]=value')

let tenantParams = [];

for (let i = 0; i < 29; i++) {
  if (params.has(`tenants[${i}]`)) {
    tenantParams.push(params.get(`tenants[${i}]`));
  } else {
    break;
  }
}

let contentTypeParams = [];

for (let i = 0; i < 29; i++) {
  if (params.has(`contentTypes[${i}]`)) {
    contentTypeParams.push(params.get(`contentTypes[${i}]`));
  } else {
    break;
  }
}

let languageParams = [];

for (let i = 0; i < 2; i++) {
  if (params.has(`language[${i}]`)) {
    languageParams.push(params.get(`language[${i}]`));
  } else {
    break;
  }
}

const form = ref({
  q: params.get('q') || undefined,
  dateFrom: params.get('dateFrom') ? Number(params.get('dateFrom')) : undefined,
  dateTo: params.get('dateTo') ? Number(params.get('dateTo')) : undefined,
  tenants: tenantParams || undefined,
  contentTypes: contentTypeParams || undefined,
  language: languageParams || undefined
});

const searchLoading = ref(false);

const tenantOptions = usePage().props.tenants.reduce((acc, tenant) => {
  if (tenant.type === 'pagrindinis') {
    // add to start of array
    acc.unshift({
      label: tenant.shortname,
      value: tenant.shortname
    });
  }
  if (tenant.type === 'padalinys') {
    // findIndex of group 
    const groupIndex = acc.findIndex((group) => group.key === 'padalinys');
    // add to children of group
    acc[groupIndex].children.push({
      label: tenant.shortname,
      value: tenant.shortname
    });

  } else if (tenant.type === 'pkp') {
    // findIndex of group 
    const groupIndex = acc.findIndex((group) => group.key === 'pkp');
    // add to children of group
    acc[groupIndex].children.push({
      label: tenant.shortname,
      value: tenant.shortname
    });
  }
  return acc;
}, [
  {
    type: 'group',
    label: 'Padaliniai',
    key: 'padalinys',
    children: []
  },
  {
    type: 'group',
    label: 'Programos, klubai ir projektai',
    key: 'pkp',
    children: []
  }
])

const contentTypeOptions = props.allContentTypes.map((contentType) => ({
  label: contentType,
  value: contentType
}));

function isStartDateDisabled(date) {
  return form.value.dateTo && date > form.value.dateTo;
}

function isEndDateDisabled(date) {
  return form.value.dateFrom && date < form.value.dateFrom;
}

function handleSearch() {
  searchLoading.value = true;

  router.visit(route('documents', { lang: usePage().props.app.locale, ...form.value }), {
    only: ['documents'],
    preserveScroll: true,
    onSuccess: () => {
      searchLoading.value = false;
    }
  });
}

const handlePageChange = (page) => {
  router.visit(route('documents', { lang: usePage().props.app.locale, ...form.value, page }), {
    only: ['documents'],
    preserveScroll: true,
    onSuccess: () => {
      searchLoading.value = false;
    }
  });
};
</script>
