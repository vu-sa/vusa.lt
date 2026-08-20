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

          <div class="space-y-4">
            <div v-for="locale in LOCALES" :key="locale.code" class="space-y-2">
              <Label class="inline-flex items-center gap-2">
                <img :src="locale.flag" :alt="locale.name" class="h-4 w-4 rounded-full">
                {{ $t('settings.site_settings.privacy_page_label') }} — {{ locale.name }}
              </Label>

              <CollectionSelectDialog
                v-model:open="dialogOpen[locale.code]"
                collection="pages"
                allow-empty
                :base-filter-by="baseFilterBy(locale.code)"
                :initial-hits="initialHits(selected[locale.code])"
                :title="$t('settings.site_settings.privacy_page_label')"
                :confirm-label="$t('Pasirinkti')"
                :search-placeholder="$t('settings.site_settings.privacy_page_search_placeholder')"
                :empty-message="$t('settings.site_settings.privacy_page_empty')"
                @confirm="hits => onConfirm(locale.code, hits)"
              >
                <template #trigger>
                  <Button type="button" variant="outline" class="w-full justify-between font-normal">
                    <span class="truncate" :class="{ 'text-muted-foreground': !selected[locale.code] }">
                      {{ triggerLabel(selected[locale.code]) }}
                    </span>
                    <ChevronDown class="size-4 opacity-50" />
                  </Button>
                </template>
              </CollectionSelectDialog>
            </div>
          </div>
        </FormElement>
      </AdminForm>
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { reactive } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronDown } from 'lucide-vue-next';

import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import AdminForm from '@/Components/AdminForms/AdminForm.vue';
import FormElement from '@/Components/AdminForms/FormElement.vue';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';
import { CollectionSelectDialog } from '@/Features/Admin/AdminSearch/Components/Select';
import { normalizeHit, type NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';

interface SelectedPage {
  id: string;
  title: string;
  lang: string;
  tenant_shortname?: string | null;
}

type LocaleCode = 'lt' | 'en';

const props = defineProps<{
  selectedPages: { lt: SelectedPage | null; en: SelectedPage | null };
}>();

const LOCALES = [
  { code: 'lt' as const, flag: 'https://hatscripts.github.io/circle-flags/flags/lt.svg', name: 'Lietuvių' },
  { code: 'en' as const, flag: 'https://hatscripts.github.io/circle-flags/flags/gb.svg', name: 'English' },
];

const selected = reactive<{ lt: SelectedPage | null; en: SelectedPage | null }>({
  lt: props.selectedPages.lt,
  en: props.selectedPages.en,
});

const dialogOpen = reactive<{ lt: boolean; en: boolean }>({ lt: false, en: false });

// The form seeds from the summaries, not the raw setting ids: a configured page that has
// since been deactivated or deleted shows as "not set" and saving clears the stale id.
const form = useForm({
  privacy_page_id_lt: props.selectedPages.lt?.id ?? null,
  privacy_page_id_en: props.selectedPages.en?.id ?? null,
});

const baseFilterBy = (locale: LocaleCode): string => `is_active:=true && lang:=${locale}`;

const initialHits = (page: SelectedPage | null): NormalizedSearchHit[] => page
  ? [normalizeHit('pages', {
      id: page.id,
      title: page.title,
      tenant_name: page.tenant_shortname,
      lang: page.lang,
    })]
  : [];

const triggerLabel = (page: SelectedPage | null): string => page
  ? `${page.title}${page.tenant_shortname ? ` (${page.tenant_shortname})` : ''}`
  : $t('settings.site_settings.privacy_page_placeholder');

function onConfirm(locale: LocaleCode, hits: NormalizedSearchHit[]): void {
  selected[locale] = hits[0]
    ? { id: hits[0].recordId, title: hits[0].title, lang: locale, tenant_shortname: hits[0].badge }
    : null;
  form[`privacy_page_id_${locale}`] = hits[0]?.recordId ?? null;
}

const handleFormSubmit = () => {
  form.post(route('settings.site.update'));
};
</script>
