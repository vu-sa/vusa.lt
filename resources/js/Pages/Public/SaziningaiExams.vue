<template>
  <!-- <PublicLayout title="Programos „Sąžiningai“ užregistruoti egzaminai"> -->
  <Head title="Programos „Sąžiningai“ užregistruoti egzaminai"></Head>
  <article class="grid grid-cols-3 gap-y-4 pt-8 last:pb-2">
    <div class="col-span-3 col-start-1 pr-8">
      <h1>Programos „Sąžiningai“ užregistruoti egzaminai</h1>
      <p class="my-6 text-gray-800 dark:text-zinc-100">
        Registruotis reikia į kiekvieną srautą atskirai.
      </p>
      <NCard class="subtle-gray-gradient w-full rounded-md p-0">
        <NDataTable
          size="small"
          :scroll-x="1200"
          :data="props.saziningaiExamFlows"
          :columns="columns"
        >
        </NDataTable>
      </NCard>
    </div>
  </article>
  <!-- </PublicLayout> -->
  <NModal v-model:show="showModal">
    <NCard
      style="width: 600px"
      :title="`Registracija į egzaminą „${formValue.exam_name}“, vyksiantį ${formValue.start_time}`"
      :bordered="false"
      size="huge"
      role="dialog"
      aria-modal="true"
      class="prose"
    >
      <p class="mb-4 font-bold">
        Atsiskaitymų stebėjimui savo padalinyje studentai negali registruotis.
      </p>
      <NForm
        ref="formRef"
        :label-width="80"
        :model="formValue"
        :rules="rules"
        size="medium"
      >
        <NFormItem label="Vardas ir pavardė" path="name">
          <NInput v-model:value="formValue.name" placeholder="" />
        </NFormItem>
        <NFormItem label="El. paštas" path="email">
          <NInput v-model:value="formValue.email" placeholder="" />
        </NFormItem>
        <NFormItem label="Telefono numeris" path="phone">
          <NInput v-model:value="formValue.phone" placeholder="" />
        </NFormItem>
        <NFormItem
          label="Padalinys, kuriame studijuojate (studijavote)"
          path="padalinys_id"
        >
          <NSelect
            v-model:value="formValue.padalinys_id"
            :options="padaliniaiOptions"
            placeholder=""
          />
        </NFormItem>
        <NFormItem path="acceptGDPR"
          ><NCheckbox v-model:checked="formValue.acceptGDPR"
            >Susipažinau su
            <a
              class="dark:text-vusa-yellow dark:hover:text-vusa-red"
              target="_blank"
              href="https://vusa.lt/uploads/Dokumentų šablonai/Asmens_duomenu_tvarkymo_VUSA_tvarkos_aprasas.pdf"
              @click.stop
              >Asmens duomenų tvarkymo Vilniaus universiteto Studentų
              atstovybėje tvarkos aprašu</a
            >
            ir sutinku</NCheckbox
          ></NFormItem
        >
        <NFormItem path="acceptDataManagement">
          <NCheckbox v-model:checked="formValue.acceptDataManagement"
            >Sutinku, kad mano pateikti asmens duomenys būtų tvarkomi vidaus
            administravimo tikslu pagal Asmens duomenų tvarkymo Vilniaus
            universiteto Studentų atstovybėje tvarkos aprašą</NCheckbox
          >
        </NFormItem>
        <NFormItem>
          <NButton type="primary" @click="handleValidateClick">
            Pateikti
          </NButton>
        </NFormItem>
      </NForm>
    </NCard>
  </NModal>
</template>

<script setup lang="ts">
import {
  type FormInst,
  type FormValidationError,
  NButton,
  NCard,
  NCheckbox,
  NDataTable,
  NForm,
  NFormItem,
  NInput,
  NModal,
  NSelect,
  createDiscreteApi,
} from "naive-ui";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import { h, ref } from "vue";

import FadeTransition from "@/Components/Transitions/FadeTransition.vue";

const props = defineProps<{
  padaliniaiOptions: App.Entities.Padalinys[];
  saziningaiExamFlows: App.Entities.SaziningaiExamFlow[];
}>();

