<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <NFormItem :label="$t('forms.fields.title')">
        <NInput v-model:value="form.title" type="text" placeholder="Įrašyti pavadinimą..." />
      </NFormItem>
      <div class="grid lg:grid-cols-2 lg:gap-4">
        <NFormItem label="Nuoroda">
          <NInput :value="form.permalink" disabled type="text" placeholder="Sugeneruojama nuoroda..." />
        </NFormItem>
        <NFormItem label="Kategorija">
          <NSelect v-model:value="form.category_id" filterable :options="categories.map((category) => ({
            value: category.id,
            label: category.name,
          }))" placeholder="Pasirinkti kategoriją..." />
        </NFormItem>
      </div>
      <div class="grid lg:grid-cols-2 lg:gap-4">
        <NFormItem label="Kalba">
          <NSelect v-model:value="form.lang" filterable :options="languageOptions" placeholder="Pasirinkti kalbą..." />
        </NFormItem>
        <NFormItem label="Kitos kalbos puslapis">
          <NSelect v-model:value="form.other_lang_id" filterable :disabled="modelRoute === 'pages.store'"
            placeholder="Pasirinkti kitos kalbos puslapį... (tik tada, kai jau sukūrėte puslapį)"
            :options="otherPageOptions" clearable />
        </NFormItem>
      </div>
    </FormElement>
    <RichContentFormElement v-model="form.content.parts" />
  </AdminForm>
</template>

<script setup lang="ts">
import { NFormItem, NInput, NSelect } from "naive-ui";
import { computed, watch } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import latinize from "latinize";

import FormElement from "./FormElement.vue";
import RichContentFormElement from "../RichContentFormElement.vue";
import AdminForm from "./AdminForm.vue";

const props = defineProps<{
  categories: App.Entities.Category[];
  page: App.Entities.Page;
  otherLangPages?: App.Entities.Page[];
  modelRoute: string;
}>();

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

const form = useForm("page", props.page);

const otherPageOptions = computed(() => {
  if (props.modelRoute === "pages.store") {
    return [];
  }

  if (props.otherLangPages === undefined) {
    return [];
  }

  return props.otherLangPages
    .map((page) => ({
      value: page.id,
      label: `${page.title} (${page.tenant?.shortname})`,
    }))
    .reverse();
});

const languageOptions = [
  {
    value: "lt",
    label: "Lietuvių",
  },
  {
    value: "en",
    label: "English",
  },
];

function updateContents() {
  // Use usePage flash.data to grab page.contents and update form.contents
  form.content = usePage().props.flash.data?.content
}

// watch form.title and update form.permalink

if (props.modelRoute == "pages.store") {
  watch(
    () => form.title,
    (title) => {
      let latinizedTitle = latinize(title);
      form.permalink = latinizedTitle
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/-+/g, "-")
        .replace(/^-+/, "")
        .replace(/-+$/, "")
        .substring(0, 30);
    }
  );
}
</script>
