<template>
  <NForm :model="form" label-placement="top">
    <div class="flex flex-col">
      <FormElement>
        <template #title> Pagrindinė informacija </template>
        <template #description
          ><p class="mb-4">
            Iniciatyva yra bet kokia testinė veikla, kurią gali vykdyti tiek ir
            organizacijos, tiek ir neformalios studentų grupės.
          </p>
        </template>
        <NFormItem :label="$t('forms.fields.title')" required>
          <NInput
            v-if="locale === 'lt'"
            v-model:value="form.title.lt"
            type="text"
            placeholder="Vilniaus universiteto Studentų atstovybė"
            ><template #suffix
              ><SimpleLocaleButton
                v-model:locale="locale"
              ></SimpleLocaleButton></template
          ></NInput>
          <NInput
            v-else
            v-model:value="form.title.en"
            type="text"
            placeholder="Vilnius University Students' Representation"
            ><template #suffix
              ><SimpleLocaleButton
                v-model:locale="locale"
              ></SimpleLocaleButton></template
          ></NInput>
        </NFormItem>
      </FormElement>
      <FormElement>
        <template #title>Kontaktinė informacija</template>
        <template #description
          >Visi kontaktinės informacijos laukeliai</template
        >
        <!-- Add email, phone, participation_url form items -->
        <NFormItem label="El. paštas">
          <NInput
            v-model:value="form.email"
            type="text"
            placeholder="iniciatyva@gmail.com"
          />
        </NFormItem>
        <NFormItem label="Telefonas">
          <NInput
            v-model:value="form.phone"
            type="text"
            placeholder="+37061234567"
          />
        </NFormItem>
        <NFormItem label="Dalyvavimo nuoroda">
          <NInput
            v-model:value="form.participation_url"
            type="text"
            placeholder="https://www.facebook.com/events/1234567890/"
          />
        </NFormItem>
      </FormElement>
      <FormElement>
        <template #title>Detalus aprašymas</template>
        <NFormItem label="Logotipas">
          <UploadImageButtons v-model="form.logo" :path="'initiatives'" />
        </NFormItem>
        <NFormItem label="Pagrindinė nuotrauka">
          <UploadImageButtons v-model="form.cover" :path="'initiatives'" />
        </NFormItem>
        <NFormItem>
          <template #label
            ><div class="inline-flex items-center gap-2">
              Aprašymas
              <SimpleLocaleButton
                v-model:locale="locale"
              ></SimpleLocaleButton></div
          ></template>
          <TipTap
            v-if="locale === 'lt'"
            v-model="form.description.lt"
            :search-files="$page.props.search.other"
          />
          <TipTap
            v-else
            v-model="form.description.en"
            :search-files="$page.props.search.other"
          />
        </NFormItem>
      </FormElement>
    </div>
    <div class="flex justify-end gap-2">
      <DeleteModelButton
        v-if="deleteModelRoute"
        :form="form"
        :model-route="deleteModelRoute"
      ></DeleteModelButton>
      <UpsertModelButton :form="form" :model-route="modelRoute" />
    </div>
  </NForm>
</template>

<script setup lang="tsx">
import { NForm, NFormItem, NInput } from "naive-ui";
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";

import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import FormElement from "./FormElement.vue";
import SimpleLocaleButton from "../Buttons/SimpleLocaleButton.vue";
import TipTap from "@/Components/TipTap/OriginalTipTap.vue";
import UploadImageButtons from "@/Components/Buttons/UploadImageButtons.vue";
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  initiative: any;
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const locale = ref("lt");

const form = useForm("initiative", props.initiative);
</script>
