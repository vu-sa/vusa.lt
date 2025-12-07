<template>
  <div class="mt-4 flex flex-col gap-4">
    <h1>{{ $t("Studentų atstovai") }}</h1>
    <div class="flex flex-row items-center gap-4">
      <div>
        <Label for="padalinys" class="mb-2">Padalinys</Label>
        <PadalinysSelector id="padalinys" size="medium" />
      </div>

      <Separator orientation="vertical" class="h-6" />

      <div class="min-w-64">
        <Label for="search" class="mb-2">{{ $t('forms.fields.title') }}</Label>
        <Input id="search" v-model="search" placeholder="Ieškoti..." class="w-full" />
      </div>
    </div>

    <section v-for="institutionType in filteredTypesAndInstitutions" :key="institutionType.id">
      <template v-for="institution in institutionType.institutions" :key="institution.id">
        <InstitutionContacts :institution="institution" :contacts="getContacts(institution)" />
        <Separator v-if="
          institutionType.institutions[
            institutionType.institutions.length - 1
          ].id !== institution.id
        " class="my-12" />
      </template>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";

import InstitutionContacts from "@/Components/Public/InstitutionContacts.vue";
import PadalinysSelector from "@/Components/Public/Nav/PadalinysSelector.vue";
import { Separator } from "@/Components/ui/separator";
import { Label } from "@/Components/ui/label";
import { Input } from "@/Components/ui/input";

const props = defineProps<{
  types: App.Entities.Type[];
}>();

const search = ref<string | null>(null);

// TODO: if institution has multiple types, show only once
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
