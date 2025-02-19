<template>
  <Card
    class="3xl:w-fit relative flex flex-col justify-items-center border border-zinc-300/50 bg-linear-to-br from-white to-zinc-50 shadow-xs duration-200 hover:shadow-md dark:border-zinc-800/60 dark:bg-zinc-800/50 dark:from-zinc-800 dark:to-zinc-800">
    <!-- <img :src="documentItem.thumbnail_url" class="mx-4 w-auto rounded-md border"
:class="[documentItem.summary ? 'h-40' : 'h-28']"> -->
    <div class="my-2 flex flex-col">
      <CardHeader class="gap-y-0.5 px-4 py-2">
        <CardTitle class="mb-0 text-left text-lg font-semibold leading-tight tracking-normal">
          <div class="flex items-center gap-4">
            <span class="[&_svg]:text-zinc-700 dark:[&_svg]:text-zinc-300">
              <IAntDesignFilePdfOutlined v-if="documentItem.name.endsWith('.pdf')" width="28" height="28" />
              <IAntDesignFileWordOutlined v-else-if="documentItem.name.endsWith('.docx')" width="28" height="28" />
              <IAntDesignFileExcelOutlined v-else-if="documentItem.name.endsWith('.xlsx')" width="28" height="28" />
              <IAntDesignFilePptOutlined v-else-if="documentItem.name.endsWith('.pptx')" width="28" height="28" />
              <IFluentGlobe28Regular v-else-if="documentItem.name.endsWith('.url')" width="28" height="28" />
              <IAntDesignFileTextOutlined v-else width="28" height="28" />
            </span>
            {{ documentItem.title }}
          </div>
        </CardTitle>
        <!-- <CardDescription v-if="documentItem.document_date"
                class="flex items-center gap-1 px-0 text-xs leading-tight" >
                <IFluentCalendarLtr28Regular width="12" />
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
        class="flex-wrap items-center gap-x-4 gap-y-1 px-4 py-2 text-xs tracking-tight text-zinc-500 dark:text-zinc-400 md:items-start">
        <div v-if="documentItem.document_date" class="inline-flex w-full items-center gap-1">
          <IFluentCalendarLtr24Regular width="16" />
          <span>{{ documentItem.document_date }}</span>
        </div>
        <div v-if="documentItem?.institution" class="inline-flex w-full items-center gap-1">
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
    <div class="absolute bottom-3 right-4 h-fit">
      <NTooltip v-if="documentItem.is_in_effect !== null">
        <template #trigger>
          <button v-if="documentItem.is_in_effect === true"
            class="size-4 rounded-full bg-linear-to-br from-green-500 to-green-700" />
          <button v-else-if="documentItem.is_in_effect === false"
            class="size-4 rounded-full bg-linear-to-br from-red-400 to-red-800" />
        </template>
        Galiojimo laikas:
        {{ documentItem.effective_date ? 'nuo ' + documentItem.effective_date : null }}
        {{ documentItem.expiration_date ? 'iki ' + documentItem.expiration_date : null }}
      </NTooltip>
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
