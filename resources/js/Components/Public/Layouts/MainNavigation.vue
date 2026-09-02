<template>
  <header class="fixed inset-x-0 top-0 z-50 border-b border-border bg-background/90 backdrop-blur-sm">
    <!-- Primary bar. `relative` so the mega-menu panel positions against this row and spans the
         full header width. -->
    <div class="relative mx-auto flex h-16 max-w-[84rem] items-center gap-6 px-4 lg:px-8">
      <SmartLink
        prefetch
        :title="$t('Grįžti į pagrindinį puslapį')"
        class="shrink-0"
        :href="`${$page.props.app.url}/${$page.props.app.locale}`"
        target="_self"
      >
        <HeaderWordmark :src="logoSrc" />
      </SmartLink>

      <span
        v-if="$page.props.tenant?.alias && $page.props.tenant.alias !== 'vusa'"
        class="max-w-20 truncate text-xs font-semibold text-muted-foreground lg:hidden"
      >
        {{ $t($page.props.tenant?.shortname ?? '') }}
      </span>

      <PadalinysSelector class="max-lg:hidden" :size="smallerThanSm ? 'tiny' : 'small'" />

      <!-- `ml-auto` here, not on the utility cluster: the design groups the nav to the right,
           adjacent to the icons, with the gap after Padaliniai. -->
      <MainMenu class="ml-auto max-lg:hidden" />

      <!-- Icon-only on purpose: wordy buttons here push the primary nav onto a second line at
           mid-laptop widths. The LT switch lives in the secondary bar for the same reason. -->
      <div class="flex shrink-0 items-center gap-2 max-lg:ml-auto">
        <SearchButton size="icon" :class="navButtonClass" />
        <AccessibilityMenu :class="navButtonClass" />
        <DarkModeSwitch size="icon" :class="navButtonClass" />
        <MobileNavigation :class="cn(navButtonClass, 'lg:hidden')" />
      </div>
    </div>

    <SecondMenu v-if="hasSecondMenu" class="max-md:hidden" />
  </header>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { breakpointsTailwind, useBreakpoints } from '@vueuse/core';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import MainMenu from '../Nav/MainMenu.vue';
import MobileNavigation from '../Nav/Mobile/MobileNavigation.vue';
import PadalinysSelector from '../Nav/PadalinysSelector.vue';
import SearchButton from '../Nav/SearchButton.vue';
import SecondMenu from '../Nav/SecondMenu.vue';
import SmartLink from '../SmartLink.vue';

import { AccessibilityMenu, HeaderWordmark } from '@/Components/Public/Base';
import { cn } from '@/Utils/Shadcn/utils';
import DarkModeSwitch from '@/Components/Buttons/DarkModeButton.vue';
import { useSecondMenu } from '@/Composables/useSecondMenu';

defineProps<{
  isThemeDark: boolean;
}>();

const breakpoints = useBreakpoints(breakpointsTailwind);
const smallerThanSm = breakpoints.smaller('sm');

const { hasSecondMenu } = useSecondMenu();

/**
 * Every control in the header carries a hairline box. Applied as a class rather than by
 * switching to the shared `outline` button variant, which still hardcodes `bg-white`/`zinc-*`
 * and would change how the admin interface's outline buttons render.
 */
/**
 * The `dark:` variants are not redundant. The ghost button variant ships
 * `dark:hover:text-zinc-50` / `dark:hover:bg-zinc-800/50`, and a `dark:`-prefixed class beats an
 * unprefixed one regardless of order — so without these the icon hovered to near-white in dark
 * mode while the border correctly went brand.
 */
const navButtonClass = 'border border-border text-foreground/70 transition-colors duration-200 '
  + 'hover:border-brand hover:bg-transparent hover:text-brand '
  + 'dark:hover:bg-transparent dark:hover:text-brand';

const logoSrc = computed(() => (usePage().props.app.locale !== 'en'
  ? '/logos/vusa.lin.hor.svg'
  : '/logos/vusa.lin.hor.en.svg'));
</script>
