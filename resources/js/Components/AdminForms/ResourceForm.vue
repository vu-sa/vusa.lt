<template>
  <NForm :model="form" label-placement="top" :disabled="formDisabled">
    <div class="flex flex-col">
      <FormElement>
        <template #title>Pagrindinė informacija</template>
        <NFormItem label="Pavadinimas" required>
          <MultiLocaleInput
            v-model:input="form.name"
            v-model:lang="inputLang"
            placeholder="JBL kolonėlė (įkraunama)"
          />
        </NFormItem>
        <NFormItem label="Aprašymas" required>
          <MultiLocaleInput
            v-model:input="form.description"
            v-model:lang="inputLang"
            input-type="textarea"
            placeholder="Tikslus modelis: ABC123. Nuoroda internete? Naudoti tik perskaičius instrukciją..."
          />
        </NFormItem>
        <NFormItem label="Padalinys, kuriam priklauso daiktas" :span="2">
          <NSelect
            v-model:value="form.padalinys_id"
            :options="padaliniai"
            label-field="shortname"
            value-field="id"
            placeholder="VU SA X"
            clearable
          />
        </NFormItem>
      </FormElement>
      <FormElement>
        <template #title>Nuotraukos</template>
        <template #description
          >Rekomenduojama, kad kiekvienas išteklius turėtų nuotraukų. Jas gali
          matyti ir rezervaciją kuriantys asmenys.</template
        >
        <NUpload
          ref="upload"
          :file-list="form.media"
          accept="image/jpg, image/jpeg, image/png"
          list-type="image-card"
          multiple
          @change="handleChange"
        >
          Įkelti paveikslėlius
        </NUpload>
      </FormElement>
      <FormElement>
        <template #title>Papildoma informacija</template>
        <NFormItem label="Adresas, vieta..." required>
          <NInput
            v-model:value="form.location"
            placeholder="Naugarduko g. X (VU P), 2 sandėliukas"
          />
        </NFormItem>
        <NFormItem label="Vnt. skaičius" required>
          <NInputNumber v-model:value="form.capacity" :min="1" type="number" />
        </NFormItem>
        <NFormItem label="Ar šiuo metu rezervuojamas?" required>
          <NSwitch
            v-model:value="form.is_reservable"
            :checked-value="1"
            :unchecked-value="0"
          />
        </NFormItem>
      </FormElement>
    </div>
    <div class="flex justify-end gap-2">
      <!-- <DeleteModelButton
        v-if="deleteModelRoute"
        :form="form"
        :model-route="deleteModelRoute"
      /> -->
      <!-- <UpsertModelButton :form="form" :model-route="modelRoute" /> -->
      <NButton @click="submit">Pateikti</NButton>
    </div>
  </NForm>
</template>

<script setup lang="ts">
import {
  NButton,
  NForm,
  NFormItem,
  NInput,
  NInputNumber,
  NSelect,
  NSwitch,
  NUpload,
  type UploadFileInfo,
} from "naive-ui";
import { computed, ref } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";

import FormElement from "./FormElement.vue";
import MultiLocaleInput from "../SimpleAugment/MultiLocaleInput.vue";
// import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";
import type { ResourceCreationTemplate } from "@/Pages/Admin/Reservations/CreateResource.vue";
import type { ResourceEditType } from "@/Pages/Admin/Reservations/EditResource.vue";

const props = defineProps<{
  resource: ResourceCreationTemplate | ResourceEditType;
  padaliniai: App.Entities.Padalinys[];
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const routeToSubmit = computed(() => {
  return props.resource?.id
    ? route(props.modelRoute, props.resource.id)
    : route(props.modelRoute);
});

const form = useForm(props.resource);

// padalinys_id is set to 0 if it's not found. Shouldn't happen for authenticated users.
const formDisabled = computed(() => {
  return form.padalinys_id === 0;
});

const submit = () => {
  // add _method: "patch" if it's an update, to the data of the request
  // because formdata doesn't support patch, it's needed
  router.post(
    routeToSubmit.value,
    {
      ...form,
      _method: props.resource?.id ? "patch" : "post",
    },
    {
      preserveScroll: true,
    }
  );
};

const handleChange = ({ fileList }: { fileList: Array<UploadFileInfo> }) => {
  form.media = fileList;
};

const inputLang = ref(usePage().props.app.locale);
</script>
