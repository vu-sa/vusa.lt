import { describe, it, expect } from 'vitest';

import { newsTemplate } from '../formTemplates';

describe('newsTemplate', () => {
  it('defaults publish_time to an ISO instant round-tripping to "now"', () => {
    // A naive "YYYY-MM-DD HH:mm:ss" string here used to be read back as UTC by
    // `new Date(...)` (NewsForm.vue) while the backend interpreted it in
    // Europe/Vilnius (StoreNewsRequest::prepareForValidation), backdating every
    // untouched publish_time by the timezone offset.
    const parsed = new Date(newsTemplate.publish_time);

    expect(Number.isNaN(parsed.getTime())).toBe(false);
    expect(Math.abs(parsed.getTime() - Date.now())).toBeLessThan(1000);
  });
});
