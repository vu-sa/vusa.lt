<template>
  <!-- Desktop sidebar rail. The mobile floating-button + Sheet variant was removed
       entirely (see script docblock) — this is now desktop-only, matching its one
       remaining caller (ContentPage.vue's `.rc-aside`, already `hidden lg:block`).

       No card: the surface separates things with a hairline and whitespace, and a rail
       whose active row is marked by a brand rule *is* the design's signature device, so
       the ToC needs no panel of its own to sit in. -->
  <nav class="hidden lg:block" aria-label="Table of contents">
    <p class="u-eyebrow mb-4 flex items-center gap-2">
      <IFluentTextBulletListLtr24Regular class="size-3.5" />
      <span>{{ $t('Turinys') }}</span>
    </p>

    <div class="border-l border-border">
      <template v-for="link in links" :key="link.href">
        <a :href="link.href" class="-ml-px block border-l-2 py-2 pl-4 text-sm transition-colors" :class="[
          activeId === link.href.slice(1)
            ? 'border-brand font-bold text-foreground'
            : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground'
        ]" @click="handleClick(link.href)">
          <span class="line-clamp-2">{{ link.title }}</span>
        </a>

        <a v-for="child in link.children" :key="child.href" :href="child.href"
          class="-ml-px block border-l-2 py-1.5 pl-7 text-xs transition-colors" :class="[
            activeId === child.href.slice(1)
              ? 'border-brand font-bold text-foreground'
              : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground'
          ]" @click="handleClick(child.href)">
          <span class="line-clamp-1">{{ child.title }}</span>
        </a>
      </template>
    </div>
  </nav>
</template>

<script setup lang="ts">
/**
 * The mobile variant (a floating button opening a bottom Sheet) is gone: it
 * duplicated `HighlightsFloatingButton`'s exact position (`fixed bottom-20 right-4
 * z-50 lg:hidden`), the two stacked on top of each other on small screens, and a ToC
 * is far less useful on a page you're mostly scrolling through top-to-bottom anyway.
 * `ContentPage.vue` was this component's only caller and no longer renders it below
 * `lg`.
 */
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import IFluentTextBulletListLtr24Regular from '~icons/fluent/text-bullet-list-ltr-24-regular';

interface AnchorLink {
  title: string;
  href: string;
  children?: { title: string; href: string }[];
}

const props = withDefaults(defineProps<{
  links: AnchorLink[];
  offset?: number;
}>(), {
  offset: 160,
});

const activeId = ref<string>('');

// Flatten all links for active tracking
const flatLinks = computed(() => {
  const flat: string[] = [];
  for (const link of props.links) {
    flat.push(link.href.slice(1));
    if (link.children) {
      for (const child of link.children) {
        flat.push(child.href.slice(1));
      }
    }
  }
  return flat;
});

const handleClick = (href: string) => {
  const id = href.slice(1);
  const element = document.getElementById(id);
  if (element) {
    const top = element.getBoundingClientRect().top + window.scrollY - props.offset;
    window.scrollTo({ top, behavior: 'smooth' });
  }
};

const updateActiveId = () => {
  const headingIds = flatLinks.value;

  // Find the heading that's currently in view
  let currentActiveId = '';

  for (const id of headingIds) {
    const element = document.getElementById(id);
    if (element) {
      const rect = element.getBoundingClientRect();
      if (rect.top <= props.offset + 150) {
        currentActiveId = id;
      }
    }
  }

  activeId.value = currentActiveId;
};

onMounted(() => {
  window.addEventListener('scroll', updateActiveId, { passive: true });
  updateActiveId();
});

onUnmounted(() => {
  window.removeEventListener('scroll', updateActiveId);
});
</script>