const showModal = ref(false);
const formRef = ref<FormInst | null>(null);

const formBlueprint = {
  name: null,
  email: null,
  phone: null,
  flow: null,
  exam_name: null,
  start_time: null,
  padalinys_id: null,
  acceptGDPR: false,
  acceptDataManagement: false,
};
const formValue = useForm("SaziningaiFlowObserver", formBlueprint);

const rules = {
  name: {
    required: true,
    message: "Įveskite savo vardą",
    trigger: "blur",
  },
  email: {
    required: true,
    trigger: "blur",
    validator(rule, value) {
      if (!value) {
        return new Error("Įrašykite savo el. paštą");
      }
      if (!/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i.test(value)) {
        return new Error("Neteisingas el. pašto formatas");
      }
      return true;
    },
  },
  phone: {
    required: true,
    // message: "Įveskite savo telefono numerį",
    trigger: "blur",
    validator(rule, value) {
      if (!value) {
        return new Error("Įrašykite savo telefono numerį");
      }
      if (!/^\+?[0-9\s]*$/i.test(value)) {
        return new Error("Neteisingas telefono numerio formatas");
      }
      return true;
    },
  },
  padalinys_id: {
    required: true,
    message: "Pasirinkite padalinį",
    trigger: "blur",
    type: "number",
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
    // validate if false, then error
    validator(rule, value) {
      return value;
    },
  },
};

const createColumns = () => {
  return [
    {
      // title: "Registruotis į stebėjimą",
      key: "register",
      width: "125",
      render(row) {
        return h(
          NButton,
          {
            onClick: () => {
              formValue.flow = row.key;
              formValue.exam_name = row.exam.subject_name;
              formValue.start_time = row.start_time;
              showModal.value = true;
            },
            size: "small",
            secondary: true,
            type:
              row.observers_registered >= row.exam.students_need
                ? "default"
                : "warning",
          },
          { default: () => "Registruotis" },
        );
      },
    },
    {
      title: "Dalyko pavadinimas",
      key: "exam.subject_name",
    },
    {
      title: "Padalinys",
      key: "unit",
    },
    {
      title: "Vieta",
      key: "exam.place",
      width: "10%",
      ellipsis: {
        tooltip: true,
      },
    },
    {
      title: "Trukmė",
      key: "exam.duration",
    },
    {
      title: "Tipas",
      key: "exam.exam_type",
    },
    {
      title: "Srauto laikas",
      key: "start_time",
    },
    {
      title: "Egzamino laikytojų skaičius",
      key: "exam.exam_holders",
    },
    {
      title: "Kiek stebėtojų jau užsiregistravo / reikia",
      key: "exam.students_need",
      width: "10%",
      ellipsis: {
        tooltip: true,
      },
      render(row) {
        return h(
          "div",
          {
            class:
              row.observers_registered >= row.exam.students_need
                ? "text-green-700"
                : "text-red-700",
          },
          `${row.observers_registered} / ${row.exam.students_need}`,
        );
      },
    },
  ];
};

const columns = ref(createColumns());
const { message } = createDiscreteApi(["message"]);

// map padaliniaiOptions to options for NSelect
const padaliniaiOptions = props.padaliniaiOptions.map((padalinys) => ({
  value: padalinys.id,
  label: padalinys.shortname_vu,
}));

const handleValidateClick = (e: MouseEvent) => {
  e.preventDefault();
  formRef.value?.validate((errors: Array<FormValidationError> | undefined) => {
    if (!errors) {
      router.post(
        route("saziningaiExamObserver.store", {
          lang: usePage().props.app.locale,
        }),
        formValue,
        {
          onSuccess: () => {
            message.success(
              `Ačiū už užsiregistravimą stebėti „${formValue.exam_name}“ atsiskaitymą!`,
            );
            showModal.value = false;
            formValue.reset();
          },
          preserveState: true,
        },
      );
    } else {
      message.error("Užpildykite visus laukelius.");
    }
  });
};
</script>
