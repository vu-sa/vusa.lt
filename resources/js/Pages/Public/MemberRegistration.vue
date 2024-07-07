<template>
  <article class="grid grid-cols-3 gap-y-4 pt-8 last:pb-2">
    <h1 class="col-span-3 col-start-1">
      {{ $t("Prašymas tapti VU SA (arba VU SA PKP) nariu") }}
    </h1>
    <div class="typography text-base col-span-3 col-start-1 max-w-prose">
      <p>
        <strong v-if="$page.props.app.locale === 'lt'"
          >Kiekvienas VU studentas gali tapti VU SA nariu!
        </strong>
        <strong v-else>Every VU student can become a VU SR member! </strong>
        <span v-if="$page.props.app.locale === 'lt'"
          >Užsiregistruok ir lauk pakvietimo iš padalinio komandos!
        </span>
        <span v-else>Register and wait for the invite from the team! </span>
        <a target="_blank" :href="aboutLink">{{ $t("Daugiau apie VU SA") }}</a
        >.
      </p>

      <p v-if="$page.props.app.locale === 'lt'">
        Taip pat gali registruotis ir į mūsų programas, klubus ir projektus
        (PKP)!

        <a target="_blank" :href="pkpLink">Visų mūsų PKP ieškok čia</a>.
      </p>
      <p v-else>
        You can also register to our programs, clubs and projects (PKP)!

        <a target="_blank" :href="pkpLink">All of our PKPs can be found here</a
        >.
      </p>

      <NForm
        ref="formRef"
        :label-width="80"
        :model="formValue"
        :rules="rules"
        size="medium"
      >
        <NFormItem :label="$t('Vardas ir pavardė')" path="name">
          <NInput
            v-model:value="formValue.name"
            placeholder="Studentas Studentaitis"
            type="text"
            :input-props="{
              autocomplete: 'name',
            }"
          />
        </NFormItem>
        <NFormItem :label="$t('El. paštas')" path="email">
          <NInput
            v-model:value="formValue.email"
            placeholder="studentas.studentaitis@padalinys.stud.vu.lt"
            :input-props="{ type: 'email' }"
          />
        </NFormItem>
        <NFormItem :label="$t('Tel. nr.')" path="phone">
          <NInput
            v-model:value="formValue.phone"
            placeholder="+370 612 34 567"
            :input-props="{ type: 'tel' }"
          />
        </NFormItem>
        <NFormItem
          :label="$t('Kur nori užsiregistruoti?')"
          path="whereToRegister"
        >
          <NSelect
            v-model:value="formValue.whereToRegister"
            :options="registerOptions"
            placeholder="VU SA"
          />
        </NFormItem>
        <NFormItem :label="$t('Studijų kursas')" path="course">
          <NSelect
            v-model:value="formValue.course"
            :options="courseOptions"
            placeholder="3"
          />
        </NFormItem>
        <NFormItem path="acceptGDPR"
          ><NCheckbox v-model:checked="formValue.acceptGDPR">
            <span v-if="$page.props.app.locale === 'lt'">
              Susipažinau su
              <a
                target="_blank"
                href="https://vusa.lt/uploads/Dokumentų šablonai/Asmens_duomenu_tvarkymo_VUSA_tvarkos_aprasas.pdf"
                @click.stop
                >Asmens duomenų tvarkymo Vilniaus universiteto Studentų
                atstovybėje tvarkos aprašu</a
              >
              ir sutinku
            </span>
            <span v-else
              >I have familiarized myself with the
              <a
                target="_blank"
                href="https://vusa.lt/uploads/files/EN/VU%20SR%20data%20protection%20description.pdf"
                @click.stop
              >
                description of the procedure for the processing of personal data
                in the Vilnius University's Students' Representation
              </a>
              and I agree</span
            >
          </NCheckbox></NFormItem
        >
        <NFormItem path="acceptDataManagement">
          <NCheckbox v-model:checked="formValue.acceptDataManagement">
            <template v-if="$page.props.app.locale === 'lt'">
              Sutinku, kad mano pateikti asmens duomenys būtų tvarkomi vidaus
              administravimo tikslu pagal Asmens duomenų tvarkymo Vilniaus
              universiteto Studentų atstovybėje tvarkos aprašą
            </template>
            <template v-else
              >I agree that the personal data provided by me will be processed
              internally for the purpose of administration in accordance with
              Personal data processing description in the Vilnius University's
              Students' Representation</template
            >
          </NCheckbox>
        </NFormItem>
        <div class="text-sm">
          <p v-if="$page.props.app.locale === 'lt'">
            Duomenų valdytojas yra Vilniaus universiteto Studentų atstovybė.
          </p>
          <p v-else>
            The data manager is the Vilnius University Students' Representation.
          </p>
          <ul>
            <li>
              {{
                `${$t("Adresas")}: ${$t(
                  "Universiteto g. 3, Observatorijos kiemelis",
                )}, Vilnius`
              }}
            </li>
            <li>
              {{ $t("Tel. nr.") }}:
              <a href="tel:+37052687144">+370 (5) 268 7144</a>
            </li>
            <li>
              {{ $t("El. paštas") }}:
              <a href="mailto:info@vusa.lt">info@vusa.lt</a>
            </li>
          </ul>
          <p v-if="$page.props.app.locale === 'lt'">
            Jūsų pateikti duomenys bus naudojami susisiekti su jumis.
          </p>
          <p v-else>The data you provide will be used to contact you.</p>

          <p v-if="$page.props.app.locale === 'lt'">
            Duomenų subjektas turi teisę susipažinti su savo asmens duomenimis,
            teisę reikalauti ištaisyti neteisingus, neišsamius, netikslius savo
            asmens duomenis ir kitas teisės aktais numatytas teises. Kilus
            klausimams ir norint realizuoti savo, kaip duomenų subjekto, teises,
            galite kreiptis į
            <a href="mailto:dap@vusa.lt">dap@vusa.lt</a>.
          </p>
          <p v-else>
            The data subject has the right to access his personal data, the
            right to demand correction of incorrect, incomplete, inaccurate
            personal data and other legal acts rights. If you have questions and
            want to realize your own data rights, you can write to
            <a href="mailto:dap@vusa.lt">dap@vusa.lt</a>.
          </p>
        </div>
        <NButton type="primary" @click="handleValidateClick">
          {{ $t("Pateikti") }}
        </NButton>
      </NForm>
    </div>
  </article>
  <!-- </PublicLayout> -->
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import {
  type FormInst,
  type FormItemRule,
  type FormRules,
  type FormValidationError,
  NButton,
  NCheckbox,
  NForm,
  NFormItem,
  NInput,
  NSelect,
  createDiscreteApi,
} from "naive-ui";
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

