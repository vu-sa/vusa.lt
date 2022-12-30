<template>
  <NSpin :show="!isFinished">
    <NForm ref="formRef" :rules="rules" :model="model">
      <NGrid cols="1"
        ><NFormItemGi label="Klausimo grupės pavadinimas" required path="title"
          ><NSelect
            v-model:value="model.title"
            :options="data"
            tag
            filterable
            label-field="title"
            value-field="id"
            placeholder="60 laisvų kreditų implementavimas Gyvybės mokslų centre"
            ><template #action>
              <span
                class="prose-sm prose-gray text-xs text-zinc-400 dark:prose-invert"
                >Gali sukurti ir savo klausimo grupę! Įrašyk +
                <NTag size="tiny">Enter</NTag></span
              >
            </template></NSelect
          >
        </NFormItemGi>
        <NFormItemGi :show-label="false">
          <NButton
            :loading="loading"
            :disabled="!model.title"
            type="primary"
            @click.prevent="pickQuestionGroup"
            >Pridėti</NButton
          >
        </NFormItemGi>
      </NGrid>
    </NForm>
    <template #description> Krauname klausimų grupes. 😊 </template>
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
import route from "ziggy-js";

const emit = defineEmits(["closeModal"]);

const props = defineProps<{
  question: App.Models.Question;
}>();

const loading = ref(false);
const rules = {
  title: {
    required: true,
    message: "Privaloma pasirinkti klausimo grupę",
  },
};
const model = useForm({
  title: props.question.question_group_id,
});

const formRef = ref(null);

const { data, isFinished } = useAxios("/api/v1/questionGroups");

const pickQuestionGroup = () => {
  formRef.value?.validate((errors) => {
    if (!errors) {
      switch (typeof model.title) {
        case "string":
          model.post(route("questionGroups.store"), {
            onSuccess: (page) => {
              Inertia.post(
                route("questions.attachQuestionGroup", {
                  question: props.question.id,
                  questionGroup: page.props.flash.data,
                }),
                {},
                {
                  onSuccess: () => {
                    emit("closeModal");
                  },
                }
              );
            },
          });
          break;
        case "number":
          Inertia.post(
            route("questions.attachQuestionGroup", {
              question: props.question.id,
              // the title is an id number here...
              questionGroup: model.title,
            }),
            {},
            {
              onSuccess: () => {
                emit("closeModal");
              },
            }
          );
          break;

        default:
          break;
      }
    }
  });
};
</script>
