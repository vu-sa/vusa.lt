<template>
  <div class="mt-4 grid grid-cols-1 items-start justify-center gap-6 md:grid-cols-[minmax(220px,25%)__1fr] lg:gap-12">
    <Collapsible v-model:open="areFiltersOpen"
      class=" rounded-lg border border-zinc-200 bg-linear-to-b from-white to-zinc-50 p-4 shadow-xs dark:border-zinc-900 dark:from-zinc-800 dark:to-zinc-900">
      <CollapsibleTrigger>
        <div class="flex items-center gap-4">
          <strong class="text-lg">
            Filtrai
          </strong>
          <NButton size="tiny" circle tertiary>
            <template #icon>
              <IFluentChevronDown24Regular v-if="!areFiltersOpen" />
              <IFluentChevronUp24Regular v-else />
            </template>
          </NButton>
        </div>
      </CollapsibleTrigger>
      <CollapsibleContent class="mt-4">
        <div class="mb-4 rounded-lg border p-3 dark:border-zinc-600">
          <div class="flex flex-col gap-2">
            <Collapsible v-model:open="expandedNames['VU SA']">
              <CollapsibleTrigger class="flex w-full items-center justify-between p-2 border-b dark:border-zinc-700">
                <span class="font-medium">VU SA</span>
                <IFluentChevronDown24Regular v-if="!expandedNames['VU SA']" />
                <IFluentChevronUp24Regular v-else />
              </CollapsibleTrigger>
              <CollapsibleContent class="p-2">
                <NCheckboxGroup v-model:value="form.contentTypes" @update:value="handleSearch">
                  <template v-for="contentType in contentTypeOptions" :key="contentType.value">
                    <NCheckbox v-if="contentType.value?.startsWith('VU SA ') && !contentType.value.startsWith('VU SA P ')"
                      :value="contentType.value">
                      {{ contentType.label }}
                    </NCheckbox>
                  </template>
                </NCheckboxGroup>
              </CollapsibleContent>
            </Collapsible>

            <Collapsible v-model:open="expandedNames['VU SA P']">
              <CollapsibleTrigger class="flex w-full items-center justify-between p-2 border-b dark:border-zinc-700">
                <span class="font-medium">VU SA P</span>
                <IFluentChevronDown24Regular v-if="!expandedNames['VU SA P']" />
                <IFluentChevronUp24Regular v-else />
              </CollapsibleTrigger>
              <CollapsibleContent class="p-2">
                <NCheckboxGroup v-model:value="form.contentTypes" @update:value="handleSearch">
                  <template v-for="contentType in contentTypeOptions" :key="contentType.value">
                    <NCheckbox v-if="contentType.value?.startsWith('VU SA P ') === true" :value="contentType.value">
                      {{ contentType.label }}
                    </NCheckbox>
                  </template>
                </NCheckboxGroup>
              </CollapsibleContent>
            </Collapsible>

            <Collapsible v-model:open="expandedNames['Kita']">
              <CollapsibleTrigger class="flex w-full items-center justify-between p-2 border-b dark:border-zinc-700">
                <span class="font-medium">Kiti VU SA dokumentai</span>
                <IFluentChevronDown24Regular v-if="!expandedNames['Kita']" />
                <IFluentChevronUp24Regular v-else />
              </CollapsibleTrigger>
              <CollapsibleContent class="p-2">
                <NCheckboxGroup v-model:value="form.contentTypes" @update:value="handleSearch">
                  <template v-for="contentType in contentTypeOptions" :key="contentType.value">
                    <NCheckbox
                      v-if="(contentType.value?.startsWith('VU SA ') === false) && (contentType.value?.startsWith('VU SA P ') === false)"
                      :value="contentType.value">
                      {{ contentType.label }}
                    </NCheckbox>
                  </template>
                </NCheckboxGroup>
              </CollapsibleContent>
            </Collapsible>
          </div>
        </div>
        <NFormItem label="Padalinys">
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
        <NFormItem label="Nuo...">
          <NDatePicker v-model:value="form.dateFrom" clearable placeholder="2023-05-01"
            :is-date-disabled="isStartDateDisabled" @update:value="handleSearch" />
        </NFormItem>
        <NFormItem label="Iki...">
          <NDatePicker v-model:value="form.dateTo" clearable placeholder="2024-05-01"
            :is-date-disabled="isEndDateDisabled" @update:value="handleSearch" />
        </NFormItem>
      </CollapsibleContent>
    </Collapsible>
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
      <div class="flex items-center justify-between mt-4">
        <NPagination v-if="documents.total > 20" :page-slot="7" :item-count="documents.total"
          :page="documents.current_page" :page-size="20" @update:page="handlePageChange" />

        <NButtonGroup class="ml-auto">
          <NButton :type="viewMode === 'grid' ? 'primary' : 'default'" @click="viewMode = 'grid'">
            <template #icon>
              <IFluentGrid24Filled />
            </template>
          </NButton>
          <NButton :type="viewMode === 'list' ? 'primary' : 'default'" @click="viewMode = 'list'">
            <template #icon>
              <IFluentAppsList20Filled />
            </template>
          </NButton>
        </NButtonGroup>
      </div>
      <template v-if="documents.data.length">
        <div v-if="viewMode === 'grid'" class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2 2xl:grid-cols-3">
          <SmartLink v-for="documentItem in documents.data" :key="documentItem.id" class="h-fit"
            :href="documentItem.anonymous_url">
            <DocumentCard :document-item />
          </SmartLink>
        </div>
        <div v-else class="mt-6">
          <NDataTable :row-props="rowProps" :data="documents.data" :columns="columns" />
        </div>
      </template>
      <p v-else class="mt-8 self-start font-bold text-zinc-500">
        Dokumentų pagal užklausą nerasta.
      </p>
      <NPagination v-if="documents.total > 20" :page-slot="7" class="mt-6" :item-count="documents.total"
        :page="documents.current_page" :page-size="20" @update:page="handlePageChange" />
    </div>
  </div>
