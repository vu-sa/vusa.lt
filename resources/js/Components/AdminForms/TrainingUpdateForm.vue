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
      <NFormItem label="Mokymų adresas">
        <NInput v-model:value="form.address" />
      </NFormItem>
      <NFormItem label="Susitikimo nuoroda">
        <NInput v-model:value="form.meeting_url" />
      </NFormItem>
      <NFormItem label="Nuotrauka">
        <UploadImageWithCropper v-model:url="form.image" folder="trainings" />
      </NFormItem>
      <NFormItem label="Dalyvių skaičius" required>
        <NInputNumber v-model:value="form.max_participants" clearable :min="0" />
      </NFormItem>
    </FormElement>
    <NTabs type="segment" animated>
      <NTabPane name="dalyviai" tab="Kas gali dalyvauti?">
        <FormElement no-divider class="mt-4 min-h-64">
          <template #title>
            Dalyviai
          </template>
          <NDynamicInput v-model:value="form.trainables" class="mt-4" @create="onTrainablesCreate">
            <template #default="{ value }">
              <div class="flex w-full gap-4">
                <NFormItem label="Tipas">
                  <NSelect v-model:value="value.trainable_type" class="min-w-40"
                    :options="trainableTypeOptions" />
                </NFormItem>
                <NFormItem v-if="value.trainable_type" label="Pasirinkimas" class="min-w-80">
                  <NSelect v-model:value="value.trainable_id" filterable
                    :options="trainableTypes[value.trainable_type].values" value-field="id"
                    :label-field="value.trainable_type === 'App\\Models\\Type' ? 'title' : 'name'" />
                </NFormItem>
              </div>
            </template>
          </NDynamicInput>
        </FormElement>
      </NTabPane>
      <NTabPane class="my-4 rounded-xl border border-zinc-500" name="registracija" tab="Registracijos forma">
        <div class="m-4 min-h-64">
          <FormForm :form="formTemplate" @submit:form="handleFormFormSubmit" />
        </div>
      </NTabPane>
      <NTabPane name="programa" tab="Programa" display-directive="show">
        <div class="mt-4 min-h-64 rounded-md border border-black p-4">
          <ProgrammePlanner editable show-time-switch :programme="training.programme"
            :start-time="new Date(form.start_time)" />
        </div>
      </NTabPane>
      <NTabPane name="uzduotys" tab="Užduotys">
        <FormElement no-divider class="mt-4 min-h-64">
          <template #title>
            Užduotys
          </template>
          <NDynamicInput v-model:value="form.tasks" @create="onTasksCreate">
            <template #default="{ value, index }">
              <NFormItem label="Užduotis">
                <div class="flex items-start gap-4">
                  <MultiLocaleInput v-model:input="value.name" />
                  <NButtonGroup class="ml-4">
                    <NButton @click="onTasksCreateAdd">
                      <template #icon>
                        <!-- add -->
                        <IFluentAdd24Filled />
                      </template>
                    </NButton>
                    <NButton @click="onTasksRemove(index)">
                      <template #icon>
                        <IFluentDelete24Filled />
                      </template>
                    </NButton>
                  </NButtonGroup>
                </div>
              </NFormItem>
            </template>
            <template #action>
              <div />
            </template>
          </NDynamicInput>
        </FormElement>
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
import { router, useForm, usePage } from "@inertiajs/vue3";
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
import ProgrammePlanner from "@/Features/Admin/ProgrammePlanner/ProgrammePlanner.vue";

interface TrainableType<T> {
  type: T;
  name: string;
  values: Array<{ id: number; name: string }>;
}

export interface TrainableTypes {
  'App\\Models\\User': TrainableType<App.Entities.User>;
  'App\\Models\\Duty': TrainableType<App.Entities.Duty>;
  'App\\Models\\Institution': TrainableType<App.Entities.Institution>;
  'App\\Models\\Membership': TrainableType<App.Entities.Membership>;
  'App\\Models\\Type': TrainableType<{ id: number; title: string }>;
}

const { training, trainableTypes } = defineProps<{
  training: App.Entities.Training;
  trainableTypes: TrainableTypes
}>();

const emit = defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

const form = useForm(`TrainingUpdate${training.id}`, training);

const trainableTypeOptions = computed(() => {
  return Object.keys(trainableTypes).map((key) => ({
    label: trainableTypes[key].name,
    value: key,
  }));
});

const onTrainablesCreate = () => {
  return {
    trainable_type: null,
    trainable_id: null
  };
};

const onTasksCreate = () => {
  return {
    name: { lt: '', en: '' },
  };
};

const onTasksCreateAdd = () => {
  form.tasks.push({
    name: { lt: "", en: "" },
  });
  //return {
  //  name: { lt: '', en: '' },
  //};
};

const onTasksRemove = (index: number) => {
  form.tasks.splice(index, 1);
};

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
    router.post(route('forms.store'), {
      ...form,
      training_id: training.id,
      redirect_to: route('trainings.edit', training.id),
    }, {
      preserveScroll: true,
    });
  } else {
    router.patch(route('forms.update', training.form_id), form);
  }
};
</script>
