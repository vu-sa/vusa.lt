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
        <span>{{ article.tenant }}</span>
      </template>
      <template #time>
        <!-- <NIcon class="mr-2" size="16"> <Clock20Regular /> </NIcon> -->
        {{ formatStaticTime(new Date(article.publish_time), { year: "numeric", month: "long", day: "numeric" },
          $page.props.app.locale
        ) }}
      </template>
      <template #title>
        {{ article.title }}
      </template>
      <template #image>
        <img class="mb-1 rounded-sm object-cover shadow-md duration-200 hover:shadow-lg" :src="article.image">
        <span v-if="article.image_author">{{ article.image_author }}</span>
      </template>
      <em v-if="$page.props.otherLangURL" class="typography col-span-full text-sm">
        {{ $t("Puslapis egzistuoja kita kalba") }}!
        <span class="ml-2">
          <SmartLink :href="$page.props.otherLangURL">
            <NButton tertiary round size="small">{{ $t("Atidaryti") }}.</NButton>
          </SmartLink>
        </span>
      </em>
      <div class="typography col-span-4 flex max-w-prose flex-col gap-2 text-base leading-7">
        <RichContentParser :content="article.content?.parts" />
      </div>
    </NewsArticle>
    <FeedbackPopover />
    <NBackTop :right="100" :bottom="28" />
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";

import { formatStaticTime } from "@/Utils/IntlTime";
import FeedbackPopover from "@/Components/Public/FeedbackPopover.vue";
import NewsArticle from "@/Components/Public/NewsArticle.vue";
import RichContentParser from "@/Components/RichContentParser.vue";
import SmartLink from "@/Components/Public/SmartLink.vue";

defineProps<{
  article: App.Entities.News;
}>();

</script>
