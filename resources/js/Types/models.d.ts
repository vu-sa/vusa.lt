export {}
declare global {
  export namespace models {

    export interface Banner {
      // columns
      id: number
      title: unknown
      image_url: string
      link_url: string
      lang: unknown
      order: number
      is_active: number
      padalinys_id: number
      created_at: string
      updated_at: string
      // relations
      padalinys: Padalinys
    }

    export interface Calendar {
      // columns
      id: number
      date: string
      end_date: string|null
      title: string
      description: string|null
      location: unknown|null
      category: unknown|null
      url: unknown|null
      padalinys_id: number
      extra_attributes: string[]|null
      created_at: string
      updated_at: string
      registration_form_id: number|null
      // relations
      padalinys: Padalinys
      category_r: Category
      registration_form: RegistrationForm
      media: Medium[]
    }

    export interface Category {
      // columns
      id: number
      alias: unknown|null
      name: unknown
      description: string|null
      created_at: string
      updated_at: string
      // relations
      banners: Banner
    }

    export interface ChangelogItem {
      // columns
      id: number
      title: string[]
      date: string
      description: string[]
      permission_id: unknown|null
      // mutators
      translations: unknown
    }

    export interface Comment {
      // columns
      id: unknown
      parent_id: unknown|null
      comment: string
      decision: unknown|null
      user_id: unknown
      commentable_type: unknown
      commentable_id: unknown
      created_at: string
      updated_at: string
      deleted_at: string|null
      // relations
      commentable: Comment
      comments: Comment[]
      user: User
      activities: Activity[]
    }

    export interface Contact {
      // columns
      id: unknown
      name: unknown
      email: unknown|null
      phone: unknown|null
      profile_photo_path: unknown|null
      created_at: string
      updated_at: string
      deleted_at: string|null
      extra_attributes: string[]|null
      // relations
      duties: Duty[]
      commentable: Contact
      comments: Comment[]
      activities: Activity[]
    }

    export interface Content {
      // columns
      id: number
      created_at: string
      updated_at: string
      // relations
      parts: ContentPart[]
    }

    export interface ContentPart {
      // columns
      id: number
      content_id: number
      type: unknown
      json_content: string[]
      options: string[]|null
      order: number
      created_at: string
      updated_at: string
      // relations
      content: Content
    }

    export interface Doing {
      // columns
      id: unknown
      title: unknown
      drive_item_name: unknown|null
      state: unknown
      date: string
      extra_attributes: unknown|null
      created_at: string
      updated_at: string
      deleted_at: string|null
      // relations
      goals: Goal[]
      matters: Matter[]
      types: Type[]
      users: User[]
      comments: Comment[]
      commentable: Doing
      files: SharepointFile[]
      tasks: Task[]
      activities: Activity[]
    }

    export interface Duty {
      // columns
      id: unknown
      name: unknown
      description: string|null
      institution_id: unknown
      order: number
      email: unknown|null
      extra_attributes: string[]|null
      places_to_occupy: number|null
      created_at: string
      updated_at: string
      deleted_at: string|null
      // relations
      dutiables: Dutiable[]
      users: User[]
      contacts: Contact[]
      types: Type[]
      institution: Institution
      roles: Role[]
      permissions: Permission[]
      activities: Activity[]
      notifications: DatabaseNotification[]
    }

    export interface File {
    }

    export interface Goal {
      // columns
      id: unknown
      group_id: unknown|null
      padalinys_id: number
      title: unknown
      description: string|null
      start_date: string
      end_date: string|null
      created_at: string
      updated_at: string
      deleted_at: string|null
      // relations
      matters: Matter[]
      doings: Doing[]
      group: GoalGroup
      padalinys: Padalinys
      commentable: Goal
      comments: Comment[]
      files: SharepointFile[]
      tasks: Task[]
      activities: Activity[]
    }

    export interface GoalGroup {
      // columns
      id: unknown
      title: unknown
      description: string|null
      created_at: string
      updated_at: string
      deleted_at: string|null
      // relations
      goals: Goal[]
      activities: Activity[]
    }

    export interface Institution {
      // columns
      id: unknown
      parent_id: unknown|null
      name: unknown|null
      short_name: unknown|null
      alias: unknown
      description: string|null
      image_url: unknown|null
      padalinys_id: number|null
      created_at: string
      updated_at: string
      extra_attributes: string[]|null
      deleted_at: string|null
      // relations
      duties: Duty[]
      types: Type[]
      padalinys: Padalinys
      matters: Matter[]
      meetings: Meeting[]
      commentable: Institution
      comments: Comment[]
      outgoing_relationships: Relationship[]
      incoming_relationships: Relationship[]
      files: SharepointFile[]
      activities: Activity[]
    }

    export interface MainPage {
      // columns
      id: number
      link: unknown|null
      text: unknown|null
      image: unknown|null
      position: unknown
      order: number|null
      type: unknown|null
      is_active: unknown
      padalinys_id: number
      lang: unknown|null
      created_at: string
      updated_at: string
      // relations
      padalinys: Padalinys
    }

    export interface Matter {
      // columns
      id: unknown
      title: unknown
      description: string|null
      created_at: string
      updated_at: string
      deleted_at: string|null
      // relations
      institutions: Institution[]
      meetings: Meeting[]
      doings: Doing[]
      goals: Goal[]
      activities: Activity[]
    }

    export interface Meeting {
      // columns
      id: unknown
      title: unknown
      description: string|null
      start_time: string
      end_time: string|null
      created_at: string
      updated_at: string
      deleted_at: string|null
      // relations
      matters: Matter[]
      agenda_items: AgendaItem[]
      institutions: Institution[]
      comments: Comment[]
      commentable: Meeting
      files: SharepointFile[]
      tasks: Task[]
      activities: Activity[]
    }

    export interface Model {
    }

    export interface Navigation {
      // columns
      id: number
      parent_id: number
      padalinys_id: number
      name: unknown
      lang: unknown
      url: unknown
      order: number
      is_active: unknown
      created_at?: string
      updated_at?: string
      // relations
      user: User
      padalinys: Padalinys
    }

    export interface News {
      // columns
      id: number
      title: unknown
      category_id: number|null
      permalink: unknown|null
      short: unknown
      lang: unknown
      other_lang_id: number|null
      content_id: number
      image: unknown|null
      image_author: unknown|null
      important: unknown
      padalinys_id: number
      publish_time: string|null
      main_points: unknown|null
      read_more: unknown|null
      draft: unknown|null
      created_at: string
      updated_at: string
      deleted_at: string|null
      // relations
      user: User
      padalinys: Padalinys
      other_language_news: News
      tags: Tag[]
      content: Content
    }

    export interface Padalinys {
      // columns
      id: number
      type: unknown|null
      fullname: unknown
      shortname: unknown
      alias: unknown
      en: unknown
      phone: unknown|null
      email: unknown|null
      address: unknown|null
      shortname_vu: unknown
      // relations
      banners: Banner[]
      calendar: Calendar[]
      duties: Duty[]
      institutions: Institution[]
      news: News[]
      pages: Page[]
      users: User[]
      resources: Resource[]
    }

    export interface Page {
      // columns
      id: number
      title: unknown
      permalink: unknown|null
      lang: unknown
      other_lang_id: number|null
      content_id: number
      category_id: number|null
      is_active: unknown
      padalinys_id: number
      created_at: string
      updated_at: string
      deleted_at: string|null
      // relations
      padalinys: Padalinys
      category: Category
      content: Content
    }

    export interface Permission {
      // columns
      id: unknown
      name: unknown
      guard_name: unknown
      created_at: string|null
      updated_at: string|null
      // relations
      roles: Role[]
      users: User[]
      permissions: Permission[]
    }

    export interface AgendaItem {
      // columns
      id: unknown
      meeting_id: unknown
      matter_id: unknown|null
      created_at: string
      updated_at: string
      title: string
      start_time: unknown|null
      outcome: unknown|null
      // relations
      matter: Matter
      meeting: Meeting
      activities: Activity[]
    }

    export interface Doable {
      // columns
      doable_type: unknown
      doable_id: unknown
      doing_id: unknown
      created_at: string
      updated_at: string
      // relations
      doing: Doing
      user: User
    }

    export interface Dutiable {
      // columns
      id: unknown
      duty_id: unknown
      dutiable_id: unknown
      dutiable_type: unknown
      start_date: string
      end_date: string|null
      extra_attributes: Record<string, unknown>|null
      created_at: string
      updated_at: string
      // relations
      dutiable: Dutiable
      duty: Duty
      user: User
      contact: Contact
    }

    export interface GoalMatter {
      // columns
      goal_id: unknown
      matter_id: unknown
      created_at: string
      updated_at: string
      // relations
      goal: Goal
      matter: Matter
    }

    export interface Relationshipable {
      // columns
      id: number
      relationship_id: number
      relationshipable_type: unknown
      relationshipable_id: unknown
      related_model_id: unknown
      created_at: string
      updated_at: string
      // relations
      relationshipable: Relationshipable
      related_model: Relationshipable
      relationship: Relationship
    }

    export interface ReservationResource {
      // columns
      id: number
      reservation_id: unknown
      resource_id: unknown
      start_time: string|null
      end_time: string|null
      quantity: number
      state: unknown
      returned_at: string|null
      created_at: string
      updated_at: string
      deleted_at: string|null
      // mutators
      approvable: bool
      state_properties: unknown
      // relations
      reservation: Reservation
      resource: Resource
      commentable: ReservationResource
      comments: Comment[]
    }

    export interface SharepointFileable {
      // columns
      sharepoint_file_id: unknown
      fileable_type: unknown
      fileable_id: unknown
      created_at: string
      updated_at: string
      // relations
      fileable: SharepointFileable
      meeting: Meeting
      institution: Institution
      type: Type
    }

    export interface Registration {
      // columns
      id: number
      registration_form_id: number
      data: string[]
      created_at: string
      updated_at: string
      // relations
      registration_form: RegistrationForm
    }

    export interface RegistrationForm {
      // columns
      id: number
      user_id: number|null
      data: unknown
      created_at: string
      updated_at: string
      // relations
      registrations: Registration[]
    }

    export interface Relationship {
      // columns
      id: number
      name: unknown
      slug: unknown
      description: string|null
      type: unknown|null
      created_at: string
      updated_at: string
      // relations
      institutions: Institution[]
      relationshipables: Relationshipable[]
      types: Type[]
    }

    export interface Reservation {
      // columns
      id: unknown
      name: unknown
      description: string|null
      start_time: string
      end_time: string
      completed_at: string|null
      created_at: string
      updated_at: string
      deleted_at: string|null
      // relations
      resources: Resource[]
      users: User[]
      commentable: Reservation
      comments: Comment[]
      tasks: Task[]
      activities: Activity[]
    }

    export interface Resource {
      // columns
      id: unknown
      name: string[]
      description: string[]|null
      location: unknown|null
      capacity: number
      padalinys_id: number
      is_reservable: unknown
      created_at: string
      updated_at: string
      deleted_at: string|null
      // mutators
      translations: unknown
      // relations
      reservations: Reservation[]
      padalinys: Padalinys
      media: Medium[]
    }

    export interface Role {
      // columns
      id: unknown
      name: unknown
      guard_name: unknown
      created_at: string|null
      updated_at: string|null
      // relations
      duties: Duty[]
      attachable_types: Type[]
      types: Type[]
      permissions: Permission[]
      users: User[]
    }

    export interface RoleType {
      // columns
      id: number
      role_id: unknown
      type_id: number
      created_at: string|null
      updated_at: string|null
      // relations
      role: Role
      type: Type
    }

    export interface SaziningaiExam {
      // columns
      id: number
      uuid: unknown
      name: unknown|null
      email: unknown|null
      exam_type: unknown|null
      padalinys_id: number|null
      place: unknown|null
      duration: unknown|null
      subject_name: unknown|null
      exam_holders: number|null
      students_need: number|null
      phone: unknown|null
      created_at: string
      updated_at: string
      // relations
      padalinys: Padalinys
      flows: SaziningaiExamFlow[]
      observers: SaziningaiExamObserver[]
    }

    export interface SaziningaiExamFlow {
      // columns
      id: number
      exam_uuid: unknown
      start_time: string
      created_at: string
      updated_at: string
      // relations
      exam: SaziningaiExam
      observers: SaziningaiExamObserver[]
      padalinys: Padalinys
    }

    export interface SaziningaiExamObserver {
      // columns
      id: number
      exam_uuid: unknown
      name: unknown
      email: unknown|null
      phone: unknown
      flow: number
      created_at: string
      has_arrived: unknown
      phone_p: unknown|null
      updated_at: string
      padalinys_id: number
      // relations
      flow: SaziningaiExamFlow
      exam: SaziningaiExam
      padalinys: Padalinys
    }

    export interface SharepointFile {
      // columns
      sharepoint_id: unknown
      id: unknown
      // relations
      fileables: SharepointFileable[]
      types: Type[]
      institutions: Institution[]
      meetings: Meeting[]
      commentable: SharepointFile
      comments: Comment[]
    }

    export interface Tag {
      // columns
      id: number
      alias: unknown|null
      name: unknown
      description: string|null
      created_at: string
      updated_at: string
    }

    export interface Task {
      // columns
      id: unknown
      name: unknown
      description: string|null
      due_date: string|null
      taskable_type: unknown
      taskable_id: unknown
      completed_at: string|null
      created_at: string
      updated_at: string
      deleted_at: string|null
      // relations
      taskable: Task
      users: User[]
    }

    export interface Type {
      // columns
      id: number
      parent_id: number|null
      title: unknown|null
      model_type: unknown|null
      description: string|null
      slug: unknown|null
      extra_attributes: unknown|null
      created_at: string
      updated_at: string
      deleted_at: string|null
      // relations
      institutions: Institution[]
      duties: Duty[]
      doings: Doing[]
      roles: Role[]
      descendants: Type[]
      parent: Type
      outgoing_relationships: Relationship[]
      incoming_relationships: Relationship[]
      files: SharepointFile[]
      activities: Activity[]
    }

    export interface Typeable {
      // relations
      type: Type
      typeable: Typeable
    }

    export interface User {
      // columns
      id: unknown
      email: unknown
      phone: unknown|null
      name: unknown
      password?: unknown|null
      is_active: unknown
      email_verified_at?: string|null
      remember_token?: unknown|null
      last_action?: string|null
      last_changelog_check?: string|null
      microsoft_token?: string|null
      google_token?: unknown|null
      updated_at: string|null
      created_at: string
      profile_photo_path: unknown|null
      deleted_at: string|null
      // mutators
      impersonate: ImpersonateManager
      // relations
      banners: Banner[]
      calendar: Calendar[]
      doings: Doing[]
      duties: Duty[]
      dutiables: Dutiable[]
      tasks: Task[]
      reservations: Reservation[]
      roles: Role[]
      permissions: Permission[]
      activities: Activity[]
      notifications: DatabaseNotification[]
    }

  }
}

