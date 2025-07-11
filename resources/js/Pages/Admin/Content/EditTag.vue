<template>
  <PageContent :title="pageTitle">
    <div>
      <UpsertModelLayout>
        <TagForm :post-tag @submit:form="(form: any) => form.patch(route('tags.update', postTag.id))"
          @delete="() => router.delete(route('tags.destroy', postTag.id))" />
      </UpsertModelLayout>
    </div>

    <!-- News using this tag -->
    <div v-if="news && news.length > 0" class="mt-4">
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <IFluentNews24Regular class="h-5 w-5" />
            {{ $t('Naujienos su šia žyma') }} ({{ news.length }})
          </CardTitle>
        </CardHeader>
        <CardContent>
          <DataTableProvider 
            :columns="columns" 
            :data="news" 
            :enable-pagination="true"
            :page-size="6"
            :empty-message="$t('No news found.')"
          >
            <template #empty>
              <div class="flex flex-col items-center justify-center gap-2 text-muted-foreground py-8">
                <IFluentNews24Regular class="h-10 w-10" />
                <span>{{ $t('Šiuo metu su šiuo tagu nėra susietos jokios naujienos') }}</span>
              </div>
            </template>
          </DataTableProvider>
        </CardContent>
      </Card>
    </div>
  </PageContent>
</template>

<script setup lang="tsx">
import { router, Link } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";
import { computed } from "vue";
import type { ColumnDef } from '@tanstack/vue-table';

import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import TagForm from "@/Components/AdminForms/TagForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import DataTableProvider from "@/Components/ui/data-table/DataTableProvider.vue";
import IFluentNews24Regular from "~icons/fluent/news-24-regular";
import IFluentEdit20Filled from "~icons/fluent/edit-20-filled";

interface NewsItem {
  id: number;
  title: string;
  permalink: string;
  publish_time: string;
  lang: string;
  tenant: string;
}

const props = defineProps<{
  postTag: App.Entities.Tag;
  news?: NewsItem[];
}>();

// Safe page title computation
const pageTitle = computed(() => {
  if (!props.postTag?.name) {
    return $t('Redaguoti žymą');
  }

  // Handle translation object
  if (typeof props.postTag.name === 'object') {
    const currentLocale = 'lt'; // You might want to get this from app locale
    return props.postTag.name[currentLocale] || props.postTag.name.en || props.postTag.name.lt || $t('Redaguoti žymą');
  }

  // Handle string (shouldn't happen based on types, but safety first)
  return String(props.postTag.name);
});

// Format date helper
const formatDate = (dateString: string) => {
  if (!dateString) return '';

  try {
    return new Date(dateString).toLocaleDateString("lt-LT", {
      year: "numeric",
      month: "short",
      day: "numeric"
    });
  } catch (error) {
    console.error('Date formatting error:', error);
    return dateString;
  }
};

// Table column definitions
const columns: ColumnDef<NewsItem, any>[] = [
  {
    accessorKey: "title",
    header: () => $t("forms.fields.title"),
    cell: ({ row }: { row: any }) => (
      <div class="max-w-[300px]">
        <div class="font-medium truncate">{row.original.title}</div>
        <div class="text-sm text-muted-foreground">{formatDate(row.original.publish_time)}</div>
      </div>
    ),
    size: 300,
  },
  {
    accessorKey: "tenant",
    header: () => $t("forms.fields.tenant"),
    cell: ({ row }: { row: any }) => (
      <Badge variant="secondary" class="text-xs">
        {row.original.tenant}
      </Badge>
    ),
    size: 120,
  },
  {
    accessorKey: "lang",
    header: () => $t("forms.fields.language"),
    cell: ({ row }: { row: any }) => (
      <Badge variant="outline" class="text-xs uppercase">
        {row.original.lang}
      </Badge>
    ),
    size: 80,
  },
  {
    id: "actions",
    header: "",
    cell: ({ row }: { row: any }) => (
      <Button
        size="sm"
        variant="ghost"
        asChild
        class="h-8 w-8 p-0"
      >
        <Link href={route('news.edit', row.original.id)}>
          <IFluentEdit20Filled class="h-4 w-4" />
        </Link>
      </Button>
    ),
    size: 60,
  },
];
</script>
