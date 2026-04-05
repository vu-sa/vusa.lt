import { trans as $t } from 'laravel-vue-i18n';

import type { DocumentSearchFilters, LanguageInfo, SearchError } from '@/Types/DocumentSearchTypes';

/**
 * Language display utilities
 */
export class LanguageUtils {
  private static readonly LANGUAGE_MAP: Record<string, LanguageInfo> = {
    Lietuvių: { code: 'lt', display: 'LT', flag: 'https://hatscripts.github.io/circle-flags/flags/lt.svg' },
    Lithuanian: { code: 'lt', display: 'LT', flag: 'https://hatscripts.github.io/circle-flags/flags/lt.svg' },
    Anglų: { code: 'en', display: 'EN', flag: 'https://hatscripts.github.io/circle-flags/flags/gb.svg' },
    English: { code: 'en', display: 'EN', flag: 'https://hatscripts.github.io/circle-flags/flags/gb.svg' },
  };

  static getLanguageFlag(languageValue: string): string {
    return this.LANGUAGE_MAP[languageValue]?.flag || '';
  }

  static getLanguageDisplay(languageValue: string): string {
    return this.LANGUAGE_MAP[languageValue]?.display || $t('search.language_unknown');
  }

  static getLanguageCode(languageValue: string): string {
    return this.LANGUAGE_MAP[languageValue]?.code || 'unknown';
  }
}

/**
 * Recent searches management
 */
export class RecentSearchManager {
  private static readonly MAX_RECENT_SEARCHES = 10;

  static addToRecentSearches(recentSearches: string[], query: string): string[] {
    const trimmed = query.trim();
    if (trimmed.length === 0 || trimmed === '*') {
      return recentSearches;
    }

    const filtered = recentSearches.filter(s =>
      s.toLowerCase() !== trimmed.toLowerCase(),
    );

    return [trimmed, ...filtered].slice(0, this.MAX_RECENT_SEARCHES);
  }

  static clearRecentSearches(): string[] {
    return [];
  }

  static removeRecentSearch(recentSearches: string[], searchToRemove: string): string[] {
    return recentSearches.filter(search => search !== searchToRemove);
  }

  static isValidSearchQuery(query: string): boolean {
    return query.trim().length > 0 || query.trim() === '*';
  }
}

/**
 * Filter validation and utilities
 */
export class FilterUtils {
  static toggleArrayValue<T>(values: T[], value: T): T[] {
    const current = [...values];
    const index = current.indexOf(value);

    if (index >= 0) {
      current.splice(index, 1);
    }
    else {
      current.push(value);
    }

    return current;
  }

  static hasActiveFilters(filters: DocumentSearchFilters): boolean {
    return filters.tenants.length > 0
      || filters.contentTypes?.length > 0
      || filters.languages?.length > 0
      || !!filters.dateRange?.preset
      || !!filters.dateRange?.from
      || !!filters.dateRange?.to;
  }

  static countActiveFilters(filters: DocumentSearchFilters): number {
    let count = 0;
    if (filters.tenants.length > 0) count++;
    if (filters.contentTypes.length > 0) count++;
    if (filters.languages.length > 0) count++;
    if (filters.dateRange.preset || filters.dateRange.from || filters.dateRange.to) count++;
    return count;
  }

  static getFilterSummary(filters: DocumentSearchFilters): string[] {
    const summary: string[] = [];
    if (filters.tenants.length > 0) summary.push(`${filters.tenants.length} org.`);
    if (filters.contentTypes.length > 0) summary.push(`${filters.contentTypes.length} tipas`);
    if (filters.languages.length > 0) summary.push(`${filters.languages.length} kalba`);
    if (filters.dateRange.preset || filters.dateRange.from || filters.dateRange.to) {
      summary.push('data');
    }
    return summary;
  }

  static clearFilters(currentQuery: string): DocumentSearchFilters {
    return {
      query: currentQuery, // Keep the search query
      tenants: [],
      contentTypes: [],
      languages: [],
      dateRange: {}, // Clear all date filtering
    };
  }
}

/**
 * Error handling utilities
 */
