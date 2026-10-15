import { describe, expect, it } from 'vitest';

import { publishChoices } from '../useCollectionPublishing';

interface Row {
  published: boolean;
}

const isPublished = (row: Row) => row.published;

describe('publishChoices', () => {
  it('offers only "to drafts" when every selected row is published', () => {
    expect(publishChoices<Row>([{ published: true }, { published: true }], isPublished)).toEqual({ publish: false, draft: true });
  });

  it('offers only "publish" when every selected row is a draft', () => {
    expect(publishChoices<Row>([{ published: false }], isPublished)).toEqual({ publish: true, draft: false });
  });

  it('offers both for a mixed selection', () => {
    expect(publishChoices<Row>([{ published: true }, { published: false }], isPublished)).toEqual({ publish: true, draft: true });
  });
});
