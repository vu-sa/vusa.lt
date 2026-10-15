import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import CollectionSkeleton from '../Skeletons/CollectionSkeleton.vue';
import RecordSkeleton from '../Skeletons/RecordSkeleton.vue';
import FormSkeleton from '../Skeletons/FormSkeleton.vue';
import SectionCardSkeleton from '../Skeletons/SectionCardSkeleton.vue';

describe('Skeletons (Patterns)', () => {
  describe('CollectionSkeleton', () => {
    it('renders rows view by default with specified row count', () => {
      const wrapper = mount(CollectionSkeleton, {
        props: { rows: 4 },
      });

      expect(wrapper.find('[data-slot="collection-skeleton"]').exists()).toBe(true);
      // In rows view, find row items
      const rows = wrapper.findAll('.flex.items-center.gap-4');
      expect(rows).toHaveLength(4);
    });

    it('renders table view when viewMode is table', () => {
      const wrapper = mount(CollectionSkeleton, {
        props: { viewMode: 'table', rows: 3 },
      });

      expect(wrapper.find('table').exists()).toBe(true);
      expect(wrapper.findAll('tbody tr')).toHaveLength(3);
    });
  });

  describe('RecordSkeleton', () => {
    it('renders title band, key facts strip, and content section', () => {
      const wrapper = mount(RecordSkeleton);

      expect(wrapper.find('[data-slot="record-skeleton"]').exists()).toBe(true);
      expect(wrapper.findAll('.grid > div')).toHaveLength(4);
    });
  });

  describe('FormSkeleton', () => {
    it('renders form fields and sticky save bar placeholder', () => {
      const wrapper = mount(FormSkeleton, {
        props: { fields: 3 },
      });

      expect(wrapper.find('[data-slot="form-skeleton"]').exists()).toBe(true);
      expect(wrapper.findAll('.space-y-5 > div')).toHaveLength(3);
    });
  });

  describe('SectionCardSkeleton', () => {
    it('renders card header and item rows', () => {
      const wrapper = mount(SectionCardSkeleton, {
        props: { items: 4 },
      });

      expect(wrapper.find('[data-slot="section-card-skeleton"]').exists()).toBe(true);
      expect(wrapper.findAll('.divide-y > div')).toHaveLength(4);
    });
  });
});
