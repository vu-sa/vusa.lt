<template>
  <NForm :model="doingForm">
    <NGrid cols="2">
      <NFormItemGi label="Veiklos pavadinimas" path="title" required :span="2">
        <NSelect
          v-model:value="doingForm.title"
          placeholder="Susitikimas su studentais"
          filterable
          tag
          :options="doingOptions"
          ><template #action>
            <span
              class="prose-sm prose-gray text-xs text-zinc-400 dark:prose-invert"
              >Gali įrašyti ir savo veiklą...</span
            >
          </template></NSelect
        >
      </NFormItemGi>
      <NFormItemGi v-if="doingTypes" label="Tipas" path="doing_type_id" required
        ><NSelect
          v-model:value="doingForm.type_id"
          placeholder="Pasirinkti tipą"
          filterable
          :options="doingTypes"
        ></NSelect
      ></NFormItemGi>

      <NFormItemGi :span="2" :show-label="false"
        ><NButton type="primary" @click="upsertDoing"
          >Sukurti</NButton
        ></NFormItemGi
      >
    </NGrid>
  </NForm>
</template>

<script setup lang="ts">
import { NButton, NForm, NFormItemGi, NGrid, NSelect } from "naive-ui";
import { ref } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

const emit = defineEmits(["success"]);

const props = defineProps<{
  doing: any;
  doingTypes?: any;
  modelRoute: string;
  question: any;
}>();

const showModal = ref(false);
const doingForm = useForm(props.doing);

const doingOptions = [
  {
    label: "Susitikimas su studentais",
    value: "Susitikimas su studentais",
  },
  {
    label: "Planuotas posėdis",
    value: "Planuotas posėdis",
  },
  {
    label: "Susitikimas su koordinatoriumi",
    value: "Susitikimas su koordinatoriumi",
  },
];

const upsertDoing = () => {
  if (props.modelRoute == "doings.update") {
    doingForm.patch(
      route(props.modelRoute, {
        question_id: parseInt(props.question.id),
        doing: props.doing.id,
      }),
      {
        onSuccess: () => {
          showModal.value = false;
          emit("success");
        },
      }
    );
  } else {
    doingForm.post(
      route(props.modelRoute, {
        question_id: parseInt(props.question.id),
      }),
      {
        onSuccess: () => {
          showModal.value = false;
          emit("success");
        },
      }
    );
  }
};
</script>
