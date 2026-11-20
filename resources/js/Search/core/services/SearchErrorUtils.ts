/**
 * SearchErrorUtils - Unified error handling utilities
 *
 * Provides common utilities for handling search errors:
 * - Error type detection
 * - User-friendly message generation
 * - Retry logic helpers
 *
 * Used by both admin and public search implementations.
 */

import type { SearchError } from '../types';

/**
 * ErrorUtils class - provides static methods for error handling
 */
export class ErrorUtils {
  /**
   * Create a SearchError object
   */
  static createSearchError(
    type: SearchError['type'],
    message: string,
    userMessage: string,
    code?: string | number,
    retryable = true,
  ): SearchError {
    return {
      type,
      message,
      userMessage,
      code,
      retryable,
      timestamp: new Date(),
    };
  }

  /**
   * Check if error is a network error
   */
  static isNetworkError(error: unknown): boolean {
    if (error instanceof Error) {
      const message = error.message.toLowerCase();
      return (
        message.includes('fetch')
        || message.includes('network')
        || message.includes('connection')
        || message.includes('failed to fetch')
        || message.includes('net::err')
      );
    }
    return false;
  }

  /**
   * Check if error is a timeout error
   */
  static isTimeoutError(error: unknown): boolean {
    if (error instanceof Error) {
      const message = error.message.toLowerCase();
      return message.includes('timeout') || message.includes('timed out');
    }
    return false;
  }

  /**
   * Check if error is an abort error (request cancelled)
   */
  static isAbortError(error: unknown): boolean {
    if (error instanceof Error) {
      return error.name === 'AbortError';
    }
    return false;
  }

  /**
   * Check if error is an authentication error
   */
  static isAuthError(error: unknown): boolean {
    if (error instanceof Error) {
      const message = error.message.toLowerCase();
      return (
        message.includes('401')
        || message.includes('unauthorized')
        || message.includes('unauthenticated')
      );
    }
    return false;
  }

  /**
   * Check if error is a rate limit error
   */
  static isRateLimitError(error: unknown): boolean {
    if (error instanceof Error) {
      const message = error.message.toLowerCase();
      return message.includes('429') || message.includes('rate limit');
    }
    return false;
  }

  /**
   * Get the error type from an unknown error
   */
  static getErrorType(error: unknown): SearchError['type'] {
    if (this.isAbortError(error)) return 'abort';
    if (this.isNetworkError(error)) return 'network';
    if (this.isTimeoutError(error)) return 'timeout';

    if (error instanceof Error) {
      const { message } = error;
      if (message.includes('404') || message.includes('400')) {
        return 'client';
      }
      if (message.includes('500') || message.includes('503')) {
        return 'server';
      }
    }

    return 'client';
  }

  /**
   * Get a user-friendly error message based on error type
   */
  static getUserFriendlyMessage(error: unknown): string {
    const type = this.getErrorType(error);

    const messages: Record<SearchError['type'], string> = {
      network: 'Check your internet connection and try again',
      timeout: 'Search is taking too long. Please try again.',
      client: 'There was a problem with your search. Please try different terms.',
      server: 'Search is temporarily unavailable. Please try again in a few minutes.',
      abort: 'Search was cancelled',
    };

    return messages[type];
  }

  /**
   * Determine if an error is retryable
   */
  static isRetryable(error: unknown): boolean {
    // Abort errors should not be retried
    if (this.isAbortError(error)) return false;

    // Network and timeout errors are retryable
    if (this.isNetworkError(error)) return true;
    if (this.isTimeoutError(error)) return true;

    // Server errors (5xx) are retryable
    if (error instanceof Error) {
      const { message } = error;
      if (message.includes('500') || message.includes('502') || message.includes('503')) {
        return true;
      }
    }

    // Client errors (4xx) are generally not retryable
    return false;
  }

  /**
   * Create a SearchError from an unknown error
   */
  static fromError(error: unknown, context = 'search'): SearchError {
    const type = this.getErrorType(error);
    const message = error instanceof Error ? error.message : 'Unknown error';
    const userMessage = this.getUserFriendlyMessage(error);
    const retryable = this.isRetryable(error);

    return this.createSearchError(type, message, userMessage, type.toUpperCase(), retryable);
  }

  /**
   * Calculate exponential backoff delay
   *
   * @param attempt - Current attempt number (1-based)
   * @param baseDelay - Base delay in milliseconds (default 1000)
   * @param maxDelay - Maximum delay in milliseconds (default 30000)
   * @returns Delay in milliseconds
   */
  static getRetryDelay(attempt: number, baseDelay = 1000, maxDelay = 30000): number {
    const delay = Math.min(Math.pow(2, attempt - 1) * baseDelay, maxDelay);
    // Add some jitter (±10%)
    const jitter = delay * 0.1 * (Math.random() * 2 - 1);
    return Math.round(delay + jitter);
  }

  /**
   * Check if we should retry based on attempt count and error type
   */
  static shouldRetry(error: unknown, attempt: number, maxAttempts: number): boolean {
    if (attempt >= maxAttempts) return false;
    return this.isRetryable(error);
  }
}

/**
 * QueryUtils - Query validation and manipulation utilities
 */
export class QueryUtils {
  static readonly MIN_QUERY_LENGTH = 1; // Single character is valid for search

  /**
   * Check if a query is valid for searching
   */
  static isValidQuery(query: string): boolean {
    const trimmed = query.trim();
    return trimmed.length >= this.MIN_QUERY_LENGTH || trimmed === '*';
  }

  /**
   * Check if we should perform a search based on query and filters
   */
  static shouldSearch(query: string, hasActiveFilters: boolean): boolean {
    const trimmed = query.trim();

    // Always search if there are active filters
    if (hasActiveFilters) return true;

    // Search if query is valid
    return this.isValidQuery(trimmed);
  }

  /**
   * Normalize a search query
   */
  static normalizeQuery(query: string): string {
    return query.trim();
  }

  /**
   * Check if query is a wildcard search
   */
  static isWildcard(query: string): boolean {
    return query.trim() === '*';
  }
}

/**
 * RecentSearchManager - Recent searches management
 */
export class RecentSearchManager {
  static readonly MAX_RECENT_SEARCHES = 10;

  /**
   * Add a search to recent searches list
   */
  static addToRecentSearches(recentSearches: string[], query: string): string[] {
    const trimmed = query.trim();

    // Don't add empty or wildcard searches
    if (trimmed.length === 0 || trimmed === '*') {
      return recentSearches;
    }

    // Remove existing instance (case-insensitive)
    const filtered = recentSearches.filter(
      s => s.toLowerCase() !== trimmed.toLowerCase(),
    );

    // Add to beginning and limit to max
    return [trimmed, ...filtered].slice(0, this.MAX_RECENT_SEARCHES);
  }

  /**
   * Clear all recent searches
   */
  static clearRecentSearches(): string[] {
    return [];
  }

  /**
   * Remove a specific search from recent searches
   */
  static removeRecentSearch(recentSearches: string[], searchToRemove: string): string[] {
    return recentSearches.filter(search => search !== searchToRemove);
  }
}
