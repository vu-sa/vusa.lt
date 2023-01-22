export {};

import type { DriveItem } from "@microsoft/microsoft-graph-types";

// parseFileItems @ SharepointService
declare global {
  export type MyDriveItem = Pick<
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
}
