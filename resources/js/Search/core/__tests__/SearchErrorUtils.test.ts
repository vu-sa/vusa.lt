import { describe, it, expect } from 'vitest';

import { ErrorUtils, QueryUtils, RecentSearchManager } from '../services/SearchErrorUtils';

describe('ErrorUtils', () => {
  describe('createSearchError', () => {
    it('creates a SearchError with all fields', () => {
      const error = ErrorUtils.createSearchError(
        'network',
        'Connection failed',
        'Check your internet',
        'ERR_NETWORK',
        true,
      );

      expect(error.type).toBe('network');
      expect(error.message).toBe('Connection failed');
      expect(error.userMessage).toBe('Check your internet');
      expect(error.code).toBe('ERR_NETWORK');
      expect(error.retryable).toBe(true);
      expect(error.timestamp).toBeInstanceOf(Date);
    });

    it('defaults to retryable true', () => {
      const error = ErrorUtils.createSearchError('client', 'Error', 'Try again');
      expect(error.retryable).toBe(true);
    });
  });

  describe('isNetworkError', () => {
    it('returns true for fetch errors', () => {
      expect(ErrorUtils.isNetworkError(new Error('fetch failed'))).toBe(true);
      expect(ErrorUtils.isNetworkError(new Error('Failed to fetch'))).toBe(true);
    });

    it('returns true for network/connection errors', () => {
      expect(ErrorUtils.isNetworkError(new Error('network error'))).toBe(true);
      expect(ErrorUtils.isNetworkError(new Error('connection refused'))).toBe(true);
      expect(ErrorUtils.isNetworkError(new Error('net::err_connection_refused'))).toBe(true);
    });

    it('returns false for other errors', () => {
      expect(ErrorUtils.isNetworkError(new Error('validation error'))).toBe(false);
      expect(ErrorUtils.isNetworkError('string error')).toBe(false);
    });
  });

  describe('isTimeoutError', () => {
    it('returns true for timeout errors', () => {
      expect(ErrorUtils.isTimeoutError(new Error('request timed out'))).toBe(true);
      expect(ErrorUtils.isTimeoutError(new Error('timeout exceeded'))).toBe(true);
    });

    it('returns false for other errors', () => {
      expect(ErrorUtils.isTimeoutError(new Error('server error'))).toBe(false);
    });
  });

  describe('isAbortError', () => {
    it('returns true for AbortError', () => {
      const error = new Error('Request aborted');
      error.name = 'AbortError';
      expect(ErrorUtils.isAbortError(error)).toBe(true);
    });

    it('returns false for other errors', () => {
      expect(ErrorUtils.isAbortError(new Error('some error'))).toBe(false);
      expect(ErrorUtils.isAbortError('not an error')).toBe(false);
    });
  });

  describe('isAuthError', () => {
    it('returns true for 401 errors', () => {
      expect(ErrorUtils.isAuthError(new Error('401 Unauthorized'))).toBe(true);
      expect(ErrorUtils.isAuthError(new Error('unauthorized access'))).toBe(true);
      expect(ErrorUtils.isAuthError(new Error('unauthenticated'))).toBe(true);
    });

    it('returns false for other errors', () => {
      expect(ErrorUtils.isAuthError(new Error('403 Forbidden'))).toBe(false);
    });
  });

  describe('isRateLimitError', () => {
    it('returns true for 429 errors', () => {
      expect(ErrorUtils.isRateLimitError(new Error('429 Too Many Requests'))).toBe(true);
      expect(ErrorUtils.isRateLimitError(new Error('rate limit exceeded'))).toBe(true);
    });

    it('returns false for other errors', () => {
      expect(ErrorUtils.isRateLimitError(new Error('500 Server Error'))).toBe(false);
    });
  });

  describe('getErrorType', () => {
    it('returns abort for AbortError', () => {
      const error = new Error('Aborted');
      error.name = 'AbortError';
      expect(ErrorUtils.getErrorType(error)).toBe('abort');
    });

    it('returns network for network errors', () => {
      expect(ErrorUtils.getErrorType(new Error('fetch failed'))).toBe('network');
    });

    it('returns timeout for timeout errors', () => {
      expect(ErrorUtils.getErrorType(new Error('request timeout'))).toBe('timeout');
    });

    it('returns client for 4xx errors', () => {
      expect(ErrorUtils.getErrorType(new Error('404 Not Found'))).toBe('client');
      expect(ErrorUtils.getErrorType(new Error('400 Bad Request'))).toBe('client');
    });

    it('returns server for 5xx errors', () => {
      expect(ErrorUtils.getErrorType(new Error('500 Internal Server Error'))).toBe('server');
      expect(ErrorUtils.getErrorType(new Error('503 Service Unavailable'))).toBe('server');
    });

    it('defaults to client for unknown errors', () => {
      expect(ErrorUtils.getErrorType(new Error('unknown'))).toBe('client');
    });
  });

  describe('getUserFriendlyMessage', () => {
    it('returns appropriate message for each error type', () => {
      expect(ErrorUtils.getUserFriendlyMessage(new Error('fetch failed'))).toContain('internet');
      expect(ErrorUtils.getUserFriendlyMessage(new Error('timeout'))).toContain('too long');
      expect(ErrorUtils.getUserFriendlyMessage(new Error('500'))).toContain('unavailable');
    });
  });

  describe('isRetryable', () => {
    it('returns false for abort errors', () => {
      const error = new Error('Aborted');
      error.name = 'AbortError';
      expect(ErrorUtils.isRetryable(error)).toBe(false);
    });

    it('returns true for network errors', () => {
      expect(ErrorUtils.isRetryable(new Error('network error'))).toBe(true);
    });

    it('returns true for timeout errors', () => {
      expect(ErrorUtils.isRetryable(new Error('timeout'))).toBe(true);
    });

    it('returns true for 5xx server errors', () => {
      expect(ErrorUtils.isRetryable(new Error('500 error'))).toBe(true);
      expect(ErrorUtils.isRetryable(new Error('503 error'))).toBe(true);
    });

    it('returns false for client errors', () => {
      expect(ErrorUtils.isRetryable(new Error('404 not found'))).toBe(false);
    });
  });

  describe('fromError', () => {
    it('creates SearchError from unknown error', () => {
      const error = ErrorUtils.fromError(new Error('network failed'));

      expect(error.type).toBe('network');
      expect(error.message).toBe('network failed');
      expect(error.retryable).toBe(true);
      expect(error.timestamp).toBeInstanceOf(Date);
    });

    it('handles non-Error objects', () => {
      const error = ErrorUtils.fromError('string error');

      expect(error.message).toBe('Unknown error');
    });
  });

  describe('getRetryDelay', () => {
    it('returns exponential backoff delay', () => {
      const delay1 = ErrorUtils.getRetryDelay(1, 1000, 30000);
      const delay2 = ErrorUtils.getRetryDelay(2, 1000, 30000);
      const delay3 = ErrorUtils.getRetryDelay(3, 1000, 30000);

      // Should be approximately 1s, 2s, 4s (with jitter)
      expect(delay1).toBeGreaterThanOrEqual(900);
      expect(delay1).toBeLessThanOrEqual(1100);
      expect(delay2).toBeGreaterThanOrEqual(1800);
      expect(delay2).toBeLessThanOrEqual(2200);
      expect(delay3).toBeGreaterThanOrEqual(3600);
      expect(delay3).toBeLessThanOrEqual(4400);
    });

    it('caps delay at maxDelay', () => {
      const delay = ErrorUtils.getRetryDelay(10, 1000, 5000);
      expect(delay).toBeLessThanOrEqual(5500); // 5000 + 10% jitter
    });
  });

  describe('shouldRetry', () => {
    it('returns false when max attempts reached', () => {
      expect(ErrorUtils.shouldRetry(new Error('network'), 3, 3)).toBe(false);
    });

    it('returns false for non-retryable errors', () => {
      expect(ErrorUtils.shouldRetry(new Error('404'), 1, 3)).toBe(false);
    });

    it('returns true for retryable errors under max attempts', () => {
      expect(ErrorUtils.shouldRetry(new Error('network error'), 1, 3)).toBe(true);
    });
  });
});

