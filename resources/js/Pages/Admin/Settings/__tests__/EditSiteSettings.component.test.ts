import { useForm } from '@inertiajs/vue3';
import { describe, it, expect, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent } from 'vue';

import type { MockForm } from '@/tests/helpers/createMockForm';
import EditSiteSettings from '@/Pages/Admin/Settings/EditSiteSettings.vue';
import type { NormalizedSearchHit } from '@/Components/Search/adminSearchCollections';

/**
 * Stands in for CollectionSelectDialog — the real Typesense search flow is out of scope
 * here (network + dialog behavior are covered by the AdminSearch Select suite). This stub
 * keeps the trigger slot, which is the contract this page relies on for rendering.
 */
const CollectionSelectDialogStub = defineComponent({
  name: 'CollectionSelectDialog',
  props: [
    'open',
    'collection',
    'baseFilterBy',
    'initialHits',
    'allowEmpty',
    'title',
    'confirmLabel',
    'searchPlaceholder',
    'emptyMessage',
  ],
  emits: ['update:open', 'confirm'],
  template: `
    <div data-testid="collection-select-dialog">
      <slot name="trigger" />
    </div>
  `,
});

const selectedPage = (id: string, lang: 'lt' | 'en', title: string) => ({
  id,
  title,
  lang,
  tenant_shortname: 'VU SA',
});

const hitFor = (id: string, title: string): NormalizedSearchHit => ({
  id: `pages-${id}`,
  recordId: id,
  collection: 'pages',
  title,
  badge: 'VU SA',
  raw: {},
});

function createWrapper(props: {
  selectedPages: { lt: ReturnType<typeof selectedPage> | null; en: ReturnType<typeof selectedPage> | null };
}) {
  return mount(EditSiteSettings, {
    props: { canEdit: true, ...props },
    global: {
      stubs: {
        CollectionSelectDialog: CollectionSelectDialogStub,
        FormPage: { emits: ['submit'], props: ['title'], template: `<form @submit.prevent="$emit('submit')"><slot /></form>` },
        FormPanel: { template: '<section><slot /></section>' },
        FormFieldWrapper: { props: ['id', 'label', 'error'], template: '<div><label>{{ label }}</label><slot /></div>' },
        Button: { template: '<button type="button"><slot /></button>' },
      },
    },
  });
}

const dialogsOf = (wrapper: ReturnType<typeof mount>) =>
  wrapper.findAllComponents(CollectionSelectDialogStub);

async function submitPrivacyPages(wrapper: ReturnType<typeof mount>) {
  const form = vi.mocked(useForm).mock.results.at(-1)!.value as unknown as MockForm;
  const submitted = vi.fn(() => ({ lt: form.privacy_page_id_lt, en: form.privacy_page_id_en }));
  form.post.mockImplementation(submitted);
  await wrapper.get('form').trigger('submit');

  expect(form.post).toHaveBeenCalledExactlyOnceWith('/mocked-route/settings.site.update');
  return submitted.mock.results[0]?.value;
}

describe('EditSiteSettings.vue — per-language privacy page pickers', () => {
  let wrapper: ReturnType<typeof mount>;

  afterEach(() => {
    wrapper?.unmount();
  });

  it('renders one search-backed picker per language, scoped by lang and is_active', () => {
    wrapper = createWrapper({
      selectedPages: {
        lt: selectedPage('10', 'lt', 'Privatumo politika'),
        en: selectedPage('20', 'en', 'Privacy policy'),
      },
    });

    const dialogs = dialogsOf(wrapper);
    expect(dialogs).toHaveLength(2);

    expect(dialogs[0]!.props('collection')).toBe('pages');
    expect(dialogs[0]!.props('baseFilterBy')).toBe('is_active:=true && lang:=lt');
    expect(dialogs[1]!.props('baseFilterBy')).toBe('is_active:=true && lang:=en');
  });

  it('labels each picker with its language code instead of an externally hosted flag', () => {
    wrapper = createWrapper({ selectedPages: { lt: null, en: null } });

    const labels = wrapper.findAll('label').map(label => label.text());
    expect(labels[0]).toContain('· LT');
    expect(labels[1]).toContain('· EN');
    expect(wrapper.find('img').exists()).toBe(false);
  });

  it('seeds the form and initial hits from the server-provided summaries', async () => {
    wrapper = createWrapper({
      selectedPages: {
        lt: selectedPage('10', 'lt', 'Privatumo politika'),
        en: null,
      },
    });

    expect(await submitPrivacyPages(wrapper)).toEqual({ lt: '10', en: null });

    const dialogs = dialogsOf(wrapper);
    expect(dialogs[0]!.props('initialHits')).toHaveLength(1);
    expect(dialogs[0]!.props('initialHits')[0].recordId).toBe('10');
    expect(dialogs[1]!.props('initialHits')).toHaveLength(0);
  });

  it('shows the selected title on the trigger and the placeholder when unset', () => {
    wrapper = createWrapper({
      selectedPages: {
        lt: selectedPage('10', 'lt', 'Privatumo politika'),
        en: null,
      },
    });

    const triggers = wrapper.findAll('button');
    expect(triggers[0]!.text()).toContain('Privatumo politika');
    expect(triggers[0]!.text()).toContain('VU SA');
    expect(triggers[1]!.text()).toContain('settings.site_settings.privacy_page_placeholder');
  });

  it('writes the confirmed hit recordId into the matching form field', async () => {
    wrapper = createWrapper({
      selectedPages: { lt: null, en: null },
    });

    const dialogs = dialogsOf(wrapper);
    dialogs[1]!.vm.$emit('confirm', [hitFor('20', 'Privacy policy')]);

    expect(await submitPrivacyPages(wrapper)).toEqual({ lt: null, en: '20' });
  });

  it('clears the field when confirmed with no selection', async () => {
    wrapper = createWrapper({
      selectedPages: {
        lt: selectedPage('10', 'lt', 'Privatumo politika'),
        en: null,
      },
    });

    const dialogs = dialogsOf(wrapper);
    dialogs[0]!.vm.$emit('confirm', []);

    expect(await submitPrivacyPages(wrapper)).toEqual({ lt: null, en: null });
  });

  it('updates the trigger label after a new pick', async () => {
    wrapper = createWrapper({
      selectedPages: { lt: null, en: null },
    });

    const dialogs = dialogsOf(wrapper);
    dialogs[0]!.vm.$emit('confirm', [hitFor('10', 'Naujas puslapis')]);

    await wrapper.vm.$nextTick();
    const triggers = wrapper.findAll('button');
    expect(triggers[0]!.text()).toContain('Naujas puslapis');
  });
});

// Not covered: the live Typesense query inside the dialog and its list rendering — network
// behavior belongs to the AdminSearch Select suite. Here the dialog is stubbed to its
// trigger-slot + confirm contract only.
