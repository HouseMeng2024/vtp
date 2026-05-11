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

export interface LoginResult {
  token: string
  token_type: string
  expires_in: number
  user: AdminUser
}

export interface ApiResponse<T> {
  code: number
  message: string
  data: T
}
