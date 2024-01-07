<template>
  <div class="mt-8 flex flex-col gap-4">
    <h1>{{ $t("Studentų atstovai") }}</h1>
    <NFormItem label="Tipas" class="max-w-sm"
      ><NSelect
        v-model:value="selectedTypeID"
        :label="$t('Tipas')"
        placeholder="Pasirinkite tipą..."
        :options="typeOptions"
        clearable
    /></NFormItem>

    <section v-if="selectedType && selectedType.description">
      <h2>Aprašymas</h2>
      <div
        class="prose prose-sm mb-8 dark:text-zinc-50"
        v-html="selectedType?.description"
      />
    </section>

    <section v-for="institutionType in filteredTypes" :key="institutionType.id">
      <template
        v-for="institution in institutionType.institutions"
        :key="institution.id"
      >
        <InstitutionContacts
          :institution="institution"
          :contacts="getContacts(institution)"
        />
        <!-- add divider except for last element -->
        <NDivider
          v-if="
            institutionType.institutions[
              institutionType.institutions.length - 1
            ].id !== institution.id
          "
          class="my-8"
        />
      </template>
    </section>
  </div>
</template>

<script setup lang="ts">
import { NDivider, NFormItem, NSelect } from "naive-ui";
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
      let newUser = { ...user };

      newUser.duties = [duty];
      contacts.push(newUser);
    });
  });

  return contacts;
};
</script>
