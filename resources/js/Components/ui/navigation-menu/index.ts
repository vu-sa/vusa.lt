import { cva } from 'class-variance-authority';

export { default as NavigationMenu } from './NavigationMenu.vue';
export { default as NavigationMenuContent } from './NavigationMenuContent.vue';
export { default as NavigationMenuIndicator } from './NavigationMenuIndicator.vue';
export { default as NavigationMenuItem } from './NavigationMenuItem.vue';
export { default as NavigationMenuLink } from './NavigationMenuLink.vue';
export { default as NavigationMenuList } from './NavigationMenuList.vue';
export { default as NavigationMenuTrigger } from './NavigationMenuTrigger.vue';
export { default as NavigationMenuViewport } from './NavigationMenuViewport.vue';

export const navigationMenuTriggerStyle = cva(
  // Tokenised rather than shadcn's stock zinc palette. This primitive is used only by the public
  // navigation (MainMenu, MainNavigationMenuContent), so it can follow the public surface
  // directly: muted label, brand on hover and while the panel is open, transparent ground so the
  // header's own background shows through.
  'group inline-flex h-9 w-max items-center justify-center rounded-md bg-transparent px-4 py-2 '
  + 'text-sm font-medium text-foreground/80 outline-none transition-colors duration-200 '
  + 'hover:bg-transparent hover:text-brand focus:bg-transparent focus:text-brand '
  + 'data-[state=open]:bg-transparent data-[state=open]:text-brand '
  + 'focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-1 '
  + 'disabled:pointer-events-none disabled:opacity-50',
);
