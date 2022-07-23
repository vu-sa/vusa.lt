<template>
  <PublicLayout title="Programos „Sąžiningai“ užregistruoti egzaminai">
    <!-- <PageArticle> -->
    <div class="px-8 pt-8 last:pb-2 lg:px-16">
      <h1>Programos „Sąžiningai“ užregistruoti egzaminai</h1>
      <p class="my-4">Registruotis reikia į kiekvieną srautą atskirai.</p>
      <div class="main-card">
        <NDataTable
          size="small"
          :data="props.saziningaiExamFlows"
          :columns="columns"
        >
        </NDataTable>
      </div>
    </div>
    <!-- </PageArticle> -->
  </PublicLayout>
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
  FormInst,
  FormValidationError,
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
import { Inertia } from "@inertiajs/inertia";
import { h, ref } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

import PublicLayout from "@/components/Public/Layouts/PublicLayout.vue";

const props = defineProps<{
  padaliniaiOptions: App.Models.Padalinys[];
  saziningaiExamFlows: App.Models.SaziningaiExamFlow[];
}>();

// const { message } = createDiscreteApi(["message"]);
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
          { default: () => "Registruotis" }
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
          `${row.observers_registered} / ${row.exam.students_need}`
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
      Inertia.post(route("saziningaiExamObserver.store"), formValue, {
        onSuccess: () => {
          message.success(
            `Ačiū už užregistravimą stebėtį „${formValue.exam_name}“ atsiskaitymą!`
          );
          showModal.value = false;
          formValue.reset();
        },
      });
    } else {
      message.error("Užpildykite visus laukelius.");
    }
  });
};
</script>
