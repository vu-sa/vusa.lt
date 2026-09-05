<template>
  <!-- border-(--border-opaque), not border-border: dark mode's --border is translucent white, so
       the same token renders a visibly different RGB depending on what's behind it — this header
       sits on bg-background/90 while SecondMenu below sits on bg-secondary/50. --border-opaque
       (surface.css) flattens that into a fixed color so the two borders read as the same weight. -->
  <header class="fixed inset-x-0 top-0 z-50 border-b border-(--border-opaque) bg-background/90 backdrop-blur-sm [.a11y-contrast_&]:bg-background [.a11y-contrast_&]:backdrop-blur-none">
    <!-- Primary bar. `relative` so the mega-menu panel positions against this row and spans the
         full header width. -->
    <div class="relative mx-auto flex h-16 max-w-7xl items-center gap-6 px-5 sm:px-6 lg:px-8">
      <!-- `inline-flex items-center`: `SmartLink` renders a plain `<a>`, an inline element whose
           box height follows the surrounding line height, not its content — leaving the anchor
           visibly taller than the logo sitting inside it. Making it a flex container sizes it to
           the logo exactly. -->
      <SmartLink
        prefetch
        :title="$t('Grįžti į pagrindinį puslapį')"
        class="inline-flex shrink-0 items-center"
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

      <!-- Wrapped rather than classed directly: PadalinysSelector's root is reka's renderless
           `Popover`, which has no element to inherit a fallthrough class, so `max-lg:hidden`
           silently vanished and the selector kept its 156px on a phone — pushing the menu button
           clean off the viewport. -->
      <div class="max-lg:hidden [[data-a11y-font-scale=xl]_&]:hidden">
        <PadalinysSelector :size="smallerThanSm ? 'tiny' : 'small'" />
      </div>

      <!-- `ml-auto` here, not on the utility cluster: the design groups the nav to the right,
           adjacent to the icons, with the gap after Padaliniai. -->
      <MainMenu class="ml-auto max-lg:hidden [[data-a11y-font-scale=xl]_&]:hidden" />

      <!-- Icon-only on purpose: wordy buttons here push the primary nav onto a second line at
           mid-laptop widths. The LT switch lives in the secondary bar for the same reason.

           Below `lg` only the menu button survives. Four controls plus the wordmark do not fit a
           phone — they overflowed the bar and pushed the menu button itself off-screen — and all
           three are already in the menu panel's footer, so nothing is lost by collapsing them
           into it. -->
      <div class="flex shrink-0 items-center gap-2 max-lg:ml-auto [[data-a11y-font-scale=xl]_&]:ml-auto">
        <SearchButton size="icon" :class="cn(navButtonClass)" />
        <AccessibilityMenu :class="cn(navButtonClass)" />
        <DarkModeSwitch size="icon" :class="cn(navButtonClass)" />
        <MobileNavigation :class="cn(navButtonClass, 'lg:hidden [[data-a11y-font-scale=xl]_&]:!flex')" />
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
