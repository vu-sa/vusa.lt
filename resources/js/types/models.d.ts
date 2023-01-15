/**
 * This file is auto generated using 'php artisan typescript:generate'
 *
 * Changes to this file will be lost when the command is run again
 */

declare namespace App.Models {
  export interface Banner {
    id: number;
    title: string;
    image_url: string;
    link_url: string;
    lang: string;
    order: number;
    is_active: number;
    padalinys_id: number;
    created_at: any;
    updated_at: any;
    padalinys?: App.Models.Padalinys | null;
  }

  export interface Calendar {
    id: number;
    date: string;
    end_date: string | null;
    title: string;
    description: string | null;
    location: string | null;
    category: string | null;
    url: string | null;
    padalinys_id: number;
    extra_attributes: string | null;
    created_at: any;
    updated_at: any;
    registration_form_id: number | null;
    padalinys?: App.Models.Padalinys | null;
    registration_form?: App.Models.RegistrationForm | null;
  }

  export interface Category {
    id: number;
    alias: string | null;
    name: string;
    description: string | null;
    created_at: any;
    updated_at: any;
    banners?: App.Models.Banner | null;
  }

  export interface Comment {
    id: string;
    parent_id: string | null;
    comment: string;
    user_id: string;
    commentable_type: string;
    commentable_id: string;
    created_at: any;
    updated_at: any;
    deleted_at: any | null;
    commentable?: any | null;
    comments?: Array<App.Models.Comment> | null;
    user?: App.Models.User | null;
    comments_count?: number | null;
  }

  export interface Contact {
    id: string;
    name: string;
    email: string | null;
    phone: string | null;
    profile_photo_path: string | null;
    created_at: any;
    updated_at: any;
    deleted_at: any | null;
    extra_attributes: string | null;
    duties?: Array<App.Models.Duty> | null;
    duties_count?: number | null;
  }

  export interface Doing {
    id: string;
    title: string;
    status: string;
    date: string;
    extra_attributes: string | null;
    created_at: any;
    updated_at: any;
    deleted_at: any | null;
    doables?: Array<App.Models.Pivots.Doable> | null;
    goals?: Array<App.Models.Goal> | null;
    matters?: Array<App.Models.Matter> | null;
    types?: Array<App.Models.Type> | null;
    documents?: Array<App.Models.SharepointDocument> | null;
    tasks?: Array<App.Models.Task> | null;
    users?: Array<App.Models.User> | null;
    doables_count?: number | null;
    goals_count?: number | null;
    matters_count?: number | null;
    types_count?: number | null;
    documents_count?: number | null;
    tasks_count?: number | null;
    users_count?: number | null;
  }

  export interface Duty {
    id: string;
    name: string;
    description: string | null;
    institution_id: string;
    order: number;
    email: string | null;
    extra_attributes: string | null;
    places_to_occupy: number | null;
    created_at: any;
    updated_at: any;
    deleted_at: any | null;
    dutiables?: Array<App.Models.Pivots.Dutiable> | null;
    users?: Array<App.Models.User> | null;
    contacts?: Array<App.Models.Contact> | null;
    matters?: any | null;
    types?: Array<App.Models.Type> | null;
    institution?: App.Models.Institution | null;
    padaliniai?: any | null;
    dutiables_count?: number | null;
    users_count?: number | null;
    contacts_count?: number | null;
    types_count?: number | null;
  }

  export interface Goal {
    id: string;
    group_id: string | null;
    padalinys_id: number;
    title: string;
    description: string | null;
    start_date: string;
    end_date: string | null;
    created_at: any;
    updated_at: any;
    deleted_at: any | null;
    matters?: Array<App.Models.Matter> | null;
    doings?: Array<App.Models.Doing> | null;
    group?: App.Models.GoalGroup | null;
    padalinys?: App.Models.Padalinys | null;
    matters_count?: number | null;
    doings_count?: number | null;
  }

  export interface GoalGroup {
    id: string;
    title: string;
    description: string | null;
    created_at: any;
    updated_at: any;
    deleted_at: any | null;
    goals?: Array<App.Models.Goal> | null;
    goals_count?: number | null;
  }

  export interface Institution {
    id: string;
    parent_id: string | null;
    name: string | null;
    short_name: string | null;
    alias: string;
    description: string | null;
    image_url: string | null;
    padalinys_id: number | null;
    created_at: any;
    updated_at: any;
    extra_attributes: string | null;
    deleted_at: any | null;
    duties?: Array<App.Models.Duty> | null;
    types?: Array<App.Models.Type> | null;
    padalinys?: App.Models.Padalinys | null;
    matters?: Array<App.Models.Matter> | null;
    meetings?: Array<App.Models.Meeting> | null;
    users?: any | null;
    documents?: Array<App.Models.SharepointDocument> | null;
    duties_count?: number | null;
    types_count?: number | null;
    matters_count?: number | null;
    meetings_count?: number | null;
    documents_count?: number | null;
  }

