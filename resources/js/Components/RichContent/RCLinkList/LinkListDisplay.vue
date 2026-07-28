<template>
  <RCSection
    :title="element.options?.title" :subtitle="element.options?.subtitle"
    :background="element.options?.background ?? 'none'" :padding="element.options?.padding ?? 'lg'"
    inner="wide" :id="anchorId ? `rc-${anchorId}` : undefined"
  >
    <div v-if="!isEmpty">
      <!-- Photo style: a grid of RCFeatureCard, one per link. -->
      <div v-if="style === 'photo'" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <RCFeatureCard
          v-for="item in items" :key="item.id ?? item.href"
          :title="item.title" :cover-image="item.imageUrl" :cover-alt="item.title"
          :meta="publishedLabel(item.publishedAt)" :href="item.href"
        >
          <template #cover-fallback>
            <IFluentLink24Regular class="size-10 text-vusa-red/50" />
          </template>
        </RCFeatureCard>
      </div>

      <!-- Compact style: a divided list, title left, date right. -->
      <ul v-else class="divide-y divide-zinc-200/60 dark:divide-zinc-800">
        <li v-for="item in items" :key="item.id ?? item.href">
          <SmartLink :href="item.href" class="group flex items-center justify-between gap-4 py-3"
            :rel="isExternal(item.href) ? 'noopener noreferrer' : undefined">
            <span class="truncate font-medium text-zinc-800 transition-colors group-hover:text-vusa-red dark:text-zinc-200">
              {{ item.title }}
            </span>
            <span class="flex shrink-0 items-center gap-2">
              <span v-if="item.publishedAt" class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
                {{ publishedLabel(item.publishedAt) }}
              </span>
              <IFluentChevronRight12Regular class="size-3 text-zinc-300 transition-colors group-hover:text-vusa-red dark:text-zinc-600" />
            </span>
          </SmartLink>
        </li>
      </ul>
    </div>
  </RCSection>
</template>

<script setup lang="ts">
/**
 * Displays the `link-list` block's server-resolved payload (see `LinkListResolver`).
 * When `meta.total === 0` this renders nothing but the (optional) RCSection header —
 * an empty list never renders empty chrome.
 */
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import type { LinkListResolved } from '@/Types/contentParts';
import RCSection from '../RCSection.vue';
import RCFeatureCard from '../RCFeatureCard.vue';
import SmartLink from '@/Components/Public/SmartLink.vue';
import { LocaleEnum } from '@/Types/enums';

const props = defineProps<{
  element: models.ContentPart;
  html?: boolean;
  anchorId?: number | null;
  resolved?: LinkListResolved | null;
}>();

const page = usePage();
const locale = computed(() => (page.props.app.locale ?? LocaleEnum.LT) as LocaleEnum);

const style = computed(() => props.element.options?.style ?? 'photo');
const items = computed(() => props.resolved?.items ?? []);
const isEmpty = computed(() => items.value.length === 0);

function publishedLabel(iso: string | null): string {
  if (!iso) return '';
  return new Date(iso).toLocaleDateString(locale.value === LocaleEnum.EN ? 'en-GB' : 'lt-LT', {
    year: 'numeric', month: 'short', day: 'numeric',
  });
}

function isExternal(href: string): boolean {
  return /^https?:\/\//.test(href);
}
</script>
