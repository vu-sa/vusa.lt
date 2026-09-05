import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import EditHomePage from '../EditHomePage.vue';

/**
 * Regression: this page used to mount `RichContentFormElement` without `:tenant-id` at
 * all (unlike PageForm.vue/NewsForm.vue, which always passed it) — every server-resolved
 * block (link-list, event-list, news, calendar) then silently got no preview data,
 * indistinguishable from "nothing to show" without the warning
 * `useContentPartPreview.ts` now logs for exactly this case.
 */
describe('EditHomePage', () => {
  it('passes the tenant id through to RichContentFormElement', () => {
    const wrapper = mount(EditHomePage, {
      props: {
        tenant: { id: 42, content: { parts: [] } } as unknown as App.Entities.Tenant,
      },
      global: {
        stubs: {
          ActivityLogSheet: true,
          AdminContentPage: { template: '<div><slot name="aside-header" /><slot /></div>' },
          AdminForm: { template: '<div><slot /></div>' },
          UpsertModelLayout: { template: '<div><slot /></div>' },
          RichContentFormElement: { props: ['tenantId'], template: '<div :data-tenant-id="tenantId" />' },
        },
      },
    });

    expect(wrapper.get('[data-tenant-id]').attributes('data-tenant-id')).toBe('42');
  });
});