</template>

<script setup lang="tsx">
import { ref, reactive } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

import SmartLink from '@/Components/Public/SmartLink.vue';
import DocumentCard from '@/Components/Cards/DocumentCard.vue';
import { breakpointsTailwind, useBreakpoints, useStorage } from '@vueuse/core';
import Collapsible from '@/Components/ShadcnVue/ui/collapsible/Collapsible.vue';
import CollapsibleContent from '@/Components/ShadcnVue/ui/collapsible/CollapsibleContent.vue';
import CollapsibleTrigger from '@/Components/ShadcnVue/ui/collapsible/CollapsibleTrigger.vue';
import type { DataTableColumns } from 'naive-ui';

const props = defineProps<{
  //documents: PaginatedModels<App.Entities.Document>;
  documents: PaginatedModels<App.Entities.Document>;
  allContentTypes: App.Entities.Document['content_type'][];
}>();

const viewMode = useStorage('showArchive-viewMode', 'list');

const isMdOrSmaller = useBreakpoints(breakpointsTailwind).isSmallerOrEqual('md');

const expandedNames = reactive({
  'VU SA': true,
  'VU SA P': false,
  'Kita': false
});

const areFiltersOpen = ref(!isMdOrSmaller);

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

const columns: DataTableColumns<App.Entities.Document> = [
  {
    key: 'icon', title: '',
    width: 30,
    render: (row) => {
      return <span class="[&_svg]:text-zinc-700 dark:[&_svg]:text-zinc-300">
        { row.name.endsWith('.pdf') && <IAntDesignFilePdfOutlined width="20" height="20" /> }
        { row.name.endsWith('.docx') && <IAntDesignFileWordOutlined width="20" height="20" /> }
        { row.name.endsWith('.xlsx') && <IAntDesignFileExcelOutlined width="20" height="20" /> }
        { row.name.endsWith('.pptx') && <IAntDesignFilePptOutlined width="20" height="20" /> }
        { row.name.endsWith('.url') && <IFluentGlobe20Regular width="20" height="20" /> }
        { !row.name.endsWith('.pdf') && !row.name.endsWith('.docx') && !row.name.endsWith('.xlsx') && !row.name.endsWith('.pptx') && !row.name.endsWith('.url') && <IAntDesignFileTextOutlined width="20" height="20" /> }
      </span>
        ;
    }
  },
  {
    key: 'title',
    title: 'Pavadinimas',
    maxWidth: 300,
    className: 'font-medium tracking-tight',
    render: (row) => {
      return <SmartLink href={row.anonymous_url}>{row.title}</SmartLink>;
    }
  },
  {
    key: 'content_type',
    title: 'Dokumento tipas',
    render: (row) => {
      return <p class="line-clamp-2 text-zinc-500 dark:text-zinc-400 text-sm leading-tight">{row.content_type}</p>;
    }
  },
  {
    key: 'document_date',
    title: 'Dokumento data',
    width: 115,
  }
];

const rowProps = (row) => ({
  class: 'cursor-pointer',
  onClick: () => {
    window.open(row.anonymous_url, '_blank');
  }
});

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
