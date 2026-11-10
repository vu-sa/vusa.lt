import { usePage } from '@inertiajs/vue3';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import IndexGlossaryTerm from '@/Pages/Admin/Learning/IndexGlossaryTerm.vue';
import IndexLearningStep from '@/Pages/Admin/Learning/IndexLearningStep.vue';
import { LearningAudienceType, LearningImportance, LearningStage, LearningStepKind } from '@/Types/enums';
import type { LearningLibraryStep } from '@/Types/learning';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';
import LearningTabs from '../LearningTabs.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const originalUrl = window.location.href;
const steps: LearningLibraryStep[] = [
  {
    id: 'guide', title: 'Read the guide', kind: LearningStepKind.Knowledge,
    audiences: [{ id: 'a1', type: LearningAudienceType.Everyone, label: 'Visi', stage: LearningStage.BeforeStart, importance: LearningImportance.Essential }],
    audienceTypes: [LearningAudienceType.Everyone], section: { id: 's1', title: 'Pagrindai' }, order: 0, isActive: true, canUpdate: false, flags: [],
  },
  {
    id: 'action', title: 'Meet your team', kind: LearningStepKind.Action,
    audiences: [{ id: 'a2', type: LearningAudienceType.InstitutionType, label: 'Taryba', stage: LearningStage.BeforeStart, importance: LearningImportance.Recommended }],
    audienceTypes: [LearningAudienceType.InstitutionType], section: null, order: 1, isActive: true, canUpdate: true, flags: [],
  },
];

beforeEach(() => {
  vi.stubGlobal('route', (name: string) => `/mocked/${name}`);
  localStorage.clear();
  window.history.replaceState({}, '', '/mano/learning/library?view=rows');
  vi.mocked(usePage).mockReturnValue({ ...createMockPage({ learningNavigation: { canManage: true, canSeeReps: false } }), url: '/mano/learning/library' });
  vi.stubGlobal('matchMedia', (query: string) => ({
    matches: query.includes('min-width'), media: query, onchange: null,
    addListener: vi.fn(), removeListener: vi.fn(),
    addEventListener: vi.fn(), removeEventListener: vi.fn(), dispatchEvent: vi.fn(),
  }));
});

afterEach(() => {
  vi.unstubAllGlobals();
  localStorage.clear();
  window.history.replaceState({}, '', originalUrl);
});

function render(canCreate = false) {
  return mount(IndexLearningStep, {
    props: { steps, canCreate, canSeeReps: false },
    global: { stubs: {
      ...commonStubs,
      Sheet: commonStubs.Dialog, SheetContent: commonStubs.DialogContent,
      SheetHeader: commonStubs.DialogHeader, SheetTitle: commonStubs.DialogTitle, SheetDescription: commonStubs.DialogDescription,
    } },
  });
}

const rowTitles = (wrapper: ReturnType<typeof render>) =>
  wrapper.findAll('[data-slot="collection-row"]').map(row => row.get('a').text());

