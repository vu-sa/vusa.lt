/* eslint-disable no-secrets/no-secrets */
import Alert24Regular from "~icons/fluent/alert24-regular";
import BookQuestionMark24Regular from "~icons/fluent/book-question-mark24-regular";
import Bookmark24Regular from "~icons/fluent/bookmark24-regular";
import CalendarLtr24Regular from "~icons/fluent/calendar-ltr24-regular";
import Comment24Regular from "~icons/fluent/comment24-regular";
import Cube24Regular from "~icons/fluent/cube24-regular";
import DeviceMeetingRoomRemote24Regular from "~icons/fluent/device-meeting-room-remote24-regular";
import DocumentBulletList24Regular from "~icons/fluent/document-bullet-list24-regular";
import DocumentMultiple24Regular from "~icons/fluent/document-multiple24-regular";
import DocumentSave24Regular from "~icons/fluent/document-save24-regular";
import DocumentSettings20Regular from "~icons/fluent/document-settings20-regular";
import Flow20Regular from "~icons/fluent/flow20-regular";
import FlowchartCircle24Regular from "~icons/fluent/flowchart-circle24-regular";
import Grid24Regular from "~icons/fluent/grid24-regular";
import Home24Regular from "~icons/fluent/home24-regular";
import Image24Regular from "~icons/fluent/image24-regular";
import ImageArrowBack24Regular from "~icons/fluent/image-arrow-back24-regular";
import Important24Regular from "~icons/fluent/important24-regular";
import Navigation24Regular from "~icons/fluent/navigation24-regular";
import News24Regular from "~icons/fluent/news24-regular";
import Notebook24Regular from "~icons/fluent/notebook24-regular";
import PeopleSearch24Regular from "~icons/fluent/people-search24-regular";
import PeopleTeam24Regular from "~icons/fluent/people-team24-regular";
import Person24Regular from "~icons/fluent/person24-regular";
import PersonBoard24Regular from "~icons/fluent/person-board24-regular";
import PersonClock24Regular from "~icons/fluent/person-clock24-regular";
import PuzzlePiece24Regular from "~icons/fluent/puzzle-piece24-regular";
import ShieldKeyhole24Regular from "~icons/fluent/shield-keyhole24-regular";
import Sparkle24Regular from "~icons/fluent/sparkle24-regular";
import StarLineHorizontal324Regular from "~icons/fluent/star-line-horizontal-3-24-regular";
import Tag24Regular from "~icons/fluent/tag24-regular";
import TaskListSquareLtr24Regular from "~icons/fluent/task-list-square-ltr24-regular";
import TextBulletListSquare24Regular from "~icons/fluent/text-bullet-list-square24-regular";
import TextField24Regular from "~icons/fluent/text-field24-regular";

import type { Component } from "vue";
import type { FormEnum, OtherIconEnum } from "../otherEnums";
import type { ModelEnum } from "../enums";

const modelIcons: Record<keyof typeof ModelEnum, Component> = {
  AGENDA_ITEM: DocumentBulletList24Regular,
  BANNER: ImageArrowBack24Regular,
  CALENDAR: CalendarLtr24Regular,
  CATEGORY: TextBulletListSquare24Regular,
  CHANGELOG_ITEM: DocumentBulletList24Regular,
  COMMENT: Comment24Regular,
  DOCUMENT: DocumentMultiple24Regular,
  DOING: Important24Regular,
  DUTIABLE: PersonClock24Regular,
  DUTY: PuzzlePiece24Regular,
  FORM: DocumentBulletList24Regular,
  GOAL: StarLineHorizontal324Regular,
  GOAL_GROUP: Sparkle24Regular,
  INSTITUTION: PeopleTeam24Regular,
  MAIN_PAGE: Grid24Regular,
  MATTER: BookQuestionMark24Regular,
  MEETING: DeviceMeetingRoomRemote24Regular,
  NAVIGATION: Navigation24Regular,
  NEWS: News24Regular,
  PAGE: DocumentMultiple24Regular,
  PERMISSION: ShieldKeyhole24Regular,
  RELATIONSHIP: Flow20Regular,
  RELATIONSHIPABLE: FlowchartCircle24Regular,
  RESERVATION: Bookmark24Regular,
  RESOURCE: Cube24Regular,
  RESOURCE_CATEGORY: TextBulletListSquare24Regular,
  ROLE: PersonBoard24Regular,
  SHAREPOINT_FILE: DocumentMultiple24Regular,
  SHAREPOINT_FILEABLE: DocumentMultiple24Regular,
  TAG: Tag24Regular,
  TASK: TaskListSquareLtr24Regular,
  TENANT: PeopleSearch24Regular,
  TRAINING: Notebook24Regular,
  TYPE: DocumentSettings20Regular,
  USER: Person24Regular,
};

const formIcons: Record<keyof typeof FormEnum, Component> = {
  DATE: CalendarLtr24Regular,
  SAVE: DocumentSave24Regular,
  TITLE: TextField24Regular,
};

const otherIcons: Record<keyof typeof OtherIconEnum, Component> = {
  FILE: DocumentMultiple24Regular,
  HOME: Home24Regular,
  IMAGE: Image24Regular,
  NOTIFICATION: Alert24Regular,
};

export default { ...modelIcons, ...formIcons, ...otherIcons };
