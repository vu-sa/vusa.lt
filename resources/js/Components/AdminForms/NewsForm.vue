<template>
  <NForm :model="form" label-placement="top">
    <div class="flex flex-col">
      <FormElement>
        <template #title>
          {{ $t("forms.context.main_info") }}</template>
        <template #description>
          <strong>Nuoroda</strong> susiformuoja automatiškai pagal pavadinimą. Pabandykite pakeisti pavadinimą, jeigu
          tokia nuoroda jau egzistuoja.
        </template>
        <NFormItem required :label="$t('forms.fields.title')">
          <NInput v-model:value="form.title" type="text" placeholder="Įrašyti pavadinimą..." />
        </NFormItem>
        <div class="grid lg:grid-cols-3 lg:gap-4">
          <NFormItem required label="Kalba">
            <NSelect v-model:value="form.lang" :options="languageOptions" placeholder="Pasirinkti kalbą..." />
          </NFormItem>
          <NFormItem required label="Naujienos paskelbimo laikas">
            <NDatePicker v-model:value="form.publish_time" placeholder="Data..." type="datetime"
              value-format="yyyy-MM-dd'T'HH:mm:ss.SSSxxx" />
          </NFormItem>
          <NFormItem label="Ar juodraštis?">
            <NSwitch v-model:value="form.draft" :checked-value="1" :unchecked-value="0" />
          </NFormItem>
        </div>
        <NFormItem class="items-start" label="Kitos kalbos puslapis">
          <NSelect v-model:value="form.other_lang_id" filterable :disabled="modelRoute === 'news.store'"
            placeholder="Pasirinkti kitos kalbos puslapį... (tik tada, kai jau sukūrėte puslapį)"
            :options="otherLangNewsOptions" clearable />
        </NFormItem>
        <NFormItem>
          <template #label>
            <div class="inline-flex items-center gap-2">
              <IFluentLink24Regular />
              Nuoroda
            </div>
          </template>
          <div class="flex grow flex-col gap-1">
            <NInput :value="form.permalink" type="text" placeholder="Sugeneruojama nuoroda" />
            <InfoText>Atsargiai: pakeitus nuorodą, sena nuoroda nebeveiks!</InfoText>
          </div>
        </NFormItem>
      </FormElement>
      <FormElement>
        <template #title>
          Nuotrauka
        </template>
        <NFormItem required label="Nuotrauka">
          <NMessageProvider>
            <UploadImageWithCropper v-model:url="form.image" folder="news" />
          </NMessageProvider>
        </NFormItem>
        <NFormItem label="Nuotraukos autorius">
          <NInput v-model:value="form.image_author" type="text" placeholder="Žmogus arba organizacija.." />
        </NFormItem>
      </FormElement>
      <FormElement>
        <template #title>
          Įvadinis tekstas
        </template>
        <template #description>
          <p>Šiuo metu naudojamas <strong>tik paieškos rezultatuose</strong>. Maksimalus ženklų skaičius: 200.</p>
        </template>
        <TipTap v-model="form.short" disable-tables :max-characters="200" html
          :search-files="$page.props.search.other" />
      </FormElement>
      <h4 class="mb-4 text-3xl font-bold">
        Turinys
      </h4>
      <RichContentFormElement v-model="form.content.parts" />
      <div class="flex justify-end gap-2">
        <DeleteModelButton v-if="deleteModelRoute" :form="form" :model-route="deleteModelRoute" />
        <UpsertModelButton :form="form" :model-route="modelRoute" @save="updateContents">
          Sukurti
        </UpsertModelButton>
      </div>
    </div>
  </NForm>
</template>

<script setup lang="ts">
import {
  NDatePicker,
  NForm,
  NFormItem,
  NInput,
  NMessageProvider,
  NSelect,
  NSwitch,
} from "naive-ui";
import { computed, watch } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import latinize from "latinize";

import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import FormElement from "./FormElement.vue";
import InfoText from "../SmallElements/InfoText.vue";
import RichContentFormElement from "../RichContentFormElement.vue";
import TipTap from "@/Components/TipTap/OriginalTipTap.vue";
import UploadImageWithCropper from "../Buttons/UploadImageWithCropper.vue";
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  news: App.Entities.News;
  otherLangNews: App.Entities.News[];
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("news", props.news);

const otherLangNewsOptions = computed(() => {
  if (props.modelRoute === "news.store") {
    return [];
  }

  return props.otherLangNews
    .map((news) => ({
      value: news.id,
      label: `${news.title} (${news.padalinys?.shortname})`,
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

if (props.modelRoute == "news.store") {
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
    }
  );
}
</script>
