import { describe, it, expect } from 'vitest';

import { escapeFilterValue, buildFilterString } from '../AdminSearchService';
import type { CollectionFacetConfig } from '../../Types/AdminSearchTypes';

describe('escapeFilterValue', () => {
  describe('returns value unchanged when no special characters', () => {
    it('handles normal strings', () => {
      expect(escapeFilterValue('normal')).toBe('normal');
      expect(escapeFilterValue('hello world')).toBe('hello world');
      expect(escapeFilterValue('VU SA')).toBe('VU SA');
    });

    it('handles empty string', () => {
      expect(escapeFilterValue('')).toBe('');
    });
  });

  describe('escapes values with commas', () => {
    it('wraps value with backticks', () => {
      expect(escapeFilterValue('value, with, commas')).toBe('`value, with, commas`');
    });
  });

  describe('escapes values with colons', () => {
    it('wraps value with backticks', () => {
      expect(escapeFilterValue('key:value')).toBe('`key:value`');
    });
  });

  describe('escapes values with backticks', () => {
    it('escapes backticks inside the value', () => {
      expect(escapeFilterValue('test`value')).toBe('`test\\`value`');
    });

    it('escapes multiple backticks', () => {
      expect(escapeFilterValue('`test`value`')).toBe('`\\`test\\`value\\``');
    });
  });

  describe('escapes values with backslashes', () => {
    it('escapes single backslash', () => {
      expect(escapeFilterValue('test\\value')).toBe('`test\\\\value`');
    });

    it('escapes multiple backslashes', () => {
      expect(escapeFilterValue('path\\to\\file')).toBe('`path\\\\to\\\\file`');
    });

    it('escapes backslash at end of string', () => {
      expect(escapeFilterValue('trailing\\')).toBe('`trailing\\\\`');
    });
  });

  describe('handles combinations of special characters', () => {
    it('escapes backslash followed by backtick correctly', () => {
      // Backslashes must be escaped FIRST, then backticks
      // Input: test\`value
      // After backslash escape: test\\`value
      // After backtick escape: test\\\`value
      // Wrapped: `test\\\`value`
      expect(escapeFilterValue('test\\`value')).toBe('`test\\\\\\`value`');
    });

    it('escapes multiple types of special characters', () => {
      const input = 'key:value, with\\backslash';
      const result = escapeFilterValue(input);
      expect(result).toBe('`key:value, with\\\\backslash`');
    });

    it('handles complex injection attempt patterns', () => {
      // Potential injection: \` could try to escape the wrapper backtick
      // Our fix ensures backslashes are escaped first, preventing this
      const input = '\\`injection\\`attempt';
      const result = escapeFilterValue(input);
      // Input: \`injection\`attempt
      // After backslash escape: \\`injection\\`attempt
      // After backtick escape: \\\`injection\\\`attempt
      // Wrapped: `\\\`injection\\\`attempt`
      expect(result).toBe('`\\\\\\`injection\\\\\\`attempt`');
    });
  });

  describe('order of escaping is correct', () => {
    it('escapes backslashes before backticks to prevent injection', () => {
      // This is the security fix: if we escaped backticks first,
      // a string like \` would become \` (unchanged backtick after \)
      // But with backslashes first, \` becomes \\\`

      // Verify order by checking a backslash-backtick combination
      const input = '\\`';
      const result = escapeFilterValue(input);

      // Correct: \\ + \` wrapped = `\\\``
      expect(result).toBe('`\\\\\\``');

      // The wrong order would produce `\\`` which leaves backtick unescaped
    });
  });
});

describe('buildFilterString', () => {
  const mockFacetConfig: CollectionFacetConfig = {
    fields: [
      { field: 'tenant', label: 'Tenant', type: 'checkbox' },
      { field: 'status', label: 'Status', type: 'checkbox' },
      { field: 'year', label: 'Year', type: 'year-pills' },
    ],
  };

  it('returns empty string for empty filters', () => {
    const result = buildFilterString({ query: '' }, mockFacetConfig);
    expect(result).toBe('');
  });

  it('builds filter with array values', () => {
    const result = buildFilterString(
      { query: '', tenant: ['VU SA', 'VU SA MIF'] },
      mockFacetConfig,
    );
    expect(result).toBe('tenant:[VU SA,VU SA MIF]');
  });

  it('escapes special characters in filter values', () => {
    const result = buildFilterString(
      { query: '', tenant: ['Value, with comma'] },
      mockFacetConfig,
    );
    expect(result).toBe('tenant:[`Value, with comma`]');
  });

  it('escapes backslashes in filter values', () => {
    const result = buildFilterString(
      { query: '', tenant: ['path\\to\\folder'] },
      mockFacetConfig,
    );
    expect(result).toBe('tenant:[`path\\\\to\\\\folder`]');
  });

  it('builds filter with single string value', () => {
    const result = buildFilterString(
      { query: '', status: 'active' },
      mockFacetConfig,
    );
    expect(result).toBe('status:=active');
  });

  it('combines multiple filters with &&', () => {
    const result = buildFilterString(
      { query: '', tenant: ['VU SA'], status: 'active' },
      mockFacetConfig,
    );
    expect(result).toBe('tenant:[VU SA] && status:=active');
  });

  it('ignores query field', () => {
    const result = buildFilterString(
      { query: 'search term', tenant: ['VU SA'] },
      mockFacetConfig,
    );
    expect(result).toBe('tenant:[VU SA]');
  });
});
