<template>
  <NForm :model="form" label-placement="top">
    <div class="flex flex-col">
      <FormElement>
        <template #title>
          {{ $t("forms.context.main_info") }}
        </template>
        <NFormItem :label="$t('forms.fields.title')">
          <NInput v-model:value="form.title" type="text" placeholder="Įrašyti pavadinimą..." />
        </NFormItem>
        <NFormItem label="Nuoroda">
          <NInput :value="form.permalink" disabled type="text" placeholder="Sugeneruojama nuoroda..." />
        </NFormItem>
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
    </div>
    <div class="flex justify-end gap-2">
      <DeleteModelButton v-if="deleteModelRoute" :form="form" :model-route="deleteModelRoute" />
      <UpsertModelButton :form="form" :model-route="modelRoute" @save="updateContents">
        Sukurti
      </UpsertModelButton>
    </div>
  </NForm>
</template>

<script setup lang="ts">
import { NForm, NFormItem, NInput, NSelect } from "naive-ui";
import { computed, watch } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import latinize from "latinize";

import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import FormElement from "./FormElement.vue";
import RichContentFormElement from "../RichContentFormElement.vue";
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  page: App.Entities.Page;
  otherLangPages?: App.Entities.Page[];
  modelRoute: string;
  deleteModelRoute?: string;
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
      label: `${page.title} (${page.padalinys?.shortname})`,
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
