<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <FormFieldWrapper id="organizer" label="Pagrindinis organizatorius">
        <UserPopover class="mt-2" show-name :user="training.organizer" />
      </FormFieldWrapper>
      <FormFieldWrapper id="name" label="Pavadinimas" required :error="form.errors.name">
        <MultiLocaleInput v-model:input="form.name" />
      </FormFieldWrapper>
      <div class="space-y-2">
        <div class="inline-flex items-center gap-2">
          <Label for="description">Aprašymas</Label>
          <span class="text-red-500">*</span>
          <SimpleLocaleButton v-model:locale="locale" />
        </div>
        <TiptapEditor v-if="locale === 'lt'" v-model="form.description.lt" preset="full" :html="true" />
        <TiptapEditor v-else v-model="form.description.en" preset="full" :html="true" />
        <p v-if="form.errors.description" class="text-xs text-red-600 dark:text-red-400">
          {{ form.errors.description }}
        </p>
      </div>
      <FormFieldWrapper id="institution_id" :label="$t('Kas organizuoja mokymus?')" required :error="form.errors.institution_id">
        <Select v-model="institutionIdString">
          <SelectTrigger>
            <SelectValue :placeholder="$t('Pasirinkite instituciją')" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="institution in institutions" :key="institution.value" :value="String(institution.value)">
              {{ institution.label }}
            </SelectItem>
          </SelectContent>
        </Select>
      </FormFieldWrapper>
      <div class="flex flex-wrap gap-4">
        <FormFieldWrapper id="start_time" label="Mokymų pradžia" required :error="form.errors.start_time">
          <DateTimePicker v-model="form.start_time" :hour-range="[8, 22]" :minute-step="5" />
        </FormFieldWrapper>
        <FormFieldWrapper id="end_time" label="Mokymų pabaiga" required :error="form.errors.end_time">
          <DateTimePicker v-model="form.end_time" :hour-range="[8, 22]" :minute-step="5" />
        </FormFieldWrapper>
      </div>
    </FormElement>
    <FormElement>
      <template #title>
        Papildoma informacija
      </template>
      <FormFieldWrapper id="address" label="Mokymų adresas" :error="form.errors.address">
        <Input id="address" v-model="form.address" />
      </FormFieldWrapper>
      <FormFieldWrapper id="meeting_url" label="Susitikimo nuoroda" :error="form.errors.meeting_url">
        <Input id="meeting_url" v-model="form.meeting_url" />
      </FormFieldWrapper>
      <FormFieldWrapper id="image" label="Nuotrauka" :error="form.errors.image">
        <ImageUpload v-model:url="form.image" mode="immediate" folder="trainings" cropper :existing-url="training?.image" />
      </FormFieldWrapper>
      <FormFieldWrapper id="max_participants" label="Dalyvių skaičius" required :error="form.errors.max_participants">
        <NumberField id="max_participants" v-model="form.max_participants" :min="0" />
      </FormFieldWrapper>
    </FormElement>
    <Tabs default-value="dalyviai">
      <TabsList class="grid w-full grid-cols-4">
        <TabsTrigger value="dalyviai">
          Kas gali dalyvauti?
        </TabsTrigger>
        <TabsTrigger value="registracija">
          Registracijos forma
        </TabsTrigger>
        <TabsTrigger value="programa">
          Programa
        </TabsTrigger>
        <TabsTrigger value="uzduotys">
          Užduotys
        </TabsTrigger>
      </TabsList>
      <TabsContent value="dalyviai">
        <FormElement no-divider class="mt-4 min-h-64">
          <template #title>
            Dalyviai
          </template>
          <DynamicListInput v-model="form.trainables" :create-item="onTrainablesCreate" allow-empty
            empty-text="Nėra pridėtų dalyvių" add-first-text="Pridėti pirmą dalyvį" add-text="Pridėti dalyvį">
            <template #item="{ item }">
              <div class="flex w-full gap-4">
                <FormFieldWrapper id="trainable_type" label="Tipas">
                  <Select v-model="item.trainable_type">
                    <SelectTrigger class="min-w-40">
                      <SelectValue placeholder="Pasirinkite tipą" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="opt in trainableTypeOptions" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </FormFieldWrapper>
                <FormFieldWrapper v-if="item.trainable_type" id="trainable_id" label="Pasirinkimas" class="min-w-80">
                  <Select v-model="item.trainable_id">
                    <SelectTrigger>
                      <SelectValue placeholder="Pasirinkite" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="val in trainableTypes[item.trainable_type].values" :key="val.id" :value="String(val.id)">
                        {{ item.trainable_type === 'App\\Models\\Type' ? val.title : val.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </FormFieldWrapper>
              </div>
            </template>
          </DynamicListInput>
        </FormElement>
      </TabsContent>
      <TabsContent value="registracija">
        <div class="my-4 min-h-64 rounded-xl border border-zinc-500 p-4">
          <FormForm :form="formTemplate" @submit:form="handleFormFormSubmit" />
        </div>
      </TabsContent>
      <TabsContent value="programa">
        <div class="mt-4 min-h-64 rounded-md border border-black p-4">
          <ProgrammePlanner editable show-time-switch :programme="training.programme"
            :start-time="new Date(form.start_time)" />
        </div>
      </TabsContent>
      <TabsContent value="uzduotys">
        <FormElement no-divider class="mt-4 min-h-64">
          <template #title>
            Užduotys
          </template>
          <DynamicListInput v-model="form.tasks" :create-item="onTasksCreate" allow-empty
            empty-text="Nėra pridėtų užduočių" add-first-text="Pridėti pirmą užduotį" add-text="Pridėti užduotį">
            <template #item="{ item }">
              <FormFieldWrapper id="task_name" label="Užduotis">
                <MultiLocaleInput v-model:input="item.name" />
              </FormFieldWrapper>
            </template>
          </DynamicListInput>
        </FormElement>
      </TabsContent>
    </Tabs>
    <template #buttons>
      <Button variant="outline" @click="$emit('submit:form', form)">
        <IFluentSave24Filled />
        {{ $t('Įsaugoti dabartinę būseną') }}
      </Button>
      <Button>
        <IFluentSave24Filled />
        {{ $t('Teikti tvirtinimui') }}
      </Button>
    </template>
  </AdminForm>
</template>

<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

import MultiLocaleInput from '../FormItems/MultiLocaleInput.vue';
import SimpleLocaleButton from '../Buttons/SimpleLocaleButton.vue';
import UserPopover from '../Avatars/UserPopover.vue';

import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import AdminForm from './AdminForm.vue';
import FormForm from './FormForm.vue';

import { DateTimePicker } from '@/Components/ui/date-picker';
import { DynamicListInput } from '@/Components/ui/dynamic-list-input';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { NumberField } from '@/Components/ui/number-field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Button } from '@/Components/ui/button';
import { ImageUpload } from '@/Components/ui/upload';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import ProgrammePlanner from '@/Features/Admin/ProgrammePlanner/ProgrammePlanner.vue';

interface TrainableType<T> {
  type: T;
  name: string;
  values: Array<{ id: number; name: string; title?: string }>;
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
  trainableTypes: TrainableTypes;
}>();

const emit = defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const form = useForm(`TrainingUpdate${training.id}`, training);

const trainableTypeOptions = computed(() => {
  return Object.keys(trainableTypes).map(key => ({
    label: trainableTypes[key].name,
    value: key,
  }));
});

const onTrainablesCreate = () => ({
  trainable_type: null,
  trainable_id: null,
});

const onTasksCreate = () => ({
  name: { lt: '', en: '' },
});

const locale = ref('lt');

// Shadcn Select requires string values
const institutionIdString = computed({
  get: () => form.institution_id != null ? String(form.institution_id) : '',
  set: (val: string) => { form.institution_id = val ? Number(val) : null; },
});

const formTemplate = training.form?.id
  ? training.form
  : {
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
    .filter(institution => institution !== undefined).filter(
      (value, index, self) =>
        self.findIndex(t => t?.value === value?.value) === index,
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
  }
  else {
    router.patch(route('forms.update', training.form_id), form);
  }
};
</script>
