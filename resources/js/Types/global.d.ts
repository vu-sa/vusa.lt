import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import type { route as ziggyRoute } from 'ziggy-js';
import type { DriveItem } from '@microsoft/microsoft-graph-types';

import type { PageProps as AppPageProps } from './';

declare global {

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
    | 'id'
    | 'name'
    | 'file'
    | 'folder'
    | 'size'
    | 'createdDateTime'
    | 'lastModifiedDateTime'
    | 'webUrl'
    | 'listItem'
    | 'thumbnails'
  >;
}

declare module 'vue' {
  interface ComponentCustomProperties {
    route: typeof ziggyRoute;
  }
}

declare module '@inertiajs/core' {
  interface PageProps extends InertiaPageProps, AppPageProps { }
}
