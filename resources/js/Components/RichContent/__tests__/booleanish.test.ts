import { describe, expect, it } from 'vitest';

import { asBoolean } from '../booleanish';

describe('asBoolean', () => {
  it('reads real booleans', () => {
    expect(asBoolean(true)).toBe(true);
    expect(asBoolean(false)).toBe(false);
  });

  it('reads FormData-mangled strings ("1"/"0") the way they were authored', () => {
    expect(asBoolean('1')).toBe(true);
    expect(asBoolean('0')).toBe(false);
    expect(asBoolean('true')).toBe(true);
    expect(asBoolean('false')).toBe(false);
  });

  it('reads numeric 1 as on', () => {
    expect(asBoolean(1)).toBe(true);
    expect(asBoolean(0)).toBe(false);
  });

  it('treats absent and unrelated values as off', () => {
    expect(asBoolean(undefined)).toBe(false);
    expect(asBoolean(null)).toBe(false);
    // e.g. an enum select accidentally fed here must not read as on
    expect(asBoolean('medium')).toBe(false);
  });
});
