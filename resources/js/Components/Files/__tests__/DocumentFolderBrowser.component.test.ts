import { flushPromises, mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import DocumentFolderBrowser from '../DocumentFolderBrowser.vue';
import type { DocumentFolderListing, DocumentFolderRow } from '../types';

import { Checkbox } from '@/Components/ui/checkbox';
import { DocumentStatus } from '@/Types/enums';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const file = (overrides: Partial<DocumentFolderRow> = {}): DocumentFolderRow => ({
  id: 1,
  title: 'Nuostatai 2024',
  name: 'Nuostatai 2024.pdf',
  status: DocumentStatus.Published,
  content_type: 'Nuostatai',
  language: 'Lietuvių',
  document_date: '2024-05-17',
  institution: { id: 'i1', name: 'VU SA MIF', tenant_shortname: 'VU SA MIF' },
  sharepoint_institution_label: 'VU SA MIF',
  sharepoint_path: 'Dokumentų sistema/MIF',
  sharepoint_web_url: 'https://vusa.sharepoint.com/x.pdf',
  sharepoint_modified_at: null,
  removed_from_sharepoint_at: null,
  problems: [],
  sync_status: 'success',
  sync_error_message: null,
  public_url: 'https://vusa.lt/d/abc',
  can: { update: true },
  ...overrides,
});

const listing = (overrides: Partial<DocumentFolderListing> = {}): DocumentFolderListing => ({
  path: 'Dokumentų sistema/MIF',
  breadcrumbs: [{ name: 'Dokumentų sistema', path: 'Dokumentų sistema' }, { name: 'MIF', path: 'Dokumentų sistema/MIF' }],
  folders: [{ name: 'Protokolai', path: 'Dokumentų sistema/MIF/Protokolai', counts: { published: 12, pending: 2, hidden: 0 } }],
  files: [file(), file({ id: 2, name: 'Planas.pdf', title: 'Planas', status: DocumentStatus.Pending, public_url: null })],
  next_offset: null,
  content_types: ['Nuostatai', 'Protokolai'],
  ...overrides,
});

let responses: DocumentFolderListing[] = [];

beforeEach(() => {
  localStorage.clear();
  // The browser mirrors its filters into the URL; start every test from a clean one.
  window.history.replaceState(null, '', '/mano/documents');
  vi.stubGlobal('route', (name: string) => `http://localhost/mocked/${name}`);
  responses = [listing()];
  vi.stubGlobal('fetch', vi.fn().mockImplementation(async (url: string | URL, init?: RequestInit) => {
    const request = url.toString().includes('api.v1.admin.documents.status')
      ? JSON.parse(String(init?.body)) as { document_ids: number[]; status: DocumentStatus }
      : null;
    const body = request
      ? { success: true, data: request.document_ids.map(id => file({ id, status: request.status })) }
      : { success: true, data: responses.shift() ?? listing() };
    const text = JSON.stringify(body);

    return new Response(text, { status: 200, headers: { 'Content-Type': 'application/json' } });
  }));
  vi.mocked(router.post).mockClear();
  vi.mocked(router.reload).mockClear();
});

afterEach(() => {
  vi.unstubAllGlobals();
});

async function mountBrowser(props: Record<string, unknown> = {}) {
  const wrapper = mount(DocumentFolderBrowser, {
    props: { eyebrow: 'Svetainė · Dokumentai', title: 'Dokumentai', pendingCount: 2, removedCount: 0, ...props },
    global: { stubs: {
      ...commonStubs,
      Head: true,
      CollectionTitleBand: true,
      SpotlightPopover: { template: '<div><slot /></div>' },
      // A reka dropdown; its own tests cover it. Here only what it is given and what it emits matter.
      CollectionStatusMenu: { name: 'CollectionStatusMenu', props: ['status', 'modelValue', 'options', 'editable', 'pending'], emits: ['update:modelValue'], template: '<span />' },
    } },
  });
  await flushPromises();

  return wrapper;
}

const folderCalls = () => vi.mocked(fetch).mock.calls.filter(([url]) => url.toString().includes('api.v1.admin.documents.folder'));
const lastFolderParams = () => new URL(folderCalls().at(-1)![0].toString()).searchParams;
const statusCalls = () => vi.mocked(fetch).mock.calls.filter(([url]) => url.toString().includes('api.v1.admin.documents.status'));
const setStatus = (wrapper: ReturnType<typeof mount>, index: number, value: DocumentStatus) =>
  wrapper.findAll('[data-slot="document-file"]')[index].findComponent({ name: 'CollectionStatusMenu' }).vm.$emit('update:modelValue', value);
const segment = (wrapper: ReturnType<typeof mount>, group: string, label: string) =>
  wrapper.get(`[data-slot="${group}"]`).findAll('button').find(button => button.text().startsWith(label))!;

describe('DocumentFolderBrowser', () => {
  it('opens as one list of every file below the folder the server picks', async () => {
    await mountBrowser();

    expect(lastFolderParams().get('path')).toBeNull();
    expect(lastFolderParams().get('flat')).toBe('1');
  });

  it('switches to SharePoint folders and remembers it', async () => {
    const wrapper = await mountBrowser();

    await segment(wrapper, 'document-layout-switch', 'Aplankai').trigger('click');
    await flushPromises();

    expect(lastFolderParams().get('flat')).toBeNull();
    expect(localStorage.getItem('documents-layout')).toBe('folders');
    expect(wrapper.get('[data-slot="document-folder"]').text()).toContain('Protokolai');
  });

  it('opens a subfolder', async () => {
    localStorage.setItem('documents-layout', 'folders');
    const wrapper = await mountBrowser();

    await wrapper.get('[data-slot="document-folder"]').trigger('click');
    await flushPromises();

    expect(lastFolderParams().get('path')).toBe('Dokumentų sistema/MIF/Protokolai');
  });

  it('filters by status, and lists removed files only as a list', async () => {
    localStorage.setItem('documents-layout', 'folders');
    const wrapper = await mountBrowser({ removedCount: 1 });

    await segment(wrapper, 'document-status-filter', 'Paslėpti').trigger('click');
    await flushPromises();
    expect(lastFolderParams().get('show')).toBe('hidden');

    await segment(wrapper, 'document-status-filter', 'Pašalinti iš SharePoint').trigger('click');
    await flushPromises();
    expect(lastFolderParams().get('show')).toBe('removed');
    expect(lastFolderParams().get('flat')).toBe('1');
  });

  it('offers the removed filter only while there is something removed', async () => {
    const wrapper = await mountBrowser();

    expect(wrapper.get('[data-slot="document-status-filter"]').text()).not.toContain('Pašalinti iš SharePoint');
  });

  it('says when a published file has no public link yet', async () => {
    responses = [listing({ files: [file({ public_url: null, sync_status: 'pending' })] })];
    const wrapper = await mountBrowser();

    expect(wrapper.find('[data-slot="document-link-pending"]').exists()).toBe(true);
    expect(wrapper.text()).not.toContain('Bandyti dar kartą');
  });

  it('offers a retry for a published file whose sync finished without a link', async () => {
    responses = [listing({ files: [file({ public_url: null, sync_status: 'success' })] })];
    const wrapper = await mountBrowser();

    expect(wrapper.find('[data-slot="document-link-failed"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('Bandyti dar kartą');
  });

  it('shows a read-only status on a file the user may not change', async () => {
    responses = [listing({ files: [file({ can: { update: false } })] })];
    const wrapper = await mountBrowser();

    expect(wrapper.findComponent({ name: 'CollectionStatusMenu' }).props('editable')).toBe(false);
  });

  it('publishes from the status menu with one request, not a page visit', async () => {
    const wrapper = await mountBrowser();

    setStatus(wrapper, 1, DocumentStatus.Published);
    await flushPromises();

    expect(router.post).not.toHaveBeenCalled();
    expect(JSON.parse(String((statusCalls()[0][1] as RequestInit).body))).toEqual({ document_ids: [2], status: 'published' });
    expect(router.reload).toHaveBeenCalledWith({ only: ['discovery'] });
  });

  it('asks first when metadata is missing', async () => {
    responses = [listing({ files: [file({ id: 3, status: DocumentStatus.Hidden, problems: ['document_date'], public_url: null })] })];
    const wrapper = await mountBrowser();

    setStatus(wrapper, 0, DocumentStatus.Published);
    await flushPromises();

    expect(statusCalls()).toHaveLength(0);
    expect(wrapper.findAllComponents({ name: 'ConfirmDialog' })[0].props('open')).toBe(true);
  });

  it('changes two files one after the other, so neither request cancels the other', async () => {
    let inFlight = 0;
    let mostAtOnce = 0;
    const fetchListing = vi.mocked(fetch).getMockImplementation()!;
    vi.mocked(fetch).mockImplementation(async (url, init) => {
      if (!url.toString().includes('api.v1.admin.documents.status')) return fetchListing(url, init);

      mostAtOnce = Math.max(mostAtOnce, ++inFlight);
      await new Promise(resolve => setTimeout(resolve, 5));
      inFlight--;

      return fetchListing(url, init);
    });
    const wrapper = await mountBrowser();

    setStatus(wrapper, 0, DocumentStatus.Hidden);
    setStatus(wrapper, 1, DocumentStatus.Published);
    await vi.waitFor(() => expect(statusCalls()).toHaveLength(2));
    await flushPromises();

    expect(mostAtOnce).toBe(1);
  });

  it('shows files a SharePoint check found, and drops ones it removed, keeping the folder', async () => {
    const wrapper = await mountBrowser();
    responses = [listing({ files: [file({ id: 7, title: 'Naujas protokolas' })] })];

    await wrapper.setProps({ refreshKey: '2026-10-07T12:00:00Z' });
    await flushPromises();

    expect(lastFolderParams().get('limit')).toBe('100');
    expect(wrapper.findAll('[data-slot="document-file"]')).toHaveLength(1);
    expect(wrapper.text()).toContain('Naujas protokolas');
  });

  it('keeps checking a newly published file until its link exists, then stops', async () => {
    vi.useFakeTimers();
    responses = [listing({ files: [file({ id: 2, status: DocumentStatus.Pending, public_url: null })] })];
    vi.mocked(fetch).mockImplementation(async (url: string | URL) => {
      const body = url.toString().includes('api.v1.admin.documents.status')
        ? { success: true, data: [file({ id: 2, public_url: null, sync_status: 'pending' })] }
        : { success: true, data: responses.shift() ?? listing({ files: [file({ id: 2, public_url: null, sync_status: 'pending' })] }) };

      return new Response(JSON.stringify(body), { status: 200, headers: { 'Content-Type': 'application/json' } });
    });
    const wrapper = await mountBrowser();

    setStatus(wrapper, 0, DocumentStatus.Published);
    await flushPromises();
    const afterPublish = folderCalls().length;

    responses = [listing({ files: [file({ id: 2, public_url: 'https://vusa.lt/d/x', sync_status: 'success' })] })];
    await vi.advanceTimersByTimeAsync(3000);
    await flushPromises();

    expect(folderCalls().length).toBe(afterPublish + 1);
    expect(wrapper.find('[data-slot="document-link-pending"]').exists()).toBe(false);

    await vi.advanceTimersByTimeAsync(9000);
    expect(folderCalls().length).toBe(afterPublish + 1);

    vi.useRealTimers();
  });

  it('adds the next files on "Rodyti daugiau"', async () => {
    responses = [listing({ next_offset: 100 }), listing({ folders: [], files: [file({ id: 9, title: 'Kitas' })], next_offset: null })];
    const wrapper = await mountBrowser();

    await wrapper.get('[data-slot="document-folder-more"]').trigger('click');
    await flushPromises();

    expect(lastFolderParams().get('offset')).toBe('100');
    expect(wrapper.findAll('[data-slot="document-file"]')).toHaveLength(3);
    expect(wrapper.find('[data-slot="document-folder-more"]').exists()).toBe(false);
  });

  it('publishes the selected files together', async () => {
    const wrapper = await mountBrowser();

    for (const row of wrapper.findAll('[data-slot="document-file"]')) {
      row.findComponent(Checkbox).vm.$emit('update:modelValue', true);
    }
    await flushPromises();
    // One is published already, so only publishing and hiding both make sense.
    await wrapper.get('[data-slot="document-bulk-publish"]').trigger('click');
    await flushPromises();

    expect(JSON.parse(String((statusCalls()[0][1] as RequestInit).body))).toEqual({ document_ids: [1, 2], status: 'published' });
  });

  it('offers hiding waiting files as well as publishing them', async () => {
    responses = [listing({ files: [file({ id: 2, status: DocumentStatus.Pending, public_url: null })] })];
    const wrapper = await mountBrowser();

    wrapper.findComponent(Checkbox).vm.$emit('update:modelValue', true);
    await flushPromises();

    expect(wrapper.find('[data-slot="document-bulk-publish"]').exists()).toBe(true);
    expect(wrapper.find('[data-slot="document-bulk-hide"]').exists()).toBe(true);
  });

  it('names a file\'s folder relative to the open one, shortening deep paths', async () => {
    responses = [listing({ files: [
      file({ id: 1, sharepoint_path: 'Dokumentų sistema/MIF' }),
      file({ id: 2, sharepoint_path: 'Dokumentų sistema/MIF/Protokolai' }),
      file({ id: 3, sharepoint_path: 'Dokumentų sistema/MIF/Ataskaitos/2025/Metinės' }),
    ] })];
    const wrapper = await mountBrowser();
    const rows = wrapper.findAll('[data-slot="document-file"]');

    expect(rows[0].text()).not.toContain('Dokumentų sistema');
    expect(rows[1].text()).toContain('Protokolai');
    expect(rows[2].text()).toContain('Ataskaitos / … / Metinės');
    expect(rows[2].find('[title="Dokumentų sistema/MIF/Ataskaitos/2025/Metinės"]').exists()).toBe(true);
  });

  it('deletes a removed file after asking', async () => {
    responses = [listing({ files: [file({ id: 4, removed_from_sharepoint_at: '2026-10-01T00:00:00Z' })] })];
    const wrapper = await mountBrowser({ removedCount: 1 });

    wrapper.findComponent({ name: 'CollectionRowActions' }).vm.$emit('select', 'delete');
    await flushPromises();
    const dialog = wrapper.findAllComponents({ name: 'ConfirmDialog' })[1];
    expect(dialog.props('open')).toBe(true);

    dialog.vm.$emit('confirm');
    await flushPromises();

    const call = vi.mocked(fetch).mock.calls.find(([url]) => url.toString().includes('api.v1.admin.documents.destroyRemoved'));
    expect((call![1] as RequestInit).method).toBe('DELETE');
    expect(JSON.parse(String((call![1] as RequestInit).body))).toEqual({ document_ids: [4] });
  });
});
