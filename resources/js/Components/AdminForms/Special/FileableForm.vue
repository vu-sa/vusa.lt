<template>
  <NForm ref="formRef">
    <FadeTransition>
      <SuggestionAlert class="mt-4" :show-alert @alert-closed="$emit('close:alert')">
        <p>
          <span><strong>mano.vusa.lt</strong> platformoje failai yra
            laikomi</span>
          <ModelChip>
            <template #icon>
              <IFluentDocumentTableSearch24Regular />
            </template>
            objektuose
          </ModelChip><span>
            (institucijose, posėdžiuose, etc.), kad būtų išlaikyta failų
            struktūra.</span>
        </p>
        <p class="my-0">
          Dažniausiai šis <em>objektas</em> parenkamas automatiškai, tačiau
          šiuo atveju turėsi pasirinkti, kur įkelti failą. 😊
        </p>
      </SuggestionAlert>
    </FadeTransition>
    <NFormItem label="Objektas">
      <NTreeSelect default-expand-all placeholder="Pasirink instituciją, susitikimą, etc." show-path filterable
        clearable virtual-scroll :options="options" check-strategy="all" @update:value="handleSelect" />
    </NFormItem>
    <NFormItem>
      <Button type="button" @click="handleClick">
        Pateikti
      </Button>
    </NFormItem>
  </NForm>
</template>

<script setup lang="ts">
import {
  type FormInst,
  type TreeSelectOption,
  NForm,
  NFormItem,
  NTreeSelect,
} from "naive-ui";
import { ref, useTemplateRef, watch } from "vue";
import { useFetch } from "@vueuse/core";

import { Button } from "@/Components/ui/button";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import ModelChip from "@/Components/Tag/ModelChip.vue";
import SuggestionAlert from "@/Components/Alerts/SuggestionAlert.vue";
import { Spinner } from "@/Components/ui/spinner";

const emit = defineEmits<{
  (e: "close:alert"): void;
  (e: "submit", data: any): void;
}>();

defineProps<{
  showAlert: boolean;
}>();

const formRef = useTemplateRef<FormInst | null>('formRef');
const spin = ref(true);

const selectedFileable = ref<string | undefined>(undefined);

const handleSelect = (value: string) => {
  selectedFileable.value = value;
};

const options = ref<TreeSelectOption[] | undefined>(undefined);

// Use useFetch reactively - it returns refs that update when data arrives
const { data: fileablesData, isFetching } = useFetch(
  route("sharepoint.getPotentialFileables")
).json();

// Watch for data to arrive and build options
watch([fileablesData, isFetching], ([data, fetching]) => {
  if (!fetching && data) {
    const institutionOptions = data.institutions?.map(
      (institution: App.Entities.Institution) => ({
        label: institution.name,
        key: `${institution.id}_${institution.name}_Institution`,
        children:
          institution.meetings && institution.meetings.length > 0
            ? institution.meetings?.map((meeting: App.Entities.Meeting) => ({
              label: meeting.title,
              key: `${meeting.id}_${meeting.start_time}_Meeting`,
            }))
            : undefined,
      })
    ) ?? [];

    const typeOptions = data.types?.map((type: App.Entities.Type) => ({
      label: type.title,
      key: `${type.id}_${type.title}_Type`,
    })) ?? [];

    options.value = [
      {
        label: "Institucijos",
        key: "institutions",
        children: institutionOptions,
      },
      {
        label: "Tipai",
        key: "types",
        children: typeOptions,
      },
    ];

    spin.value = false;
  }
}, { immediate: true });

const handleClick = () => {
  formRef.value?.validate((errors) => {
    if (!errors) {
      // delimit fileable type, name and id by _
      const [fileableId, fileableName, fileableType] = selectedFileable.value
        ?.split("_")
        .map((item) => item.trim()) ?? [];

      emit("submit", {
        id: fileableId,
        fileable_name: fileableName,
        type: fileableType,
      });
    }
  });
};
</script>
