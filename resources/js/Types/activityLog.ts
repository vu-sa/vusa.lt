/**
 * Types for the activity log feed. Mirrors app/Http/Resources/ActivityResource.php
 * (fields) and App\Services\ActivityChangeFormatter (change shape).
 */

export type ActivityEvent = 'created' | 'updated' | 'deleted' | 'restored' | 'relation_updated' | 'content_reordered';

export type ActivityChangeType
  = | 'text'
    | 'diff'
    | 'rich'
    | 'date'
    | 'datetime'
    | 'boolean'
    | 'enum'
    | 'relation'
    | 'json'
    | 'translatable';

export interface ActivityCauser {
  id: string;
  name: string | null;
  profile_photo_path: string | null;
}

export interface ActivitySubjectRef {
  type: string;
  id: string;
  label: string;
  is_root: boolean;
}

export interface ActivityChange {
  key: string;
  label: string;
  type: ActivityChangeType;
  old: unknown;
  new: unknown;
  old_display: string | null;
  new_display: string | null;
}

export interface ActivityRelationRef {
  id: string;
  label: string;
}

export interface ActivityRelationChange {
  relation: string;
  label: string;
  attached: ActivityRelationRef[];
  detached: ActivityRelationRef[];
}

export interface ActivityEntry {
  id: number;
  event: ActivityEvent;
  created_at: string | null;
  causer: ActivityCauser | null;
  subject: ActivitySubjectRef;
  changes: ActivityChange[];
  relation_change?: ActivityRelationChange;
}

export interface ActivityLogCursor {
  next: string | null;
  prev: string | null;
  per_page: number;
  has_more: boolean;
}

/**
 * Filters accepted by GET api.v1.admin.activityLog.index — see
 * App\Http\Requests\Api\Admin\ActivityLogIndexRequest.
 */
export interface ActivityLogFilters {
  scope?: 'tree' | 'self';
  event?: ActivityEvent;
  subject_type?: string;
  causer_id?: string;
}
