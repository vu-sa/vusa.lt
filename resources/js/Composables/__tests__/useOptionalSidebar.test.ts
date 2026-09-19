import { describe, expect, it } from 'vitest';
import { defineComponent, h } from 'vue';
import { mount } from '@vue/test-utils';

import { useOptionalSidebar } from '@/Composables/useOptionalSidebar';

describe('useOptionalSidebar', () => {
  it('does not throw without a SidebarProvider and falls back to no-op controls', () => {
    let sidebar!: ReturnType<typeof useOptionalSidebar>;

    mount(defineComponent({
      setup() {
        sidebar = useOptionalSidebar();

        return () => h('div');
      },
    }));

    expect(sidebar.isMobile.value).toBe(false);
    expect(() => {
      sidebar.setOpen(true);
      sidebar.setOpenMobile(true);
    }).not.toThrow();
  });
});
