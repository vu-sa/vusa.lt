import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import { toPlainText } from '../useInstitutionCard';

describe('toPlainText', () => {
  it('keeps paragraphs apart and decodes common entities', () => {
    expect(toPlainText('<p>Rašyk&nbsp;<strong>mums</strong></p><p>Q&amp;A</p>')).toBe('Rašyk mums Q&A');
  });

  it('returns null when nothing visible is left', () => {
    expect(toPlainText('<p> </p>')).toBeNull();
    expect(toPlainText(null)).toBeNull();
  });
});
