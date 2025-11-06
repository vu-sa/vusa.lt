// Tree-shakable model icons with regular and filled variants

import type { Component } from "vue";
import type { ModelEnum } from "../../Types/enums";

// =============================================================================
// REGULAR ICONS - Import each icon only ONCE
// =============================================================================
import DocumentBulletList24Regular from "~icons/fluent/document-bullet-list24-regular";
import ImageArrowBack24Regular from "~icons/fluent/image-arrow-back24-regular";
import CalendarLtr24Regular from "~icons/fluent/calendar-ltr24-regular";
import TextBulletListSquare24Regular from "~icons/fluent/text-bullet-list-square24-regular";
import Comment24Regular from "~icons/fluent/comment24-regular";
import DocumentMultiple24Regular from "~icons/fluent/document-multiple24-regular";
import PersonClock24Regular from "~icons/fluent/person-clock24-regular";
import PuzzlePiece24Regular from "~icons/fluent/puzzle-piece24-regular";
import PeopleTeam24Regular from "~icons/fluent/people-team24-regular";
import DeviceMeetingRoomRemote24Regular from "~icons/fluent/device-meeting-room-remote24-regular";
import Navigation24Regular from "~icons/fluent/navigation24-regular";
import News24Regular from "~icons/fluent/news24-regular";
import ShieldKeyhole24Regular from "~icons/fluent/shield-keyhole24-regular";
import Grid24Regular from "~icons/fluent/grid24-regular";
import Flow20Regular from "~icons/fluent/flow20-regular";
import FlowchartCircle24Regular from "~icons/fluent/flowchart-circle24-regular";
import Bookmark24Regular from "~icons/fluent/bookmark24-regular";
import Cube24Regular from "~icons/fluent/cube24-regular";
import PersonBoard24Regular from "~icons/fluent/person-board24-regular";
import BookOpenGlobe24Regular from "~icons/fluent/book-open-globe24-regular";
import Tag24Regular from "~icons/fluent/tag24-regular";
import TaskListSquareLtr24Regular from "~icons/fluent/task-list-square-ltr24-regular";
import PeopleSearch24Regular from "~icons/fluent/people-search24-regular";
import Notebook24Regular from "~icons/fluent/notebook24-regular";
import DocumentSettings20Regular from "~icons/fluent/document-settings20-regular";
import Person24Regular from "~icons/fluent/person24-regular";
import Alert24Regular from "~icons/fluent/alert24-regular";

// =============================================================================
// FILLED ICONS - Import each icon only ONCE
// =============================================================================
import DocumentBulletList24Filled from "~icons/fluent/document-bullet-list24-filled";
import ImageArrowBack24Filled from "~icons/fluent/image-arrow-back24-filled";
import CalendarLtr24Filled from "~icons/fluent/calendar-ltr24-filled";
import TextBulletListSquare24Filled from "~icons/fluent/text-bullet-list-square24-filled";
import Comment24Filled from "~icons/fluent/comment24-filled";
import DocumentMultiple24Filled from "~icons/fluent/document-multiple24-filled";
import PersonClock24Filled from "~icons/fluent/person-clock24-filled";
import PuzzlePiece24Filled from "~icons/fluent/puzzle-piece24-filled";
import PeopleTeam24Filled from "~icons/fluent/people-team24-filled";
import DeviceMeetingRoomRemote24Filled from "~icons/fluent/device-meeting-room-remote24-filled";
import Navigation24Filled from "~icons/fluent/navigation24-filled";
import News24Filled from "~icons/fluent/news24-filled";
import ShieldKeyhole24Filled from "~icons/fluent/shield-keyhole24-filled";
import Grid24Filled from "~icons/fluent/grid24-filled";
import Flow20Filled from "~icons/fluent/flow20-filled";
import FlowchartCircle24Filled from "~icons/fluent/flowchart-circle24-filled";
import Bookmark24Filled from "~icons/fluent/bookmark24-filled";
import Cube24Filled from "~icons/fluent/cube24-filled";
import PersonBoard24Filled from "~icons/fluent/person-board24-filled";
import BookOpenGlobe24Filled from "~icons/fluent/book-open-globe24-filled";
import Tag24Filled from "~icons/fluent/tag24-filled";
import TaskListSquareLtr24Filled from "~icons/fluent/task-list-square-ltr24-filled";
import Notebook24Filled from "~icons/fluent/notebook24-filled";
import DocumentSettings20Filled from "~icons/fluent/document-settings20-filled";
import Person24Filled from "~icons/fluent/person24-filled";
import Alert24Filled from "~icons/fluent/alert24-filled";

