import { describe, it, expect, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';

import PublicUrlHistoryCard, { type PublicUrlRow } from '@/Components/AdminForms/PublicUrlHistoryCard.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const stubs = {
  ...commonStubs,
  DeleteConfirmationDialog: {
    props: ['isOpen', 'title', 'message', 'isDeleting'],
    template: '<div v-if="isOpen" data-testid="delete-dialog"><button data-testid="confirm-delete" @click="$emit(\'confirm\')">confirm</button></div>',
  },
};

describe('PublicUrlHistoryCard.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  afterEach(() => {
    wrapper?.unmount();
  });

  // Every row is a retired permalink — public_urls never stores the current one, so there is
  // no is_canonical field to filter on anymore.
  const legacy: PublicUrlRow = {
    id: 2,
    url: 'https://vusa.lt/lt/senas-puslapis',
    locale: 'lt',
    created_at: '2025-01-01T00:00:00Z',
  };

  it('renders nothing when there are no legacy urls', () => {
    wrapper = mount(PublicUrlHistoryCard, {
      props: { urls: [], destroyRoute: (id: number) => `/mano/pages/1/public-urls/${id}` },
      global: { stubs },
    });

    expect(wrapper.html()).toBe('<!--v-if-->');
  });

  it('lists every url passed in', () => {
    wrapper = mount(PublicUrlHistoryCard, {
      props: { urls: [legacy], destroyRoute: (id: number) => `/mano/pages/1/public-urls/${id}` },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('senas-puslapis');
    expect(wrapper.findAll('li')).toHaveLength(1);
  });

  it('deletes the confirmed row via the caller-supplied destroy route', async () => {
    wrapper = mount(PublicUrlHistoryCard, {
      props: { urls: [legacy], destroyRoute: (id: number) => `/mano/pages/1/public-urls/${id}` },
      global: { stubs },
    });

    await wrapper.find('[data-testid="delete-public-url"]').trigger('click');
    await wrapper.find('[data-testid="confirm-delete"]').trigger('click');

    expect(router.delete).toHaveBeenCalledWith('/mano/pages/1/public-urls/2', expect.objectContaining({
      preserveScroll: true,
    }));
  });
});
