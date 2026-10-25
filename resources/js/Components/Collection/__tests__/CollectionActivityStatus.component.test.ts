import { mount, flushPromises } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import CollectionActivityStatus from '../CollectionActivityStatus.vue';
import CollectionStatusMenu from '../CollectionStatusMenu.vue';

import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';
import { commonStubs } from '@/tests/stubs';

const apiMocks = vi.hoisted(() => ({ get: vi.fn(), mutate: vi.fn(), dismiss: vi.fn() }));
vi.mock('@/Composables/useApi', () => ({ useApi: apiMocks.get, useApiMutation: apiMocks.mutate }));
vi.mock('@/Composables/useFeatureSpotlight', () => ({ useFeatureSpotlight: () => ({ isDismissed: { value: true }, dismiss: apiMocks.dismiss }) }));
vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

function render(props: Record<string, unknown> = {}) {
  return mount(CollectionActivityStatus, {
    props: { entity: 'duties', recordId: 'duty-1', active: true, editable: true, ...props },
    global: { stubs: { ...commonStubs, CollectionStatusMenu: true, ConfirmDialog: true, SpotlightPopover: { template: '<div><slot /></div>' } } },
  });
}

beforeEach(() => {
  vi.clearAllMocks();
  apiMocks.mutate.mockReturnValue({ execute: vi.fn().mockResolvedValue(undefined), isSuccess: ref(true), data: ref({ is_active: false }) });
  apiMocks.get.mockReturnValue({ execute: vi.fn().mockResolvedValue(undefined), isSuccess: ref(true), data: ref({ deactivation: { endingCount: 2, effectiveEndDate: '2026-10-24', blockers: [] } }) });
});

describe('CollectionActivityStatus', () => {
  it('keeps an unauthorized status read-only and rejects emitted changes', async () => {
    const wrapper = render({ editable: false });
    expect(wrapper.findComponent(CollectionStatusMenu).props('editable')).toBe(false);
    wrapper.findComponent(CollectionStatusMenu).vm.$emit('update:modelValue', 'inactive');
    await flushPromises();
    expect(apiMocks.mutate).not.toHaveBeenCalled();
  });

  it('persists a duty activity change without posting the record attributes', async () => {
    const wrapper = render();
    wrapper.findComponent(CollectionStatusMenu).vm.$emit('update:modelValue', 'inactive');
    await flushPromises();
    expect(apiMocks.mutate).toHaveBeenCalledWith(expect.any(String), 'PATCH', { is_active: false }, expect.any(Object));
    expect(wrapper.emitted('updated')).toEqual([[false]]);
    expect(wrapper.findComponent(CollectionStatusMenu).props('status')).toMatchObject({ label: 'Neaktyvi', role: 'neutral', uppercase: true });
  });

  it('rolls back a rejected mutation', async () => {
    apiMocks.mutate.mockReturnValue({ execute: vi.fn().mockResolvedValue(undefined), isSuccess: ref(false), data: ref(null) });
    const wrapper = render();
    wrapper.findComponent(CollectionStatusMenu).vm.$emit('update:modelValue', 'inactive');
    await flushPromises();
    expect(wrapper.emitted('updated')).toBeUndefined();
    expect(wrapper.findComponent(CollectionStatusMenu).props('modelValue')).toBe('active');
  });

  it('loads institution consequences and waits for explicit confirmation', async () => {
    const wrapper = render({ entity: 'institutions' });
    wrapper.findComponent(CollectionStatusMenu).vm.$emit('update:modelValue', 'inactive');
    await flushPromises();
    expect(apiMocks.get).toHaveBeenCalled();
    expect(apiMocks.mutate).not.toHaveBeenCalled();
    expect(wrapper.findComponent(ConfirmDialog).props('open')).toBe(true);
    wrapper.findComponent(ConfirmDialog).vm.$emit('confirm');
    await flushPromises();
    expect(wrapper.emitted('updated')).toEqual([[false]]);
  });

  it('leaves the institution unchanged on cancellation and exposes blockers', async () => {
    apiMocks.get.mockReturnValue({ execute: vi.fn().mockResolvedValue(undefined), isSuccess: ref(true), data: ref({ deactivation: { endingCount: 0, effectiveEndDate: '2026-10-24', blockers: [{ duty: 'Pirmininkas', startDate: '2026-11-01' }] } }) });
    const wrapper = render({ entity: 'institutions' });
    wrapper.findComponent(CollectionStatusMenu).vm.$emit('update:modelValue', 'inactive');
    await flushPromises();
    expect(wrapper.findComponent(ConfirmDialog).props('confirmDisabled')).toBe(true);
    wrapper.findComponent(ConfirmDialog).vm.$emit('update:open', false);
    await flushPromises();
    expect(wrapper.findComponent(ConfirmDialog).props('open')).toBe(false);
    expect(apiMocks.mutate).not.toHaveBeenCalled();
  });
});
