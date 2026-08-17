<template>
  <PageContent :title="$t('settings.pages.site.title')" :back-url="route('settings.index')">
    <UpsertModelLayout>
      <AdminForm :model="form" @submit:form="handleFormSubmit">
        <FormElement>
          <template #title>
            {{ $t('settings.site_settings.privacy_page_title') }}
          </template>
          <template #description>
            {{ $t('settings.site_settings.privacy_page_description') }}
          </template>

          <div class="space-y-2">
            <Label class="inline-flex items-center gap-1">
              <component :is="PageIcon" class="h-4 w-4" />
              {{ $t('settings.site_settings.privacy_page_label') }}
            </Label>

            <SingleSelect
              v-model="selectedPage"
              :options="pageOptions"
              label-field="label"
              value-field="id"
              :placeholder="$t('settings.site_settings.privacy_page_placeholder')"
              :empty-text="$t('settings.site_settings.no_pages_found')"
            />
          </div>
        </FormElement>
      </AdminForm>
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import AdminForm from '@/Components/AdminForms/AdminForm.vue';
import FormElement from '@/Components/AdminForms/FormElement.vue';
import { Label } from '@/Components/ui/label';
import { SingleSelect } from '@/Components/ui/single-select';
import { PageIcon } from '@/Components/icons';

interface PageOption {
  id: string;
  title: string;
  permalink: string;
  lang: string;
  label: string;
}

const props = defineProps<{
  privacy_page_id: string | null;
  pages: Array<Omit<PageOption, 'label'>>;
}>();

// The permalink is shown alongside the title because several pages share a title across the
// two language records, and the permalink is what the visitor will actually land on.
const pageOptions = computed<PageOption[]>(() =>
  props.pages.map(page => ({
    ...page,
    label: `${page.title} (${page.lang}/${page.permalink})`,
  })),
);

const selectedPage = ref<PageOption | null>(
  pageOptions.value.find(page => page.id === props.privacy_page_id) ?? null,
);

const form = useForm({
  privacy_page_id: props.privacy_page_id,
});

watch(selectedPage, (page) => {
  form.privacy_page_id = page?.id ?? null;
});

const handleFormSubmit = () => {
  form.post(route('settings.site.update'));
};
</script>
