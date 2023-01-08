<template>
  <NSpin :show="!isFinished">
    <NForm ref="formRef" :rules="rules" :model="model">
      <NGrid cols="1"
        ><NFormItemGi label="Tikslo pavadinimas" required path="title">
          <!-- TODO: Dabar neveikia sukūrimas tikslo iš šios vietos -->
          <NSelect
            v-model:value="model.id"
            :options="data"
            tag
            filterable
            label-field="title"
            value-field="id"
            placeholder="60 laisvų kreditų implementavimas Gyvybės mokslų centre"
            ><template #action>
              <span
                class="prose-sm prose-gray text-xs text-zinc-400 dark:prose-invert"
                >Gali sukurti ir savo tikslą! Įrašyk +
                <NTag size="tiny">Enter</NTag></span
              >
            </template></NSelect
          >
        </NFormItemGi>
        <NFormItemGi :show-label="false">
          <NButton
            :loading="loading"
            :disabled="!model.id"
            type="primary"
            @click.prevent="pickGoal"
            >Pridėti</NButton
          >
        </NFormItemGi>
      </NGrid>
    </NForm>
    <template #description> Krauname tikslus. 😊 </template>
  </NSpin>
</template>

<script setup lang="tsx">
import { Inertia } from "@inertiajs/inertia";
import {
  NButton,
  NForm,
  NFormItemGi,
  NGrid,
  NSelect,
  NSpin,
  NTag,
} from "naive-ui";
import { ref } from "vue";
import { useAxios } from "@vueuse/integrations/useAxios";
import { useForm } from "@inertiajs/inertia-vue3";


const emit = defineEmits(["closeModal"]);

const props = defineProps<{
  matter: App.Entities.Matter;
}>();

const loading = ref(false);
const rules = {
  id: {
    required: true,
    message: "Privaloma pasirinkti klausimo grupę",
  },
};
const model = useForm({
  id: props.matter.goal_id,
});

const formRef = ref(null);

const { data, isFinished } = useAxios("/api/v1/goals");

const pickGoal = () => {
  formRef.value?.validate((errors) => {
    if (!errors) {
      // switch (typeof model.title) {
      // case "string":
      //   model.post(route("goals.store"), {
      //     onSuccess: (page) => {
      //       Inertia.post(
      //         route("matters.attachGoal", {
      //           matter: props.matter.id,
      //           goal: page.props.flash.data,
      //         }),
      //         {},
      //         {
      //           onSuccess: () => {
      //             emit("closeModal");
      //           },
      //         }
      //       );
      //     },
      //   });
      //   break;
      // case "number":
      Inertia.post(
        route("matters.attachGoal", {
          matter: props.matter.id,
          // the title is an id number here...
          goal: model.id,
        }),
        {},
        {
          onSuccess: () => {
            emit("closeModal");
          },
        }
      );
    }
  });
};
</script>
