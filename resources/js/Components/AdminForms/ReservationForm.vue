<template>
  <NForm :model="form" label-placement="top">
    <div class="flex flex-col">
      <FormElement>
        <template #title>{{ $t("forms.context.main_info") }}</template>
        <template #description>
          <component
            :is="RESERVATION_DESCRIPTIONS.main_info[$page.props.app.locale]"
          />
        </template>
        <NFormItem :label="$t('forms.fields.title')" required>
          <MultiLocaleInput
            v-model:input="form.name"
            v-model:lang="inputLang"
            :placeholder="RESERVATION_PLACEHOLDERS.name"
          />
        </NFormItem>
        <NFormItem :label="$t('forms.fields.description')" required>
          <MultiLocaleInput
            v-model:input="form.description"
            v-model:lang="inputLang"
            :placeholder="RESERVATION_PLACEHOLDERS.description"
            input-type="textarea"
          />
        </NFormItem>
        <NFormItem
          required
          :label="capitalize($t('entities.reservation.period'))"
        >
          <NDatePicker
            v-model:value="date"
            :loading="resourceLoading"
            type="datetimerange"
            :first-day-of-week="0"
            format="yyyy-MM-dd HH:mm"
            default-time="13:00:00"
            :time-picker-props="{
              format: 'HH:mm',
              minutes: 15,
            }"
            @update:value="onDateChange"
          />
        </NFormItem>
      </FormElement>
      <FormElement :icon="Icons.RESOURCE">
        <template #title>{{
          capitalize($tChoice("entities.resource.model", 2))
        }}</template>
        <template #description>
          <component
            :is="RESERVATION_DESCRIPTIONS.resources[$page.props.app.locale]"
          />
          <Link class="w-fit" :href="route('resources.index')">
            <div class="inline-flex items-center gap-2">
              <NIcon :component="Icons.RESOURCE" class="align-center" />
              <strong class="underline">{{
                $t("entities.meta.model_list", {
                  model: capitalize($tChoice("entities.resource.model", 11)),
                })
              }}</strong>
            </div>
          </Link>
        </template>
        <NFormItem>
          <template #label>
            <span class="mb-2 inline-flex items-center gap-1"
              ><NIcon :component="Icons.RESOURCE" />
              {{ $t("Pasirinkti ištekliai") }}
            </span>
          </template>
          <NDynamicInput v-model:value="form.resources" :on-create="onCreate">
            <template #default="{ value }">
              <div class="flex w-full gap-2">
                <NSelect
                  v-model:value="value.id"
                  filterable
                  clearable
                  value-field="id"
                  label-field="name"
                  :options="allResourceOptions"
                  :placeholder="
                    RESERVATION_PLACEHOLDERS.resource[$page.props.app.locale]
                  "
                  :render-label="handleRenderResourceLabel"
                  :render-tag="handleRenderResourceTag"
                  @update:value="value.quantity = 1"
                />
                <NInputNumber
                  v-model:value="value.quantity"
                  :min="1"
                  :max="getleftCapacity(value.id)"
                  :default-value="1"
                ></NInputNumber>
              </div>
            </template>
          </NDynamicInput>
        </NFormItem>
      </FormElement>
      <FormElement>
        <template #title>{{ $t("forms.context.additional_info") }}</template>
        <NFormItem :show-label="false">
          <NCheckbox v-model:checked="conditionAcquaintance">
            <template v-if="$page.props.app.locale === 'lt'">
              Sutinku įdėmiai sekti rezervacijos informaciją, išteklius pasiimti
              ir grąžinti laiku.
            </template>
            <template v-else>
              I agree to carefully follow the reservation information, take and
              return the resources on time.
            </template>
          </NCheckbox>
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
      <NButton :disabled="!conditionAcquaintance" @click="submit"
        >Pateikti</NButton
      >
    </div>
  </NForm>
</template>

<script setup lang="tsx">
import { Link, router, usePage } from "@inertiajs/vue3";
import {
  NButton,
  NCheckbox,
  NDatePicker,
  NDynamicInput,
  NForm,
  NFormItem,
  NIcon,
  NInputNumber,
  NSelect,
  type SelectOption,
} from "naive-ui";
import { computed, ref, watch } from "vue";
import { useForm } from "laravel-precognition-vue-inertia";

import { RESERVATION_DESCRIPTIONS } from "@/Constants/I18n/Descriptions";
import { RESERVATION_PLACEHOLDERS } from "@/Constants/I18n/Placeholders";
import { capitalize } from "@/Utils/String";
import {
  renderResourceLabel,
  renderResourceTag,
} from "@/Features/Admin/Reservations/Helpers";
import FormElement from "./FormElement.vue";
import Icons from "@/Types/Icons/regular";
import MultiLocaleInput from "../SimpleAugment/MultiLocaleInput.vue";
import type { ReservationCreationTemplate } from "@/Pages/Admin/Reservations/CreateReservation.vue";
import type { ReservationEditType } from "@/Pages/Admin/Reservations/EditReservation.vue";
// import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

defineEmits<{
  (event: "update:value", value: number | null): void;
}>();

const props = defineProps<{
  reservation: ReservationCreationTemplate | ReservationEditType;
  allResources: App.Entities.Resource[];
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const inputLang = ref(usePage().props.app.locale);
const resourceLoading = ref(false);
const conditionAcquaintance = ref(false);

const routeToSubmit = computed(() => {
  return props.reservation?.id
    ? route(props.modelRoute, props.reservation.id)
    : route(props.modelRoute);
});

const form = useForm(
  props.reservation?.id ? "patch" : "post",
  routeToSubmit.value,
  props.reservation
);

const date = ref<[number, number]>([form.start_time, form.end_time]);

watch(date, (newVal) => {
  form.start_time = newVal[0];
  form.end_time = newVal[1];
});

const onCreate = () => {
  return {
    id: undefined,
    quantity: 1,
  };
};

const onDateChange = (value: [number, number]) => {
  form.resources = [];
  router.reload({
    data: {
      dateTimeRange: { start: value[0], end: value[1] },
    },
    preserveScroll: true,
    only: ["resources"],
    onSuccess: () => {
      resourceLoading.value = false;
    },
  });
};

const getleftCapacity = (id: string) => {
  return props.allResources.find((resource) => resource.id === id)
    ?.lowestCapacityAtDateTimeRange;
};

const handleRenderResourceLabel = (option: SelectOption, selected: boolean) => {
  return renderResourceLabel(option, selected, getleftCapacity(option.id));
};

const handleRenderResourceTag = ({ option }: { option: SelectOption }) => {
  return renderResourceTag(option, props.allResources);
};

const allResourceOptions = computed(() => {
  let selectedResources = form.resources.map((resource) => resource.id);

  return props.allResources.map((resource) => ({
    ...resource,
    disabled:
      resource.lowestCapacityAtDateTimeRange === 0 ||
      selectedResources.includes(resource.id) ||
      !resource.is_reservable,
  }));
});

const submit = () => {
  form.submit({
    preserveScroll: true,
  });
};
</script>
