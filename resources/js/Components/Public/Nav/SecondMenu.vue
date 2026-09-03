<template>
  <!-- border-(--border-opaque): matches the header's own border-color override directly above —
       see that comment for why the plain translucent --border renders differently here
       (bg-secondary/50) than it does on the header (bg-background/90). -->
  <section class="relative z-5 border-t border-(--border-opaque) bg-secondary/50">
    <div class="mx-auto grid h-11 max-w-7xl grid-cols-[min-content__1fr] items-center px-5 sm:px-6 lg:px-8">
      <SmartLink prefetch href="/"
        class="mr-6 whitespace-nowrap text-xs font-bold uppercase tracking-[0.2em] text-brand transition-colors hover:text-foreground">
        {{
          $page.props.tenant?.shortname
            ? $t($page.props.tenant?.shortname)
            : "VU SA"
        }}
      </SmartLink>
      <nav class="grid grid-cols-[1fr_auto_auto] items-center gap-5 whitespace-nowrap text-sm">
        <!-- The overflow hint is a mask, not a gradient overlay. An overlay has to be painted in
             the bar's own colour to disappear, and the bar is a translucent tint over the page
             background — so the overlay showed up as a pale smudge instead of a fade. A mask
             fades the content itself and needs to know nothing about what is behind it. -->
        <div
          ref="containerRef"
          class="flex w-full items-center gap-5 overflow-hidden"
          style="mask-image: linear-gradient(to right, black calc(100% - 2.5rem), transparent)"
        >
          <div
            v-for="(link, index) in tenantLinks"
            :key="link?.id"
            :ref="(el) => setItemRef(el, index)"
          >
            <QuickLink :quick-link="link" />
          </div>
        </div>
        <div v-if="overflowOptions.length > 0" class="inline-flex gap-1">
          <!-- `modal="false"`: see LocaleButton.vue — matches the non-modal Popover/mega
               menu controls elsewhere in the header instead of locking body scroll. -->
          <DropdownMenu :modal="false">
            <DropdownMenuTrigger as-child>
              <Button
                variant="ghost"
                size="sm"
                class="h-7 px-1.5 text-muted-foreground transition-colors
                  hover:bg-transparent hover:text-brand
                  dark:hover:bg-transparent dark:hover:text-brand"
                :title="$t('Daugiau nuorodų')"
              >
                <IFluentMoreHorizontal20Filled class="h-4 w-4" />
                <span class="sr-only">{{ $t('Daugiau nuorodų') }}</span>
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-48">
              <DropdownMenuItem
                v-for="option in overflowOptions"
                :key="option.key"
                class="cursor-pointer"
                @click="() => handleSelect(option.key)"
              >
                {{ option.label }}
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
        <div class="ml-auto flex items-center gap-2">
          <!-- The language switcher lives here, not in the primary bar: the primary row has to
               fit the uppercase nav without wrapping, and a wordy LT/EN control is what pushed it
               onto two lines. Search is icon-only in the primary bar, so there is none here. -->
          <LocaleButton :locale="$page.props.app.locale" size="sm" class="h-7 px-2 text-xs" />
          <span class="h-4 w-px bg-border" aria-hidden="true" />
          <SmartLink :href="$page.props.auth?.user ? route('dashboard') : route('login')"
            target="_self"
            class="plain inline-flex h-7 items-center justify-center gap-1.5 px-2 text-xs font-medium text-muted-foreground transition-colors hover:text-brand"
            :title="$page.props.auth?.user ? $page.props.auth.user?.name : $t('auth.login')">
            <template v-if="$page.props.auth?.user">
              <!-- `rounded-lg` overrides the primitive's base `rounded-full`. Keep it: the radius
                   token resolves to 0 on the public surface (the design has no circular avatars),
                   and dropping the class would fall back to a circle rather than a square. -->
              <Avatar class="h-4 w-4 rounded-lg ring-1 ring-border">
                <AvatarImage v-if="$page.props.auth.user.profile_photo_path"
                  :src="$page.props.auth.user.profile_photo_path" :alt="$page.props.auth.user.name" />
                <AvatarFallback class="bg-secondary text-[0.4375rem] font-semibold text-foreground">
                  {{ $page.props.auth.user.name.substring(0, 2).toUpperCase() }}
                </AvatarFallback>
              </Avatar>
            </template>
            <IFluentPerson24Regular v-else class="h-4 w-4" aria-hidden="true" />
            <!-- The label stays "Mano VU SA" in both states — the avatar already signals the
                 logged-in state, and the user's full name doesn't reliably fit this bar. -->
            <span class="hidden sm:inline">{{ $t('Mano VU SA') }}</span>
            <span class="sr-only sm:hidden">{{ $t('Mano VU SA') }}</span>
          </SmartLink>
        </div>
      </nav>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, nextTick, ref, shallowRef, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { useResizeObserver } from '@vueuse/core';

import SmartLink from '../SmartLink.vue';

import QuickLink from './QuickLink.vue';
import LocaleButton from './LocaleButton.vue';

import { Button } from '@/Components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

const tenantLinks = computed(() => usePage().props.tenant?.links ?? []);

const containerRef = shallowRef<HTMLElement | null>(null);
const itemRefs = ref<(HTMLElement | null)[]>([]);
const overflowIndexes = ref<Set<number>>(new Set());

const setItemRef = (el: Element | null, index: number) => {
  itemRefs.value[index] = el as HTMLElement | null;
};

/**
 * The row already clips overflowing links visually (the mask + `overflow-hidden`
 * above); this mirrors that clipping in JS so the "more" dropdown lists only the
 * links actually hidden by it, instead of repeating every link already visible.
 * jsdom has no layout, so `clientWidth` stays 0 there and nothing is ever flagged
 * as overflowing — the dropdown correctly stays hidden in component tests.
 */
const measureOverflow = () => {
  const container = containerRef.value;
  if (!container || container.clientWidth === 0) {
    return;
  }

  const containerWidth = container.clientWidth;
  const containerLeft = container.getBoundingClientRect().left;
  const next = new Set<number>();

  itemRefs.value.forEach((item, index) => {
    if (item && item.getBoundingClientRect().right - containerLeft > containerWidth) {
      next.add(index);
    }
  });

  overflowIndexes.value = next;
};

useResizeObserver(containerRef, measureOverflow);
watch(tenantLinks, () => nextTick(measureOverflow), { immediate: true, flush: 'post' });

const overflowOptions = computed(() => tenantLinks.value
  .filter((link, index): link is NonNullable<typeof link> =>
    link?.text != null && link?.link != null && overflowIndexes.value.has(index))
  .map(link => ({
    label: link.text!,
    key: link.link!,
  })));

const handleSelect = (value: string) => {
  window.location.href = value;
};
</script>
