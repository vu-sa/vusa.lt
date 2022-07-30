<template>
  <NForm
    ref="formRef"
    class="prose"
    :label-width="80"
    :model="formValue"
    :rules="rules"
    size="medium"
  >
    <NFormItem class="w-1/2" label="Vardas ir pavardė" path="name">
      <NInput
        v-model:value="formValue.name"
        placeholder=""
        type="text"
        :input-props="{
          autocomplete: 'name',
        }"
      />
    </NFormItem>
    <NFormItem
      class="w-1/2"
      label="Naudojami įvardžiai (pronouns)"
      path="pronouns"
    >
      <NInput v-model:value="formValue.pronouns" placeholder="" type="text" />
    </NFormItem>
    <NFormItem label="Telefono numeris" path="phone">
      <NInput
        v-model:value="formValue.phone"
        placeholder=""
        :input-props="{ type: 'tel' }"
      />
    </NFormItem>
    <NFormItem label="El. paštas" path="email">
      <NInput
        v-model:value="formValue.email"
        placeholder=""
        :input-props="{ type: 'email' }"
      />
    </NFormItem>
    <!-- Add date picker -->
    <NFormItem label="Gimimo data" path="birthDate">
      <NDatePicker
        v-model:value="formValue.birthDate"
        placeholder="Gimimo data"
        type="date"
        :input-props="{
          autocomplete: 'birthDate',
        }"
      />
    </NFormItem>
    <!-- Add select -->
    <NFormItem label="Studijų programa" path="studyProgram">
      <NSelect
        v-model:value="formValue.studyProgram"
        placeholder="Studijų programa"
        :options="studyProgramOptions"
      />
    </NFormItem>
    <NFormItem
      class="my-8"
      label="Primename, kad 4 dienų stovyklos kaina – 52 eurai. Į šią kainą įskaičiuota nakvynė, maitinimas, kelionė autobusais."
      path="reminderForPrice"
    >
      <NCheckbox v-model:checked="formValue.reminderForPrice"
        >Patvirtinu, kad esu susipažinęs su šia informacija</NCheckbox
      >
      <template #label
        ><p class="mb-0">
          Primename, kad <strong>4 dienų stovyklos kaina – 52 eurai.</strong> Į
          šią kainą įskaičiuota nakvynė, maitinimas, kelionė autobusais.
        </p></template
      >
    </NFormItem>
    <NFormItem
      class="mt-4"
      label="Ar būsi stovykloje visas keturias dienas? Jei ne, nurodyk, kuriomis dienomis stovykloje dalyvausi. Primename, stovykla vyks rugpjūčio 19–22 dienomis (penktadienis–pirmadienis)"
      path="all4Days"
    >
      <NInput
        v-model:value="formValue.all4Days"
        type="textarea"
        placeholder=""
      />
    </NFormItem>
    <NFormItem label="Kaip planuoji vykti į stovyklą?" path="transport">
      <NRadioGroup v-model:value="formValue.transport" name="Transportas">
        <NSpace>
          <NRadio value="Autobusu kartu su visais nuo Vilniaus"
            >Autobusu kartu su visais nuo Vilniaus</NRadio
          >
          <NRadio value="Savu transportu">Savu transportu</NRadio>
        </NSpace>
      </NRadioGroup>
    </NFormItem>
    <NFormItem label="Tu esi:" path="vegetarian">
      <NRadioGroup v-model:value="formValue.vegetarian" name="Valgymo ypatumai">
        <NSpace>
          <NRadio value="Visavalgis">Visavalgis</NRadio>
          <NRadio value="Vegetaras">Vegetaras</NRadio>
          <NRadio value="Veganas">Veganas</NRadio>
        </NSpace>
      </NRadioGroup>
    </NFormItem>
    <NFormItem label="Ar turi alergijų? Jei taip, kokių?" path="allergies">
      <NInput
        v-model:value="formValue.allergies"
        type="textarea"
        placeholder=""
      />
    </NFormItem>
    <NFormItem label="Ar turi specialiųjų poreikių?" path="specialNeeds">
      <NInput
        v-model:value="formValue.specialNeeds"
        type="textarea"
        placeholder=""
      />
    </NFormItem>
    <NFormItem
      label="Papildoma informacija apie Tave, kurią turėtų žinoti organizatoriai!"
      path="moreInfo"
    >
      <NInput
        v-model:value="formValue.moreInfo"
        type="textarea"
        placeholder=""
      />
    </NFormItem>
    <NFormItem label="Įdomiausias faktas apie Tave:" path="special1">
      <NInput
        v-model:value="formValue.special1"
        type="textarea"
        placeholder=""
      />
    </NFormItem>
    <NFormItem
      label="Filmas/serialas/knyga, kurį visi turi pamatyti/perskaityti?"
      path="special2"
    >
      <NInput
        v-model:value="formValue.special2"
        type="textarea"
        placeholder=""
      />
    </NFormItem>
    <NFormItem label="Ar turi integralą?" path="special3">
      <NInput
        v-model:value="formValue.special3"
        type="textarea"
        placeholder=""
      />
    </NFormItem>
    <NFormItem label="Mėgstamiausias „Magija“ sūrelis?" path="special4">
      <NInput
        v-model:value="formValue.special4"
        type="textarea"
        placeholder=""
      />
    </NFormItem>
    <NFormItem
      class="mt-6"
      label="Jeigu oro temperatūra +31, vėjas pučia iš rytų ir Naglis Šulija pranešė, jog jis atneš lietų. Kokia dabar saulės padėtis Burkina Fase, jeigu žinome, kad vakar naktį Antarktidoje pingvinai šoko lietaus šokį?"
      path="special5"
    >
      <NInput
        v-model:value="formValue.special5"
        type="textarea"
        placeholder=""
      />
    </NFormItem>
    <NFormItem path="acceptGDPR"
      ><NCheckbox v-model:checked="formValue.acceptGDPR">
        Susipažinau su
        <a
          target="_blank"
          href="https://vusa.lt/uploads/Dokumentų šablonai/Asmens_duomenu_tvarkymo_VUSA_tvarkos_aprasas.pdf"
          @click.stop
          >Asmens duomenų tvarkymo Vilniaus universiteto Studentų atstovybėje
          tvarkos aprašu</a
        >
        ir sutinku</NCheckbox
      ></NFormItem
    >
    <NFormItem path="acceptDataManagement">
      <NCheckbox v-model:checked="formValue.acceptDataManagement"
        >Sutinku, kad mano pateikti asmens duomenys būtų tvarkomi renginių
        organizavimo tikslu pagal Asmens duomenų tvarkymo Vilniaus universiteto
        Studentų atstovybėje tvarkos aprašą</NCheckbox
      >
    </NFormItem>

    <NButton type="primary" @click="handleValidateClick"> Pateikti </NButton>
  </NForm>
