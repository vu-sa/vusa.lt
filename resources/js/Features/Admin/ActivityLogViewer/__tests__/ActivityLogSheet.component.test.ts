import { describe, test, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent } from 'vue';
import { usePage } from '@inertiajs/vue3';

import ActivityLogSheet from '../ActivityLogSheet.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

const mockFetch = vi.fn();
vi.stubGlobal('fetch', mockFetch);

function jsonResponse(body: unknown) {
  return {
    ok: true,
    status: 200,
    json: () => Promise.resolve(body),
  };
}

// Sheet is built on reka-ui's DialogRoot/DialogPortal (Teleport-based), which
// is unpredictable in jsdom -- same rationale as the shared Dialog stubs. The
// stub always renders its content (no open-state gating): this test exercises
// ActivityLogSheet's own fetch-on-open wiring, not the sheet's visual
// open/close transition, which is intentionally left uncovered here.
const SheetStub = defineComponent({
  name: 'SheetStub',
  emits: ['update:open'],
  template: '<div><slot /></div>',
});
const SheetTriggerStub = defineComponent({ name: 'SheetTriggerStub', template: '<div><slot /></div>' });
const SheetContentStub = defineComponent({ name: 'SheetContentStub', template: '<div><slot /></div>' });
const SheetHeaderStub = defineComponent({ name: 'SheetHeaderStub', template: '<div><slot /></div>' });
const SheetTitleStub = defineComponent({ name: 'SheetTitleStub', template: '<div><slot /></div>' });

const sheetStubs = {
  ...commonStubs,
  Sheet: SheetStub,
  SheetTrigger: SheetTriggerStub,
  SheetContent: SheetContentStub,
  SheetHeader: SheetHeaderStub,
  SheetTitle: SheetTitleStub,
  SpotlightPopover: { template: '<div><slot /></div>' },
};

function activityLogCalls() {
  // The mocked route() (tests/setup.ts) returns `/mocked-route/{routeName}`,
  // not a real path -- match on the route name, not a URL segment.
  return mockFetch.mock.calls.filter(call => String(call[0]).includes('activityLog.index'));
}

describe('ActivityLogSheet', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.mocked(usePage).mockReturnValue(createMockPage({ csrf_token: 'test-csrf-token' }));
    mockFetch.mockResolvedValue(jsonResponse({
      success: true,
      data: [],
      meta: { cursor: { next: null, prev: null, per_page: 25, has_more: false } },
    }));
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  test('does not fetch the activity log on mount', () => {
    mount(ActivityLogSheet, {
      props: { subjectType: 'meeting', subjectId: '1' },
      global: { stubs: sheetStubs },
    });

    expect(activityLogCalls()).toHaveLength(0);
  });

  test('fetches once when the sheet is opened', async () => {
    const wrapper = mount(ActivityLogSheet, {
      props: { subjectType: 'meeting', subjectId: '1' },
      global: { stubs: sheetStubs },
    });

    await wrapper.findComponent(SheetStub).vm.$emit('update:open', true);
    await flushPromises();

    expect(activityLogCalls()).toHaveLength(1);
  });

  test('does not refetch when the sheet is opened a second time', async () => {
    const wrapper = mount(ActivityLogSheet, {
      props: { subjectType: 'meeting', subjectId: '1' },
      global: { stubs: sheetStubs },
    });

    await wrapper.findComponent(SheetStub).vm.$emit('update:open', true);
    await flushPromises();
    await wrapper.findComponent(SheetStub).vm.$emit('update:open', false);
    await wrapper.findComponent(SheetStub).vm.$emit('update:open', true);
    await flushPromises();

    expect(activityLogCalls()).toHaveLength(1);
  });

  test('changing the scope filter resets and refetches', async () => {
    const wrapper = mount(ActivityLogSheet, {
      props: { subjectType: 'meeting', subjectId: '1' },
      global: { stubs: sheetStubs },
    });

    await wrapper.findComponent(SheetStub).vm.$emit('update:open', true);
    await flushPromises();
    expect(activityLogCalls()).toHaveLength(1);

    const buttons = wrapper.findAll('button');
    const selfButton = buttons.find(b => b.text().includes('activity.filter.scope_self'));
    await selfButton?.trigger('click');
    await flushPromises();

    const calls = activityLogCalls();
    expect(calls).toHaveLength(2);
    expect(String(calls[1][0])).toContain('scope=self');
  });
});

async function flushPromises(): Promise<void> {
  await Promise.resolve();
  await Promise.resolve();
}
