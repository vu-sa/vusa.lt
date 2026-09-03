<template>
  <component :is="rootElement" class="bg-popover text-popover-foreground">
    <ul :class="ulClasses">
      <li
        v-for="(links, columnIndex) in item.links"
        :key="columnIndex"
        class="flex flex-col border-b border-border last:border-b-0 lg:border-b-0 lg:border-r lg:last:border-r-0"
        :class="isMediaColumn(links) ? 'p-0' : 'p-6'"
      >
        <template v-for="(link, index) in links" :key="link.id ?? link.name">
          <div v-if="link.type === 'divider'" class="my-4 border-t border-border">
            <slot v-if="showEditIcons" :index :link :links name="editIconsDivider" />
          </div>

          <div v-else-if="link.type === 'heading'" class="u-eyebrow mb-3 mt-6 border-b border-border pb-3 first:mt-0">
            {{ link.name }}
          </div>

          <component :is="isUsedWithoutRoot ? linkComponent : NavigationMenuLink"
            v-else-if="link.image && link.image_render !== 'thumbnail'"
            :as="isUsedWithoutRoot ? undefined : linkComponent"
            prefetch
            :href="link.url"
            :target="link.new_tab ? '_blank' : undefined"
            :rel="link.new_tab ? 'noopener' : undefined"
            :class="[
              // Fixed-dark card: the scrim keeps the white copy legible in either theme, so it
              // does not follow the surface the way the rest of the panel does. `--ink` is the
              // shared ground for that — see the hero and the podcast panel.
              // Fills the cell: a photograph that stops short of the panel edge reads as a
              // thumbnail pinned to a card, not as the panel's featured image.
              'group relative flex overflow-hidden bg-ink transition-all duration-300',
              mediaCardSizeClass(links, link),
              'focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring',
              linkClasses(link),
              resolveColSpan(link),
              link.featured && 'ring-2 ring-brand/60',
            ]"
            @click="handleCloseMenu">
            <img class="absolute left-0 top-0 size-full object-cover grayscale contrast-110 transition-transform duration-500 group-hover:scale-105"
              :class="[resolveImageOverlay(link), resolveImageBlur(link)]"
              :style="{ objectPosition: link.image_focal ?? '50% 50%' }"
              :src="link.image" alt="">
            <div class="absolute left-0 top-0 size-full transition-opacity duration-300 group-hover:opacity-90" :class="resolveImageGradient(link)" />
            <div :class="['relative z-10 mt-auto', isCompactCard(links) ? 'p-4' : 'p-6']">
              <p v-if="link.eyebrow" class="mb-1 text-[11px] font-bold uppercase tracking-[0.2em] text-brand-fill">
                {{ link.eyebrow }}
              </p>
              <p :class="['inline-flex items-center gap-1.5 text-balance font-bold uppercase leading-tight text-white', isCompactCard(links) ? 'text-sm' : 'text-lg']">
                {{ link.name }}
                <Icon v-if="link.new_tab" icon="fluent:open-16-regular" class="size-3.5 opacity-80" />
                <Badge v-if="link.small_text" :variant="link.badge_variant ?? 'rose'" class="ml-1 px-2 py-0 text-[10px]">
                  {{ link.small_text }}
                </Badge>
              </p>
              <p v-if="link.description" :class="['mt-1 text-xs leading-relaxed text-white/70', isCompactCard(links) ? 'line-clamp-1' : 'mt-1.5 line-clamp-2']">
                {{ link.description }}
              </p>
              <span v-if="link.cta && !isCompactCard(links)" class="mt-3 inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-brand-fill">
                {{ link.cta }}
                <IFluentArrowRight16Regular class="size-3.5 transition-transform group-hover:translate-x-1" aria-hidden="true" />
              </span>
            </div>
            <div v-if="showEditIcons" class="relative z-20 my-auto inline-flex h-fit bg-background/90 p-2">
              <slot :index :link :links name="editIconsBg" />
            </div>
          </component>

          <component :is="isUsedWithoutRoot ? linkComponent : NavigationMenuLink"
            v-else :as="isUsedWithoutRoot ? undefined : linkComponent"
            prefetch
            :href="link.url"
            :target="link.new_tab ? '_blank' : undefined"
            :rel="link.new_tab ? 'noopener' : undefined"
            :class="[
              'group flex h-fit items-center text-left transition-colors',
              'focus:outline-2 focus:-outline-offset-2 focus:outline-ring',
              linkClasses(link),
              resolveColSpan(link),
              link.featured && 'border-l-2 border-brand pl-3',
            ]"
            @click="handleCloseMenu">
            <div class="flex w-full items-center justify-between gap-2">
              <img v-if="link.image && link.image_render === 'thumbnail'" class="mr-3 size-10 shrink-0 object-cover grayscale"
                :style="{ objectPosition: link.image_focal ?? '50% 50%' }" :src="link.image" alt="">
              <div class="h-fit">
                <div class="inline-flex items-center gap-1.5" :class="textClasses(link)">
                  <Icon v-if="link.icon && !(link.image && link.image_render === 'thumbnail')" :icon="`fluent:${link.icon}`" :class="['shrink-0', link.type === 'category-link' ? 'size-3.5' : 'mr-0.5 size-5 text-brand']" />
                  {{ link.name }}
                  <Icon v-if="link.new_tab" icon="fluent:open-16-regular" class="size-3.5 opacity-60" />
                  <!-- Slides in on hover instead of sitting there permanently: with one per row it
                       would read as a column of arrows rather than a pointer to the hovered link. -->
                  <IFluentArrowRight16Regular
                    v-if="link.type !== 'category-link'"
                    class="size-3.5 -translate-x-1 opacity-0 transition-all group-hover:translate-x-0 group-hover:opacity-100"
                    aria-hidden="true"
                  />
                  <Badge v-if="link.small_text" :variant="link.badge_variant ?? 'rose'" class="ml-1 px-2 py-0 text-[10px]">
                    {{ link.small_text }}
                  </Badge>
                </div>
                <p
                  v-if="link.description && link.type !== 'link'"
                  class="mt-0.5 line-clamp-2 text-xs leading-snug text-muted-foreground"
                >
                  {{ link.description }}
                </p>
              </div>
              <slot v-if="showEditIcons" :index :link :links name="editIconsLink" />
            </div>
          </component>
        </template>
      </li>
    </ul>
  </component>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Icon } from '@iconify/vue';