describe('learning library', () => {
  it.each([
    ['audience', 'institution_type', 'Meet your team'],
    ['kind', 'knowledge', 'Read the guide'],
    ['importance', 'essential', 'Read the guide'],
    ['section', 's1', 'Read the guide'],
  ])('renders the learning tabs and narrows rows with the %s quick filter', async (field, value, title) => {
    const filter = `${field}:${value}`;
    const wrapper = render();
    await flushPromises();
    expect(wrapper.findComponent(LearningTabs).exists()).toBe(true);
    expect(wrapper.get('nav [aria-current="page"]').text()).toBe('learning.tabs.library');
    expect(rowTitles(wrapper)).toEqual(['Read the guide', 'Meet your team']);

    await wrapper.get(`[data-filter-id="${filter}"]`).trigger('click');
    expect(rowTitles(wrapper)).toEqual([title]);
    expect(wrapper.get(`[data-filter-id="${filter}"]`).attributes('aria-pressed')).toBe('true');
    expect(new URLSearchParams(window.location.search).get(field)).toBe(value);

    await wrapper.get(`[data-filter-id="${filter}"]`).trigger('click');
    expect(rowTitles(wrapper)).toEqual(['Read the guide', 'Meet your team']);
    wrapper.unmount();
  });

  it('combines filters across fields and lets the active chips clear them', async () => {
    const wrapper = render();
    await flushPromises();
    await wrapper.get('[data-filter-id="audience:institution_type"]').trigger('click');
    await wrapper.get('[data-filter-id="kind:action"]').trigger('click');
    expect(rowTitles(wrapper)).toEqual(['Meet your team']);
    expect(new URLSearchParams(window.location.search).get('audience')).toBe('institution_type');
    expect(new URLSearchParams(window.location.search).get('kind')).toBe('action');
    const clear = wrapper.get('[data-slot="collection-active-chips"]').findAll('button').find(button => button.text() === 'Išvalyti visus');
    expect(clear).toBeDefined();
    await clear!.trigger('click');
    expect(rowTitles(wrapper)).toEqual(['Read the guide', 'Meet your team']);
    expect(window.location.search).not.toContain('audience=');
    expect(window.location.search).not.toContain('kind=');
    wrapper.unmount();
  });

  it('renders the learning tabs above the glossary collection', async () => {
    vi.mocked(usePage).mockReturnValue({ ...createMockPage(), url: '/mano/learning/glossary' });
    const wrapper = mount(IndexGlossaryTerm, {
      props: { terms: [], canManage: false, canSeeReps: false },
      global: { stubs: commonStubs },
    });
    await flushPromises();
    expect(wrapper.findComponent(LearningTabs).exists()).toBe(true);
    expect(wrapper.get('nav [aria-current="page"]').text()).toBe('learning.tabs.glossary');
    wrapper.unmount();
  });

  it('shows glossary definitions and related document links in rows for a member', async () => {
    window.history.replaceState({}, '', '/mano/learning/glossary?view=table');
    vi.mocked(usePage).mockReturnValue({ ...createMockPage(), url: '/mano/learning/glossary' });
    const wrapper = mount(IndexGlossaryTerm, {
      props: {
        terms: [{
          id: 'quorum', term: 'Kvorumas', definition: 'Būtinas narių skaičius.', stepsCount: 1,
          translations: { term: { lt: 'Kvorumas', en: 'Quorum' }, definition: { lt: 'Būtinas narių skaičius.', en: 'Required members.' } },
          steps: [{ id: 'rules', title: 'Senato reglamentas', url: 'https://example.test/rules', isDocument: true }],
        }],
        canManage: false, canSeeReps: false,
      },
      global: { stubs: commonStubs },
    });
    await flushPromises();
    const row = wrapper.get('[data-slot="collection-row"]');
    expect(row.text()).toContain('Būtinas narių skaičius.');
    expect(row.text()).toContain('learning.glossary.where_used');
    expect(row.get('a[href="https://example.test/rules"]').text()).toBe('Senato reglamentas');
    expect(wrapper.find('[data-testid="create-glossary-term"]').exists()).toBe(false);
    wrapper.unmount();
  });

  it('uses the controller permission prop for the creation action', async () => {
    const wrapper = render();
    expect(wrapper.find('[data-testid="create-learning-step"]').exists()).toBe(false);
    await wrapper.setProps({ canCreate: true });
    expect(wrapper.get('[data-testid="create-learning-step"]').attributes('href')).toContain('learningSteps.create');
    wrapper.unmount();
  });
});

it('shows only two audience labels plus a remainder and uses the highest importance', async () => {
  const wrapper = render();
  const base = steps[1]!;
  await wrapper.setProps({ steps: [{ ...base, audiences: [
    { ...base.audiences[0]!, label: 'Viena', importance: LearningImportance.Optional },
    { ...base.audiences[0]!, id: 'a3', label: 'Antra', importance: LearningImportance.Essential },
    { ...base.audiences[0]!, id: 'a4', label: 'Trečia', importance: LearningImportance.Recommended },
  ] }] });
  await flushPromises();
  const row = wrapper.get('[data-slot="collection-row"]');
  expect(row.text()).toContain('Viena, Antra +1');
  expect(row.text()).not.toContain('Trečia');
  expect(row.text()).toContain('learning.importance.essential');
  wrapper.unmount();
});

it('opens sections from the library header', async () => {
  const wrapper = render(true);
  await wrapper.get('[data-testid="manage-sections"]').trigger('click');
  expect(wrapper.findComponent({ name: 'LearningSectionsSheet' }).props('open')).toBe(true);
  wrapper.unmount();
});
