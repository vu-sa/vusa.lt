<template>
  <NForm :model="form" label-placement="top">
    <NGrid cols="1 s:4 l:6" responsive="screen" :x-gap="24">
      <NFormItemGi :label="$t('forms.fields.title')" :span="2">
        <NInput v-model:value="form.title" type="text" placeholder="Įrašyti pavadinimą..." />
      </NFormItemGi>

      <NFormItemGi label="Nuoroda" :span="2">
        <NInput :value="form.permalink" disabled type="text" placeholder="Sugeneruojama nuoroda..." />
      </NFormItemGi>

      <NFormItemGi label="Kalba" :span="2">
        <NSelect v-model:value="form.lang" filterable :options="languageOptions" placeholder="Pasirinkti kalbą..." />
      </NFormItemGi>

      <NFormItemGi label="Kitos kalbos puslapis" :span="2">
        <NSelect v-model:value="form.other_lang_id" filterable :disabled="modelRoute === 'pages.store'"
          placeholder="Pasirinkti kitos kalbos puslapį... (tik tada, kai jau sukūrėte puslapį)"
          :options="otherPageOptions" clearable />
      </NFormItemGi>

      <NFormItemGi :span="6">
        <template #label>
          <span class="text-2xl font-bold">Turinys</span>
        </template>
        <div class="flex w-full flex-col gap-6">
          <div v-for="content, index in form.contents" :key="content.id"
            class="relative w-full border border-zinc-800 dark:border-zinc-700/40 p-6 rounded-md shadow-md dark:bg-zinc-800/5">
            <div class="absolute -right-4 -top-4">
              <NButton :disabled="form.contents?.length < 2" circle type="error" size="small"
                @click="form.contents.splice(index, 1)">
                <template #icon>
                  <NIcon :component="Dismiss24Regular" />
                </template>
              </NButton>
            </div>
            <p class="font-bold text-xl underline mb-4">#{{ index + 1 }}: {{ contentTypes.find((type) => type.value === content.type)?.label }} </p>
            <div v-if="content.type === 'tiptap'">
              <TipTap v-model="content.json_content" />
            </div>
            <div v-else-if="content.type === 'naiveui-collapse'">
              <NDynamicInput v-model:value="content.json_content" @create="onCreate">
                <template #create-button-default>
                  Sukurti
                </template>
                <template #default="{ value }">
                  <div class="flex w-full flex-col gap-6 rounded-lg border border-zinc-400/80 dark:border-zinc-800/50 dark:bg-zinc-800/20 p-4">
                    <NFormItem label="Pavadinimas" :show-feedback="false">
                      <NInput v-model:value="value.label" type="text" />
                    </NFormItem>
                    <TipTap v-model="value.content" />
                  </div>
                </template>
              </NDynamicInput>
            </div>
          </div>
          <div class="my-2 flex max-w-64 gap-2">
            <NSelect v-model:value="selectedNewContent" :options="contentTypes" />
            <NButton type="primary" @click="form.contents?.push({ json_content: {}, type: selectedNewContent })">Sukurti
              naują
              turinio bloką
            </NButton>
          </div>
        </div>
      </NFormItemGi>
    </NGrid>
    <div class="flex justify-end gap-2">
      <DeleteModelButton v-if="deleteModelRoute" :form="form" :model-route="deleteModelRoute" />
      <UpsertModelButton :form="form" :model-route="modelRoute" @save="updateContents">
        Sukurti
      </UpsertModelButton>
    </div>
  </NForm>
</template>

<script setup lang="ts">
import { Dismiss24Regular } from "@vicons/fluent";
import { NButton, NDynamicInput, NForm, NFormItem, NFormItemGi, NGrid, NIcon, NInput, NSelect } from "naive-ui";
import { computed, ref, watch } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import latinize from "latinize";

import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import TipTap from "@/Components/TipTap/OriginalTipTap.vue";
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  page: App.Entities.Page;
  otherLangPages?: App.Entities.Page[];
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("page", props.page);

function onCreate() {
  return {
    label: "",
    content: {},
  };
}

const selectedNewContent = ref("tiptap");

const contentTypes = [
  {
    value: "tiptap",
    label: "Tekstas",
  },
  {
    value: "naiveui-collapse",
    label: "Akordeonas",
  }
];

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
  form.contents = usePage().props.flash.data?.contents
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