import SmartLink from '../SmartLink.vue';

import type { NavItem, NavLink, NavLinkType } from './types';
import { resolveColSpan, resolveCols, resolveImageBlur, resolveImageGradient, resolveImageOverlay } from './navLinkStyles';

import {
  NavigationMenuContent,
  NavigationMenuLink,
} from '@/Components/ui/navigation-menu';
import { Badge } from '@/Components/ui/badge';

const { isUsedWithoutRoot, areLinksDisabled, item } = defineProps<{
  isUsedWithoutRoot?: boolean;
  areLinksDisabled?: boolean;
  item: NavItem;
  showEditIcons?: boolean;
}>();

const emit = defineEmits<{ closeMenu: [] }>();

/**
 * `textClass` styles the label, `blockClass` the row around it.
 *
 * None of them fill on hover. The design marks the hovered link by colouring it and sliding an
 * arrow in — a background swatch per row turned the panel into a grid of buttons, which is the
 * look this migration is replacing.
 */
const linkTypes = {
  // Two link treatments, and the difference is *content*, not decoration: `link` is a bare
  // headline and packs tightly; `block-link` carries an explanation under it and needs the room
  // for it. They used to differ only in weight/padding with nothing to justify it, which is why
  // every existing block-link (none of which had a description) was migrated down to `link` by
  // 2026_09_03_104936_collapse_block_link_navigation_type.
  'link': {
    textClass: 'text-sm font-bold text-foreground transition-colors group-hover:text-brand',
    blockClass: 'py-1.5 focus-visible:text-brand',
  },
  'block-link': {
    textClass: 'text-sm font-bold text-foreground transition-colors group-hover:text-brand',
    blockClass: 'py-2.5 focus-visible:text-brand',
  },
  // A category heads a group of links; it is not one of them. Same eyebrow-and-rule treatment as
  // a `heading`, so a column reads as "category, then its links" either way — the difference is
  // only that this one is clickable.
  'category-link': {
    textClass: 'u-eyebrow transition-colors group-hover:text-foreground',
    blockClass: 'mb-3 mt-6 border-b border-border pb-3 first:mt-0',
  },
  'full-height-background-link': {
    textClass: 'text-sm font-bold text-foreground transition-colors group-hover:text-brand',
    blockClass: 'h-full py-2.5 focus-visible:text-brand',
  },
};

const rootElement = computed(() => isUsedWithoutRoot ? 'div' : NavigationMenuContent);

/**
 * No gap and no width: the panel is as wide as the header (see NavigationMenuViewport), and its
 * columns are separated by a rule rather than by whitespace — the same hairline vocabulary the
 * rest of the surface uses. Each column owns its own padding, which is why there is none here.
 */
const ulClasses = computed(() => [
  'grid max-lg:max-h-[calc(100dvh-20rem)] lg:max-h-[calc(100vh-10rem)] w-full overflow-y-auto content-stretch',
  resolveCols(item.cols),
]);

const linkComponent = computed(() => areLinksDisabled ? 'div' : SmartLink);

function renderableLinks(links: NavLink[]): NavLink[] {
  return links.filter(link => link.type !== 'divider' && link.type !== 'heading');
}

/**
 * A column whose every actual link is a full-bleed image card. Those fill their cell edge to
 * edge, so the column must not add padding around them.
 */
function isMediaColumn(links: NavLink[]): boolean {
  const renderable = renderableLinks(links);

  return renderable.length > 0
    && renderable.every(link => Boolean(link.image) && link.image_render !== 'thumbnail');
}

/**
 * More than two cards in a column means each is a row in a list, not a feature — so the copy
 * drops to one line and the CTA goes. A column has to hold five without the panel scrolling.
 */
function isCompactCard(links: NavLink[]): boolean {
  return renderableLinks(links).length > 2;
}

/**
 * A lone card grows to fill its column; a stack of them takes a fixed height so five fit. The
 * authored `tall` only applies to a lone card — it is the "this card carries the section" switch,
 * and honouring it in a stack is what made a five-card column overflow.
 */
function mediaCardSizeClass(links: NavLink[], link: NavLink): string {
  const count = renderableLinks(links).length;

  if (count === 1) {
    return link.image_height === 'tall' ? 'min-h-[20rem] flex-1' : 'min-h-[11rem] flex-1';
  }

  return isCompactCard(links) ? 'min-h-[5.5rem]' : 'min-h-[9rem] flex-1';
}

const linkClasses = (link: NavLink) => {
  const linkType = (link?.type && link.type !== 'divider' && link.type !== 'heading' ? link.type : 'link') as NavLinkType;
  return linkTypes[linkType]?.blockClass;
};

const textClasses = (link: NavLink) => {
  const linkType = (link?.type && link.type !== 'divider' && link.type !== 'heading' ? link.type : 'link') as NavLinkType;
  return linkTypes[linkType]?.textClass;
};

const handleCloseMenu = () => {
  emit('closeMenu');
};

</script>