</template>

<script lang="ts">
const { message } = createDiscreteApi(["message"]);
</script>

<script setup lang="ts">
import {
  FormInst,
  FormItemRule,
  FormRules,
  FormValidationError,
  NButton,
  NCheckbox,
  NDatePicker,
  NForm,
  NFormItem,
  NInput,
  NInputNumber,
  NRadio,
  NRadioGroup,
  NSelect,
  NSpace,
  createDiscreteApi,
} from "naive-ui";
import { computed, ref } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";

import PageArticle from "@/Components/Public/PageArticle.vue";
import PublicLayout from "@/Components/Public/Layouts/PublicLayout.vue";

import { Inertia, Method } from "@inertiajs/inertia";
import route from "ziggy-js";

const formRef = ref<FormInst | null>(null);

const formBlueprint = {
  name: null,
  pronouns: null,
  phone: null,
  email: null,
  birthDate: null,
  studyProgram: null,
  reminderForPrice: null,
  all4Days: null,
  transport: null,
  vegetarian: null,
  allergies: null,
  specialNeeds: null,
  moreInfo: null,
  special1: null,
  special2: null,
  special3: null,
  special4: null,
  special5: null,
  acceptGDPR: false,
  acceptDataManagement: false,
};

const formValue = useForm("SaziningaiExam", formBlueprint);

