import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import LinkTargetField from '@/Components/AdminForms/LinkTargetField.vue';

const resolveUrlData = ref<{ url: string } | null>(null);
const isFetching = ref(false);
const executeResolveUrl = vi.fn(async () => {});

vi.mock('@/Composables/useApi', () => ({
  useApiMutation: vi.fn((_url, _method, bodyRef) => ({
    execute: async () => {
      await executeResolveUrl(bodyRef?.value);
    },
    isFetching,
    data: resolveUrlData,
    error: ref(null),
  })),
}));

const stubs = {
  MultiCollectionSelectDialog: {
    props: ['open', 'title', 'collections'],
    emits: ['confirm', 'update:open'],
    template: `
      <div data-testid="picker-dialog" :data-title="title">
        <slot name="trigger" />
        <button type="button" data-testid="confirm-hit" @click="$emit('confirm', [{ collection: 'pages', recordId: '42', title: 'Stipendijos ir parama' }])">confirm</button>
        <button type="button" data-testid="confirm-empty" @click="$emit('confirm', [])">empty</button>
      </div>
    `,
  },
};

function mountField(props: Record<string, unknown> = {}) {
  return mount(LinkTargetField, {
    props: {
      topicOptions: [
        { id: 5, name: 'Renginiai', alias: 'renginiai' },
        { id: 8, name: 'Parama', alias: 'parama' },
      ],
      ...props,
    },
    global: { stubs },
  });
}

describe('LinkTargetField.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    resolveUrlData.value = null;
    isFetching.value = false;
  });

  it('resolves a picked search hit, updates trigger text, and emits select', async () => {
    const wrapper = mountField();
    resolveUrlData.value = { url: '/lt/stipendijos-ir-parama' };

    await wrapper.get('[data-testid="confirm-hit"]').trigger('click');
    await flushPromises();

    expect(executeResolveUrl).toHaveBeenCalledWith({ collection: 'pages', id: '42' });
    expect(wrapper.emitted('select')).toEqual([['/lt/stipendijos-ir-parama']]);
    expect(wrapper.text()).toContain('Stipendijos ir parama');
    wrapper.unmount();
  });

  it('resolves a selected topic and emits select', async () => {
    const wrapper = mountField();
    resolveUrlData.value = { url: '/lt/tema/renginiai' };

    const select = wrapper.get('select');
    await select.setValue('5');
    await flushPromises();

    expect(executeResolveUrl).toHaveBeenCalledWith({ collection: 'topics', id: 5 });
    expect(wrapper.emitted('select')).toEqual([['/lt/tema/renginiai']]);
    expect(wrapper.text()).toContain('Renginiai');
    wrapper.unmount();
  });

  it('ignores empty confirmations and responses without a url', async () => {
    const wrapper = mountField();

    await wrapper.get('[data-testid="confirm-empty"]').trigger('click');
    await flushPromises();
    expect(executeResolveUrl).not.toHaveBeenCalled();

    await wrapper.get('[data-testid="confirm-hit"]').trigger('click');
    await flushPromises();
    expect(executeResolveUrl).toHaveBeenCalledTimes(1);
    expect(wrapper.emitted('select')).toBeUndefined();
    wrapper.unmount();
  });

  it('forwards custom dialogTitle and displays the spinner while resolving', () => {
    isFetching.value = true;
    const wrapper = mountField({ dialogTitle: 'Pasirink objektą' });

    expect(wrapper.get('[data-testid="picker-dialog"]').attributes('data-title')).toBe('Pasirink objektą');
    expect(wrapper.find('.animate-spin').exists()).toBe(true);
    wrapper.unmount();
  });
});