// =============================================================================
// TREE-SHAKABLE EXPORTS - Clean, concise naming
// =============================================================================
export const AgendaItemIcon = DocumentBulletList24Regular;
export const BannerIcon = ImageArrowBack24Regular;
export const CalendarIcon = CalendarLtr24Regular;
export const CategoryIcon = TextBulletListSquare24Regular;
export const ChangelogItemIcon = DocumentBulletList24Regular;
export const CommentIcon = Comment24Regular;
export const DocumentIcon = DocumentMultiple24Regular;
export const DutiableIcon = PersonClock24Regular;
export const DutyIcon = PuzzlePiece24Regular;
export const FileIcon = DocumentMultiple24Regular;
export const FormIcon = DocumentBulletList24Regular;
export const InstitutionIcon = PeopleTeam24Regular;
export const MeetingIcon = DeviceMeetingRoomRemote24Regular;
export const MembershipIcon = PersonClock24Regular;
export const NavigationIcon = Navigation24Regular;
export const NewsIcon = News24Regular;
export const PageIcon = DocumentMultiple24Regular;
export const PermissionIcon = ShieldKeyhole24Regular;
export const ProblemIcon = Alert24Regular;
export const QuickLinkIcon = Grid24Regular;
export const RelationshipIcon = Flow20Regular;
export const RelationshipableIcon = FlowchartCircle24Regular;
export const ReservationIcon = Bookmark24Regular;
export const ReservationResourceIcon = Cube24Regular;
export const ResourceIcon = Cube24Regular;
export const RoleIcon = PersonBoard24Regular;
export const SharepointFileIcon = DocumentMultiple24Regular;
export const SharepointFileableIcon = DocumentMultiple24Regular;
export const StudyProgramIcon = BookOpenGlobe24Regular;
export const TagIcon = Tag24Regular;
export const TaskIcon = TaskListSquareLtr24Regular;
export const TenantIcon = PeopleSearch24Regular;
export const TrainingIcon = Notebook24Regular;
export const TypeIcon = DocumentSettings20Regular;
export const UserIcon = Person24Regular;

export const AgendaItemIconFilled = DocumentBulletList24Filled;
export const BannerIconFilled = ImageArrowBack24Filled;
export const CalendarIconFilled = CalendarLtr24Filled;
export const CategoryIconFilled = TextBulletListSquare24Filled;
export const ChangelogItemIconFilled = DocumentBulletList24Filled;
export const CommentIconFilled = Comment24Filled;
export const DocumentIconFilled = DocumentMultiple24Filled;
export const DutiableIconFilled = PersonClock24Filled;
export const DutyIconFilled = PuzzlePiece24Filled;
export const FileIconFilled = DocumentMultiple24Filled;
export const FormIconFilled = DocumentBulletList24Filled;
export const InstitutionIconFilled = PeopleTeam24Filled;
export const MeetingIconFilled = DeviceMeetingRoomRemote24Filled;
export const MembershipIconFilled = PersonClock24Filled;
export const NavigationIconFilled = Navigation24Filled;
export const NewsIconFilled = News24Filled;
export const PageIconFilled = DocumentMultiple24Filled;
export const PermissionIconFilled = ShieldKeyhole24Filled;
export const ProblemIconFilled = Alert24Filled;
export const QuickLinkIconFilled = Grid24Filled;
export const RelationshipIconFilled = Flow20Filled;
export const RelationshipableIconFilled = FlowchartCircle24Filled;
export const ReservationIconFilled = Bookmark24Filled;
export const ReservationResourceIconFilled = Cube24Filled;
export const ResourceIconFilled = Cube24Filled;
export const RoleIconFilled = PersonBoard24Filled;
export const SharepointFileIconFilled = DocumentMultiple24Filled;
export const SharepointFileableIconFilled = DocumentMultiple24Filled;
export const StudyProgramIconFilled = BookOpenGlobe24Filled;
export const TagIconFilled = Tag24Filled;
export const TaskIconFilled = TaskListSquareLtr24Filled;
export const TenantIconFilled = PeopleSearch24Regular; // Note: No filled variant available
export const TrainingIconFilled = Notebook24Filled;
export const TypeIconFilled = DocumentSettings20Filled;
export const UserIconFilled = Person24Filled;


