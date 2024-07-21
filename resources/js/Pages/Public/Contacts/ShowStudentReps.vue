<template>
  <div class="mt-8 flex flex-col gap-4">
    <h1>{{ $t("Studentų atstovai") }}</h1>
    <div class="flex flex-row items-center gap-4">
      <NFormItem label="Padalinys">
        <PadalinysSelector size="medium" :prepend-options="prependPadalinysOptions()" />
      </NFormItem>

      <NDivider vertical />

      <NFormItem :label="$t('forms.fields.title')" class="min-w-64">
        <NInput v-model:value="search" placeholder="Ieškoti..." class="w-full" />
      </NFormItem>
    </div>

    <section v-for="institutionType in filteredTypesAndInstitutions" :key="institutionType.id">
      <template v-for="institution in institutionType.institutions" :key="institution.id">
        <InstitutionContacts :institution="institution" :contacts="getContacts(institution)" />
        <!-- add divider except for last element -->
        <NDivider v-if="
          institutionType.institutions[
            institutionType.institutions.length - 1
          ].id !== institution.id
        " class="my-8" />
      </template>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";

import InstitutionContacts from "@/Components/Public/InstitutionContacts.vue";
import PadalinysSelector from "@/Components/Public/Nav/PadalinysSelector.vue";

const props = defineProps<{
  types: App.Entities.Type[];
}>();

const search = ref<string | null>(null);

const prependPadalinysOptions = () => {
  return [
    {
      label: "Centriniai atstovai",
      key: 'www',
    },
    {
      type: 'divider',
    },
  ];
};

const filteredTypesAndInstitutions = computed(() => {

  // filter institutions by search
  const filteredInstitutions = props.types.map((type) => {
    return {
      ...type,
      institutions: type.institutions.filter((institution) => {
        if (search.value) {
          return institution.name.toLowerCase().includes(search.value.toLowerCase());
        }

        return true;
      }),
    };
  });

  return filteredInstitutions;
});

// flatten institution.duties.current_users and add duty to each user
const getContacts = (institution: App.Entities.Institution) => {
  const contacts: App.Entities.User[] = [];

  institution.duties?.forEach((duty) => {
    duty.current_users?.forEach((user) => {
      let newUser = { ...user };

      newUser.duties = [duty];
      contacts.push(newUser);
    });
  });

  return contacts;
};
</script>
