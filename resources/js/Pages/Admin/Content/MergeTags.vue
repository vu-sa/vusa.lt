<template>
  <PageContent title="Žymų suliejimas" :heading-icon="Icons.TAG">
    <UpsertModelLayout>
      <AdminForm :model="form" @submit:form="handleFormSubmit">
        <FormElement>
          <template #title>
            Sulieti žymas
          </template>
          <template #description>
            <div class="typography">
              <p>
                Pasirinkus žymas, <strong>visi</strong> priskyrimai bus perduoti į tikslinę žymą.
              </p>
              <p> 
                Šis veiksmas yra <strong>neatstatomas</strong>! Sujungtos žymos bus ištrintos. 
              </p>
            </div>
          </template>
          <NFormItem required>
            <template #label>
              <span class="inline-flex items-center gap-1">
                <NIcon :component="Icons.TAG" />
                Tikslinė žyma
              </span>
            </template>
            <NSelect v-model:value="form.target_tag_id" filterable :options="tagOptions" 
              :render-label="renderTagLabel" placeholder="Pasirinkite žymą" />
          </NFormItem>
          <NFormItem required>
            <template #label>
              <span class="inline-flex items-center gap-1">
                <NIcon :component="Icons.TAG" />
                Sujungiamos žymos
              </span>
            </template>
            <NSelect v-model:value="form.source_tag_ids" multiple filterable :options="tagOptions" 
              :render-label="renderTagLabel" placeholder="Pasirinkite žymas" />
          </NFormItem>
        </FormElement>
      </AdminForm>
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">
import { NTag, type SelectRenderLabel } from "naive-ui";
import { useForm } from "@inertiajs/vue3";
import { h, computed } from "vue";

import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import AdminForm from "@/Components/AdminForms/AdminForm.vue";
import FormElement from "@/Components/AdminForms/FormElement.vue";

const { tags } = defineProps<{
  tags: App.Entities.Tag[];
}>();

const form = useForm({
  target_tag_id: null,
  source_tag_ids: [],
});

const tagOptions = computed(() => 
  tags.map(tag => ({
    label: typeof tag.name === 'object' ? (tag.name.lt || tag.name.en || 'Unknown') : tag.name,
    value: tag.id,
    alias: tag.alias,
  }))
);

const renderTagLabel: SelectRenderLabel = (option: any) => {
  return h("div", { class: "flex items-center gap-2" }, [
    h("span", option.label),
    option?.alias ? h(NTag, { size: "tiny" }, option.alias) : null,
  ]);
};

function handleFormSubmit() {
  form.post(route("tags.processMerge"), {
    onSuccess: () => {
      form.reset();
    },
  });
}
</script>