export class ErrorUtils {
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

  static isNetworkError(error: unknown): boolean {
    if (error instanceof Error) {
      return error.message.includes('fetch')
        || error.message.includes('network')
        || error.message.includes('connection');
    }
    return false;
  }

  static isTimeoutError(error: unknown): boolean {
    if (error instanceof Error) {
      return error.message.includes('timeout');
    }
    return false;
  }

  static isAbortError(error: unknown): boolean {
    if (error instanceof Error) {
      return error.name === 'AbortError';
    }
    return false;
  }

  static getErrorType(error: unknown): SearchError['type'] {
    if (this.isAbortError(error)) return 'abort';
    if (this.isNetworkError(error)) return 'network';
    if (this.isTimeoutError(error)) return 'timeout';

    if (error instanceof Error) {
      if (error.message.includes('404') || error.message.includes('400')) {
        return 'client';
      }
      if (error.message.includes('500') || error.message.includes('503')) {
        return 'server';
      }
    }

    return 'client';
  }

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
}

/**
 * Date formatting utilities
 */
export class DateUtils {
  static formatDateToTimestamp(date: Date | string | undefined | null): number {
    if (!date) return 0;

    let dateObj: Date;
    if (date instanceof Date) {
      dateObj = date;
    }
    else {
      dateObj = new Date(date);
    }

    if (isNaN(dateObj.getTime())) {
      return 0;
    }

    return Math.floor(dateObj.getTime() / 1000);
  }

  static getPresetDateRange(preset: string): { from: number; to: number } {
    const now = Date.now();
    let fromTime: number;

    switch (preset) {
      case 'recent':
        fromTime = now - (3 * 30 * 24 * 60 * 60 * 1000);
        break;
      case '3months':
        fromTime = now - (3 * 30 * 24 * 60 * 60 * 1000);
        break;
      case '6months':
        fromTime = now - (6 * 30 * 24 * 60 * 60 * 1000);
        break;
      case '1year':
        fromTime = now - (365 * 24 * 60 * 60 * 1000);
        break;
      default:
        fromTime = now - (3 * 30 * 24 * 60 * 60 * 1000);
    }

    return {
      from: Math.floor(fromTime / 1000),
      to: Math.floor(now / 1000),
    };
  }
}

/**
 * Search query utilities
 */
export class QueryUtils {
  static sanitizeQuery(query: string): string {
    return query.trim();
  }

  static isWildcardQuery(query: string): boolean {
    return query.trim() === '*';
  }

  static isValidQuery(query: string): boolean {
    const sanitized = this.sanitizeQuery(query);
    return sanitized.length > 0 && !this.isWildcardQuery(sanitized);
  }

  static shouldSearch(query: string, hasActiveFilters: boolean): boolean {
    const sanitized = this.sanitizeQuery(query);

    if (this.isWildcardQuery(sanitized)) return true;
    if (sanitized.length > 0) return true; // Allow any non-empty query
    if (hasActiveFilters && sanitized.length === 0) return true;

    return false;
  }

  static formatQueryForSearch(query: string, hasActiveFilters: boolean): string {
    const sanitized = this.sanitizeQuery(query);

    if (this.isWildcardQuery(sanitized)) return '*';
    if (sanitized.length > 0) return sanitized; // Return any non-empty query
    if (hasActiveFilters) return '*';

    return '';
  }
}

/**
 * Performance utilities
 */
export class PerformanceUtils {
  static debounce<T extends (...args: any[]) => any>(
    func: T,
    wait: number,
  ): (...args: Parameters<T>) => void {
    let timeout: NodeJS.Timeout;
    return (...args: Parameters<T>) => {
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(this, args), wait);
    };
  }

  static throttle<T extends (...args: any[]) => any>(
    func: T,
    limit: number,
  ): (...args: Parameters<T>) => void {
    let inThrottle: boolean;
    return (...args: Parameters<T>) => {
      if (!inThrottle) {
        func.apply(this, args);
        inThrottle = true;
        setTimeout(() => inThrottle = false, limit);
      }
    };
  }
}