describe('QueryUtils', () => {
  describe('isValidQuery', () => {
    it('returns true for non-empty queries', () => {
      expect(QueryUtils.isValidQuery('a')).toBe(true);
      expect(QueryUtils.isValidQuery('search term')).toBe(true);
    });

    it('returns true for wildcard', () => {
      expect(QueryUtils.isValidQuery('*')).toBe(true);
    });

    it('returns false for empty or whitespace', () => {
      expect(QueryUtils.isValidQuery('')).toBe(false);
      expect(QueryUtils.isValidQuery('   ')).toBe(false);
    });
  });

  describe('shouldSearch', () => {
    it('returns true when query is valid', () => {
      expect(QueryUtils.shouldSearch('test', false)).toBe(true);
    });

    it('returns true when filters are active', () => {
      expect(QueryUtils.shouldSearch('', true)).toBe(true);
    });

    it('returns false when no query and no filters', () => {
      expect(QueryUtils.shouldSearch('', false)).toBe(false);
    });
  });

  describe('normalizeQuery', () => {
    it('trims whitespace', () => {
      expect(QueryUtils.normalizeQuery('  test  ')).toBe('test');
    });
  });

  describe('isWildcard', () => {
    it('returns true for wildcard query', () => {
      expect(QueryUtils.isWildcard('*')).toBe(true);
      expect(QueryUtils.isWildcard(' * ')).toBe(true);
    });

    it('returns false for non-wildcard', () => {
      expect(QueryUtils.isWildcard('test')).toBe(false);
      expect(QueryUtils.isWildcard('**')).toBe(false);
    });
  });
});

