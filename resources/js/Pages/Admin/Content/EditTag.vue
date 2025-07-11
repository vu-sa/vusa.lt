<template>
  <PageContent :title="pageTitle">
    <div class="max-w-2xl">
      <UpsertModelLayout>
        <TagForm :post-tag @submit:form="(form: any) => form.patch(route('tags.update', postTag.id))"
          @delete="() => router.delete(route('tags.destroy', postTag.id))" />
      </UpsertModelLayout>
    </div>
    
    <!-- News using this tag -->
    <div v-if="news && news.length > 0" class="mt-8">
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <IFluentNews24Regular class="h-5 w-5" />
            {{ $t('Naujienos su šia žyma') }} ({{ news.length }})
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div 
              v-for="newsItem in news" 
              :key="newsItem.id"
              class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
            >
              <div class="flex-1">
                <h4 class="font-medium text-sm">{{ newsItem.title || 'Untitled' }}</h4>
                <div class="flex items-center gap-4 mt-1 text-xs text-gray-500 dark:text-gray-400">
                  <span>{{ newsItem.tenant || 'Unknown' }}</span>
                  <span>{{ formatDate(newsItem.publish_time) }}</span>
                  <span class="uppercase">{{ newsItem.lang || 'N/A' }}</span>
                </div>
              </div>
              <Button variant="outline" size="sm" asChild>
                <Link :href="route('news.edit', newsItem.id)">
                  {{ $t('Redaguoti') }}
                </Link>
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </PageContent>
</template>

<script setup lang="ts">
import { router, Link } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";
import { computed } from "vue";

import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import TagForm from "@/Components/AdminForms/TagForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { Button } from "@/Components/ui/button";
import IFluentNews24Regular from "~icons/fluent/news-24-regular";

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

// Safe page title computation - handle the case where tag.name might be null/undefined
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

// Format date helper with safety checks
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
    return dateString; // Return original string if formatting fails
  }
};
</script>
