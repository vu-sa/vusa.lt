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
      <nav
        :class="[
          'grid items-center gap-5 whitespace-nowrap text-sm',
          overflowOptions.length > 0 ? 'grid-cols-[1fr_auto_auto]' : 'grid-cols-[1fr_auto]',
        ]"
      >
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
                voice="brand"
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
          <Button
            voice="brand"
            as-child
            variant="outline"
            size="sm"
            class="w-31 shrink-0 gap-1 px-2 text-foreground/70"
          >
            <a
              :href="$page.props.auth?.user ? route('dashboard') : route('login')"
              :title="$page.props.auth?.user ? $page.props.auth.user?.name : $t('auth.login')"
            >
              <IFluentGrid24Filled
                v-if="$page.props.auth?.user"
                data-slot="mano-vusa-button-icon"
                class="size-4 text-brand"
                aria-hidden="true"
              />
              <IFluentGrid24Regular
                v-else
                data-slot="mano-vusa-button-icon"
                class="size-4"
                aria-hidden="true"
              />
              <span>{{ $t('Mano VU SA') }}</span>
            </a>
          </Button>
        </div>
      </nav>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, shallowRef } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import SmartLink from '../SmartLink.vue';

import QuickLink from './QuickLink.vue';
import LocaleButton from './LocaleButton.vue';

import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { useOverflowingItems } from '@/Composables/useOverflowingItems';
import IFluentGrid24Filled from '~icons/fluent/grid-24-filled';
import IFluentGrid24Regular from '~icons/fluent/grid-24-regular';

const tenantLinks = computed(() => usePage().props.tenant?.links ?? []);

const containerRef = shallowRef<HTMLElement | null>(null);
// The row clips overflowing links (mask + `overflow-hidden`); the "more" menu lists only those.
const { overflowIndexes, setItemRef } = useOverflowingItems(containerRef, tenantLinks);

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
