<template>
  <!-- Desktop: Styled sidebar card -->
  <nav v-if="!mobileOnly" class="hidden lg:block" aria-label="Table of contents">
    <div
      class="relative overflow-hidden rounded-xl bg-linear-to-br from-zinc-50 to-zinc-100/50 p-5 ring-1 ring-zinc-200/60 dark:from-zinc-900 dark:to-zinc-800/50 dark:ring-zinc-700/50">
      <!-- Subtle decorative element -->
      <div class="absolute -right-8 -top-8 size-24 rounded-full bg-vusa-red/5 blur-2xl" />

      <div class="relative">
        <!-- Header -->
        <div
          class="mb-3 flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
          <ListIcon class="size-3.5" />
          <span>{{ $t('Turinys') }}</span>
        </div>

        <!-- Links with visual indicator -->
        <div class="relative">
          <!-- Progress track -->
          <div class="absolute left-1.5 top-0 h-full w-0.5 rounded-full bg-zinc-200 dark:bg-zinc-700" />

          <!-- Links -->
          <div class="space-y-0.5 pl-6">
            <template v-for="(link, index) in links" :key="link.href">
              <a :href="link.href" class="group relative block py-1.5 text-sm transition-colors" :class="[
                activeId === link.href.slice(1)
                  ? 'font-medium text-zinc-900 dark:text-zinc-50'
                  : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100'
              ]" @click="handleClick(link.href)">
                <!-- Active indicator dot -->
                <span
                  class="absolute -left-4.25 top-1/2 size-2 -translate-x-1/2 -translate-y-1/2 rounded-full transition-all duration-200"
                  :class="[
                    activeId === link.href.slice(1)
                      ? 'scale-100 bg-vusa-red'
                      : 'scale-75 bg-zinc-300 dark:bg-zinc-600'
                  ]" />
                <span class="line-clamp-2">{{ link.title }}</span>
              </a>

              <!-- Child links -->
              <template v-for="child in link.children" :key="child.href">
                <a :href="child.href" class="group relative block py-1 pl-4 text-xs transition-colors" :class="[
                  activeId === child.href.slice(1)
                    ? 'font-medium text-zinc-800 dark:text-zinc-200'
                    : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-500 dark:hover:text-zinc-300'
                ]" @click="handleClick(child.href)">
                  <span
                    class="absolute -left-[18px] top-1/2 size-1.5 -translate-x-1/2 -translate-y-1/2 rounded-full transition-all duration-200"
                    :class="[
                      activeId === child.href.slice(1)
                        ? 'scale-100 bg-vusa-red/80'
                        : 'scale-75 bg-zinc-300 dark:bg-zinc-600'
                    ]" />
                  <span class="line-clamp-1">{{ child.title }}</span>
                </a>
              </template>
            </template>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <!-- Mobile: Floating button with Sheet -->
  <div v-if="showMobileButton && links.length > 0" class="fixed bottom-20 right-4 z-50 lg:hidden">
    <Sheet v-model:open="isSheetOpen">
      <SheetTrigger as-child>
        <Button variant="outline" size="icon"
          class="size-12 rounded-full bg-background shadow-lg ring-1 ring-zinc-200/50 transition-all hover:shadow-xl hover:ring-zinc-300 dark:ring-zinc-700/50 dark:hover:ring-zinc-600">
          <ListIcon class="size-5" />
          <span class="sr-only">{{ $t('Turinys') }}</span>
        </Button>
      </SheetTrigger>
      <SheetContent side="bottom" class="max-h-[70vh] rounded-t-2xl">
        <SheetHeader>
          <SheetTitle class="flex items-center gap-2 text-base">
            <ListIcon class="size-4" />
            {{ $t('Turinys') }}
          </SheetTitle>
        </SheetHeader>
        <nav class="mt-4 max-h-[50vh] space-y-1 overflow-y-auto pr-2" aria-label="Table of contents">
          <template v-for="link in links" :key="link.href">
            <a :href="link.href" class="flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm transition-colors"
              :class="[
                activeId === link.href.slice(1)
                  ? 'bg-vusa-red/10 font-medium text-vusa-red'
                  : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800'
              ]" @click="handleMobileClick(link.href)">
              <span class="size-1.5 rounded-full flex-shrink-0"
                :class="activeId === link.href.slice(1) ? 'bg-vusa-red' : 'bg-zinc-400'" />
              {{ link.title }}
            </a>
            <template v-for="child in link.children" :key="child.href">
              <a :href="child.href" class="flex items-center gap-2 rounded-lg px-3 py-2 pl-7 text-sm transition-colors"
                :class="[
                  activeId === child.href.slice(1)
                    ? 'bg-vusa-red/10 font-medium text-vusa-red'
                    : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-300'
                ]" @click="handleMobileClick(child.href)">
                <span class="size-1 rounded-full flex-shrink-0"
                  :class="activeId === child.href.slice(1) ? 'bg-vusa-red/70' : 'bg-zinc-300 dark:bg-zinc-600'" />
                {{ child.title }}
              </a>
            </template>
          </template>
        </nav>
      </SheetContent>
    </Sheet>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ListIcon } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/Components/ui/sheet';

interface AnchorLink {
  title: string;
  href: string;
  children?: { title: string; href: string }[];
}

const props = withDefaults(defineProps<{
  links: AnchorLink[];
  offset?: number;
  showMobileButton?: boolean;
  mobileOnly?: boolean;
}>(), {
  offset: 160,
  showMobileButton: true,
  mobileOnly: false,
});

const activeId = ref<string>('');
const isSheetOpen = ref(false);

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

const handleMobileClick = (href: string) => {
  handleClick(href);
  isSheetOpen.value = false;
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
