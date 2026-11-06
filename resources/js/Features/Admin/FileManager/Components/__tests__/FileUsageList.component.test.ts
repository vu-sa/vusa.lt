import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { trans } from 'laravel-vue-i18n';

import FileUsageList from '../FileUsageList.vue';

import { commonStubs } from '@/tests/stubs';

describe('file usage details', () => {
  it('shows the count of restricted records alongside visible references', () => {
    const wrapper = mount(FileUsageList, {
      props: {
        usage: {
          is_safe_to_delete: false,
          total_usages: 3,
          hidden_usages: 2,
          scanned_models: ['news'],
          usage_details: [{ model_type: 'news', model_class: 'News', id: 1, title: 'Matomas įrašas', url: '/mano/news/1/edit' }],
        },
      },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toContain('Matomas įrašas');
    expect(wrapper.text()).toContain(trans('files.usage.hidden_count', { count: 2 }));
    expect(wrapper.findAll('li')).toHaveLength(1);
  });

  it('keeps a file with only restricted references marked as used', () => {
    const wrapper = mount(FileUsageList, {
      props: {
        usage: { is_safe_to_delete: false, total_usages: 1, hidden_usages: 1, scanned_models: ['news'], usage_details: [] },
      },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toContain(trans('files.ui.usage_used_badge'));
    expect(wrapper.text()).toContain(trans('files.usage.hidden_count', { count: 1 }));
    expect(wrapper.find('a').exists()).toBe(false);
  });
});
