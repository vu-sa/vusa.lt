export enum AllowedFileablesEnum {
  "DUTY" = "duty",
  "INSTITUTION" = "institution",
  "MEETING" = "meeting",
  "TYPE" = "type",
  "USER" = "user",
}
export enum AllowedRelationshipablesEnum {
  "INSTITUTION" = "institution",
  "TYPE" = "type",
}
export enum CRUDEnum {
  "CREATE" = "create",
  "READ" = "read",
  "UPDATE" = "update",
  "DELETE" = "delete",
}
export enum ContentPartEnum {
  "IMAGE_GRID" = "image-grid",
  "SHADCN_ACCORDION" = "shadcn-accordion",
  "SHADCN_CARD" = "shadcn-card",
  "TIPTAP" = "tiptap",
  "HERO" = "hero",
  "SPOTIFY_EMBED" = "spotify-embed",
  "FLOW_GRAPH" = "flow-graph",
  "NUMBER_STAT_SECTION" = "number-stat-section",
  "NEWS" = "news",
  "CALENDAR" = "calendar",
  "CONTENT_GRID" = "content-grid",
}
export enum DegreeEnum {
  "BA" = "BA",
  "MA" = "MA",
  "PHD" = "PhD",
  "INTEGRATED_STUDIES" = "Integrated Studies",
  "PROFESSIONAL_PEDAGOGY" = "Professional Pedagogy",
  "OTHER" = "Other",
}
export enum LocaleEnum {
  "LT" = "lt",
  "EN" = "en",
}
export enum ModelEnum {
  "AGENDA_ITEM" = "agendaItem",
  "BANNER" = "banner",
  "CALENDAR" = "calendar",
  "CATEGORY" = "category",
  "CHANGELOG_ITEM" = "changelogItem",
  "COMMENT" = "comment",
  "DOCUMENT" = "document",
  "DUTIABLE" = "dutiable",
  "DUTY" = "duty",
  "FILE" = "file",
  "FORM" = "form",
  "INSTITUTION" = "institution",
  "MEETING" = "meeting",
  "MEMBERSHIP" = "membership",
  "NAVIGATION" = "navigation",
  "NEWS" = "news",
  "QUICK_LINK" = "quickLink",
  "PAGE" = "page",
  "PERMISSION" = "permission",
  "RELATIONSHIP" = "relationship",
  "RELATIONSHIPABLE" = "relationshipable",
  "RESERVATION" = "reservation",
  "RESERVATION_RESOURCE" = "reservationResource",
  "RESOURCE" = "resource",
  "ROLE" = "role",
  "SHAREPOINT_FILE" = "sharepointFile",
  "SHAREPOINT_FILEABLE" = "sharepointFileable",
  "STUDY_PROGRAM" = "studyProgram",
  "TAG" = "tag",
  "TASK" = "task",
  "TENANT" = "tenant",
  "TRAINING" = "training",
  "TYPE" = "type",
  "USER" = "user",
}
export enum PermissionScopeEnum {
  "OWN" = "own",
  "PADALINYS" = "padalinys",
  "ALL" = "*",
}
export enum SearchableModelEnum {
  "NEWS" = "news",
  "PAGE" = "page",
  "DOCUMENT" = "document",
  "CALENDAR" = "calendar",
}
export enum SharepointConfigEnum {
  "API_BASE_URL" = "https://graph.microsoft.com/v1.0/",
  "DEFAULT_TIMEOUT" = "30",
  "MAX_RETRIES" = "3",
  "RETRY_DELAY_MS" = "1000",
  "DEFAULT_BATCH_SIZE" = "20",
}
export enum SharepointFieldEnum {
  "PADALINYS" = "Padalinys",
  "TITLE" = "Title",
  "DATE" = "Date",
  "EFFECTIVE_DATE" = "Effective_x0020_Date",
  "EXPIRATION_DATE" = "Expiration_x0020_Date0",
  "LANGUAGE" = "Language",
  "TURINYS" = "Turinys",
  "SUMMARY" = "Summary",
}
export enum SharepointFolderEnum {
  "GENERAL" = "General",
  "PADALINIAI" = "Padaliniai",
  "TYPES" = "Types",
  "INSTITUTIONS" = "Institutions",
  "MEETINGS" = "Meetings",
}
export enum SharepointPermissionTypeEnum {
  "VIEW" = "view",
  "EDIT" = "edit",
  "OWNER" = "owner",
}
export enum SharepointScopeEnum {
  "ANONYMOUS" = "anonymous",
  "ORGANIZATION" = "organization",
  "USERS" = "users",
}
