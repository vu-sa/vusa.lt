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
          <FormFieldWrapper id="target_tag_id" label="Tikslinė žyma" required>
            <Select v-model="targetTagIdString">
              <SelectTrigger>
                <SelectValue placeholder="Pasirinkite žymą" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="tag in tagOptions" :key="tag.value" :value="String(tag.value)">
                  <span class="flex items-center gap-2">
                    {{ tag.label }}
                    <Badge v-if="tag.alias" size="tiny" variant="secondary">{{ tag.alias }}</Badge>
                  </span>
                </SelectItem>
              </SelectContent>
            </Select>
          </FormFieldWrapper>
          <FormFieldWrapper id="source_tag_ids" label="Sujungiamos žymos" required>
            <MultiSelect
              v-model="selectedSourceTags"
              :options="tagObjectOptions"
              label-field="label"
              value-field="value"
              placeholder="Pasirinkite žymas"
            >
              <template #option="{ item }">
                <span class="flex items-center gap-2">
                  {{ item.label }}
                  <Badge v-if="item.alias" size="tiny" variant="secondary">{{ item.alias }}</Badge>
                </span>
              </template>
            </MultiSelect>
          </FormFieldWrapper>
        </FormElement>
      </AdminForm>
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

import { Badge } from '@/Components/ui/badge';
import { MultiSelect } from '@/Components/ui/multi-select';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import AdminForm from '@/Components/AdminForms/AdminForm.vue';
import FormElement from '@/Components/AdminForms/FormElement.vue';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import Icons from '@/Types/Icons/regular';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';

const { tags } = defineProps<{
  tags: App.Entities.Tag[];
}>();

const form = useForm({
  target_tag_id: null as number | null,
  source_tag_ids: [] as number[],
});

const tagOptions = computed(() =>
  tags.map(tag => ({
    label: typeof tag.name === 'object' ? (tag.name.lt || tag.name.en || 'Unknown') : tag.name,
    value: tag.id,
    alias: tag.alias,
  })),
);

const tagObjectOptions = computed(() => tagOptions.value);

// Bridge string <-> number for Select
const targetTagIdString = computed({
  get: () => form.target_tag_id != null ? String(form.target_tag_id) : '',
  set: (val: string) => { form.target_tag_id = val ? Number(val) : null; },
});

// Bridge object array <-> id array for MultiSelect
const selectedSourceTags = computed({
  get: () => tagObjectOptions.value.filter(opt => form.source_tag_ids.includes(opt.value)),
  set: (items) => { form.source_tag_ids = items.map(item => item.value); },
});

function handleFormSubmit() {
  form.post(route('tags.processMerge'), {
    onSuccess: () => {
      form.reset();
    },
  });
}
</script>
