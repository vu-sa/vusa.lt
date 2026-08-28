import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import FolderStrip from '../Components/FolderStrip.vue';
import { commonStubs } from '@/tests/stubs';

const wrappers: ReturnType<typeof mount>[] = [];

beforeEach(() => {
  localStorage.clear();
});

afterEach(() => {
  wrappers.splice(0).forEach(wrapper => wrapper.unmount());
});

function makeDirectories(count: number) {
  return Array.from({ length: count }, (_, i) => ({
    path: `public/files/folder-${i}`,
    name: `folder-${i}`,
  }));
}

function mountStrip(count: number) {
  const wrapper = mount(FolderStrip, {
    props: { directories: makeDirectories(count) },
    attachTo: document.body,
    global: { stubs: { ...commonStubs } },
  });
  wrappers.push(wrapper);
  return wrapper;
}

describe('FolderStrip', () => {
  it('collapses a long folder list so it cannot bury the files below it', () => {
    const wrapper = mountStrip(49);

    expect(wrapper.text()).toContain('49');
    // The names are what would push the file grid off screen; the count stays visible.
    expect(wrapper.findAll('button[type="button"]').some(b => b.text().includes('folder-0'))).toBe(false);
  });

  it('stays open when there are few enough folders to be worth showing', () => {
    const wrapper = mountStrip(5);

    expect(wrapper.findAll('button[type="button"]').some(b => b.text().includes('folder-0'))).toBe(true);
  });

  it('narrows the list as you filter', async () => {
    const wrapper = mountStrip(5);

    await wrapper.find('input').setValue('folder-3');

    const names = wrapper.findAll('button[type="button"]').map(b => b.text()).filter(t => t.includes('folder-'));
    expect(names).toHaveLength(1);
    expect(names[0]).toContain('folder-3');
  });

  it('reports when nothing matches the filter', async () => {
    const wrapper = mountStrip(5);

    await wrapper.find('input').setValue('nothing-like-this');

    expect(wrapper.text()).toContain('files.ui.no_folders_match');
  });

  it('opens a folder on a single click', async () => {
    const wrapper = mountStrip(5);

    const folderButton = wrapper.findAll('button[type="button"]').find(b => b.text().includes('folder-2'));
    await folderButton!.trigger('click');

    expect(wrapper.emitted('open')?.[0]?.[0]).toMatchObject({ name: 'folder-2' });
  });

  it('renders nothing at all when the directory holds no folders', () => {
    const wrapper = mountStrip(0);

    expect(wrapper.find('[data-slot="folder-strip"]').exists()).toBe(false);
  });
});
