<template>
  <PublicLayout title="Programos „Sąžiningai“ užregistruoti egzaminai">
    <!-- <PageArticle> -->
    <div class="pt-8 px-8 lg:px-16 last:pb-2">
      <h1>Programos „Sąžiningai“ užregistruoti egzaminai</h1>
      <p class="my-4">Registruotis reikia į kiekvieną srautą atskirai.</p>
      <div class="main-card">
        <NDataTable
          :scroll-x="1200"
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
      <p class="font-bold mb-4">
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
          ><NCheckbox
            v-model:checked="formValue.acceptGDPR"
            :label="labelGDPR"
          ></NCheckbox
        ></NFormItem>
        <NFormItem path="acceptDataManagement">
          <NCheckbox
            v-model:checked="formValue.acceptDataManagement"
            :label="labelAcceptDataManagement"
          ></NCheckbox>
        </NFormItem>
        <NFormItem>
          <NButton type="success" @click="handleValidateClick"> Pateikti </NButton>
        </NFormItem>
      </NForm>
    </NCard>
  </NModal>
</template>

<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue";
import PageArticle from "../../Components/Public/PageArticle.vue";
import { ref, h } from "vue";
import {
  NForm,
  NFormItem,
  NInput,
  NSelect,
  NDynamicInput,
  NDatePicker,
  useMessage,
  NInputNumber,
  NCheckbox,
  NDataTable,
  NButton,
  NModal,
  NCard,
} from "naive-ui";
import { Inertia } from "@inertiajs/inertia";

const props = defineProps({
  padaliniaiOptions: Array,
  saziningaiExamFlows: Array,
});

const message = useMessage();
const showModal = ref(false);
const formRef = ref(null);
const formValue = ref({
  name: null,
  email: null,
  phone: null,
  flow: null,
  exam_name: null,
  start_time: null,
  padalinys_id: null,
  acceptGDPR: false,
  acceptDataManagement: false,
});

const labelGDPR = h("label", {}, [
  "Susipažinau su ",
  h(
    "a",
    {
      target: "_blank",
      href:
        "https://vusa.lt/uploads/Dokumentų šablonai/Asmens_duomenu_tvarkymo_VUSA_tvarkos_aprasas.pdf",
    },
    "Asmens duomenų tvarkymo Vilniaus universiteto Studentų atstovybėje tvarkos aprašu"
  ),
  " ir sutinku.",
]);

const labelAcceptDataManagement = h(
  "label",
  {},
  "Sutinku, kad mano pateikti asmens duomenys būtų tvarkomi vidaus administravimo tikslu pagal Asmens duomenų tvarkymo Vilniaus universiteto Studentų atstovybėje tvarkos aprašą."
);

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
      title: "Registruotis į stebėjimą",
      key: "register",
      render(row) {
        return h(
          NButton,
          {
            onClick: () => {
              formValue.value.flow = row.key;
              formValue.value.exam_name = row.exam.subject_name;
              formValue.value.start_time = row.start_time;
              showModal.value = true;
            },
          },
          "Registruotis"
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

// map padaliniaiOptions to options for NSelect
const padaliniaiOptions = props.padaliniaiOptions.map((padalinys) => ({
  value: padalinys.id,
  label: padalinys.shortname_vu,
}));

const handleValidateClick = (e) => {
  e.preventDefault();
  formRef.value?.validate((errors) => {
    if (!errors) {
      Inertia.post(route("saziningaiExamObserver.store"), formValue.value, {
        onSuccess: () => {
          showModal.value = false;
          message.success(
            `Ačiū už užsiregistravimą stebėti „${formValue.value.exam_name}“!`
          );
          Object.keys(formValue.value).forEach((i) => (formValue.value[i] = null));
        },
      });
    } else {
      // console.log(errors);
      message.error("Užpildykite visus laukelius.");
    }
  });
};
</script>
