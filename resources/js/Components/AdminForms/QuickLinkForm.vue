<template>
  <FormPage
    :title="isCreate ? $t('Nauja greitoji nuoroda') : (form.text || $t('Greitoji nuoroda'))"
    :bar-title
    entity-type="quick_link"
    :back-href="route('quickLinks.index')"
    :back-label="$t('Greitosios nuorodos')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :field-ids
    :mode="isCreate ? 'create' : 'edit'"
    :activity-subject="quickLink?.id ? { type: 'quick_link', id: quickLink.id } : undefined"
    :created-at="isCreate ? undefined : quickLink?.created_at"
    :updated-at="isCreate ? undefined : quickLink?.updated_at"
    @submit="emit('submit:form', form)"
  >
    <template v-if="!isCreate && form.is_important" #title-status>
      <StatusBadge :status="importantStatus" />
    </template>

    <div class="space-y-6">
      <FormFieldWrapper
        id="text"
        :label="$t('Mygtuko tekstas')"
        required
        :error="form.errors.text"
      >
        <Input
          id="text"
          v-model="form.text"
          type="text"
          :placeholder="$t('Įrašyti tekstą...')"
          :class="['h-11', fieldSurfaceClass]"
        />
      </FormFieldWrapper>

      <FormFieldWrapper id="icon" :label="$t('Ikona')" :hint="$t('Piktograma, rodoma šalia nuorodos teksto.')">
        <Suspense>
          <FluentIconSelect :icon="form.icon" @update:icon="(value) => form.icon = value" />
        </Suspense>
      </FormFieldWrapper>

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
                <ChevronDown class="ml-2 size-4 opacity-50" />
              </Button>
            </template>
          </MultiCollectionSelectDialog>

          <span class="text-xs text-muted-foreground">{{ $t('navigation.form.or') }}</span>

          <Select :model-value="topicSelectValue" @update:model-value="onTopicSelected">
            <SelectTrigger class="w-auto min-w-40">
              <SelectValue :placeholder="$t('navigation.form.link_target_topic')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="topic in topicOptions" :key="topic.id" :value="String(topic.id)">
                {{ topic.name }}
              </SelectItem>
            </SelectContent>
          </Select>

          <Loader2 v-if="isResolvingUrl" class="size-4 animate-spin text-muted-foreground" />
        </div>
      </FormFieldWrapper>

      <FormFieldWrapper
        id="link"
        :label="$t('Nuoroda')"
        required
        :error="form.errors.link"
        :hint="$t('navigation.form.link_target_manual')"
      >
        <div class="flex gap-1.5">
          <Input
            id="link"
            v-model="form.link"
            type="text"
            :placeholder="$t('Nuoroda...')"
            :class="['h-11', fieldSurfaceClass]"
          />
          <Button
            v-if="form.link"
            variant="outline"
            size="icon"
            as-child
            class="size-11 shrink-0"
          >
            <a :href="form.link" target="_blank" rel="noopener noreferrer" :aria-label="$t('Atidaryti nuorodą')">
              <ExternalLink class="size-4" />
            </a>
          </Button>
        </div>
      </FormFieldWrapper>
    </div>

    <template #aside>
      <FormPanel :title="$t('Kalba')" :icon="Languages" title-class="text-brand">
        <FormFieldWrapper
          id="lang"
          :label="$t('Kalba')"
          required
          :error="form.errors.lang"
        >
          <FormSegmentedControl
            v-model="form.lang"
            :options="langOptions"
            :aria-label="$t('Kalba')"
          >
            <template #option="{ option }">
              <LocaleFlag :locale="option.value" />
            </template>
          </FormSegmentedControl>
        </FormFieldWrapper>
      </FormPanel>

      <FormPanel :title="$t('Rodymo nustatymai')" :icon="LayoutGrid" title-class="text-brand" flush>
        <div class="p-4">
          <FormFieldWrapper
            id="tenant_id"
            :label="$t('Padalinys')"
            :hint="$t('Padalinys, kuriam priklauso nuoroda')"
            :error="form.errors.tenant_id"
          >
            <SingleSelect
              v-model="selectedTenant"
              :options="tenantOptions"
              value-field="value"
              label-field="label"
              :placeholder="$t('Pasirinkti padalinį...')"
            />
          </FormFieldWrapper>
        </div>

        <FormToggleRow
          v-model="form.is_important"
          :label="$t('Svarbi nuoroda')"
          :hint="$t('Paryškinama pradiniame puslapyje.')"
          data-testid="toggle-important"
        />
      </FormPanel>
    </template>

    <template v-if="enableDelete && !isCreate" #danger-zone>
      <Button
        type="button"
        variant="outline"
        class="border-destructive/40 text-destructive hover:bg-destructive hover:text-destructive-foreground pointer-coarse:min-h-11"
        @click="isDeleteDialogOpen = true"
      >
        <Trash2 class="size-4" />
        {{ $t('Ištrinti greitąją nuorodą') }}
      </Button>
    </template>
  </FormPage>

  <ConfirmDialog
    v-model:open="isDeleteDialogOpen"
    :title="$t('Ištrinti greitąją nuorodą?')"
    :description="$t('Ar tikrai norite perkelti šią greitąją nuorodą į šiukšliadėžę?')"
    :confirm-label="$t('Ištrinti')"
    destructive
    @confirm="emit('delete')"
  />
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';
import { ChevronDown, ExternalLink, Languages, LayoutGrid, Loader2, Star, Trash2 } from 'lucide-vue-next';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import FluentIconSelect from '@/Components/FormItems/FluentIconSelect.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import { ConfirmDialog, FormPanel, FormSegmentedControl, FormToggleRow, StatusBadge, type FormSegmentOption } from '@/Components/Patterns';
import LocaleFlag from '@/Components/Public/Nav/LocaleFlag.vue';
import { Button } from '@/Components/ui/button';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { SingleSelect } from '@/Components/ui/single-select';
import { useApiMutation } from '@/Composables/useApi';
import type { StatusPresentation } from '@/Constants/statuses';
import { MultiCollectionSelectDialog } from '@/Features/Admin/AdminSearch/Components/Select';
import type { NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';

interface TopicOption {
  id: number;
  name: string;
  alias: string | null;
}

const props = withDefaults(defineProps<{
  quickLink: App.Entities.QuickLink;
  tenantOptions: Record<string, unknown>[];
  topicOptions?: TopicOption[];
  rememberKey?: string;
  enableDelete?: boolean;
}>(), {
  topicOptions: () => [],
  rememberKey: undefined,
});

const emit = defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const isCreate = computed(() => !props.quickLink?.id || props.rememberKey === 'CreateQuickLink');
const isDeleteDialogOpen = ref(false);

const form = props.rememberKey
  ? useForm(props.rememberKey, props.quickLink)
  : useForm(props.quickLink);

const fieldIds = ['text', 'icon', 'link', 'lang', 'tenant_id', 'is_important'];

const barTitle = computed(() =>
  form.text?.trim() || (isCreate.value ? $t('Nauja greitoji nuoroda') : $t('Greitoji nuoroda')),
);

const importantStatus: StatusPresentation = {
  label: $t('Svarbi'),
  role: 'attention',
  icon: Star,
};

const langOptions: FormSegmentOption<'lt' | 'en'>[] = [
  { value: 'lt', label: 'Lietuvių' },
  { value: 'en', label: 'English' },
];

const tenantOptions = computed(() =>
  props.tenantOptions.map(padalinys => ({
    value: padalinys.id,
    label: padalinys.shortname,
  })),
);

const selectedTenant = computed({
  get: () => tenantOptions.value.find(opt => String(opt.value) === String(form.tenant_id)) ?? null,
  set: (val: { value: string | number; label: string } | null) => {
    form.tenant_id = (val?.value as number) ?? null;
  },
});

// --- Link target picker -----------------------------------------------------
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

const topicSelectValue = ref<string | undefined>(undefined);
const onTopicSelected = (val: unknown) => {
  const value = val as string | undefined;
  topicSelectValue.value = value;
  const topic = props.topicOptions?.find(t => String(t.id) === value);
  if (topic) {
    resolveAndFillUrl('topics', topic.id, topic.name);
  }
};

defineExpose({
  form,
});
</script>
