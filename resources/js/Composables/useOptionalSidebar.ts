import { useSidebar } from '@/Components/ui/sidebar/utils';
import { useIsMobile } from '@/Composables/useIsMobile';

const noop = () => {};

/**
 * `useSidebar()` throws outside a `SidebarProvider`, and the new admin shell has none. Pages and
 * the ActionWindow that only need "am I on a phone" or a best-effort "open the sidebar" use this
 * instead: the controls become no-ops and `isMobile` falls back to the shell's own media query.
 *
 * @deprecated Goes away with the sidebar in PR 8.1; callers then use `useIsMobile()` directly.
 */
export function useOptionalSidebar() {
  const sidebar = useSidebar(null as never);
  const isMobile = useIsMobile();

  return {
    isMobile: sidebar?.isMobile ?? isMobile,
    setOpen: sidebar?.setOpen ?? noop,
    setOpenMobile: sidebar?.setOpenMobile ?? noop,
  };
}
