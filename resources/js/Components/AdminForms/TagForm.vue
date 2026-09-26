<template>
  <FormPage
    :title="isCreate ? $t('Nauja žyma') : (titleText || $t('Žyma'))"
    :bar-title
    entity-type="tag"
    :back-href="route('tags.index')"
    :back-label="$t('Žymos')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :field-ids
    :mode="isCreate ? 'create' : 'edit'"
    :activity-subject="postTag?.id ? { type: 'tag', id: postTag.id } : undefined"
    :created-at="isCreate ? undefined : (postTag?.created_at as string | undefined)"
    :updated-at="isCreate ? undefined : (postTag?.updated_at as string | undefined)"
    @submit="emit('submit:form', form)"
  >
    <template v-if="!isCreate && form.is_topic" #title-status>
      <StatusBadge :status="topicStatus" />
    </template>

    <div class="space-y-6">
      <FormFieldWrapper id="name" :label="$t('forms.fields.title')" required :error="form.errors.name">
        <MultiLocaleInput id="name" v-model:input="form.name" />
      </FormFieldWrapper>

      <FormFieldWrapper id="alias" :label="$t('forms.fields.alias')" :hint="$t('forms.helpers.tag_alias_hint')" :error="form.errors.alias">
        <Input id="alias" v-model="form.alias" placeholder="Pvz: stipendijos" :class="['h-11', fieldSurfaceClass]" />
      </FormFieldWrapper>

      <FormFieldWrapper id="description" :label="$t('forms.fields.description')">
        <MultiLocaleTiptapFormItem v-model:input="form.description" />
      </FormFieldWrapper>

      <!-- Associated news list -->
      <SectionCard v-if="news && news.length > 0" :title="`${$t('Naujienos su šia žyma')} (${news.length})`" :icon="NewsIcon">
        <SimpleDataTable
          :columns
          :data="news"
          enable-pagination
          :page-size="6"
          :empty-message="$t('No news found.')"
        />
      </SectionCard>
    </div>

    <template #aside>
      <FormPanel :title="$t('Nustatymai')" :icon="Tags" title-class="text-brand" flush>
        <FormToggleRow
          v-model="form.is_topic"
          :label="$t('forms.fields.is_topic')"
          :hint="$t('forms.helpers.is_topic_hint')"
          data-testid="toggle-topic"
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
        {{ $t('Ištrinti žymą') }}
      </Button>
    </template>
  </FormPage>

  <ConfirmDialog
    v-model:open="isDeleteDialogOpen"
    :title="$t('Ištrinti žymą?')"
    :description="$t('Ar tikrai norite perkelti šią žymą į šiukšliadėžę?')"
    :confirm-label="$t('Ištrinti')"
    destructive
    @confirm="emit('delete')"
  />
</template>

<script setup lang="tsx">
import { computed, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ExternalLink, Star, Tags, Trash2 } from 'lucide-vue-next';
import type { ColumnDef } from '@tanstack/vue-table';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import MultiLocaleInput from '@/Components/FormItems/MultiLocaleInput.vue';
import MultiLocaleTiptapFormItem from '@/Components/FormItems/MultiLocaleTiptapFormItem.vue';
import { NewsIcon } from '@/Components/icons';
import FormPage from '@/Components/Layouts/FormPage.vue';
import { ConfirmDialog, FormPanel, FormToggleRow, SectionCard, StatusBadge } from '@/Components/Patterns';
import SimpleDataTable from '@/Components/Tables/SimpleDataTable.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';
import type { StatusPresentation } from '@/Constants/statuses';
import { formatDate } from '@/Utils/dateTime';

interface NewsItem {
  id: number;
  title: string;
  permalink: string;
  publish_time: string;
  lang: string;
  tenant: string;
}

const props = withDefaults(defineProps<{
  postTag: App.Entities.Tag;
  news?: NewsItem[];
  rememberKey?: 'CreateTag';
  enableDelete?: boolean;
}>(), {
  news: () => [],
  rememberKey: undefined,
});

const emit = defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const isCreate = computed(() => !props.postTag?.id || props.rememberKey === 'CreateTag');
const isDeleteDialogOpen = ref(false);

const form = props.rememberKey
  ? useForm(props.rememberKey, props.postTag as unknown as Record<string, unknown>)
  : useForm(props.postTag as unknown as Record<string, unknown>);

if (typeof form.is_topic !== 'boolean') {
  form.is_topic = Boolean(form.is_topic);
}

const fieldIds = ['name', 'alias', 'description', 'is_topic'];

const titleText = computed(() => {
  if (!form.name) return '';
  if (typeof form.name === 'object') {
    const nameObj = form.name as Record<string, string>;
    return nameObj.lt || nameObj.en || '';
  }
  return String(form.name);
});

const barTitle = computed(() =>
  titleText.value.trim() || (isCreate.value ? $t('Nauja žyma') : $t('Žyma')),
);

const topicStatus: StatusPresentation = {
  label: $t('Teminė'),
  role: 'info',
  icon: Star,
};

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const columns: ColumnDef<any, NewsItem, unknown>[] = [
  {
    accessorKey: 'title',
    header: () => $t('forms.fields.title'),
    cell: ({ row }) => (
      <div class="max-w-[300px]">
        <div class="font-medium truncate">{row.original.title}</div>
        <div class="text-xs text-muted-foreground">{formatDate(row.original.publish_time)}</div>
      </div>
    ),
  },
  {
    accessorKey: 'tenant',
    header: () => $t('forms.fields.tenant'),
    cell: ({ row }) => (
      <Badge variant="secondary" class="text-xs">
        {row.original.tenant}
      </Badge>
    ),
  },
  {
    accessorKey: 'lang',
    header: () => $t('forms.fields.language'),
    cell: ({ row }) => (
      <Badge variant="outline" class="text-xs uppercase">
        {row.original.lang}
      </Badge>
    ),
  },
  {
    id: 'actions',
    header: '',
    cell: ({ row }) => (
      <Button
        size="sm"
        variant="ghost"
        asChild
        class="size-8 p-0"
      >
        <Link href={route('news.edit', row.original.id)}>
          <ExternalLink class="size-4" />
        </Link>
      </Button>
    ),
  },
];

defineExpose({
  form,
});
</script>
