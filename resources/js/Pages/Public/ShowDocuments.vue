<template>
  <div class="mt-4 grid grid-cols-1 items-start justify-center gap-8 sm:grid-cols-[minmax(25%,_200px),_1fr]">
    <NForm
      class="flex flex-col gap-3 rounded-lg border border-zinc-200 bg-gradient-to-b from-white to-zinc-50 p-6 shadow-sm">
      <p class="mb-3 text-xl font-bold">
        Filtrai
      </p>
      <NFormItem :show-label="false" :show-feedback="false">
        <NCheckboxGroup v-model:value="form.contentTypes" @update:value="handleSearch">
          <NCheckbox v-for="contentType in contentTypeOptions" :key="contentType.value" :value="contentType.value">
            {{ contentType.label }}
          </NCheckbox>
        </NCheckboxGroup>
      </NFormItem>
      <NFormItem label="Padalinys" :show-feedback="false">
        <NSelect v-model:value="form.tenants" :consistent-menu-width="false" clearable multiple
          :options="tenantOptions" placeholder="VU SA" max-tag-count="responsive" @update:value="handleSearch" />
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
        <NDatePicker v-model:value="form.dateFrom" clearable placeholder="2023-05-01" @update:value="handleSearch" />
      </NFormItem>
      <NFormItem label="Iki..." :show-feedback="false">
        <NDatePicker v-model:value="form.dateTo" clearable placeholder="2024-05-01" @update:value="handleSearch" />
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
      <div v-if="documents.length" class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2 2xl:grid-cols-3">
        <SmartLink v-for="documentItem in documents" :key="documentItem.id" :href="documentItem.anonymous_url">
          <DocumentCard :document-item />
        </SmartLink>
      </div>
      <p v-else class="mt-8 self-start font-bold text-zinc-500">
        Dokumentų pagal užklausą nerasta.
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';

import SmartLink from '@/Components/Public/SmartLink.vue';
import DocumentCard from '@/Components/Cards/DocumentCard.vue';

const props = defineProps<{
  //documents: PaginatedModels<App.Entities.Document>;
  documents: App.Entities.Document[];
  allContentTypes: App.Entities.Document['content_type'][];
}>();

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

const handleSearchDebounce = useDebounceFn(() => {
  handleSearch();
}, 500);

//function isStartDateDisabled(date) {
//  return form.dateRange[1] && date > form.dateRange[1];
//}
//
//function isEndDateDisabled(date) {
//  return form.dateRange[0] && date < form.dateRange[0];
//}

function handleSearch() {
  searchLoading.value = true;

  console.log(form.value)

  router.visit(route('documents', { lang: usePage().props.app.locale, ...form.value }), {
    only: ['documents'],
    onSuccess: () => {
      searchLoading.value = false;
    }
  });
}
</script>
