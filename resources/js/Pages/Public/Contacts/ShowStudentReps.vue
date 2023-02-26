<template>
  <div class="mx-auto mt-8 flex max-w-7xl flex-col gap-4 px-8 lg:px-32">
    <h1>Studentų atstovai</h1>
    <NFormItem label="Tipas" class="max-w-sm"
      ><NSelect
        v-model:value="selectedTypeID"
        :label="$t('Tipas')"
        placeholder="Pasirinkite tipą..."
        :options="typeOptions"
        clearable
    /></NFormItem>

    <div v-if="selectedType" class="mb-8">
      <h2>{{ selectedType?.title }}</h2>
      {{ selectedType?.description }}
    </div>

    <section
      v-for="institutionType in filteredTypes"
      :key="institutionType.id"
      class="mb-4"
    >
      <div
        v-for="institution in institutionType.institutions"
        :key="institution.id"
        class="mb-8 flex flex-col gap-4"
      >
        <InstitutionFigure :institution="institution" />
        <template v-for="duty in institution.duties">
          <ContactWithPhoto
            v-for="contact in duty.users"
            :key="contact.id"
            :contact="contact"
            :duties="[duty]"
          >
          </ContactWithPhoto>
        </template>
      </div>
      <NDivider />
    </section>
  </div>
</template>

<script setup lang="ts">
import { NDivider, NFormItem, NSelect } from "naive-ui";
import { computed, ref } from "vue";

import ContactWithPhoto from "@/Components/Public/ContactWithPhoto.vue";
import InstitutionFigure from "@/Components/Public/InstitutionFigure.vue";

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
</script>
