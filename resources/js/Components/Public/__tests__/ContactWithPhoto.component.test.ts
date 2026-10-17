import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import ContactCard from '../ContactWithPhoto.vue';

import { commonStubs } from '@/tests/stubs';

// InfoPopover is stubbed to a marker so presence/absence reflects the component's
// own v-if decision (the gating logic is what we want to assert, not the popover internals).
const stubs = {
  ...commonStubs,
  InfoPopover: { template: '<span class="info-popover" />' },
  IFluentMail20Regular: { template: '<span class="icon-mail" />' },
  IFluentPhone20Regular: { template: '<span class="icon-phone" />' },
  ISimpleIconsFacebook: { template: '<span class="icon-facebook" />' },
  // Render the trigger inline but keep the content hidden — mirrors real popover
  // behaviour where additional emails are not in the DOM until opened.
  Popover: { template: '<div class="popover"><slot /></div>' },
  PopoverTrigger: { template: '<div class="popover-trigger"><slot /></div>' },
  PopoverContent: { template: '<div class="popover-content" />' },
};

const makeContact = (overrides: Record<string, unknown> = {}) => ({
  id: 'u1',
  name: 'Jonas Jonaitis',
  email: 'jonas@vusa.lt',
  phone: null,
  facebook_url: null,
  show_pronouns: false,
  pronouns: null,
  duties: [],
  ...overrides,
}) as any;

const makeDuty = (overrides: Record<string, unknown> = {}) => ({
  id: 'd1',
  name: 'Pirmininkas',
  description: null,
  email: null,
  pivot: { additional_email: null, description: null },
  ...overrides,
}) as any;

const mountCard = (contact: any, duties: any[], options: Record<string, unknown> = {}) =>
  mount(ContactCard, {
    props: { contact, duties, ...options },
    global: { stubs },
  });

