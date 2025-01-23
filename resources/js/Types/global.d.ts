import { PageProps as InertiaPageProps } from '@inertiajs/core';
import { AxiosInstance } from 'axios';
import { route as ziggyRoute } from 'ziggy-js';
import { PageProps as AppPageProps } from './';
import type { PostHog } from 'posthog-js';

import type { DriveItem } from "@microsoft/microsoft-graph-types";

declare global {
  interface Window {
    axios: AxiosInstance;
  }

  /* eslint-disable no-var */
  var route: typeof ziggyRoute;

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
