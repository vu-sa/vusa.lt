/* eslint-disable no-secrets/no-secrets */
import type { FunctionalComponent } from 'vue';
import { X } from 'lucide-vue-next';

import type { FormEnum, OtherIconEnum } from '../otherEnums';
import type { ModelEnum } from '../enums';

import Alert24Filled from '~icons/fluent/alert24-filled';
import Bookmark24Filled from '~icons/fluent/bookmark24-filled';
import CalendarLtr24Filled from '~icons/fluent/calendar-ltr24-filled';
import Checkmark24Filled from '~icons/fluent/checkmark24-filled';
import CheckmarkCircle24Filled from '~icons/fluent/checkmark-circle24-filled';
import ChevronDown24Filled from '~icons/fluent/chevron-down24-filled';
import Add24Filled from '~icons/fluent/add24-filled';
import Comment24Filled from '~icons/fluent/comment24-filled';
import Cube24Filled from '~icons/fluent/cube24-filled';
import DeviceMeetingRoomRemote24Filled from '~icons/fluent/device-meeting-room-remote24-filled';
import DocumentBulletList24Filled from '~icons/fluent/document-bullet-list24-filled';
import DocumentMultiple24Filled from '~icons/fluent/document-multiple24-filled';
import DocumentSave24Filled from '~icons/fluent/document-save24-filled';
import DocumentSettings20Filled from '~icons/fluent/document-settings20-filled';
import Flow20Filled from '~icons/fluent/flow20-filled';
import FlowchartCircle24Filled from '~icons/fluent/flowchart-circle24-filled';
import Grid24Filled from '~icons/fluent/grid24-filled';
import Home24Filled from '~icons/fluent/home24-filled';
import Image24Filled from '~icons/fluent/image24-filled';
import ImageArrowBack24Filled from '~icons/fluent/image-arrow-back24-filled';
import Navigation24Filled from '~icons/fluent/navigation24-filled';
import News24Filled from '~icons/fluent/news24-filled';
import PeopleTeam24Filled from '~icons/fluent/people-team24-filled';
import Person24Filled from '~icons/fluent/person24-filled';
import PersonBoard24Filled from '~icons/fluent/person-board24-filled';
import PersonClock24Filled from '~icons/fluent/person-clock24-filled';
import PuzzlePiece24Filled from '~icons/fluent/puzzle-piece24-filled';
import Settings24Filled from '~icons/fluent/settings24-filled';
import ShieldKeyhole24Filled from '~icons/fluent/shield-keyhole24-filled';
import Sparkle24Filled from '~icons/fluent/sparkle24-filled';
import StarLineHorizontal324Filled from '~icons/fluent/star-line-horizontal-3-24-filled';
import Tag24Filled from '~icons/fluent/tag24-filled';
import TaskListSquareLtr24Filled from '~icons/fluent/task-list-square-ltr24-filled';
import TextBulletListSquare24Filled from '~icons/fluent/text-bullet-list-square24-filled';
import TextField24Filled from '~icons/fluent/text-field24-filled';

const modelIcons: Record<keyof typeof ModelEnum, FunctionalComponent> = {
  AGENDA_ITEM: DocumentBulletList24Filled,
  BANNER: ImageArrowBack24Filled,
  CALENDAR: CalendarLtr24Filled,
  CATEGORY: TextBulletListSquare24Filled,
  COMMENT: Comment24Filled,
  DOCUMENT: DocumentMultiple24Filled,
  DUTIABLE: PersonClock24Filled,
  DUTY: PuzzlePiece24Filled,
  INSTITUTION: PeopleTeam24Filled,
  MEETING: DeviceMeetingRoomRemote24Filled,
  NAVIGATION: Navigation24Filled,
  NEWS: News24Filled,
  QUICK_LINK: Grid24Filled,
  PAGE: DocumentMultiple24Filled,
  PERMISSION: ShieldKeyhole24Filled,
  RELATIONSHIP: Flow20Filled,
  RELATIONSHIPABLE: FlowchartCircle24Filled,
  RESERVATION: Bookmark24Filled,
  RESOURCE: Cube24Filled,
  RESOURCE_CATEGORY: TextBulletListSquare24Filled,
  ROLE: PersonBoard24Filled,
  SHAREPOINT_FILE: DocumentMultiple24Filled,
  SHAREPOINT_FILEABLE: DocumentMultiple24Filled,
  TAG: Tag24Filled,
  TASK: TaskListSquareLtr24Filled,
  TENANT: PeopleTeam24Filled,
  TYPE: DocumentSettings20Filled,
  USER: Person24Filled,
};

const formIcons: Record<keyof typeof FormEnum, FunctionalComponent> = {
  DATE: CalendarLtr24Filled,
  SAVE: DocumentSave24Filled,
  TITLE: TextField24Filled,
};

const otherIcons: Record<keyof typeof OtherIconEnum, FunctionalComponent> = {
  FILE: DocumentMultiple24Filled,
  HOME: Home24Filled,
  IMAGE: Image24Filled,
  NOTIFICATION: Alert24Filled,
  PLUS: Add24Filled,
  CHECK: Checkmark24Filled,
  CHECK_CIRCLE: CheckmarkCircle24Filled,
  CALENDAR: CalendarLtr24Filled,
  CHEVRON_DOWN: ChevronDown24Filled,
  CLOSE: X,
  SETTING: Settings24Filled,
};

export default { ...modelIcons, ...formIcons, ...otherIcons };