// =============================================================================
// DYNAMIC ACCESS MAPPINGS (for backward compatibility and helper functions)
// =============================================================================

const modelIconMappingRegular: Record<keyof typeof ModelEnum, Component> = {
  AGENDA_ITEM: AgendaItemIcon,
  BANNER: BannerIcon,
  CALENDAR: CalendarIcon,
  CATEGORY: CategoryIcon,
  CHANGELOG_ITEM: ChangelogItemIcon,
  COMMENT: CommentIcon,
  DOCUMENT: DocumentIcon,
  DUTIABLE: DutiableIcon,
  DUTY: DutyIcon,
  FILE: FileIcon,
  FORM: FormIcon,
  INSTITUTION: InstitutionIcon,
  MEETING: MeetingIcon,
  MEMBERSHIP: MembershipIcon,
  NAVIGATION: NavigationIcon,
  NEWS: NewsIcon,
  PAGE: PageIcon,
  PERMISSION: PermissionIcon,
  PROBLEM: ProblemIcon,
  QUICK_LINK: QuickLinkIcon,
  RELATIONSHIP: RelationshipIcon,
  RELATIONSHIPABLE: RelationshipableIcon,
  RESERVATION: ReservationIcon,
  RESERVATION_RESOURCE: ReservationResourceIcon,
  RESOURCE: ResourceIcon,
  ROLE: RoleIcon,
  SHAREPOINT_FILE: SharepointFileIcon,
  SHAREPOINT_FILEABLE: SharepointFileableIcon,
  STUDY_PROGRAM: StudyProgramIcon,
  TAG: TagIcon,
  TASK: TaskIcon,
  TENANT: TenantIcon,
  TRAINING: TrainingIcon,
  TYPE: TypeIcon,
  USER: UserIcon,
};

const modelIconMappingFilled: Record<keyof typeof ModelEnum, Component> = {
  AGENDA_ITEM: AgendaItemIconFilled,
  BANNER: BannerIconFilled,
  CALENDAR: CalendarIconFilled,
  CATEGORY: CategoryIconFilled,
  CHANGELOG_ITEM: ChangelogItemIconFilled,
  COMMENT: CommentIconFilled,
  DOCUMENT: DocumentIconFilled,
  DUTIABLE: DutiableIconFilled,
  DUTY: DutyIconFilled,
  FILE: FileIconFilled,
  FORM: FormIconFilled,
  INSTITUTION: InstitutionIconFilled,
  MEETING: MeetingIconFilled,
  MEMBERSHIP: MembershipIconFilled,
  NAVIGATION: NavigationIconFilled,
  NEWS: NewsIconFilled,
  PAGE: PageIconFilled,
  PERMISSION: PermissionIconFilled,
  PROBLEM: ProblemIconFilled,
  QUICK_LINK: QuickLinkIconFilled,
  RELATIONSHIP: RelationshipIconFilled,
  RELATIONSHIPABLE: RelationshipableIconFilled,
  RESERVATION: ReservationIconFilled,
  RESERVATION_RESOURCE: ReservationResourceIconFilled,
  RESOURCE: ResourceIconFilled,
  ROLE: RoleIconFilled,
  SHAREPOINT_FILE: SharepointFileIconFilled,
  SHAREPOINT_FILEABLE: SharepointFileableIconFilled,
  STUDY_PROGRAM: StudyProgramIconFilled,
  TAG: TagIconFilled,
  TASK: TaskIconFilled,
  TENANT: TenantIconFilled,
  TRAINING: TrainingIconFilled,
  TYPE: TypeIconFilled,
  USER: UserIconFilled,
};

// =============================================================================
// HELPER FUNCTIONS
// =============================================================================

/**
 * Get model icon by enum key with variant support
 * @param modelKey - The model enum key
 * @param variant - Icon variant ('regular' or 'filled')
 * @returns Vue component for the icon
 */
export function getModelIcon(
  modelKey: keyof typeof ModelEnum,
  variant: 'regular' | 'filled' = 'regular'
): Component {
  return variant === 'filled' 
    ? modelIconMappingFilled[modelKey] 
    : modelIconMappingRegular[modelKey];
}

const modelIconMapping = modelIconMappingRegular;

// Export mappings for external use
export { 
  modelIconMapping, 
  modelIconMappingRegular, 
  modelIconMappingFilled 
};