  export interface MainPage {
    id: number;
    user_id: number | null;
    link: string | null;
    text: string | null;
    image: string | null;
    position: string;
    order: number | null;
    type: string | null;
    is_active: boolean;
    padalinys_id: number;
    lang: string | null;
    created_at: any;
    updated_at: any;
    padalinys?: App.Models.Padalinys | null;
  }

  export interface Matter {
    id: string;
    title: string;
    description: string | null;
    created_at: any;
    updated_at: any;
    deleted_at: any | null;
    institutions?: Array<App.Models.Institution> | null;
    meetings?: Array<App.Models.Meeting> | null;
    doings?: Array<App.Models.Doing> | null;
    goals?: Array<App.Models.Goal> | null;
    padaliniai?: any | null;
    institutions_count?: number | null;
    meetings_count?: number | null;
    doings_count?: number | null;
    goals_count?: number | null;
  }

  export interface Meeting {
    id: string;
    description: string | null;
    start_time: string;
    end_time: string | null;
    created_at: any;
    updated_at: any;
    deleted_at: any | null;
    matters?: Array<App.Models.Matter> | null;
    agenda_items?: Array<App.Models.Pivots.AgendaItem> | null;
    institutions?: Array<App.Models.Institution> | null;
    documents?: Array<App.Models.SharepointDocument> | null;
    tasks?: Array<App.Models.Task> | null;
    users?: any | null;
    padaliniai?: any | null;
    matters_count?: number | null;
    agenda_items_count?: number | null;
    institutions_count?: number | null;
    documents_count?: number | null;
    tasks_count?: number | null;
  }

  export interface Navigation {
    id: number;
    parent_id: number;
    padalinys_id: number;
    name: string;
    lang: string;
    url: string;
    order: number;
    is_active: boolean;
    created_at: any;
    updated_at: any;
    user?: App.Models.User | null;
    padalinys?: App.Models.Padalinys | null;
  }

  export interface News {
    id: number;
    user_id: number | null;
    title: string;
    category_id: number | null;
    permalink: string | null;
    short: string;
    text: string;
    lang: string;
    other_lang_id: number | null;
    image: string | null;
    image_author: string | null;
    important: boolean;
    padalinys_id: number;
    publish_time: any | null;
    main_points: string | null;
    read_more: string | null;
    draft: boolean | null;
    created_at: any;
    updated_at: any;
    user?: App.Models.User | null;
    padalinys?: App.Models.Padalinys | null;
    tags?: Array<App.Models.Tag> | null;
    tags_count?: number | null;
  }

  export interface Padalinys {
    id: number;
    type: string | null;
    fullname: string;
    shortname: string;
    alias: string;
    en: boolean;
    phone: string | null;
    email: string | null;
    address: string | null;
    shortname_vu: string;
    banners?: Array<App.Models.Banner> | null;
    calendar?: Array<App.Models.Calendar> | null;
    duties?: Array<App.Models.Duty> | null;
    institutions?: Array<App.Models.Institution> | null;
    news?: Array<App.Models.News> | null;
    pages?: Array<App.Models.Page> | null;
    users?: Array<App.Models.User> | null;
    banners_count?: number | null;
    calendar_count?: number | null;
    duties_count?: number | null;
    institutions_count?: number | null;
    news_count?: number | null;
    pages_count?: number | null;
    users_count?: number | null;
  }

  export interface Page {
    id: number;
    user_id: number | null;
    title: string;
    permalink: string | null;
    text: string;
    lang: string;
    other_lang_id: number | null;
    category_id: number | null;
    is_active: boolean;
    padalinys_id: number;
    created_at: any;
    updated_at: any;
    padalinys?: App.Models.Padalinys | null;
    category?: App.Models.Category | null;
  }

  export interface Permission {
    id: string;
    name: string;
    guard_name: string;
    created_at: any | null;
    updated_at: any | null;
    roles?: Array<App.Models.Role> | null;
    users?: Array<App.Models.User> | null;
    permissions?: Array<App.Models.Permission> | null;
    roles_count?: number | null;
    users_count?: number | null;
    permissions_count?: number | null;
  }

  export interface Registration {
    id: number;
    registration_form_id: number;
    data: string;
    created_at: any;
    updated_at: any;
    registration_form?: App.Models.RegistrationForm | null;
  }

  export interface RegistrationForm {
    id: number;
    user_id: number | null;
    data: string;
    created_at: any;
    updated_at: any;
    registrations?: Array<App.Models.Registration> | null;
    registrations_count?: number | null;
  }

  export interface Relationship {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    type: string | null;
    created_at: any;
    updated_at: any;
    institutions?: Array<App.Models.Institution> | null;
    relationshipables?: Array<App.Models.Pivots.Relationshipable> | null;
    types?: Array<App.Models.Type> | null;
    institutions_count?: number | null;
    relationshipables_count?: number | null;
    types_count?: number | null;
  }

