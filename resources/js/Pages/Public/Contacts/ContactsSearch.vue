<template>
  <div class="mt-12">
    <h1>VU SA kontaktai</h1>
    <NFormItem>
      <NInputGroup class="my-4" size="large" round>
        <NSelect
          :style="{ width: '70%' }"
          :options="treeOptions"
          filterable
          placement="bottom"
          placeholder="Įrašykite ir spauskite Enter..."
          @update:value="handleSelectInstitution"
        />
        <NSelect
          :value="selectedPadaliniai"
          multiple
          max-tag-count="responsive"
          :options="padaliniaiOptions"
          :style="{ width: '30%' }"
          @update:value="handleUpdateSelectedPadaliniai"
        />
      </NInputGroup>
    </NFormItem>
    <template v-for="institution in institutions" :key="institution.id">
      <InstitutionFigure :institution="institution" />
      <NDivider />
    </template>
    <pre>{{ institutions }}</pre>
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
  selectedPadaliniai: number[];
}>();

const treeOptions = computed(() => {
  // selected padaliniai are ids and then they have children: institutions
  return props.selectedPadaliniai.map((padalinys_id) => {
    return {
      label: usePage().props.padaliniai.find(
        (padalinys) => padalinys.id === padalinys_id,
      )?.shortname,
      value: padalinys_id,
      type: "group",
      children: props.institutions
        .filter((institution) => institution.padalinys?.id === padalinys_id)
        .map((institution) => {
          return {
            label: institution.name,
            value: institution.id,
            padalinys_alias: institution.padalinys?.alias,
          };
        }),
    };
  });
});

const padaliniaiOptions = computed(() => {
  return usePage().props.padaliniai.map((padalinys) => {
    return {
      label: padalinys.shortname,
      value: padalinys.id,
    };
  });
});

const handleUpdateSelectedPadaliniai = (value: number[]) => {
  router.reload({
    data: {
      selectedPadaliniai: btoa(JSON.stringify(value)),
    },
  });
};

const handleSelectInstitution = (value, option: SelectOption) => {
  //   go to page without opening blank tab
  console.log(option);
  window.open(
    route("contacts.institution", {
      institution: option.value,
      subdomain:
        option.padalinys_alias === "vusa" ? "www" : option.padalinys_alias,
      lang: usePage().props.app.locale,
    } as RouteParamsWithQueryOverload),
    "_self",
  );
};
</script>
