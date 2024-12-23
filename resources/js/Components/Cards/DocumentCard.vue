<template>
  <Card
    class="flex flex-col items-center justify-items-center border border-zinc-600/50 bg-white/10 duration-200 hover:shadow-md dark:bg-zinc-800/50 md:grid md:grid-cols-3">
    <!-- <img :src="documentItem.thumbnail_url" class="mx-4 w-auto rounded-md border"
:class="[documentItem.summary ? 'h-40' : 'h-32']"> -->
    <div class="my-6 [&_svg]:text-zinc-500">
      <IAntDesignFilePdfOutlined v-if="documentItem.name.endsWith('.pdf')" width="64" height="64" />
      <IAntDesignFileWordOutlined v-else-if="documentItem.name.endsWith('.docx')" width="64" height="64" />
      <IAntDesignFileExcelOutlined v-else-if="documentItem.name.endsWith('.xlsx')" width="64" height="64" />
      <IAntDesignFilePptOutlined v-else-if="documentItem.name.endsWith('.pptx')" width="64" height="64" />
      <IFluentGlobe24Regular v-else-if="documentItem.name.endsWith('.url')" width="64" height="64" />
      <IAntDesignFileTextOutlined v-else width="64" height="64" />
    </div>
    <div class="col-span-2 my-2 flex flex-col">
      <CardHeader class="gap-y-0.5 px-4 py-2">
        <CardTitle class="mb-0 text-center text-lg leading-tight md:text-left">
          {{ documentItem.title }}
        </CardTitle>
        <!-- <CardDescription v-if="documentItem.document_date"
                class="flex items-center gap-1 px-0 text-xs leading-tight" >
                <IFluentCalendarLtr24Regular width="12" />
                <span>{{ documentItem.document_date }}</span>
</CardDescription> -->
      </CardHeader>
      <CardContent v-if="documentItem.summary" class="px-4 py-1 text-xs leading-4 text-zinc-700 dark:text-zinc-400">
        <p>
          {{ documentItem.summary }}
          {{ documentItem.created_at }}
        </p>
      </CardContent>
      <CardFooter v-if="documentItem.content_type || documentItem.language || documentItem.institution"
        class="flex-wrap items-center gap-x-4 gap-y-1 px-4 py-2 text-xs text-zinc-500 md:items-start">
        <div v-if="documentItem.document_date" class="inline-flex w-full items-center gap-1">
          <IFluentCalendarLtr24Regular width="16" />
          <span>{{ documentItem.document_date }}</span>
        </div>
        <div v-if="documentItem?.institution" class="inline-flex items-center gap-1">
          <component :is="Icons.INSTITUTION" />
          <span>{{ documentItem.institution.short_name }}</span>
        </div>
        <div v-if="documentItem.content_type" class="inline-flex items-center gap-1">
          <IFluentTag16Regular />
          <span>{{ documentItem.content_type }}</span>
        </div>
        <!-- <div v-if="documentItem.language" class="inline-flex items-center gap-1">
                <IFluentLocalLanguage16Regular />
                <span>{{ documentItem.language }}</span>
</div> -->
      </CardFooter>
    </div>
  </Card>
</template>

<script setup lang="ts">
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/Components/ShadcnVue/ui/card';
import Icons from "@/Types/Icons/regular";

defineProps<{
  documentItem: App.Entities.Document;
}>();
</script>