  export interface Role {
    id: string;
    name: string;
    guard_name: string;
    created_at: any | null;
    updated_at: any | null;
    duties?: Array<App.Models.Duty> | null;
    users_through_duties?: any | null;
    permissions?: Array<App.Models.Permission> | null;
    users?: Array<App.Models.User> | null;
    duties_count?: number | null;
    permissions_count?: number | null;
    users_count?: number | null;
  }

  export interface SaziningaiExam {
    id: number;
    uuid: string;
    name: string | null;
    email: string | null;
    exam_type: string | null;
    padalinys_id: number | null;
    place: string | null;
    duration: string | null;
    subject_name: string | null;
    exam_holders: number | null;
    students_need: number | null;
    phone: string | null;
    created_at: any;
    updated_at: any;
    padalinys?: App.Models.Padalinys | null;
    flows?: Array<App.Models.SaziningaiExamFlow> | null;
    observers?: Array<App.Models.SaziningaiExamObserver> | null;
    flows_count?: number | null;
    observers_count?: number | null;
  }

  export interface SaziningaiExamFlow {
    id: number;
    exam_uuid: string;
    start_time: string;
    created_at: any;
    updated_at: any;
    exam?: App.Models.SaziningaiExam | null;
    observers?: Array<App.Models.SaziningaiExamObserver> | null;
    padalinys?: App.Models.Padalinys | null;
    observers_count?: number | null;
  }

  export interface SaziningaiExamObserver {
    id: number;
    exam_uuid: string;
    name: string;
    email: string | null;
    phone: string;
    flow: number;
    created_at: string;
    has_arrived: string;
    phone_p: string | null;
    updated_at: any;
    padalinys_id: number;
    flow?: App.Models.SaziningaiExamFlow | null;
    exam?: App.Models.SaziningaiExam | null;
    padalinys?: App.Models.Padalinys | null;
  }

  export interface SharepointDocument {
    sharepoint_id: string;
    documentable_type: string;
    documentable_id: string;
    id: string;
  }

  export interface Tag {
    id: number;
    alias: string | null;
    name: string;
    description: string | null;
    created_at: any;
    updated_at: any;
  }

  export interface Task {
    id: string;
    name: string;
    description: string | null;
    due_date: string | null;
    taskable_type: string;
    taskable_id: string;
    completed_at: any | null;
    created_at: any;
    updated_at: any;
    deleted_at: any | null;
    taskable?: any | null;
    users?: Array<App.Models.User> | null;
    padaliniai?: any | null;
    users_count?: number | null;
  }

  export interface Type {
    id: number;
    parent_id: number | null;
    title: string | null;
    model_type: string | null;
    description: string | null;
    slug: string | null;
    extra_attributes: string | null;
    created_at: any;
    updated_at: any;
    deleted_at: any | null;
    institutions?: Array<App.Models.Institution> | null;
    duties?: Array<App.Models.Duty> | null;
    doings?: Array<App.Models.Doing> | null;
    documents?: Array<App.Models.SharepointDocument> | null;
    institutions_count?: number | null;
    duties_count?: number | null;
    doings_count?: number | null;
    documents_count?: number | null;
  }

  export interface User {
    id: string;
    email: string;
    phone: string | null;
    name: string;
    password: string | null;
    is_active: boolean;
    email_verified_at: any | null;
    remember_token: string | null;
    last_action: any | null;
    microsoft_token: string | null;
    google_token: string | null;
    updated_at: any | null;
    created_at: any;
    profile_photo_path: string | null;
    deleted_at: any | null;
    banners?: Array<App.Models.Banner> | null;
    calendar?: Array<App.Models.Calendar> | null;
    doings?: Array<App.Models.Doing> | null;
    duties?: Array<App.Models.Duty> | null;
    padaliniai?: any | null;
    tasks?: Array<App.Models.Task> | null;
    institutions?: any | null;
    banners_count?: number | null;
    calendar_count?: number | null;
    doings_count?: number | null;
    duties_count?: number | null;
    tasks_count?: number | null;
  }
}

declare namespace App.Models.Pivots {
  export interface AgendaItem {
    id: string;
    meeting_id: string;
    matter_id: string | null;
    created_at: any;
    updated_at: any;
    title: string;
    start_time: number | null;
    outcome: string | null;
  }

  export interface Doable {
    doable_type: string;
    doable_id: string;
    doing_id: string;
    created_at: any;
    updated_at: any;
  }

  export interface Dutiable {
    duty_id: string;
    dutiable_id: string;
    dutiable_type: string;
    start_date: string;
    end_date: string | null;
    extra_attributes: string | null;
    created_at: any;
    updated_at: any;
    dutiable?: any | null;
  }

  export interface GoalMatter {
    goal_id: string;
    matter_id: string;
    created_at: any;
    updated_at: any;
  }

  export interface Relationshipable {
    id: number;
    relationship_id: number;
    relationshipable_type: string;
    relationshipable_id: string;
    related_model_id: string;
    created_at: any;
    updated_at: any;
    relationshipable?: any | null;
    related_model?: any | null;
    relationship?: App.Models.Relationship | null;
  }
}
