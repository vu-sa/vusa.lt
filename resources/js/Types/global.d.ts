import { PageProps as InertiaPageProps } from '@inertiajs/core';
import { route as ziggyRoute } from 'ziggy-js';
import { PageProps as AppPageProps } from './';
import type { PostHog } from 'posthog-js';

import type { DriveItem } from "@microsoft/microsoft-graph-types";

declare global {
  /* eslint-disable no-var */
  var route: typeof ziggyRoute;

  /**
   * Minimal structure required for file upload form data.
   * Used when passing fileable to FileManager/FileUploader components.
   * Only id and type are required for backend validation.
   * fileable_name is optional but used for auto-generating file names.
   */
  interface FileableFormData {
    id: number | string;
    type: string;
    /** Optional - used by generateNameForFile for auto-naming */
    fileable_name?: string;
  }

  interface PaginatedModels<T> {
    current_page: number;
    data: Array<T> | [];
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    // links are array of object with active, label and url
    links: Array<{
      active: boolean;
      label: string;
      url: string;
    }>;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
  }

  type MyDriveItem = Pick<
    DriveItem,
    | "id"
    | "name"
    | "file"
    | "folder"
    | "size"
    | "createdDateTime"
    | "lastModifiedDateTime"
    | "webUrl"
    | "listItem"
    | "thumbnails"
  >;

  const $posthog: PostHog;
}

declare module 'vue' {
  interface ComponentCustomProperties {
    route: typeof ziggyRoute;
  }
}

declare module '@inertiajs/core' {
  interface PageProps extends InertiaPageProps, AppPageProps { }
}
