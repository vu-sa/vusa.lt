<template>
  <NForm :model="form" label-placement="top">
    <NGrid cols="1 s:4 l:6" responsive="screen" :x-gap="24">
      <NFormItemGi :label="$t('forms.fields.title')" :span="2">
        <NInput
          v-model:value="form.title"
          type="text"
          placeholder="Įrašyti pavadinimą..."
        />
      </NFormItemGi>

      <NFormItemGi label="Nuoroda" :span="2">
        <NInput
          :value="form.permalink"
          disabled
          type="text"
          placeholder="Sugeneruojama nuoroda"
        />
      </NFormItemGi>

      <NFormItemGi label="Kalba" :span="2">
        <NSelect
          v-model:value="form.lang"
          :options="languageOptions"
          placeholder="Pasirinkti kalbą..."
        />
      </NFormItemGi>

      <NFormItemGi label="Kitos kalbos puslapis" :span="2">
        <NSelect
          v-model:value="form.other_lang_id"
          filterable
          :disabled="modelRoute === 'news.store'"
          placeholder="Pasirinkti kitos kalbos puslapį... (tik tada, kai jau sukūrėte puslapį)"
          :options="otherLangNewsOptions"
          clearable
        />
      </NFormItemGi>

      <NFormItemGi label="Naujienos paskelbimo laikas" :span="2">
        <NDatePicker
          v-model:formatted-value="form.publish_time"
          placeholder="Data..."
          type="datetime"
          value-format="yyyy-MM-dd HH:mm:ss"
        />
      </NFormItemGi>

      <NFormItemGi label="Ar juodraštis?" :span="2">
        <NSwitch
          v-model:value="form.draft"
          :checked-value="1"
          :unchecked-value="0"
        >
        </NSwitch>
      </NFormItemGi>

      <NFormItemGi :span="6"
        ><NDivider> Naujienos nuotrauka</NDivider>
      </NFormItemGi>

      <NFormItemGi label="Nuotrauka" :span="2">
        <UploadImageButtons
          v-model="form.image"
          :path="'news'"
        ></UploadImageButtons>
      </NFormItemGi>

      <NFormItemGi label="Nuotraukos autorius" :span="2">
        <NInput
          v-model:value="form.image_author"
          type="text"
          placeholder="Žmogus arba organizacija.."
        />
      </NFormItemGi>
      <NFormItemGi :span="6"
        ><NDivider> Straipsnio turinys</NDivider>
      </NFormItemGi>
      <NFormItemGi :span="6">
        <TipTap v-model="form.short" :search-files="$page.props.search.other" />
        <template #label>
          <span class="text-lg font-bold">Įvadas</span>
        </template>
      </NFormItemGi>

      <NFormItemGi :span="6" class="mt-2">
        <TipTap v-model="form.text" :search-files="$page.props.search.other" />
        <template #label>
          <span class="text-lg font-bold">Pagrindinis tekstas</span>
        </template>
      </NFormItemGi>
    </NGrid>
    <div class="flex justify-end gap-2">
      <UpsertModelButton :form="form" :model-route="modelRoute"
        >Sukurti</UpsertModelButton
      >
    </div>
  </NForm>
</template>

<script setup lang="ts">
import {
  NDatePicker,
  NDivider,
  NForm,
  NFormItemGi,
  NGrid,
  NInput,
  NSelect,
  NSwitch,
} from "naive-ui";
import { computed, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import latinize from "latinize";

import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import TipTap from "@/Components/TipTap/OriginalTipTap.vue";
import UploadImageButtons from "@/Components/Buttons/UploadImageButtons.vue";
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
        .substring(0, 30);
    }
  );
}
</script>
