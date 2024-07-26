<template>
  <h2 class="pt-8 text-gray-900 dark:text-zinc-50">
    Dokumentai
  </h2>
  <!-- <div>
    {{ documents }}
</div> -->
  <div class="grid grid-cols-3">
    <div> Filtrai </div>
    <div class="col-span-2 flex max-w-lg flex-col gap-6">
      <SmartLink v-for="documentItem in documents" :key="documentItem.id" :href="documentItem.anonymous_url">
        <Card variant="soft"
          class="grid grid-cols-3 items-center justify-items-center py-4 shadow-sm duration-200 hover:shadow-lg">
          <img :src="documentItem.thumbnail_url" class="mx-4 w-auto rounded-md border"
            :class="[documentItem.summary ? 'h-40' : 'h-32']">
          <div class="col-span-2 my-2 flex flex-col">
            <CardHeader class="gap-y-0.5 px-4 pb-1 pt-0">
              <CardTitle class="mb-0 text-lg leading-tight">
                {{ documentItem.title }}
              </CardTitle>
              <CardDescription v-if="documentItem.document_date"
                class="flex items-center gap-1 px-0 text-xs leading-tight" />
            </CardHeader>
            <CardContent v-if="documentItem.summary" class="mt-2 pb-2 px-4 text-xs leading-4">
              <p>
                {{ documentItem.summary }}
                {{ documentItem.created_at }}
              </p>
            </CardContent>
            <CardFooter v-if="documentItem.content_type || documentItem.language"
              class="items-center gap-4 px-4 py-2 text-xs">
              <div v-if="documentItem.document_date" class="inline-flex items-center gap-1 text-zinc-400">
                <IFluentCalendarLtr24Regular width="12" />
                <span>{{ documentItem.document_date }}</span>
              </div>
              <div v-if="documentItem.content_type" class="inline-flex items-center gap-1 text-zinc-400">
                <IFluentTag16Regular />
                <span>{{ documentItem.content_type }}</span>
              </div>
              <div v-if="documentItem.language" class="inline-flex items-center gap-1 text-zinc-400">
                <IFluentLocalLanguage16Regular />
                <span>{{ documentItem.language }}</span>
              </div>
            </CardFooter>
          </div>
        </Card>
      </SmartLink>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/Components/ShadcnVue/ui/card';
import SmartLink from '@/Components/Public/SmartLink.vue';

defineProps<{
  //documents: PaginatedModels<App.Entities.Document>;
  documents: App.Entities.Document[];
}>();
</script>
