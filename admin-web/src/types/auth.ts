export interface AdminUser {
  id: number
  username: string
  nickname: string
  avatar: string
  mobile: string
  email: string
  roles: string[]
  data_scope: string
  permissions: string[]
}

export interface AdminMenu {
  id: number
  parent_id: number
  type: number
  title: string
  permission: string
  path: string
  component: string
  icon: string
  sort: number
  visible: number
  status: number
  remark: string
  children: AdminMenu[]
}

export interface SelectOption {
  label: string
  value: string
}

export interface SiteConfigPayload {
  admin_title: string
  site_logo: string
  site_description: string
}

export interface SystemConfigOptions {
  types: SelectOption[]
  option_types: string[]
}

export interface UploadFileOptions {
  extensions: string[]
  image_extensions: string[]
  categories: SelectOption[]
  accept: string
  image_accept: string
}

export interface BackendContext {
  user: AdminUser
  menus: AdminMenu[]
  site_config: SiteConfigPayload
  config_options: SystemConfigOptions
  file_options: UploadFileOptions
}

export interface LoginResult extends BackendContext {
  token: string
  token_type: string
  expires_in: number
}

export interface ApiResponse<T> {
  code: number
  message: string
  data: T
}