describe('ContactWithPhoto', () => {
  let wrapper: ReturnType<typeof mount>;

  it('uses the holder’s pronouns for duty and email labels without animation', () => {
    wrapper = mount(ContactCard, {
      props: {
        contact: makeContact({ name: 'Jonas Jonaitis', pronouns: 'ji / jos' }),
        duties: [
          makeDuty({ id: 'd1', email: 'pirm@vusa.lt' }),
          makeDuty({ id: 'd2', name: 'Koordinatorius', email: 'antr@vusa.lt', pivot: { use_original_duty_name: true } }),
        ],
      },
      global: { stubs: { ...stubs, PopoverContent: { template: '<div><slot /></div>' } } },
    });

    expect(wrapper.text().match(/Pirmininkė/g)).toHaveLength(2);
    expect(wrapper.text().match(/Koordinatorius/g)).toHaveLength(2);
    expect(wrapper.find('[data-testid="duty-ending-trigger"]').exists()).toBe(false);
    wrapper.unmount();
  });

  it('names the represented tenant next to the inflected duty', () => {
    wrapper = mountCard(makeContact({ pronouns: 'ji / jos' }), [
      makeDuty({ name: 'Parlamento narys', contact_tenant: 'VU SA MIF' }),
    ]);

    expect(wrapper.find('[data-testid="contact-duty-tenant"]').text()).toBe('· VU SA MIF');
    expect(wrapper.text()).toContain('Parlamento narė');
    wrapper.unmount();
  });

  it('falls back to the other locale’s pronouns before guessing from the name', () => {
    wrapper = mountCard(makeContact({
      name: 'Jonas Jonaitis', pronouns: '', duty_pronouns: { lt: '', en: 'she / her' },
    }), [makeDuty()]);

    expect(wrapper.text()).toContain('Pirmininkė');
    expect(wrapper.find('[data-testid="duty-ending-trigger"]').exists()).toBe(false);
    wrapper.unmount();
  });

  describe('duty info popover', () => {
    it('hides the popover when the pivot description is an empty paragraph', () => {
      // Regression: duty.description is non-empty, so the old v-if showed the button,
      // but the rendered (pivot) description was "<p></p>" — an empty popover.
      wrapper = mountCard(makeContact(), [
        makeDuty({ description: '<p>Real duty description</p>', pivot: { description: '<p></p>' } }),
      ]);

      expect(wrapper.find('.info-popover').exists()).toBe(false);
    });

    it('shows the popover when only the pivot carries a description', () => {
      // Inverse of the bug: duty.description is null, but pivot has real content.
      wrapper = mountCard(makeContact(), [
        makeDuty({ description: null, pivot: { description: '<p>Pivot-only info</p>' } }),
      ]);

      expect(wrapper.find('.info-popover').exists()).toBe(true);
    });

    it('hides the popover when both descriptions are empty or null', () => {
      wrapper = mountCard(makeContact(), [
        makeDuty({ description: '<p></p>', pivot: { description: '<p><br></p>' } }),
      ]);

      expect(wrapper.find('.info-popover').exists()).toBe(false);
    });
  });

  describe('email display', () => {
    it('shows the primary email as a visible mailto link', () => {
      wrapper = mountCard(makeContact({ email: 'jonas@vusa.lt' }), [
        makeDuty({ email: 'pirmininkas@vusa.lt' }),
      ]);

      const mailto = wrapper.find('a[href^="mailto:"]');
      expect(mailto.exists()).toBe(true);
      // Precedence: pivot.additional_email ?? duty.email ?? contact.email
      expect(mailto.attributes('href')).toBe('mailto:pirmininkas@vusa.lt');
      expect(mailto.text()).toContain('pirmininkas@vusa.lt');
      // Single email → no "+N" affordance.
      expect(wrapper.findAll('button').some(b => b.text().includes('+'))).toBe(false);
    });

    it('offers a +N control for additional emails while keeping the primary visible', () => {
      wrapper = mountCard(makeContact(), [
        makeDuty({ id: 'd1', email: 'pirm@vusa.lt' }),
        makeDuty({ id: 'd2', email: 'antr@vusa.lt' }),
      ]);

      expect(wrapper.find('a[href^="mailto:"]').attributes('href')).toBe('mailto:pirm@vusa.lt');
      expect(wrapper.findAll('button').some(b => b.text().includes('+1'))).toBe(true);
    });
  });

  describe('action links', () => {
    it('renders phone and Facebook as plain links, not buttons nested in links', () => {
      wrapper = mountCard(makeContact({ phone: '+37060000000', facebook_url: 'https://facebook.com/vusa' }), []);

      const phone = wrapper.find('a[href="tel:+37060000000"]');
      const facebook = wrapper.find('a[href="https://facebook.com/vusa"]');
      expect(phone.exists()).toBe(true);
      expect(facebook.exists()).toBe(true);
      expect(phone.find('button').exists()).toBe(false);
      expect(facebook.find('button').exists()).toBe(false);
    });
  });

  describe('empty state', () => {
    it('renders neither email nor action row when there are no duties or contact links', () => {
      wrapper = mountCard(makeContact({ phone: null, facebook_url: null }), []);

      expect(wrapper.find('a[href^="mailto:"]').exists()).toBe(false);
      expect(wrapper.find('.info-popover').exists()).toBe(false);
      // Action row is conditional on phone / facebook.
      expect(wrapper.find('a[href^="tel:"]').exists()).toBe(false);
    });
  });

  describe('photo', () => {
    const profileMedia = { id: 1, url: '/uploads/media/1/profile.webp', thumb: '/t.webp', srcset: '/uploads/media/1/profile.webp 1200w',
      width: 1200, height: 900, focal_point: null, alt: null, author: null };

    it('uses assignment media even when its cached URL is missing or stale', () => {
      const assignmentMedia = { ...profileMedia, id: 2, url: '/assignment.webp', srcset: '/assignment.webp 1200w', focal_point: '15% 25%' };
      for (const cached of [null, '/stale.jpg']) {
        const contact = makeContact({
          profile_photo_path: profileMedia.url,
          profile_photo_focal_point: '80% 90%',
          duties: { duty: makeDuty({ pivot: { additional_photo: cached, additional_photo_focal_point: '50% 50%', photo_media: assignmentMedia } }) },
        });
        const card = mountCard(contact, []);
        const img = card.get('img');
        expect(img.attributes('src')).toBe(assignmentMedia.url);
        expect(img.attributes('srcset')).toBe(assignmentMedia.srcset);
        expect(img.attributes('style')).toContain('15% 25%');
        card.unmount();
      }
    });

    it('shows profile media without a cached profile URL and keeps its focal point', () => {
      const card = mountCard(makeContact({ profile_photo_media: { ...profileMedia, focal_point: '20% 40%' } }), []);
      expect(card.get('img').attributes('src')).toBe(profileMedia.url);
      expect(card.get('img').attributes('style')).toContain('20% 40%');
      card.unmount();
    });

    it('does not borrow the profile focal point for a legacy assignment photo', () => {
      const card = mountCard(makeContact({ profile_photo_focal_point: '90% 90%', pivot: { additional_photo: '/legacy.jpg' } }), []);
      expect(card.get('img').attributes('style')).toContain('50% 30%');
      card.unmount();
    });

    it('shows an assignment photo instead of the profile photo, even before it has media', () => {
      const contact = makeContact({
        profile_photo_path: '/uploads/media/1/profile.webp',
        profile_photo_media: profileMedia,
        duties: [makeDuty({ pivot: { additional_photo: '/uploads/files/assignment.jpg', additional_photo_focal_point: '10% 20%' } })],
      });
      const img = mountCard(contact, []).get('img');

      expect(img.attributes('src')).toBe('/uploads/files/assignment.jpg');
      expect(img.attributes('srcset')).toBeUndefined();
      expect(img.attributes('style')).toContain('object-position: 10% 20%');
    });

    it('uses the profile photo with its srcset when the assignment has none', () => {
      const contact = makeContact({ profile_photo_path: '/uploads/media/1/profile.webp', profile_photo_media: profileMedia, duties: [makeDuty()] });
      const img = mountCard(contact, []).get('img');

      expect(img.attributes('src')).toBe(profileMedia.url);
      expect(img.attributes('srcset')).toBe(profileMedia.srcset);
    });
  });
});
