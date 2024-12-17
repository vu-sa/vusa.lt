<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <template #description>
        <p>Pagrindinė informacija apie renginį.</p>
        <ul>
          <li>
            <p>
              <strong>Kategorija </strong>keičia spalvą renginių kalendoriuje.
            </p>
          </li>
          <li>
            <p>
              <strong>Organizatorius</strong>, jeigu neįrašytas, bus
              <strong>{{ defaultOrganizer }}</strong>
            </p>
          </li>
        </ul>
      </template>
      <NFormItem :label="$t('forms.fields.title')" required>
        <MultiLocaleInput v-model:input="form.title" />
      </NFormItem>
      <div class="grid gap-4 lg:grid-cols-2">
        <NFormItem label="Organizatorius">
          <MultiLocaleInput v-model:input="form.organizer" />
        </NFormItem>
        <NFormItem label="Renginio vieta">
          <MultiLocaleInput v-model:input="form.location" />
        </NFormItem>
      </div>
      <NFormItem label="Ar šablonas?" required>
        <NSwitch v-model:value="form.is_draft" />
      </NFormItem>
      <NFormItem label="Kategorija">
        <NSelect v-model:value="form.category" :options="categories" placeholder="Pasirinkti kategoriją..."
          label-field="name" value-field="id" clearable />
      </NFormItem>
      <NFormItem label="Viešinimo auditorija">
        <div class="grid w-full grid-cols-2 gap-4">
          <NButton strong :type="form.is_international ? 'primary' : 'default'" @click="form.is_international = true">
            Visi studentai
            <template #icon>
              <IFluentGlobe20Regular width="16" />
            </template>
          </NButton>
          <NButton :type="form.is_international ? 'default' : 'primary'" @click="form.is_international = false">
            Tik lietuviškai mokantys studentai
          </NButton>
        </div>
      </NFormItem>
      <NFormItem label="Padalinys">
        <NSelect v-model:value="form.tenant_id" :options="assignableTenants" label-field="shortname" value-field="id"
          placeholder="VU SA ..." :default-value="assignableTenants[0].id ?? ''" />
      </NFormItem>
    </FormElement>
    <FormElement>
      <template #title>
        Informacija apie renginio laiką
      </template>
      <template #description>
        <p class="mb-2">
          Jeigu nėra nurodytas pabaigos laikas, kalendoriuje renginys
          rodomas kaip 1 val. trukmės.
        </p>
      </template>
      <div class="grid gap-4 lg:grid-cols-3">
        <NFormItem label="Renginio pradžia" required>
          <NDatePicker v-model:formatted-value="form.date" placeholder="Pasirinkti pradžios laiką..."
            format="yyyy-MM-dd HH:mm" :time-picker-props="{ format: 'HH:mm' }" type="datetime" />
        </NFormItem>

        <NFormItem label="Renginio pabaiga">
          <NDatePicker v-model:formatted-value="form.end_date" placeholder="Pasirinkti pabaigos laiką..."
            format="yyyy-MM-dd HH:mm" :time-picker-props="{ format: 'HH:mm' }" type="datetime" clearable />
        </NFormItem>
        <NFormItem>
          <template #label>
            <div class="inline-flex items-center gap-2">
              Visos dienos renginys<InfoPopover>
                ICS kalendoriuje šis renginys bus žymimas kaip
                <strong>visos dienos</strong> renginys.
              </InfoPopover>
            </div>
          </template>
          <NSwitch v-model:value="form.is_all_day" />
        </NFormItem>
      </div>
    </FormElement>
    <FormElement>
      <template #title>
        Informacija viešinimui
      </template>
      <NFormItem>
        <template #label>
          <div class="inline-flex items-center gap-2">
            CTO (Call to action) nuoroda<InfoPopover>
              Įvedus nuorodą, renginio aprašyme bus rodomas
              <strong>CTO mygtukas</strong>, kuris nukreips vartotoją į
              nurodytą nuorodą. Dažniausiai šis mygtukas turėtų vesti į
              <strong> pagrindinį renginio puslapį</strong> arba
              <strong>registracijos formą</strong>.
            </InfoPopover>
          </div>
        </template>
        <MultiLocaleInput v-model:input="form.cto_url" />
      </NFormItem>

      <NFormItem :label="$t('forms.fields.facebook_url')">
        <NInput v-model:value="form.facebook_url" type="text"
          placeholder="https://www.facebook.com/events/584152539934772" />
      </NFormItem>

      <NFormItem label="Youtube video kodas">
        <NInputGroup>
          <NInput autosize value="https://www.youtube.com/embed/" :disabled="true" />
          <NInput v-model:value="form.video_url" type="text" placeholder="dQw4w9WgXcQ" />
        </NInputGroup>
      </NFormItem>
    </FormElement>
    <FormElement>
      <template #title>
        Papildoma informacija
      </template>
      <NFormItem
        label="Įkelti paveikslėlius (pirmas bus panaudotas, kaip pagrindinis. Jeigu metama klaida, prieš tai sumažinkite paveikslėlius)"
        :span="6">
        <NUpload ref="upload" accept="image/jpg, image/jpeg, image/png" list-type="image-card"
          :default-file-list="form.images" multiple @change="handleUploadChange" @remove="handleUploadRemove">
          Įkelti paveikslėlius
        </NUpload>
      </NFormItem>

      <NFormItem label="Aprašymas">
        <template #label>
          <div class="inline-flex items-center gap-2">
            Aprašymas
            <SimpleLocaleButton v-model:locale="locale" />
          </div>
        </template>
        <TipTap v-if="locale === 'lt'" v-model="form.description.lt" html />
        <TipTap v-else v-model="form.description.en" html />
      </NFormItem>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import {
  type UploadFileInfo,
  type UploadInst,
} from "naive-ui";
import { computed, ref, useTemplateRef } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";

import FormElement from "./FormElement.vue";
import InfoPopover from "../Buttons/InfoPopover.vue";
import TipTap from "@/Components/TipTap/OriginalTipTap.vue";
import MultiLocaleInput from "../FormItems/MultiLocaleInput.vue";
import SimpleLocaleButton from "../Buttons/SimpleLocaleButton.vue";
import AdminForm from "./AdminForm.vue";

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

const props = defineProps<{
  calendar: CalendarEventForm;
  categories: App.Entities.Category[];
  assignableTenants: App.Entities.Tenant[];
}>();

const locale = ref("lt");

const form = useForm("calendar", props.calendar);

const defaultOrganizer = computed(() => {
  return (
    props.calendar.tenant?.shortname ?? usePage().props.auth?.user.tenant
  );
});

const upload = useTemplateRef<UploadInst | null>('upload');

const handleUploadChange = (options: {
  file: UploadFileInfo;
  fileList: Array<UploadFileInfo>;
  event?: Event;
}) => {
  form.images = options.fileList;
};

const handleUploadRemove = (options: {
  file: UploadFileInfo;
  fileList: Array<UploadFileInfo>;
  event?: Event;
}) => {
  if (options.file.status === "pending") return;
  router.post(
    route("calendar.destroyMedia", {
      calendar: form.id,
      media: options.file.id,
    }),
    {},
    {
      preserveScroll: true,
    }
  );
};
</script>
