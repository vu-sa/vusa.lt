import { mount } from '@vue/test-utils';
import { expect, it, vi } from 'vitest';

import UnifiedResultItem from '../UnifiedResultItem.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

it('renders the readable part of a search summary', () => {
  const wrapper = mount(UnifiedResultItem, {
    props: {
      collection: 'news',
      doc: {
        id: 1, title: 'Naujiena',
        short: '<style>p{}</style><script>void(0)</script><p>Tekstas &amp; <strong>žodžiai</strong></p><img src="/photo.png" onload="void(0)">',
      },
      icon: { template: '<span />' },
    },
  });

  expect(wrapper.text()).toContain('Tekstas & žodžiai');
  expect(wrapper.text()).not.toContain('void(0)');
  expect(wrapper.find('img, script, style, strong').exists()).toBe(false);
});
