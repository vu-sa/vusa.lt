import { computed, ref, onMounted, onUnmounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import { SCROLL_THRESHOLD } from "@/Constants/navigation";

/**
 * Navigation height constants in pixels
 * These values match the actual rendered heights of navigation components
 */
export const NAV_HEIGHTS = {
  /** Primary navigation bar height (logo, main menu, buttons) */
  PRIMARY: 64,
  /** Secondary menu height (tenant links) - only shown for some tenants */
  SECONDARY: 48,
  /** Additional margin/padding around navigation */
  MARGIN: 16,
} as const;

/**
 * Composable for managing secondary menu visibility and navigation spacing.
 * 
 * The secondary menu is visible when:
 * 1. The tenant has links configured (hasSecondMenu)
 * 2. The user hasn't scrolled past the threshold (not hasScrolledDown)
 * 
 * This composable centralizes the logic for both:
 * - MainNavigation.vue (to show/hide the menu)
 * - PublicLayout.vue (to adjust main content offset)
 * - Any other component that needs to react to navigation state
 * 
 * @example
 * ```vue
 * const { isSecondMenuVisible, hasSecondMenu, mainContentOffset } = useSecondMenu();
 * ```
 */
export function useSecondMenu() {
  const hasScrolledDown = ref(false);
  
  // Check if tenant has second menu links configured
  const hasSecondMenu = computed(() => {
    const page = usePage();
    const links = page.props.tenant?.links;
    return links && links.length > 0;
  });

  // Second menu is visible when tenant has it AND user hasn't scrolled down
  const isSecondMenuVisible = computed(() => 
    hasSecondMenu.value && !hasScrolledDown.value
  );

  // Calculate the main content offset based on navigation state
  // This ensures content doesn't overlap with the fixed navigation
  const mainContentOffset = computed(() => {
    const baseOffset = NAV_HEIGHTS.PRIMARY + NAV_HEIGHTS.MARGIN;
    
    if (hasSecondMenu.value) {
      // When second menu exists, add its height
      // The menu slides away when scrolled, but we keep the offset for smooth transitions
      return baseOffset + NAV_HEIGHTS.SECONDARY;
    }
    
    return baseOffset;
  });

  // CSS class for main content top margin (Tailwind classes)
  const mainContentMarginClass = computed(() => {
    if (hasSecondMenu.value) {
      // mt-32 = 128px = 64px (primary) + 48px (secondary) + 16px (margin)
      return 'mt-32';
    }
    // mt-20 = 80px = 64px (primary) + 16px (margin)
    return 'mt-20';
  });

  // Scroll event handler
  const handleScroll = () => {
    hasScrolledDown.value = window.scrollY > SCROLL_THRESHOLD;
  };

  // Set up scroll listener
  onMounted(() => {
    window.addEventListener("scroll", handleScroll, { passive: true });
    // Check initial scroll position
    handleScroll();
  });

  onUnmounted(() => {
    window.removeEventListener("scroll", handleScroll);
  });

  return {
    /** Whether the tenant has a second menu configured */
    hasSecondMenu,
    /** Whether the second menu is currently visible (not scrolled away) */
    isSecondMenuVisible,
    /** Whether the user has scrolled past the threshold */
    hasScrolledDown,
    /** Main content offset in pixels */
    mainContentOffset,
    /** Tailwind class for main content margin-top */
    mainContentMarginClass,
  };
}

/**
 * Simplified version that only checks if second menu exists (no scroll tracking)
 * Use this when you only need to know about the menu existence, not visibility state.
 * 
 * @example
 * ```vue
 * const hasSecondMenu = useHasSecondMenu();
 * ```
 */
export function useHasSecondMenu() {
  return computed(() => {
    const page = usePage();
    const links = page.props.tenant?.links;
    return links && links.length > 0;
  });
}