describe('RecentSearchManager', () => {
  describe('addToRecentSearches', () => {
    it('adds search to beginning of list', () => {
      const result = RecentSearchManager.addToRecentSearches(['old'], 'new');
      expect(result[0]).toBe('new');
    });

    it('removes duplicates (case-insensitive)', () => {
      const result = RecentSearchManager.addToRecentSearches(['Test', 'other'], 'test');
      expect(result).toHaveLength(2);
      expect(result[0]).toBe('test');
    });

    it('limits to max recent searches', () => {
      const existing = Array.from({ length: 15 }, (_, i) => `search${i}`);
      const result = RecentSearchManager.addToRecentSearches(existing, 'new');
      expect(result).toHaveLength(10);
      expect(result[0]).toBe('new');
    });

    it('ignores empty and wildcard searches', () => {
      expect(RecentSearchManager.addToRecentSearches(['a'], '')).toEqual(['a']);
      expect(RecentSearchManager.addToRecentSearches(['a'], '   ')).toEqual(['a']);
      expect(RecentSearchManager.addToRecentSearches(['a'], '*')).toEqual(['a']);
    });
  });

  describe('clearRecentSearches', () => {
    it('returns empty array', () => {
      expect(RecentSearchManager.clearRecentSearches()).toEqual([]);
    });
  });

  describe('removeRecentSearch', () => {
    it('removes specified search', () => {
      const result = RecentSearchManager.removeRecentSearch(['a', 'b', 'c'], 'b');
      expect(result).toEqual(['a', 'c']);
    });

    it('returns unchanged if search not found', () => {
      const result = RecentSearchManager.removeRecentSearch(['a', 'b'], 'c');
      expect(result).toEqual(['a', 'b']);
    });
  });
});
