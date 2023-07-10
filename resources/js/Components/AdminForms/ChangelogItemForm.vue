<template>
  <NForm :model="form" label-placement="top">
    <div class="flex flex-col">
      <FormElement>
        <template #title>{{ $t("forms.context.main_info") }} </template>
        <template #description></template>
        <NFormItem label="Pavadinimas" :required="true">
          <NInput v-if="locale === 'lt'" v-model:value="form.title.lt">
            <template #suffix
              ><SimpleLocaleButton v-model:locale="locale"></SimpleLocaleButton
            ></template>
          </NInput>
          <NInput v-if="locale === 'en'" v-model:value="form.title.en">
            <template #suffix
              ><SimpleLocaleButton v-model:locale="locale"></SimpleLocaleButton
            ></template>
          </NInput>
        </NFormItem>
        <NFormItem label="Aprašymas">
          <OriginalTipTap
            v-if="locale === 'lt'"
            v-model="form.description.lt"
            :search-files="$page.props.search.other"
          />
          <OriginalTipTap
            v-if="locale === 'en'"
            v-model="form.description.en"
            :search-files="$page.props.search.other"
          />
        </NFormItem>
        <NFormItem label="Data">
          <NDatePicker v-model:formatted-value="form.date" type="datetime" />
        </NFormItem>
      </FormElement>
    </div>
    <div class="flex justify-end gap-2">
      <UpsertModelButton :form="form" :model-route="modelRoute"
        >Sukurti</UpsertModelButton
      >
    </div>
  </NForm>
</template>

<script setup lang="tsx">
import { NDatePicker, NFormItem, NInput } from "naive-ui";
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";

import FormElement from "./FormElement.vue";
import OriginalTipTap from "../TipTap/OriginalTipTap.vue";
import SimpleLocaleButton from "../Buttons/SimpleLocaleButton.vue";
import UpsertModelButton from "../Buttons/UpsertModelButton.vue";

const props = defineProps<{
  changelogItem: App.Entities.ChangelogItem;
  modelRoute: string;
}>();

const locale = ref("lt");

const form = useForm("changelogItem", props.changelogItem);
</script>