const rules: FormRules = {
  name: {
    required: true,
    message: "Įrašykite savo vardą ir pavardę",
    trigger: "blur",
  },
  pronouns: {
    required: true,
    message: "Įrašykite savo įvardžius",
    trigger: "blur",
  },
  phone: {
    required: true,
    // message: "Įrašykite savo telefono numerį",
    trigger: "blur",
    type: "number",
    // validate phone number with + sign and spaces
    validator(rule: FormItemRule, value: string) {
      if (!value) {
        return new Error("Įrašykite savo telefono numerį");
      }
      if (!/^\+?[0-9\s]*$/i.test(value)) {
        return new Error("Neteisingas telefono numerio formatas");
      }
      return true;
    },
  },
  email: {
    required: true,
    trigger: "blur",
    validator(rule: FormItemRule, value: string) {
      if (!value) {
        return new Error("Įrašykite savo el. paštą");
      }
      if (!/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i.test(value)) {
        return new Error("Neteisingas el. pašto formatas");
      }
      return true;
    },
  },
  birthDate: {
    type: "number",
    required: true,
    message: "Pasirinkite savo gimimo datą",
    trigger: "blur",
  },
  studyProgram: {
    required: true,
    message: "Pasirinkite savo studijų programą",
    trigger: "blur",
  },
  reminderForPrice: {
    required: true,
    message: "Turite sutikti su GDPR taisyklėmis",
    trigger: "blur",
    validator(rule, value) {
      return value;
    },
  },
  all4Days: {
    required: true,
    message: "Įrašyk dienas, kuriomis dalyvausi",
    trigger: "blur",
  },
  transport: {
    required: true,
    message: "Pasirinkite transportą",
    trigger: "blur",
  },
  vegetarian: {
    required: true,
    message: "Pasirinkite",
    trigger: "blur",
  },
  allergies: {
    required: true,
    message: "Įrašykite",
    trigger: "blur",
  },
  specialNeeds: {
    required: true,
    message: "Pasirinkite",
    trigger: "blur",
  },
  moreInfo: {
    required: true,
    message: "Pasirinkite",
    trigger: "blur",
  },
  special1: {
    required: true,
    message: "Pasirinkite",
    trigger: "blur",
  },
  special2: {
    required: true,
    message: "Pasirinkite",
    trigger: "blur",
  },
  special3: {
    required: true,
    message: "Pasirinkite",
    trigger: "blur",
  },
  special4: {
    required: true,
    message: "Pasirinkite",
    trigger: "blur",
  },
  special5: {
    required: true,
    message: "Pasirinkite",
    trigger: "blur",
  },
  acceptGDPR: {
    required: true,
    message: "Turite sutikti su GDPR taisyklėmis",
    trigger: "blur",
    validator(rule, value) {
      return value;
    },
  },
  acceptDataManagement: {
    required: true,
    message: "Turite sutikti su duomenų tvarkymu",
    trigger: "blur",
    // * Error if not defined
    validator(rule, value) {
      return value;
    },
  },
};

const handleValidateClick = (e: MouseEvent) => {
  e.preventDefault();
  formRef.value?.validate((errors: Array<FormValidationError> | undefined) => {
    if (!errors) {
      formValue.submit(
        Method.POST,
        route("registration.store", { registration: 1 }),
        {
          onSuccess: () => {
            message.success(
              `Sėkmingai užsiregistravote į VU MIF pirmakursių stovyklą! Laukite laiško iš VU SA MIF komandos!`
            );
            // showModal.value = false;
            // formValue.reset();
          },
        }
      );
    } else {
      message.error("Užpildykite visus laukelius.");
    }
  });
};

const studyProgramOptions = [
  {
    label: "Bioinformatika",
    value: "Bioinformatika",
  },
  {
    label: "Duomenų mokslas",
    value: "Duomenų mokslas",
  },
  {
    label: "Finansų ir draudimo matematika",
    value: "Finansų ir draudimo matematika",
  },
  {
    label: "Informacinės technologijos",
    value: "Informacinės technologijos",
  },
  {
    label: "Informacinių sistemų inžinerija",
    value: "Informacinių sistemų inžinerija",
  },
  {
    label: "Informatika",
    value: "Informatika",
  },
  {
    label: "Matematika ir matematikos taikymai",
    value: "Matematika ir matematikos taikymai",
  },
  {
    label: "Programų sistemos",
    value: "Programų sistemos",
  },
  {
    label: "Verslo duomenų analitika",
    value: "Verslo duomenų analitika",
  },
];
</script>
