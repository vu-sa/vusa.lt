<template>
  <div class="mx-auto mt-8 flex max-w-7xl flex-col gap-4 px-8 lg:px-32">
    <h1>{{ $t("Studentų atstovai") }}</h1>
    <NFormItem :show-feedback="false" label="Tipas" class="max-w-sm"
      ><NSelect
        v-model:value="selectedTypeID"
        :label="$t('Tipas')"
        placeholder="Pasirinkite tipą..."
        :options="typeOptions"
        clearable
    /></NFormItem>

    <div
      v-if="selectedType"
      class="prose prose-sm mb-8 dark:text-zinc-50"
      v-html="selectedType?.description"
    />

    <section
      v-for="institutionType in filteredTypes"
      :key="institutionType.id"
      class="my-4"
    >
      <InstitutionContacts
        v-for="institution in institutionType.institutions"
        :key="institution.id"
        :institution="institution"
        :contacts="getContacts(institution)"
      />
    </section>
  </div>
</template>

<script setup lang="ts">
import { NFormItem, NSelect } from "naive-ui";
import { computed, ref } from "vue";

import InstitutionContacts from "@/Components/Public/InstitutionContacts.vue";

const props = defineProps<{
  types: App.Entities.Type[];
}>();

const selectedTypeID = ref<number | null>(null);
const typeOptions = computed<Record<string, any>>(() => {
  return props.types.map((type) => {
    return {
      label: type.title,
      value: type.id,
    };
  });
});

const filteredTypes = computed(() => {
  if (selectedType.value) {
    return props.types.filter((type) => type.id === selectedTypeID.value);
  }

  return props.types;
});

const selectedType = computed(() => {
  if (selectedTypeID.value) {
    return props.types.find((type) => type.id === selectedTypeID.value);
  }

  return null;
});

// flatten institution.duties.current_users and add duty to each user
const getContacts = (institution: App.Entities.Institution) => {
  const contacts: App.Entities.User[] = [];

  institution.duties?.forEach((duty) => {
    duty.current_users?.forEach((user) => {
      user.duties = [duty];
      contacts.push(user);
    });
  });

  return contacts;
};
</script>
