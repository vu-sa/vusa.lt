/**
 * API Response Types
 *
 * Standardized response shapes for all API endpoints.
 * Use these types with useApi() composable for type-safe API calls.
 *
 * Response format follows the ApiResponses trait pattern:
 * - Success: { success: true, data: T, message?: string, meta?: object }
 * - Error: { success: false, message: string, errors?: object, code?: string }
 * - Paginated: { success: true, data: T[], meta: { pagination: {...} } }
 */

/**
 * Base API response structure (success case)
 */
export interface ApiSuccessResponse<T = unknown> {
  success: true;
  data: T;
  message?: string;
  meta?: Record<string, unknown>;
}

/**
 * API error response structure
 */
export interface ApiErrorResponse {
  success: false;
  message: string;
  errors?: Record<string, string[]>;
  code?: ApiErrorCode;
}

/**
 * Combined API response type
 */
export type ApiResponse<T = unknown> = ApiSuccessResponse<T> | ApiErrorResponse;

/**
 * Pagination metadata structure
 */
export interface PaginationMeta {
  pagination: {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
  };
}

/**
 * Paginated API response
 */
export interface ApiPaginatedResponse<T = unknown> extends ApiSuccessResponse<T[]> {
  meta: PaginationMeta & Record<string, unknown>;
}

/**
 * Standard API error codes
 */
export type ApiErrorCode =
  | 'NOT_FOUND'
  | 'FORBIDDEN'
  | 'UNAUTHORIZED'
  | 'VALIDATION_ERROR'
  | 'INVALID_PATH'
  | 'INVALID_TYPE'
  | 'SERVER_ERROR';

/**
 * Type guard to check if response is successful
 */
export function isApiSuccess<T>(response: ApiResponse<T>): response is ApiSuccessResponse<T> {
  return response.success === true;
}

/**
 * Type guard to check if response is an error
 */
export function isApiError<T>(response: ApiResponse<T>): response is ApiErrorResponse {
  return response.success === false;
}

/*
|--------------------------------------------------------------------------
| Admin API Response Types
|--------------------------------------------------------------------------
*/

/**
 * Task indicator response
 * Route: GET /api/v1/admin/tasks/indicator
 */
export interface TaskIndicatorData {
  id: number;
  name: string;
  due_date: string | null;
  completed_at: string | null;
  taskable_type: string;
  taskable_id: number;
  taskable?: {
    id: number;
    [key: string]: unknown;
  };
}

/**
 * File browser response
 * Route: GET /api/v1/admin/files
 */
export interface FileBrowserData {
  files: FileItem[];
  directories: DirectoryItem[];
  path: string;
  redirected?: boolean;
}

export interface FileItem {
  path: string;
  name: string;
  type: 'file';
  size: number;
  modified: number;
  mimeType: string;
  url: string;
}

export interface DirectoryItem {
  path: string;
  name: string;
  type: 'directory';
}

/**
 * Fileable files response
 * Route: GET /api/v1/admin/fileables/{type}/{id}/files
 */
export interface FileableFileData {
  id: number;
  fileable_type: string;
  fileable_id: number;
  sharepoint_id: string;
  name: string;
  file_type: string;
  file_date: string | null;
  description: string | null;
  web_url: string;
  created_at: string;
  updated_at: string;
}

/**
 * Potential fileables response
 * Route: GET /api/v1/admin/sharepoint/potential-fileables
 */
export interface PotentialFileablesData {
  institutions: Array<{
    id: number;
    name: string;
    meetings: Array<{ id: number; start_time: string }>;
  }>;
  types: Array<{
    id: number;
    title: string;
  }>;
}

/**
 * Tutorial progress response
 * Route: GET /api/v1/admin/tutorials/progress
 */
export interface TutorialProgressData {
  completedTutorials: string[];
}

/*
|--------------------------------------------------------------------------
| Public API Response Types
|--------------------------------------------------------------------------
*/

/**
 * Types list response
 * Route: GET /api/v1/types
 */
export interface TypeData {
  id: number;
  title: string;
  slug: string;
  description?: string;
  parent_id: number | null;
}

/**
 * Documents list response
 * Route: GET /api/v1/documents
 */
export interface DocumentData {
  id: number;
  title: string;
  anonymous_url: string;
}

/**
 * Tenant news response
 * Route: GET /api/v1/{lang}/news/{tenant}
 */
export interface TenantNewsData {
  id: number;
  title: string;
  lang: string;
  short: string;
  publish_time: string;
  permalink: string;
  image: string | null;
}

/**
 * Calendar event response
 * Route: GET /api/v1/{lang}/calendar/{tenant}
 */
export interface CalendarEventData {
  id: number;
  title: string;
  date: string;
  end_date: string | null;
  description?: string;
  location?: string;
  is_international: boolean;
  category?: {
    id: number;
    name: string;
    alias: string;
  };
  images?: Array<{
    id: number;
    url: string;
    thumbnail_url?: string;
  }>;
  tenant?: {
    id: number;
    shortname: string;
  };
}

/**
 * Typesense config response
 * Route: GET /api/v1/typesense/config
 */
export interface TypesenseConfigData {
  host: string;
  port: number;
  protocol: string;
  apiKey: string;
  connectionTimeoutSeconds?: number;
}
