<template>
  <!-- <SummerCamps v-if="$page.props.app.locale === 'lt'" /> -->
  <Head v-if="firstNewsImageUrl">
    <link rel="preload" as="image" :href="firstNewsImageUrl" fetchpriority="high" />
  </Head>
  <RichContentParser class="max-w-full" :content="(content as ContentWithParts)?.parts ?? []" :news="news" :calendar-events="calendarEvents" />
</template>

<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import RichContentParser from "@/Components/RichContentParser.vue";
import type { NewsItem } from "@/Types/contentParts";

// Type for content with parts
interface ContentWithParts {
  parts?: models.ContentPart[];
  [key: string]: unknown;
}

defineProps<{
  content: ContentWithParts | null;
  news?: NewsItem[];
  calendarEvents?: Array<Record<string, unknown>>;
  firstNewsImageUrl?: string | null;
}>();

// Home page doesn't need breadcrumbs - they're cleared by PublicLayout

//const SummerCamps = defineAsyncComponent(
//  // eslint-disable-next-line no-secrets/no-secrets
//  () => import("@/Components/Public/FullWidth/SummerCamps.vue"),
//);
</script>
