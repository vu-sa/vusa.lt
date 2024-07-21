<template>
  <div class="mt-12">
    <h1 class="text-4xl">
      {{ $t("Kontaktų paieška") }}
    </h1>
    <NFormItem>
      <NInputGroup class="my-4" size="large" round>
        <NSelect :style="{ width: '70%' }" :options="treeOptions" filterable placement="bottom"
          :placeholder="`${$t('Ieškoti')}...`" :virtual-scroll="false" @update:value="handleSelectInstitution" />
        <NSelect :value="selectedTenants" multiple max-tag-count="responsive" :options="tenantOptions"
          :style="{ width: '30%' }" @update:value="handleUpdateSelectedTenants">
          <template #action>
            <small class="text-zinc-500 dark:text-zinc-400">Pasirink padalinius, kuriuose ieškoti kontaktų.</small>
          </template>
        </NSelect>
      </NInputGroup>
    </NFormItem>
    <template v-for="institution in institutions" :key="institution.id">
      <InstitutionFigure :institution="institution" />
      <NDivider v-if="institution.id !== institutions[institutions.length - 1].id" />
    </template>
  </div>
</template>

<script setup lang="tsx">
import {
  NDivider,
  NFormItem,
  NInputGroup,
  NSelect,
  type SelectOption,
} from "naive-ui";
import { computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import InstitutionFigure from "@/Components/Public/InstitutionFigure.vue";
import type { RouteParamsWithQueryOverload } from "ziggy-js";

const props = defineProps<{
  institutions: App.Entities.Institution[];
  selectedTenants: number[];
}>();

const treeOptions = computed(() => {
  // selected tenants are ids and then they have children: institutions
  return props.selectedTenants.map((tenant_id) => {
    return {
      label: usePage().props.tenants.find(
        (tenant) => tenant.id === tenant_id,
      )?.shortname,
      value: tenant_id,
      type: "group",
      children: props.institutions
        .filter((institution) => institution.tenant?.id === tenant_id)
        .map((institution) => {
          return {
            label: institution.name,
            value: institution.id,
            tenant_alias: institution.tenant?.alias,
          };
        }),
    };
  });
});

const tenantOptions = computed(() => {
  return usePage().props.tenants.map((tenant) => {
    return {
      label: tenant.shortname,
      value: tenant.id,
    };
  });
});

const handleUpdateSelectedTenants = (value: number[]) => {
  router.reload({
    data: {
      selectedTenants: btoa(JSON.stringify(value)),
    },
  });
};

const handleSelectInstitution = (value, option: SelectOption) => {
  //   go to page without opening blank tab
  window.open(
    route("contacts.institution", {
      institution: option.value,
      subdomain:
        option.tenant_alias === "vusa" ? "www" : option.tenant_alias,
      lang: usePage().props.app.locale,
    } as RouteParamsWithQueryOverload),
    "_self",
  );
};
</script>
