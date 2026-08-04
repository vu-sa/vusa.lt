<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <!-- Section 1: Basic Information -->
    <FormElement :section-number="1" :is-complete="basicInfoComplete" required>
      <template #title>
        {{ $t('Pagrindinė informacija') }}
      </template>
      <template #subtitle>
        {{ $t('Mygtuko tekstas, ikona ir kalba') }}
      </template>

      <div class="space-y-4">
        <FormFieldWrapper id="text" :label="$t('Mygtuko tekstas')" required :error="form.errors.text">
          <Input id="text" v-model="form.text" type="text" :placeholder="$t('Įrašyti tekstą...')" />
        </FormFieldWrapper>

        <div class="grid gap-4 sm:grid-cols-2">
          <FormFieldWrapper id="icon" :label="$t('Ikona')">
            <Suspense>
              <FluentIconSelect :icon="form.icon" @update:icon="(value) => form.icon = value" />
            </Suspense>
          </FormFieldWrapper>

          <FormFieldWrapper id="lang" :label="$t('Kalba')" required>
            <ToggleGroup v-model="form.lang" type="single" class="justify-start">
              <ToggleGroupItem value="lt" class="gap-2">
                <img src="https://hatscripts.github.io/circle-flags/flags/lt.svg" class="h-4 w-4 rounded-full">
                Lietuvių
              </ToggleGroupItem>
              <ToggleGroupItem value="en" class="gap-2">
                <img src="https://hatscripts.github.io/circle-flags/flags/gb.svg" class="h-4 w-4 rounded-full">
                English
              </ToggleGroupItem>
            </ToggleGroup>
          </FormFieldWrapper>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div class="space-y-2">
            <FormFieldWrapper id="tenant_id" :label="$t('Padalinys')"
              :hint="$t('Padalinys, kuriam priklauso nuoroda')">
              <SingleSelect v-model="selectedTenant" :options="tenantOptions" value-field="value" label-field="label"
                :placeholder="$t('Pasirinkti padalinį...')" />
            </FormFieldWrapper>
            <Button v-if="form.tenant_id" variant="secondary" size="sm" as="a"
              :href="route('quickLinks.index', { tenant: form.tenant_id, lang: form.lang ?? 'lt' })">
              <component :is="QuickLinkIcon" class="h-4 w-4" />
              {{ $t('Tvarkyti eiliškumą') }}
            </Button>
          </div>

          <FormFieldWrapper id="is_important" :label="$t('Ar svarbus?')"
            :hint="$t('Ar rodyti mygtuką kaip svarbų?')">
            <Switch v-model="form.is_important" />
          </FormFieldWrapper>
        </div>
      </div>
    </FormElement>

    <!-- Section 2: Link Target -->
    <FormElement :section-number="2" :is-complete="linkTargetComplete" required>
      <template #title>
        {{ $t('Nuorodos tikslas') }}
      </template>
      <template #description>
        <p>{{ $t('Pasirinkite, į kur puslapį ar turinį veda ši nuoroda. Pasirinkus tipą ir objektą, nuoroda sugeneruojama automatiškai.') }}</p>
      </template>

      <div class="space-y-4">
        <FormFieldWrapper id="link_target" :label="$t('navigation.form.link_target')">
          <div class="flex flex-wrap items-center gap-2">
            <MultiCollectionSelectDialog
              v-model:open="pickerOpen"
              :collections="['pages', 'news', 'calendar', 'institutions', 'documents']"
              :title="$t('Pasirinkite objektą')"
              :confirm-label="$t('Pasirinkti')"
              :search-placeholder="$t('navigation.form.link_target_search')"
              @confirm="onTargetConfirm"
            >
              <template #trigger>
                <Button type="button" variant="outline" class="justify-between font-normal">
                  <span class="truncate" :class="{ 'text-muted-foreground': !lastPickedLabel }">
                    {{ lastPickedLabel ?? $t('navigation.form.link_target_placeholder') }}
                  </span>
                  <IFluentChevronDown24Regular class="ml-2 size-4 opacity-50" />
                </Button>
              </template>
            </MultiCollectionSelectDialog>

            <span class="text-xs text-muted-foreground">{{ $t('navigation.form.or') }}</span>

            <Select :model-value="categorySelectValue" @update:model-value="onCategorySelected">
              <SelectTrigger class="w-auto min-w-40">
                <SelectValue :placeholder="$t('navigation.form.link_target_category')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="category in categoryOptions" :key="category.id" :value="String(category.id)">
                  {{ category.name }}
                </SelectItem>
              </SelectContent>
            </Select>

            <Loader2 v-if="isResolvingUrl" class="size-4 animate-spin text-muted-foreground" />
          </div>
        </FormFieldWrapper>

        <FormFieldWrapper id="link" :label="$t('Nuoroda')" required :error="form.errors.link"
          :helper-text="$t('navigation.form.link_target_manual')">
          <div class="flex gap-1">
            <Input id="link" v-model="form.link" type="text" :placeholder="$t('Nuoroda...')" />
            <Button variant="outline" size="icon" as="a" :href="form.link" target="_blank">
              <IFluentOpen24Regular />
            </Button>
          </div>
        </FormFieldWrapper>
      </div>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Loader2 } from 'lucide-vue-next';

