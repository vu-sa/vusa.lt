export enum ActionType {
  Manual = "manual",
  Approval = "approval",
  Pickup = "pickup",
  Return = "return",
  AgendaCreation = "agenda_creation",
  AgendaCompletion = "agenda_completion",
  PeriodicityGap = "periodicity_gap",
}
export enum AgendaItemType {
  Voting = "voting",
  Informational = "informational",
  Deferred = "deferred",
}
export enum AllowedFileablesEnum {
  DUTY = "Duty",
  INSTITUTION = "Institution",
  MEETING = "Meeting",
  TYPE = "Type",
}
export enum AllowedRelationshipablesEnum {
  INSTITUTION = "INSTITUTION",
  TYPE = "TYPE",
}
export enum ApprovalDecision {
  Approved = "approved",
  Rejected = "rejected",
  Cancelled = "cancelled",
}
export enum CRUDEnum {
  CREATE = "create",
  READ = "read",
  UPDATE = "update",
  DELETE = "delete",
  FORCE_DELETE = "forceDelete",
}
export enum CalendarHeroStyleEnum {
  CARD = "card",
  SPLIT = "split",
  MINIMAL = "minimal",
}
export enum CommentKind {
  Comment = "comment",
  Poll = "poll",
}
export enum ContentPartEnum {
  IMAGE_GRID = "IMAGE_GRID",
  SHADCN_ACCORDION = "SHADCN_ACCORDION",
  SHADCN_CARD = "SHADCN_CARD",
  TIPTAP = "TIPTAP",
  HERO = "HERO",
  SPOTIFY_EMBED = "SPOTIFY_EMBED",
  SOCIAL_EMBED = "SOCIAL_EMBED",
  FLOW_GRAPH = "FLOW_GRAPH",
  NUMBER_STAT_SECTION = "NUMBER_STAT_SECTION",
  NEWS = "NEWS",
  CALENDAR = "CALENDAR",
  CONTENT_GRID = "CONTENT_GRID",
  TEXT_BOX = "TEXT_BOX",
  CAROUSEL_SLIDE_DECK = "CAROUSEL_SLIDE_DECK",
  HERO_CAROUSEL = "HERO_CAROUSEL",
  CARD_STACK = "CARD_STACK",
  PHOTO_GALLERY = "PHOTO_GALLERY",
  LINK_LIST = "LINK_LIST",
  EVENT_LIST = "EVENT_LIST",
  PERSON_QUOTE = "PERSON_QUOTE",
  SECTION = "SECTION",
  SPACER = "SPACER",
}
export enum DegreeEnum {
  BA = "BA",
  MA = "MA",
  PHD = "PHD",
  INTEGRATED_STUDIES = "INTEGRATED_STUDIES",
  PROFESSIONAL_PEDAGOGY = "PROFESSIONAL_PEDAGOGY",
  OTHER = "OTHER",
}
export enum InstitutionActivityStatus {
  NoActivity = "no_activity",
  Healthy = "healthy",
  Approaching = "approaching",
  Overdue = "overdue",
  CoveredByUpcomingMeeting = "covered_by_upcoming_meeting",
  CoveredByCheckIn = "covered_by_check_in",
}
export enum InstitutionScope {
  Vusa = "vusa",
  University = "vu",
  National = "national",
  International = "international",
}
export enum LocaleEnum {
  LT = "lt",
  EN = "en",
}
export enum MeetingType {
  InPerson = "in-person",
  Remote = "remote",
  Email = "email",
}
export enum ModelEnum {
  AGENDA_ITEM = "agenda_item",
  BANNER = "banner",
  CALENDAR = "calendar",
  CATEGORY = "category",
  COMMENT = "comment",
  DOCUMENT = "document",
  DUTIABLE = "dutiable",
  DUTY = "duty",
  FILE = "file",
  FORM = "form",
  INSTITUTION = "institution",
  MEETING = "meeting",
  NAVIGATION = "navigation",
  NEWS = "news",
  QUICK_LINK = "quick_link",
  PAGE = "page",
  PERMISSION = "permission",
  PROBLEM = "problem",
  RELATIONSHIP = "relationship",
  RELATIONSHIPABLE = "relationshipable",
  RESERVATION = "reservation",
  RESERVATION_RESOURCE = "reservation_resource",
  RESOURCE = "resource",
  ROLE = "role",
  SHAREPOINT_FILE = "sharepoint_file",
  SHAREPOINT_FILEABLE = "sharepoint_fileable",
  STUDY_PROGRAM = "study_program",
  STUDY_SET = "study_set",
  TAG = "tag",
  TASK = "task",
  TENANT = "tenant",
  TYPE = "type",
  USER = "user",
}
export enum NewsLayoutEnum {
  MODERN = "modern",
  CLASSIC = "classic",
  IMMERSIVE = "immersive",
  HEADLINE = "headline",
}
export enum NotificationCategory {
  Comment = "comment",
  Task = "task",
  Reservation = "reservation",
  Meeting = "meeting",
  Registration = "registration",
  User = "user",
  Duty = "duty",
  System = "system",
  News = "news",
  Calendar = "calendar",
}
export enum NotificationChannel {
  InApp = "in_app",
  Push = "push",
  EmailDigest = "email_digest",
}
export enum PageLayoutEnum {
  DEFAULT = "default",
  WIDE = "wide",
  FOCUSED = "focused",
}
export enum PermissionScopeEnum {
  OWN = "OWN",
  PADALINYS = "PADALINYS",
  ALL = "ALL",
}
export enum SearchableModelEnum {
  NEWS = "NEWS",
  PAGE = "PAGE",
  DOCUMENT = "DOCUMENT",
  CALENDAR = "CALENDAR",
  PUBLIC_INSTITUTION = "PUBLIC_INSTITUTION",
  PUBLIC_MEETING = "PUBLIC_MEETING",
  PUBLIC_NEWS = "PUBLIC_NEWS",
  PUBLIC_PAGE = "PUBLIC_PAGE",
  MEETING = "MEETING",
  AGENDA_ITEM = "AGENDA_ITEM",
  RESOURCE = "RESOURCE",
  INSTITUTION = "INSTITUTION",
  USER = "USER",
  DUTY = "DUTY",
}
export enum SharepointConfigEnum {
  API_BASE_URL = "API_BASE_URL",
  DEFAULT_TIMEOUT = "DEFAULT_TIMEOUT",
  MAX_RETRIES = "MAX_RETRIES",
  RETRY_DELAY_MS = "RETRY_DELAY_MS",
  DEFAULT_BATCH_SIZE = "DEFAULT_BATCH_SIZE",
}
export enum SharepointFieldEnum {
  PADALINYS = "PADALINYS",
  TITLE = "TITLE",
  DATE = "DATE",
  EFFECTIVE_DATE = "EFFECTIVE_DATE",
  EXPIRATION_DATE = "EXPIRATION_DATE",
  LANGUAGE = "LANGUAGE",
  TURINYS = "TURINYS",
  SUMMARY = "SUMMARY",
}
export enum SharepointFolderEnum {
  GENERAL = "GENERAL",
  PADALINIAI = "PADALINIAI",
  TYPES = "TYPES",
  INSTITUTIONS = "INSTITUTIONS",
  MEETINGS = "MEETINGS",
}
export enum SharepointPermissionTypeEnum {
  VIEW = "VIEW",
  EDIT = "EDIT",
  OWNER = "OWNER",
}
export enum SharepointScopeEnum {
  ANONYMOUS = "ANONYMOUS",
  ORGANIZATION = "ORGANIZATION",
  USERS = "USERS",
}
export enum TenantType {
  Pagrindinis = "pagrindinis",
  Padalinys = "padalinys",
  Pkp = "pkp",
}
export enum VoteValue {
  Positive = "positive",
  Negative = "negative",
  Neutral = "neutral",
}
