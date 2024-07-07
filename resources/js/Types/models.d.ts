export {}
declare global {
  export namespace models {

    export interface Banner {
      // columns
      id: number
      title: string
      image_url: string
      link_url: string
      lang: string
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
      location: string|null
      category: string|null
      url: string|null
      padalinys_id: number
      extra_attributes: string[]|null
      created_at: string
      updated_at: string
      registration_form_id: number|null
      // relations
      padalinys: Padalinys
      registration_form: RegistrationForm
      media: Medium[]
    }

    export interface Category {
      // columns
      id: number
      alias: string|null
      name: string
      description: string|null
      created_at: string
      updated_at: string
      // relations
      banners: Banner
      pages: Page[]
    }

    export interface ChangelogItem {
      // columns
      id: number
      title: string[]
      date: string
      description: string[]
      permission_id: string|null
      // mutators
      translations: unknown
    }

    export interface Comment {
      // columns
      id: string
      parent_id: string|null
      comment: string
      decision: string|null
      user_id: string
      commentable_type: string
      commentable_id: string
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
      id: string
      name: string
      email: string|null
      phone: string|null
      profile_photo_path: string|null
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
      type: string
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
      id: string
      title: string
      drive_item_name: string|null
      state: unknown
      date: string
      extra_attributes: string|null
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
      id: string
      name: string
      description: string|null
      institution_id: string
      order: number
      email: string|null
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
      id: string
      group_id: string|null
      padalinys_id: number
      title: string
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
      id: string
      title: string
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
      id: string
      parent_id: string|null
      name: string|null
      short_name: string|null
      alias: string
      description: string|null
      image_url: string|null
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
      link: string|null
      text: string|null
      image: string|null
      position: string
      order: number|null
      type: string|null
      is_active: boolean
      padalinys_id: number
      lang: string|null
      created_at: string
      updated_at: string
      // relations
      padalinys: Padalinys
    }

    export interface Matter {
      // columns
      id: string
      title: string
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
      id: string
      title: string
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
      name: string
      lang: string
      url: string
      order: number
      is_active: boolean
      extra_attributes: string[]|null
      created_at?: string
      updated_at?: string
      // relations
      user: User
      padalinys: Padalinys
    }

    export interface News {
      // columns
      id: number
      title: string
      category_id: number|null
      permalink: string|null
      short: string
      lang: string
      other_lang_id: number|null
      content_id: number
      image: string|null
      image_author: string|null
      important: boolean
      padalinys_id: number
      publish_time: string|null
      main_points: string|null
      read_more: string|null
      draft: boolean|null
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
      type: string|null
      fullname: string
      shortname: string
      alias: string
      en: boolean
      phone: string|null
      email: string|null
      address: string|null
      shortname_vu: string
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
      title: string
      permalink: string|null
      lang: string
      other_lang_id: number|null
      content_id: number
      category_id: number|null
      is_active: boolean
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
      id: string
      name: string
      guard_name: string
      created_at: string|null
      updated_at: string|null
      // relations
      roles: Role[]
      users: User[]
      permissions: Permission[]
    }

    export interface AgendaItem {
      // columns
      id: string
      meeting_id: string
      matter_id: string|null
      created_at: string
      updated_at: string
      title: string
      start_time: string|null
      outcome: string|null
      // relations
      matter: Matter
      meeting: Meeting
      activities: Activity[]
    }

    export interface Doable {
      // columns
      doable_type: string
      doable_id: string
      doing_id: string
      created_at: string
      updated_at: string
      // relations
      doing: Doing
      user: User
    }

    export interface Dutiable {
      // columns
      id: string
      duty_id: string
      dutiable_id: string
      dutiable_type: string
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
      goal_id: string
      matter_id: string
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
      relationshipable_type: string
      relationshipable_id: string
      related_model_id: string
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
      reservation_id: string
      resource_id: string
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
      sharepoint_file_id: string
      fileable_type: string
      fileable_id: string
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
      data: string
      created_at: string
      updated_at: string
      // relations
      registrations: Registration[]
    }

    export interface Relationship {
      // columns
      id: number
      name: string
      slug: string
      description: string|null
      type: string|null
      created_at: string
      updated_at: string
      // relations
      institutions: Institution[]
      relationshipables: Relationshipable[]
      types: Type[]
    }

    export interface Reservation {
      // columns
      id: string
      name: string
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
      id: string
      name: string[]
      description: string[]|null
      location: string|null
      capacity: number
      padalinys_id: number
      is_reservable: boolean
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

    export interface ResourceCategory {
    }

    export interface Role {
      // columns
      id: string
      name: string
      guard_name: string
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
      role_id: string
      type_id: number
      created_at: string|null
      updated_at: string|null
      // relations
      role: Role
      type: Type
    }

    export interface SharepointFile {
      // columns
      sharepoint_id: string
      id: string
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
      alias: string|null
      name: string
      description: string|null
      created_at: string
      updated_at: string
    }

    export interface Task {
      // columns
      id: string
      name: string
      description: string|null
      due_date: string|null
      taskable_type: string
      taskable_id: string
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
      title: string|null
      model_type: string|null
      description: string|null
      slug: string|null
      extra_attributes: string|null
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
      recursive_parent: Type
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
      id: string
      email: string
      phone: string|null
      facebook_url: string|null
      name: string
      pronouns: string[]|null
      show_pronouns: boolean
      password?: string|null
      is_active: boolean
      email_verified_at?: string|null
      remember_token?: string|null
      last_action?: string|null
      last_changelog_check?: string|null
      microsoft_token?: string|null
      updated_at: string|null
      created_at: string
      profile_photo_path: string|null
      deleted_at: string|null
      // mutators
      translations: unknown
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

