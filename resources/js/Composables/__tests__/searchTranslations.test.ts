import { describe, expect, it } from 'vitest';

import { getSearchTranslation, getSearchTranslations } from '../useTranslatedTitle';

import { getCollectionSortOptions, resolveSortValue } from '@/Features/Admin/AdminSearch/Config/collectionFacetConfig';

describe('search display translations', () => {
  it('selects the requested language without changing indexed fields', () => {
    const document = { name_lt: 'Taryba', name_en: 'Council', tenant_shortname: 'VU SA MIF' };

    expect(getSearchTranslation(document, 'name', 'en')).toBe('Council');
    expect(getSearchTranslation(document, 'name', 'lt')).toBe('Taryba');
    expect(document).toEqual({ name_lt: 'Taryba', name_en: 'Council', tenant_shortname: 'VU SA MIF' });
  });

  it('falls back across missing, empty and whitespace-only translations and legacy documents', () => {
    expect(getSearchTranslation({ name_lt: 'Taryba', name_en: '  ' }, 'name', 'en')).toBe('Taryba');
    expect(getSearchTranslation({ name_en: 'Council' }, 'name', 'lt')).toBe('Council');
    expect(getSearchTranslation({ category_name: 'Technika' }, 'category_name', 'en')).toBe('Technika');
    expect(getSearchTranslation({}, 'name', 'en', 'Untitled')).toBe('Untitled');
  });

  it('resolves each label independently when some related types lack a translation', () => {
    const document = { type_titles: ['Komitetas', 'Taryba'], type_titles_lt: ['Komitetas', 'Taryba'], type_titles_en: ['Committee', ''] };

    expect(getSearchTranslations(document, 'type_titles', 'en')).toEqual(['Committee', 'Taryba']);
    expect(getSearchTranslations(document, 'type_titles', 'lt')).toEqual(['Komitetas', 'Taryba']);
    expect(getSearchTranslations({ type_titles: ['Taryba'] }, 'type_titles', 'en')).toEqual(['Taryba']);
    expect(getSearchTranslations({}, 'type_titles', 'en')).toEqual([]);
  });
});

describe('search name sorting', () => {
  it('sorts duties by the displayed language in both directions', () => {
    const collection = 'duties';
    expect(getCollectionSortOptions(collection).map(option => option.value)).toContain('name_lt:asc');
    expect(getCollectionSortOptions(collection).map(option => option.value)).toContain('name_lt:desc');
    expect(resolveSortValue(collection, 'name_lt:asc', 'en')).toBe('name_en:asc');
    expect(resolveSortValue(collection, 'name_lt:desc', 'en')).toBe('name_en:desc');
    expect(resolveSortValue(collection, 'name_lt:asc', 'lt')).toBe('name_lt:asc');
    expect(resolveSortValue(collection, 'created_at:desc', 'en')).toBe('created_at:desc');
  });
});