import FluentIconSelect from '../FormItems/FluentIconSelect.vue';

import AdminForm from './AdminForm.vue';
import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';

import { useApiMutation } from '@/Composables/useApi';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Switch } from '@/Components/ui/switch';
import { SingleSelect } from '@/Components/ui/single-select';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { QuickLinkIcon } from '@/Components/icons';
import { MultiCollectionSelectDialog } from '@/Features/Admin/AdminSearch/Components/Select';
import type { NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';

interface CategoryOption {
  id: number;
  name: string;
  alias: string | null;
}

const props = defineProps<{
  quickLink: App.Entities.QuickLink;
  tenantOptions: Record<string, any>[];
  categoryOptions?: CategoryOption[];
  rememberKey?: 'CreateQuickLink';
}>();

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const isCreate = computed(() => props.rememberKey === 'CreateQuickLink');

const form = props.rememberKey
  ? useForm(props.rememberKey, props.quickLink)
  : useForm(props.quickLink);

const tenantOptions = computed(() =>
  props.tenantOptions.map(padalinys => ({
    value: padalinys.id,
    label: padalinys.shortname,
  })),
);

const selectedTenant = computed({
  get: () => tenantOptions.value.find(opt => String(opt.value) === String(form.tenant_id)) ?? null,
  set: (val: { value: string | number; label: string } | null) => {
    form.tenant_id = val?.value ?? null;
  },
});

// Section completion states
const basicInfoComplete = computed(() =>
  (form.text?.length || 0) >= 1 && Boolean(form.lang),
);

const linkTargetComplete = computed(() => (form.link?.length || 0) > 0);

// --- Link target picker -----------------------------------------------------
// The picker (and category select) are convenience fillers for `link` — the field
// itself always stays editable as a manual override. `type` is a legacy column the
// backend no longer persists (see QuickLinkController::store()/update()), so nothing
// here needs to track or submit it.

const pickerOpen = ref(false);
const lastPickedLabel = ref<string | null>(null);

const resolveUrlBody = ref<{ collection: string; id: string | number } | null>(null);
const { execute: executeResolveUrl, data: resolveUrlData, isFetching: isResolvingUrl } = useApiMutation<{ url: string }, { collection: string; id: string | number }>(
  route('api.v1.admin.navigation.resolveUrl'),
  'POST',
  resolveUrlBody,
  { showSuccessToast: false },
);

const resolveAndFillUrl = async (collection: string, id: string | number, label: string) => {
  resolveUrlBody.value = { collection, id };
  await executeResolveUrl();
  if (resolveUrlData.value?.url) {
    form.link = resolveUrlData.value.url;
    lastPickedLabel.value = label;
  }
};

const onTargetConfirm = (hits: NormalizedSearchHit[]) => {
  const hit = hits[0];
  if (!hit) {
    return;
  }
  resolveAndFillUrl(hit.collection, hit.recordId, hit.title);
};

const categorySelectValue = ref<string | undefined>(undefined);
const onCategorySelected = (val: unknown) => {
  const value = val as string | undefined;
  categorySelectValue.value = value;
  const category = props.categoryOptions?.find(c => String(c.id) === value);
  if (category) {
    resolveAndFillUrl('categories', category.id, category.name);
  }
};
</script>
