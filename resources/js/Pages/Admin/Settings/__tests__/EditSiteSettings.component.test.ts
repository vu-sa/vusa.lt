import { describe, it, expect, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, markRaw } from 'vue';

import EditSiteSettings from '@/Pages/Admin/Settings/EditSiteSettings.vue';
import type { NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';

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
  icon: markRaw(defineComponent({ template: '<i />' })),
  title,
  badge: 'VU SA',
  raw: {},
});

function createWrapper(props: {
  selectedPages: { lt: ReturnType<typeof selectedPage> | null; en: ReturnType<typeof selectedPage> | null };
}) {
  return mount(EditSiteSettings, {
    props,
    global: {
      stubs: {
        CollectionSelectDialog: CollectionSelectDialogStub,
        AdminForm: {
          template: '<form @submit.prevent><slot /></form>',
          props: ['model'],
        },
        FormElement: {
          template: '<section><slot name="title" /><slot name="description" /><slot /></section>',
        },
        UpsertModelLayout: { template: '<div><slot /></div>' },
        PageContent: { template: '<div><slot /></div>' },
        Label: { template: '<label><slot /></label>' },
        Button: { template: '<button type="button"><slot /></button>' },
      },
    },
  });
}

const dialogsOf = (wrapper: ReturnType<typeof mount>) =>
  wrapper.findAllComponents(CollectionSelectDialogStub);

interface PageFormState {
  form: { privacy_page_id_lt: string | null; privacy_page_id_en: string | null };
}

const formOf = (wrapper: ReturnType<typeof mount>): PageFormState['form'] =>
  (wrapper.vm as unknown as PageFormState).form;

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

  it('seeds the form and initial hits from the server-provided summaries', () => {
    wrapper = createWrapper({
      selectedPages: {
        lt: selectedPage('10', 'lt', 'Privatumo politika'),
        en: null,
      },
    });

    const vm = formOf(wrapper);
    expect(vm.privacy_page_id_lt).toBe('10');
    expect(vm.privacy_page_id_en).toBeNull();

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

  it('writes the confirmed hit recordId into the matching form field', () => {
    wrapper = createWrapper({
      selectedPages: { lt: null, en: null },
    });

    const dialogs = dialogsOf(wrapper);
    dialogs[1]!.vm.$emit('confirm', [hitFor('20', 'Privacy policy')]);

    const form = formOf(wrapper);
    expect(form.privacy_page_id_en).toBe('20');
    expect(form.privacy_page_id_lt).toBeNull();
  });

  it('clears the field when confirmed with no selection', () => {
    wrapper = createWrapper({
      selectedPages: {
        lt: selectedPage('10', 'lt', 'Privatumo politika'),
        en: null,
      },
    });

    const form = formOf(wrapper);
    expect(form.privacy_page_id_lt).toBe('10');

    const dialogs = dialogsOf(wrapper);
    dialogs[0]!.vm.$emit('confirm', []);

    expect(form.privacy_page_id_lt).toBeNull();
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
