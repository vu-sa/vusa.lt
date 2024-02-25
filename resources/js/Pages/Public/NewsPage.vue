<template>
  <div>
    <NewsArticle>
      <template #tags>
        <template v-for="tag in article.tags" :key="tag.id">
          <NButton size="tiny" round>
            {{ tag.name }}
          </NButton>
        </template>
      </template>
      <template #author>
        <!-- <img class="w-8 mr-1" src="/logos/vusa.lin.hor.svg" /> -->
        <span>{{ article.padalinys }}</span>
      </template>
      <template #time>
        <!-- <NIcon class="mr-2" size="16"> <Clock20Regular /> </NIcon> -->
        {{ article.publish_time }}</template>
      <template #title>
        {{ article.title }}
      </template>
      <template #image><img
          class="col-span-4 my-4 h-auto w-[65ch] rounded-sm object-cover shadow-md duration-200 hover:shadow-lg"
          :src="article.image"></template>
      <em v-if="$page.props.otherLangURL" class="prose col-span-full text-sm dark:prose-invert">
        {{ $t("Puslapis egzistuoja kita kalba") }}!
        <span class="ml-2">
          <SmartLink :href="$page.props.otherLangURL">
            <NButton tertiary round size="small">{{ $t("Atidaryti") }}.</NButton>
          </SmartLink>
        </span>
      </em>
      <div
        class="prose col-span-4 prose-zinc dark:prose-invert">
        <RichContentParser :content="article.content?.parts" />
      </div>
    </NewsArticle>
    <NBackTop :right="100" :bottom="28" />
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { NBackTop, NButton } from "naive-ui";

import NewsArticle from "@/Components/Public/NewsArticle.vue";
import RichContentParser from "@/Components/RichContentParser.vue";
import SmartLink from "@/Components/Public/SmartLink.vue";

defineProps<{
  article: App.Entities.News;
}>();
</script>
