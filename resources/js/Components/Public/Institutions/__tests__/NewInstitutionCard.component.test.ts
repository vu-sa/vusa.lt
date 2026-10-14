import { describe, expect, it, vi } from 'vitest';

import NewInstitutionCard from '../NewInstitutionCard.vue';

import { ssrRoundTrip } from '@/tests/helpers/ssrRoundTrip';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

describe('NewInstitutionCard', () => {
  it('keeps social links and a linked description intact through server render and hydration', async () => {
    const { container, hydrationWarnings, unmount } = await ssrRoundTrip(NewInstitutionCard, {
      href: 'https://www.facebook.com/external-contacts',
      institution: {
        id: 1,
        name: 'Studentų atstovybė',
        description: '<p>Rašyk <a href="https://vusa.lt">mums</a></p>',
        facebook_url: 'https://www.facebook.com/vusa',
        instagram_url: 'https://www.instagram.com/vusa',
      },
    });

    expect(hydrationWarnings).toEqual([]);
    expect(container.querySelector('a a')).toBeNull();
    expect(container.querySelector('a[href="https://www.facebook.com/vusa"]')).not.toBeNull();
    expect(container.querySelector('a[href="https://www.instagram.com/vusa"]')).not.toBeNull();
    expect(container.textContent).toContain('Rašyk mums');
    unmount();
  });

  it('renders hero image without grayscale, direct email link, and the type eyebrow in the body', async () => {
    const { container, hydrationWarnings, unmount } = await ssrRoundTrip(NewInstitutionCard, {
      institution: {
        id: 2,
        name: 'START FM',
        image_url: '/images/start-fm.jpg',
        email: 'info@startfm.lt',
        types: [{ id: 1, slug: 'pkp', title: 'Studentų radijas' }],
      },
    });

    expect(hydrationWarnings).toEqual([]);
    const img = container.querySelector('img');
    expect(img).not.toBeNull();
    expect(img?.getAttribute('src')).toBe('/images/start-fm.jpg');
    expect(img?.classList.contains('grayscale')).toBe(false);
    expect(container.querySelector('a[href="mailto:info@startfm.lt"]')).not.toBeNull();
    expect(container.querySelector('[data-slot="institution-card-eyebrow"]')?.textContent).toContain('Studentų radijas');
    expect(container.querySelector('[data-slot="media-frame"]')?.textContent).not.toContain('Studentų radijas');
    unmount();
  });
});
