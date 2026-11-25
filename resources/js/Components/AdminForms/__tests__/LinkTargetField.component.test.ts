import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import LinkTargetField from '@/Components/AdminForms/LinkTargetField.vue';

interface ResolveResult { ok: boolean; data: { url: string } | null }

let nextResult: ResolveResult = { ok: false, data: null };
const executeResolveUrl = vi.fn(async (_body?: unknown): Promise<ResolveResult> => nextResult);

vi.mock('@/Composables/useApi', () => ({
  apiRequest: vi.fn((_url: string, options: { body?: unknown }) => executeResolveUrl(options.body)),
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
    nextResult = { ok: false, data: null };
  });

  it('resolves a picked search hit, updates trigger text, and emits select', async () => {
    const wrapper = mountField();
    nextResult = { ok: true, data: { url: '/lt/stipendijos-ir-parama' } };

    await wrapper.get('[data-testid="confirm-hit"]').trigger('click');
    await flushPromises();

    expect(executeResolveUrl).toHaveBeenCalledWith({ collection: 'pages', id: '42' });
    expect(wrapper.emitted('select')).toEqual([['/lt/stipendijos-ir-parama']]);
    expect(wrapper.text()).toContain('Stipendijos ir parama');
    wrapper.unmount();
  });

  it('resolves a selected topic and emits select', async () => {
    const wrapper = mountField();
    nextResult = { ok: true, data: { url: '/lt/tema/renginiai' } };

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

  it('keeps the previous target when a later lookup fails', async () => {
    const wrapper = mountField();
    nextResult = { ok: true, data: { url: '/lt/tema/renginiai' } };
    await wrapper.get('select').setValue('5');
    await flushPromises();

    nextResult = { ok: false, data: null };
    await wrapper.get('[data-testid="confirm-hit"]').trigger('click');
    await flushPromises();

    expect(wrapper.emitted('select')).toEqual([['/lt/tema/renginiai']]);
    expect((wrapper.get('select').element as HTMLSelectElement).value).toBe('5');
    expect(wrapper.text()).not.toContain('Stipendijos ir parama');
    wrapper.unmount();
  });

  it('forwards custom dialogTitle and displays the spinner while resolving', async () => {
    executeResolveUrl.mockImplementationOnce(() => new Promise(() => {}));
    const wrapper = mountField({ dialogTitle: 'Pasirink objektą' });

    expect(wrapper.get('[data-testid="picker-dialog"]').attributes('data-title')).toBe('Pasirink objektą');
    await wrapper.get('[data-testid="confirm-hit"]').trigger('click');
    expect(wrapper.find('.animate-spin').exists()).toBe(true);
    wrapper.unmount();
  });
});
