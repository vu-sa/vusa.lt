<template>
  <h1 class="pt-8 text-gray-900 dark:text-zinc-50 text-2xl md:text-2xl">
    Dokumentai
  </h1>
  <div class="mt-4 flex max-w-4xl flex-col items-center justify-center">
    <div class="flex w-full flex-row flex-wrap items-center gap-8">
      <NForm>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3 lg:grid-cols-3">
          <NFormItem label="Pavadinimas" :show-feedback="false">
            <NInput v-model:value="form.title" clearable size="large" round type="text"
              placeholder="Ieškoti pagal pavadinimą..." />
          </NFormItem>
          <NFormItem label="Nuo..." :show-feedback="false">
            <NDatePicker v-model:value="form.dateRange[0]" size="large" clearable placeholder="2023-05-01" />
          </NFormItem>
          <NFormItem label="Iki..." :show-feedback="false">
            <NDatePicker v-model:value="form.dateRange[1]" size="large" clearable placeholder="2024-05-01" />
          </NFormItem>
        </div>
          <NFormItem :show-feedback="false">
            <NButton type="primary" round size="large" :loading="searchLoading" @click="handleSearch">
              <template #icon>
                <IFluentSearch20Filled />
              </template>
              Ieškoti
            </NButton>
          </NFormItem>

        <Collapsible v-model:open="openAdditionalOptions" class="mt-4">
          <CollapsibleTrigger>
            <NButton strong size="tiny" text icon-placement="right">
              Papildomi filtrai
              <template #icon>
                <IFluentChevronDown24Regular v-if="!openAdditionalOptions" />
                <IFluentChevronUp24Regular v-else />
              </template>
            </NButton>
          </CollapsibleTrigger>
          <CollapsibleContent>
            <div class="mt-4 grid w-full grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 items-center gap-4">
              <NFormItem class="grow" label="Padalinys" :show-feedback="false">
                <NSelect v-model:value="form.tenants" clearable size="small" multiple :options="tenantOptions" placeholder="VU SA"
                  max-tag-count="responsive" />
              </NFormItem>
              <NFormItem class="grow" label="Turinio tipas" :show-feedback="false">
                <NSelect v-model:value="form.contentTypes" clearable size="small" :options="contentTypeOptions" placeholder="Ataskaitos"
                  multiple />
              </NFormItem>
              <NFormItem label="Kalba" :show-feedback="false">
                <NCheckboxGroup v-model:value="form.language" size="small">
                  <NCheckbox value="Lietuvių">
                    🇱🇹 LT
                  </NCheckbox>
                  <NCheckbox value="Anglų">
                    🇬🇧 EN
                  </NCheckbox>
                </NCheckboxGroup>
              </NFormItem>
            </div>
          </CollapsibleContent>
        </Collapsible>
      </NForm>
      <!-- <div class="flex w-72 flex-col">
        <Label for="date" class="mb-1 text-sm font-normal text-zinc-600">
          Pavadinimas
        </Label>
        <div class="relative w-full max-w-sm items-center">

          <Input id="search" type="text" placeholder="Ieškoti pagal pavadinimą..." class="rounded-full px-10" />
          <span class="absolute inset-y-0 start-0 flex items-center justify-center px-2">
            <IFluentSearch20Filled />
          </span>
          <div class="absolute inset-y-0 end-4 flex items-center justify-center">
            <Button rounded class="border-stone-700" :disabled="searchLoading" @click="handleSearch">
              <IFluentArrowRight16Regular v-if="!searchLoading" />
              <IFluentSpinnerIos16Filled v-else class="animate-spin" />
            </Button>
          </div>
        </div>
      </div>
      <div class="flex flex-col">
        <Label for="date" class="mb-1 text-sm font-normal text-zinc-600">
          Dokumento data
        </Label>
        <DateRangePicker />
      </div>
      <div class="flex flex-col">
        <Label for="tenant" class="mb-1 text-sm font-normal text-zinc-600">
          Padalinys
        </Label>
        <Select />
      </div>
-->
    </div>
    <div v-if="documents.length" class="col-span-2 mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2">
      <SmartLink v-for="documentItem in documents" :key="documentItem.id" :href="documentItem.anonymous_url">
        <DocumentCard :document-item />
      </SmartLink>
    </div>
    <p v-else class="mt-8 self-start font-bold text-zinc-500">
      Dokumentų pagal užklausą nerasta.
    </p>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { useStorage } from '@vueuse/core';

import Collapsible from '@/Components/ShadcnVue/ui/collapsible/Collapsible.vue';
import CollapsibleContent from '@/Components/ShadcnVue/ui/collapsible/CollapsibleContent.vue';
import CollapsibleTrigger from '@/Components/ShadcnVue/ui/collapsible/CollapsibleTrigger.vue';
import SmartLink from '@/Components/Public/SmartLink.vue';
import DocumentCard from '@/Components/Cards/DocumentCard.vue';

const props = defineProps<{
  //documents: PaginatedModels<App.Entities.Document>;
  documents: App.Entities.Document[];
  allContentTypes: App.Entities.Document['content_type'][];
}>();

const form = useForm('DocumentsSearch', {
  title: undefined,
  dateRange: [undefined, undefined],
  tenants: undefined,
  contentTypes: undefined,
  language: undefined,
});

const searchLoading = ref(false);

const openAdditionalOptions = useStorage('documents-OpenAdditionalOptions', false);

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

function handleSearch() {
  searchLoading.value = true;
  router.visit(route('documents',
    { lang: usePage().props.app.locale, ...form.data() }), {
    only: ['documents'],
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      searchLoading.value = false;
    }
  });
}
</script>
