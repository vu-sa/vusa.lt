<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <NFormItem>
        <template #label>
          <span class="inline-flex items-center gap-1">
            Pagrindinis organizatorius
          </span>
        </template>
        <UserPopover class="mt-2" show-name :user="training.organizer" />
      </NFormItem>
      <NFormItem required>
        <template #label>
          <span class="inline-flex items-center gap-1">
            <NIcon :component="Icons.TITLE" />
            Pavadinimas
          </span>
        </template>
        <MultiLocaleInput v-model:input="form.name" />
      </NFormItem>
      <NFormItem required>
        <template #label>
          <div class="inline-flex items-center gap-2">
            Aprašymas
            <SimpleLocaleButton v-model:locale="locale" />
          </div>
        </template>
        <TipTap v-if="locale === 'lt'" v-model="form.description.lt" html />
        <TipTap v-else v-model="form.description.en" html />
      </NFormItem>
      <NFormItem required>
        <template #label>
          <span class="flex items-center gap-1">
            <NIcon :component="Icons.INSTITUTION" />
            {{ $t("Kas organizuoja mokymus?") }}
          </span>
        </template>

        <NSelect v-model:value="form.institution_id" filterable :options="institutions" />
      </NFormItem>
      <div class="flex flex-wrap gap-4">
        <NFormItem label="Mokymų pradžia" required>
          <NDatePicker v-model:value="form.start_time" :first-day-of-week="0" :format="'yyyy-MM-dd HH:mm'"
            :time-picker-props="{
              format: 'HH:mm',
              minutes: 5,
              hours: Array.from({ length: 22 - 8 + 1 }, (v, i) => i + 8),
            }" type="datetime" clearable :actions="['confirm']" />
        </NFormItem>
        <NFormItem label="Mokymų pabaiga" required>
          <NDatePicker v-model:value="form.end_time" :first-day-of-week="0" :format="'yyyy-MM-dd HH:mm'"
            :time-picker-props="{
              format: 'HH:mm',
              minutes: 5,
              hours: Array.from({ length: 22 - 8 + 1 }, (v, i) => i + 8),
            }" type="datetime" clearable :actions="['confirm']" />
        </NFormItem>
      </div>
    </FormElement>
    <FormElement>
      <template #title>
        Papildoma informacija
      </template>
      <NFormItem label="Mokymų tipas">
        <NCheckbox v-model:checked="form.is_online">
          Nuotoliniai mokymai
        </NCheckbox>
        <NCheckbox v-model:checked="form.is_hybrid" :disabled="!form.is_online">
          Yra galimybė dalyvauti tiek nuotoliu, tiek vietoje
        </NCheckbox>
      </NFormItem>
      <NFormItem v-if="!form.is_online || form.is_hybrid" label="Mokymų adresas">
        <NInput v-model:value="form.address" />
      </NFormItem>
      <NFormItem v-if="form.is_online" label="Susitikimo nuoroda">
        <NInput v-model:value="form.meeting_url" />
      </NFormItem>
      <NFormItem label="Nuotrauka">
        <UploadImageWithCropper v-model:url="form.image" folder="trainings" />
      </NFormItem>
      <NFormItem label="Dalyvių skaičius" required>
        <NInputNumber v-model:value="form.max_participants" clearable :min="0" />
      </NFormItem>
    </FormElement>
    <NTabs type="segment" animated default-value="programa">
      <NTabPane class="my-4 rounded-xl border border-zinc-500" name="registracija" tab="Registracija">
        <div class="m-4">
          <FormForm :form="formTemplate" @submit:form="handleFormFormSubmit" />
        </div>
      </NTabPane>
      <NTabPane name="programa" tab="Programa" display-directive="show">
        <!-- <NButton @click="showProgrammePlanner = true">
          <template #icon>
            <IFluentAdd24Filled />
          </template>
          {{ $t('Sudaryti programą') }}
</NButton> -->
        <ProgrammePlanner :start-time="new Date(form.start_time)" :show="showProgrammePlanner"
          @close="showProgrammePlanner = false" />
      </NTabPane>
    </NTabs>
    <template #buttons>
      <NButton @click="$emit('submit:form', form)">
        <template #icon>
          <IFluentSave24Filled />
        </template>
        {{ $t('Išsaugoti dabartinę būseną') }}
      </NButton>
      <NButton type="primary">
        <template #icon>
          <IFluentSave24Filled />
        </template>
        {{ $t('Teikti tvirtinimui') }}
      </NButton>
    </template>
  </AdminForm>
</template>

<script setup lang="tsx">
import { useForm, usePage } from "@inertiajs/vue3";
import { NIcon } from "naive-ui";
import { computed, ref, watch } from "vue";

import FormElement from "./FormElement.vue";
import AdminForm from "./AdminForm.vue";
import Icons from "@/Types/Icons/regular";
import MultiLocaleInput from "../FormItems/MultiLocaleInput.vue";
import SimpleLocaleButton from "../Buttons/SimpleLocaleButton.vue";
import TipTap from "@/Components/TipTap/OriginalTipTap.vue";
import UploadImageWithCropper from "../Buttons/UploadImageWithCropper.vue";
import FormForm from "./FormForm.vue";
import UserPopover from "../Avatars/UserPopover.vue";
import ProgrammePlanner from "@/Features/Admin/ProgrammePlaner/ProgrammePlanner.vue";
import { useDebounce, useDebounceFn } from "@vueuse/core";

const { training } = defineProps<{
  training: App.Entities.Membership;
}>();

const emit = defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

const form = useForm("training", training);
const showProgrammePlanner = ref(false);

form.start_time = new Date(form.start_time).getTime();
form.end_time = form.end_time ? new Date(form.end_time).getTime() : null;

const locale = ref("lt");

const formTemplate = training.form?.id ? training.form : {
  name: { lt: '', en: '' },
  description: { lt: '', en: '' },
  path: { lt: '', en: '' },
  form_fields: [],
  tenant_id: training.tenant.id,
};

watch(() => form.is_online, (value) => {
  if (!value) {
    form.is_hybrid = false;
  }
});

// NOTE: Duplicated in InstitutionSelectorForm.vue
const institutions = computed(() => {
  return usePage()
    .props.auth?.user?.current_duties?.map((duty) => {
      if (!duty.institution) {
        return;
      }

      return {
        label: duty.institution?.name,
        value: duty.institution?.id,
      };
    })
    // filter unique
    .filter((institution) => institution !== undefined).filter(
      (value, index, self) =>
        self.findIndex((t) => t?.value === value?.value) === index
    );
});

const handleFormFormSubmit = (form: unknown) => {
  if (training.form_id === null) {
    form.transform((data) => ({
      ...data,
      training_id: training.id,
      redirect_to: route('trainings.edit', training.id),
    })).post(route('forms.store'), {
      preserveScroll: true,
    });
  } else {
    form.patch(route('forms.update', training.form_id), {
      preserveScroll: true,
    });
  }
};

const autosaveFn = useDebounceFn(() => {
  emit('submit:form', form);
}, 5000);


watch(() => form.isDirty, () => {
  console.log(form)
  if (form.isDirty) {
    autosaveFn();
  }
}, { deep: true });
</script>