import FadeTransition from "@/Components/Transitions/FadeTransition.vue";

const props = defineProps<{
  padaliniaiOptions: Array<App.Entities.Padalinys>;
}>();

const aboutLink = computed(() =>
  route("page", {
    lang: usePage().props.app.locale,
    permalink: usePage().props.app.locale === "lt" ? "apie" : "about",
    subdomain: "www",
  }),
);

const pkpLink = computed(() =>
  route("page", {
    lang: usePage().props.app.locale,
    permalink:
      usePage().props.app.locale === "lt"
        ? "programos-klubai-projektai"
        : "programs-clubs-projects",
    subdomain: "www",
  }),
);

// formRefs are needed by Naive UI
const formRef = ref<FormInst | null>(null);
const { message } = createDiscreteApi(["message"]);

const formBlueprint = {
  name: null,
  // if number, it's VU SA padalinys. if string, it's PKP
  whereToRegister: null,
  course: null,
  email: null,
  phone: null,
  acceptGDPR: false,
  acceptDataManagement: false,
  registrationForm: 2,
};

// useForm saves the form value to a remembered state.
const formValue = useForm("MemberRegistration", formBlueprint);

const padaliniaiOptions = computed(() =>
  props.padaliniaiOptions.map((padalinys) => ({
    value: padalinys.id,
    label: $t(padalinys.fullname),
  })),
);

const registerOptions = computed(() => [
  {
    type: "group",
    label: $t("VU SA PKP"),
    key: "pkp",
    children: [
      {
        label: `HEMA (${$t("Istorinių Europos kovos menų klubas")})`,
        value: "hema",
      },
      { label: `${$t("Jaunųjų energetikų klubas")} (VU JEK)`, value: "jek" },
    ],
  },
  {
    type: "group",
    label: $t("VU SA padaliniai"),
    key: "padaliniai",
    children: padaliniaiOptions.value,
  },
]);

// create courseOptions from one to six
const courseOptions = Array.from({ length: 6 }, (_, i) => ({
  value: i + 1,
  label: i + 1,
}));

const rules: FormRules = {
  name: {
    required: true,
    trigger: "blur",
    validator(rule: FormItemRule, value: string) {
      if (!value) {
        return new Error(
          $t("validation.required", { attribute: $t("Vardas ir pavardė") }),
        );
      }
    },
  },
  email: {
    required: true,
    trigger: "blur",
    validator(rule: FormItemRule, value: string) {
      if (!value) {
        return new Error(
          $t("validation.required", { attribute: $t("El. paštas") }),
        );
      }
      if (!/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i.test(value)) {
        return new Error(
          $t("validation.email", { attribute: $t("El. paštas") }),
        );
      }
      return true;
    },
  },

  phone: {
    required: true,
    // message: "Įrašykite savo telefono numerį",
    trigger: "blur",
    type: "number",
    // validate phone number with + sign and spaces
    validator(rule: FormItemRule, value: string) {
      if (!value) {
        return new Error(
          $t("validation.required", { attribute: $t("Tel. nr.") }),
        );
      }
      if (!/^\+?[0-9\s]*$/i.test(value)) {
        return new Error($t("Neteisingas telefono numerio formatas"));
      }
      return true;
    },
  },
  whereToRegister: {
    required: true,
    trigger: ["blur", "change"],
    validator(rule: FormItemRule, value: string | number) {
      if (!value) {
        return new Error($t("Pasirinkite, į kur norite registruotis"));
      }
      return true;
    },
  },
  course: {
    message: $t("validation.required", { attribute: "course" }),
    trigger: ["blur", "change"],
    type: "number",
  },

  acceptGDPR: {
    required: true,
    trigger: ["blur", "change"],
    validator(rule, value) {
      if (!value) {
        return new Error($t("Turite sutikti su GDPR taisyklėmis"));
      }
    },
  },
  acceptDataManagement: {
    required: true,
    trigger: ["blur", "change"],
    // * Error if not defined
    validator(rule, value) {
      if (!value) {
        return new Error($t("Turite sutikti su duomenų tvarkymu"));
      }
    },
  },
};

const handleValidateClick = (e: MouseEvent) => {
  e.preventDefault();
  formRef.value?.validate((errors: Array<FormValidationError> | undefined) => {
    if (!errors) {
      formValue.submit(
        "post",
        route("memberRegistration.store", {
          lang: usePage().props.app.locale,
        }),
        {
          onSuccess: () => {
            formValue.reset();
            message.success(
              `${$t(
                "Sėkmingai užsiregistravai! Greitu metu susisieksime su tavimi",
              )} 👏`,
              { duration: 15000 },
            );
          },
        },
      );
    } else {
      message.error($t("Užpildykite visus privalomus laukelius"));
    }
  });
};
</script>
