<template>
  <!-- Desktop: Sticky sidebar -->
  <nav v-if="!mobileOnly" class="hidden lg:block space-y-1 text-sm" aria-label="Table of contents">
    <template v-for="link in links" :key="link.href">
      <a
        :href="link.href"
        class="block py-1 text-zinc-600 transition-colors hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100"
        :class="{ 'font-medium text-vusa-red dark:text-vusa-red': activeId === link.href.slice(1) }"
        @click="handleClick(link.href)"
      >
        {{ link.title }}
      </a>
      <template v-for="child in link.children" :key="child.href">
        <a
          :href="child.href"
          class="block py-1 pl-4 text-zinc-500 transition-colors hover:text-zinc-800 dark:text-zinc-500 dark:hover:text-zinc-200"
          :class="{ 'font-medium text-vusa-red dark:text-vusa-red': activeId === child.href.slice(1) }"
          @click="handleClick(child.href)"
        >
          {{ child.title }}
        </a>
      </template>
    </template>
  </nav>
  
  <!-- Mobile: Floating button with Sheet -->
  <div v-if="showMobileButton && links.length > 0" class="fixed bottom-20 left-4 z-50 lg:hidden">
    <Sheet v-model:open="isSheetOpen">
      <SheetTrigger as-child>
        <Button
          variant="outline"
          size="icon"
          class="h-12 w-12 rounded-full shadow-lg hover:shadow-xl transition-shadow bg-background"
        >
          <ListIcon class="h-5 w-5" />
          <span class="sr-only">Turinys</span>
        </Button>
      </SheetTrigger>
      <SheetContent side="left" class="w-80">
        <SheetHeader>
          <SheetTitle class="flex items-center gap-2">
            <ListIcon class="h-5 w-5" />
            Turinys
          </SheetTitle>
        </SheetHeader>
        <nav class="mt-4 space-y-1" aria-label="Table of contents">
          <template v-for="link in links" :key="link.href">
            <a
              :href="link.href"
              class="block rounded-md px-3 py-2 text-sm transition-colors hover:bg-muted"
              :class="{ 'bg-primary/10 font-medium text-primary': activeId === link.href.slice(1) }"
              @click="handleMobileClick(link.href)"
            >
              {{ link.title }}
            </a>
            <template v-for="child in link.children" :key="child.href">
              <a
                :href="child.href"
                class="block rounded-md px-3 py-2 pl-6 text-sm text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                :class="{ 'bg-primary/10 font-medium text-primary': activeId === child.href.slice(1) }"
                @click="handleMobileClick(child.href)"
              >
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
import { ref, onMounted, onUnmounted } from 'vue';
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
  const headingIds: string[] = [];
  
  // Collect all heading IDs from links
  for (const link of props.links) {
    headingIds.push(link.href.slice(1));
    if (link.children) {
      for (const child of link.children) {
        headingIds.push(child.href.slice(1));
      }
    }
  }

  // Find the heading that's currently in view
  let currentActiveId = '';
  for (const id of headingIds) {
    const element = document.getElementById(id);
    if (element) {
      const rect = element.getBoundingClientRect();
      if (rect.top <= props.offset + 10) {
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
