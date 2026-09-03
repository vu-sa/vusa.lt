import { describe, expect, it } from 'vitest';

import { contentTypeRegistry, getAllContentTypes, getContentType, getSkeletonForType, createContentItem } from '../index';

/**
 * Registry consistency guard. The rich-content system used to spread each type's
 * metadata across six places (this registry, two switch statements in
 * ContentEditorFactory, and three lookup tables in RichContentParser); drift between
 * them is what shipped the broken card-stack/carousel/photo-gallery types in the first
 * place. Now everything lives here — this test is the thing that would have caught it.
 *
 * Keep in sync with `App\Enums\ContentPartEnum` (app/Enums/ContentPartEnum.php).
 */
const EXPECTED_TYPES = [
  'tiptap', 'shadcn-accordion', 'shadcn-card', 'image-grid', 'hero', 'news', 'calendar',
  'spotify-embed', 'social-embed', 'flow-graph', 'number-stat-section', 'text-box',
  'content-grid', 'carousel-slide-deck', 'hero-carousel', 'card-stack', 'photo-gallery',
  'link-list', 'event-list', 'person-quote', 'section', 'process-steps', 'cta-band',
  'spacer', 'timetable',
];

describe('contentTypeRegistry', () => {
  it('registers exactly the types the backend enum expects', () => {
    expect(Object.keys(contentTypeRegistry).sort()).toEqual([...EXPECTED_TYPES].sort());
  });

  it.each(EXPECTED_TYPES)('%s has a complete, self-consistent registry entry', (type) => {
    const entry = getContentType(type);

    expect(entry.value).toBe(type);
    expect(entry.label).toBeTruthy();
    expect(entry.icon).toBeTruthy();
    expect(['text', 'media', 'section', 'embed', 'special']).toContain(entry.category);
    expect(['prose', 'content', 'wide', 'full']).toContain(entry.defaultWidth);
    expect(entry.editor).toBeTruthy();
    expect(entry.display).toBeTruthy();

    if (entry.allowedWidths) {
      expect(entry.allowedWidths).toContain(entry.defaultWidth);
    }

    // defaultContent() must not throw and must produce the shape createContentItem relies on.
    expect(() => entry.defaultContent()).not.toThrow();
    if (entry.defaultOptions) {
      expect(() => entry.defaultOptions!()).not.toThrow();
    }
  });

  it('falls back to tiptap for an unknown type instead of throwing', () => {
    expect(getContentType('does-not-exist')).toBe(contentTypeRegistry['tiptap']);
  });

  it('createContentItem seeds json_content/options from the registry defaults', () => {
    const item = createContentItem('shadcn-card');
    expect(item.type).toBe('shadcn-card');
    expect(item.json_content).toEqual({});
    expect(item.options).toMatchObject({ variant: 'outline', color: 'zinc' });
  });

  it('getSkeletonForType falls back to a generic skeleton for types without one', () => {
    // spotify-embed has no bespoke skeleton registered.
    const skeleton = getSkeletonForType('spotify-embed');
    expect(skeleton.height).toBeTruthy();
    expect(skeleton.template).toBeTruthy();
  });

  it('getAllContentTypes returns one entry per registered type', () => {
    expect(getAllContentTypes()).toHaveLength(EXPECTED_TYPES.length);
  });
});
