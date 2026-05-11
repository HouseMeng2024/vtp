import request from '../utils/request'
import type { AdminMenu } from '../types/auth'

export interface PageResult<T> {
  data: T[]
  total: number
  per_page: number
  current_page: number
  last_page: number
}

export interface AdminUserRow {
  id: number
  username: string
  nickname: string
  role_ids: number[]
  mobile: string
  email: string
  status: number
  last_login_ip: string
  last_login_time: string | null
  create_time: string | null
}

export interface AdminUserPayload {
  username: string
  password?: string
  nickname: string
  mobile: string
  email: string
  status: number
  role_ids: number[]
}

export interface MemberRow {
  id: number
  username: string
  nickname: string
  avatar: string
  mobile: string
  email: string
  gender: number
  birthday: string | null
  status: number
  register_ip: string
  register_time: string | null
  last_login_ip: string
  last_login_time: string | null
  remark: string
  create_time: string | null
}

export interface MemberPayload {
  username: string
  password?: string
  nickname: string
  avatar: string
  mobile: string
  email: string
  gender: number
  birthday: string
  status: number
  remark: string
}

export interface AdminRoleRow {
  id: number
  name: string
  code: string
  sort: number
  status: number
  data_scope: string
  remark: string
  create_time: string | null
}

export interface AdminRolePayload {
  name: string
  code: string
  sort: number
  status: number
  data_scope: string
  remark: string
}

export interface AdminMenuPayload {
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
}

export interface AdminLoginLogRow {
  id: number
  user_id: number
  username: string
  ip: string
  user_agent: string
  status: number
  message: string
  create_time: string | null
}

export interface AdminOperateLogRow {
  id: number
  user_id: number
  username: string
  title: string
  method: string
  path: string
  params: string
  response: string
  ip: string
  user_agent: string
  status_code: number
  duration_ms: string
  create_time: string | null
}

export interface SystemConfigItem {
  id: number
  group: string
  key: string
  value: string
  type: string
  name: string
  remark: string
  create_time: string | null
  update_time: string | null
}

export interface SystemConfigGroup {
  group: string
  title: string
  items: SystemConfigItem[]
}

export interface SiteConfigPayload {
  admin_title: string
  site_logo: string
  site_description: string
}

export interface UploadFileRow {
  id: number
  disk: string
  path: string
  url: string
  original_name: string
  mime_type: string
  extension: string
  category: string
  scene: string
  size: number
  sha1: string
  uploader_id: number
  create_time: string | null
}

export interface SystemToolBackup {
  name: string
  size: number
  create_time: string
}

export interface SystemToolOverview {
  cache: {
    driver: string
    path: string
  }
  backups: SystemToolBackup[]
}

export interface CodeGeneratorField {
  name: string
  label: string
  type: 'text' | 'textarea' | 'number' | 'decimal' | 'switch' | 'select' | 'radio' | 'checkbox' | 'image' | 'images' | 'file' | 'date' | 'datetime' | 'richtext'
  required: boolean
  search: boolean
  list: boolean
  default: string | number
  max_length?: number
  min?: number
  max?: number
  dict_type?: string
  options?: Array<{
    label: string
    value: string
  }>
}

export interface CodeGeneratorCleanupResult {
  module: string
  deleted: string[]
}

export interface CodeGeneratorPayload {
  module: string
  title: string
  table: string
  route_path: string
  menu_parent: string
  navigation_options?: Array<{
    id: number
    title: string
    path: string
  }>
  fields: CodeGeneratorField[]
  options: {
    write_backend: boolean
    write_frontend: boolean
    merge_api: boolean
    create_menu: boolean
    menu_parent_id: number | null
    execute_schema: boolean
    overwrite_existing: boolean
  }
}

export interface CodeGeneratorResult {
  module: string
  output_dir: string
  config_path: string
  files: string[]
  installed_files: string[]
  merged_files: string[]
  messages: string[]
  log: string[]
}

export interface CodeGeneratorPreviewItem {
  label: string
  path: string
  exists: boolean
}

export interface CodeGeneratorPreview {
  module: string
  route_path: string
  has_conflict: boolean
  checks: {
    backend_files: CodeGeneratorPreviewItem[]
    frontend_files: CodeGeneratorPreviewItem[]
    api_files: CodeGeneratorPreviewItem[]
    database: CodeGeneratorPreviewItem[]
    menus: CodeGeneratorPreviewItem[]
  }
}

export interface AdminNoticeRow {
  id: number
  title: string
  content: string
  type: string
  scope_type: 'all' | 'role' | 'user'
  scope_ids: number[]
  popup: number
  status: number
  read: number
  create_time: string | null
}

export interface AdminNoticePayload {
  title: string
  content: string
  type: string
  scope_type: 'all' | 'role' | 'user'
  scope_ids: number[]
  popup: number
  status: number
}

export interface NoticeResult {
  items: AdminNoticeRow[]
  unread_count: number
}

export interface DictTypeRow {
  id: number
  name: string
  type: string
  sort: number
  status: number
  remark: string
  create_time: string | null
}

export interface DictTypePayload {
  name: string
  type: string
  sort: number
  status: number
  remark: string
}

export interface DictTypeOption {
  id: number
  name: string
  type: string
}

export interface DictOption {
  label: string
  value: string
  tag_type?: string
  sort?: number
}

export interface DictDataRow {
  id: number
  type_id: number
  label: string
  value: string
  tag_type: string
  sort: number
  status: number
  remark: string
  create_time: string | null
}

export interface DictDataPayload {
  type_id: number
  label: string
  value: string
  tag_type: string
  sort: number
  status: number
  remark: string
}

export function fetchUsers(params: { page: number; limit: number; keyword?: string }) {
  return request.get<PageResult<AdminUserRow>>('/user/index', { params }).then((response) => response.data)
}

export function createUser(data: AdminUserPayload) {
  return request.post<AdminUserRow>('/user/save', data).then((response) => response.data)
}

export function updateUser(id: number, data: AdminUserPayload) {
  return request.put<AdminUserRow>(`/user/update/id/${id}`, data).then((response) => response.data)
}

export function updateUserStatus(id: number, status: number) {
  return request.patch<AdminUserRow>(`/user/status/id/${id}`, { status }).then((response) => response.data)
}

export function batchUpdateUserStatus(ids: number[], status: number) {
  return request.patch<never>('/user/batchStatus', { ids, status }).then((response) => response.data)
}

export function deleteUser(id: number) {
  return request.delete<never>(`/user/delete/id/${id}`).then((response) => response.data)
}

export function batchDeleteUsers(ids: number[]) {
  return request.delete<never>('/user/batchDelete', { data: { ids } }).then((response) => response.data)
}

export function forceLogoutUser(id: number) {
  return request.post<never>(`/user/forceLogout/id/${id}`).then((response) => response.data)
}

export function fetchMembers(params: { page: number; limit: number; keyword?: string; status?: number | '' }) {
  return request.get<PageResult<MemberRow>>('/member/index', { params }).then((response) => response.data)
}

export function fetchMemberDetail(id: number) {
  return request.get<MemberRow>(`/member/detail/id/${id}`).then((response) => response.data)
}

export function createMember(data: MemberPayload) {
  return request.post<MemberRow>('/member/save', data).then((response) => response.data)
}

export function updateMember(id: number, data: MemberPayload) {
  return request.put<MemberRow>(`/member/update/id/${id}`, data).then((response) => response.data)
}

export function updateMemberStatus(id: number, status: number) {
  return request.patch<MemberRow>(`/member/status/id/${id}`, { status }).then((response) => response.data)
}

export function batchUpdateMemberStatus(ids: number[], status: number) {
  return request.patch<never>('/member/batchStatus', { ids, status }).then((response) => response.data)
}

export function resetMemberPassword(id: number, password: string) {
  return request.post<never>(`/member/resetPassword/id/${id}`, { password }).then((response) => response.data)
}

export function deleteMember(id: number) {
  return request.delete<never>(`/member/delete/id/${id}`).then((response) => response.data)
}

export function batchDeleteMembers(ids: number[]) {
  return request.delete<never>('/member/batchDelete', { data: { ids } }).then((response) => response.data)
}

export function fetchRoles(params: { page: number; limit: number; keyword?: string }) {
  return request.get<PageResult<AdminRoleRow>>('/role/index', { params }).then((response) => response.data)
}

export function createRole(data: AdminRolePayload) {
  return request.post<AdminRoleRow>('/role/save', data).then((response) => response.data)
}

export function updateRole(id: number, data: AdminRolePayload) {
  return request.put<AdminRoleRow>(`/role/update/id/${id}`, data).then((response) => response.data)
}

export function updateRoleStatus(id: number, status: number) {
  return request.patch<AdminRoleRow>(`/role/status/id/${id}`, { status }).then((response) => response.data)
}

export function batchUpdateRoleStatus(ids: number[], status: number) {
  return request.patch<never>('/role/batchStatus', { ids, status }).then((response) => response.data)
}

export function deleteRole(id: number) {
  return request.delete<never>(`/role/delete/id/${id}`).then((response) => response.data)
}

export function batchDeleteRoles(ids: number[]) {
  return request.delete<never>('/role/batchDelete', { data: { ids } }).then((response) => response.data)
}

export function fetchRoleOptions() {
  return request.get<Pick<AdminRoleRow, 'id' | 'name' | 'code'>[]>('/role/options').then((response) => response.data)
}

export function fetchRoleMenus(id: number) {
  return request.get<{ menu_ids: number[] }>(`/role/menus/id/${id}`).then((response) => response.data)
}

export function updateRoleMenus(id: number, menuIds: number[]) {
  return request.put<never>(`/role/saveMenus/id/${id}`, { menu_ids: menuIds }).then((response) => response.data)
}

export function fetchMenus() {
  return request.get<AdminMenu[]>('/menu/index').then((response) => response.data)
}

export function createMenu(data: AdminMenuPayload) {
  return request.post<AdminMenu>('/menu/save', data).then((response) => response.data)
}

export function updateMenu(id: number, data: AdminMenuPayload) {
  return request.put<AdminMenu>(`/menu/update/id/${id}`, data).then((response) => response.data)
}

export function deleteMenu(id: number) {
  return request.delete<never>(`/menu/delete/id/${id}`).then((response) => response.data)
}

export function fetchLoginLogs(params: { page: number; limit: number; keyword?: string; status?: number | '' }) {
  return request.get<PageResult<AdminLoginLogRow>>('/log/login', { params }).then((response) => response.data)
}

export function fetchOperateLogs(params: { page: number; limit: number; keyword?: string; method?: string }) {
  return request.get<PageResult<AdminOperateLogRow>>('/log/operate', { params }).then((response) => response.data)
}

export function batchDeleteLoginLogs(ids: number[]) {
  return request.delete<never>('/log/batchDeleteLogin', { data: { ids } }).then((response) => response.data)
}

export function clearLoginLogs() {
  return request.delete<never>('/log/clearLogin').then((response) => response.data)
}

export function batchDeleteOperateLogs(ids: number[]) {
  return request.delete<never>('/log/batchDeleteOperate', { data: { ids } }).then((response) => response.data)
}

export function clearOperateLogs() {
  return request.delete<never>('/log/clearOperate').then((response) => response.data)
}

export function fetchSystemConfigs() {
  return request.get<SystemConfigGroup[]>('/config/index').then((response) => response.data)
}

export function fetchSiteConfig() {
  return request.get<SiteConfigPayload>('/config/site').then((response) => response.data)
}

export function updateSystemConfigs(data: Record<string, string | number>) {
  return request.put<SystemConfigGroup[]>('/config/save', data).then((response) => response.data)
}

export function fetchUploadFiles(params: { page: number; limit: number; keyword?: string; extension?: string; category?: string; scene?: string }) {
  return request.get<PageResult<UploadFileRow>>('/file/index', { params }).then((response) => response.data)
}

export function uploadFile(data: FormData) {
  return request.post<UploadFileRow>('/file/upload', data).then((response) => response.data)
}

export function deleteUploadFile(id: number) {
  return request.delete<never>(`/file/delete/id/${id}`).then((response) => response.data)
}

export function fetchUploadFileDeleteInfo(id: number) {
  return request.get<{ reference_count: number; will_delete_physical: number }>(`/file/deleteInfo/id/${id}`).then((response) => response.data)
}

export function batchDeleteUploadFiles(ids: number[]) {
  return request.delete<never>('/file/batchDelete', { data: { ids } }).then((response) => response.data)
}

export function renameUploadFile(id: number, name: string) {
  return request.patch<UploadFileRow>(`/file/rename/id/${id}`, { name }).then((response) => response.data)
}

export function fetchSystemTools() {
  return request.get<SystemToolOverview>('/system_tool/index').then((response) => response.data)
}

export function clearSystemCache() {
  return request.delete<never>('/system_tool/clearCache').then((response) => response.data)
}

export function fetchDatabaseBackups() {
  return request.get<SystemToolBackup[]>('/system_tool/backups').then((response) => response.data)
}

export function createDatabaseBackup() {
  return request.post<SystemToolBackup>('/system_tool/createBackup').then((response) => response.data)
}

export function restoreDatabaseBackup(name: string) {
  return request.post<never>('/system_tool/restoreBackup', { name }).then((response) => response.data)
}

export function deleteDatabaseBackup(name: string) {
  return request.delete<never>('/system_tool/deleteBackup', { data: { name } }).then((response) => response.data)
}

export function downloadDatabaseBackup(name: string) {
  return request.get<Blob>('/system_tool/downloadBackup', {
    params: { name },
    responseType: 'blob',
  }).then((response) => response.data)
}

export function fetchRecentCodeGenerate() {
  return request.get<CodeGeneratorResult | null>('/code_generator/recent').then((response) => response.data)
}

export function previewCodeGenerate(data: CodeGeneratorPayload) {
  return request.post<CodeGeneratorPreview>('/code_generator/preview', data).then((response) => response.data)
}

export function generateCode(data: CodeGeneratorPayload) {
  return request.post<CodeGeneratorResult>('/code_generator/generate', data).then((response) => response.data)
}

export function cleanupGeneratedCode(data: Pick<CodeGeneratorPayload, 'module' | 'table'>) {
  return request.post<CodeGeneratorCleanupResult>('/code_generator/cleanup', data).then((response) => response.data)
}

export function fetchRecentNotices() {
  return request.get<NoticeResult>('/notice/recent').then((response) => response.data)
}

export function fetchNoticePage(params: { page: number; limit: number; keyword?: string; status?: number | '' }) {
  return request.get<PageResult<AdminNoticeRow>>('/notice/index', { params }).then((response) => response.data)
}

export function createNotice(data: AdminNoticePayload) {
  return request.post<AdminNoticeRow>('/notice/save', data).then((response) => response.data)
}

export function updateNotice(id: number, data: AdminNoticePayload) {
  return request.put<AdminNoticeRow>(`/notice/update/id/${id}`, data).then((response) => response.data)
}

export function updateNoticeStatus(id: number, status: number) {
  return request.patch<AdminNoticeRow>(`/notice/status/id/${id}`, { status }).then((response) => response.data)
}

export function deleteNotice(id: number) {
  return request.delete<never>(`/notice/delete/id/${id}`).then((response) => response.data)
}

export function readNotice(id: number) {
  return request.post<never>(`/notice/read/id/${id}`).then((response) => response.data)
}

export function readAllNotices() {
  return request.post<never>('/notice/readAll').then((response) => response.data)
}

export function fetchDictTypes(params: { page: number; limit: number; keyword?: string }) {
  return request.get<PageResult<DictTypeRow>>('/dict/types', { params }).then((response) => response.data)
}

export function fetchDictTypeOptions() {
  return request.get<DictTypeOption[]>('/dict/typeOptions').then((response) => response.data)
}

export function fetchDictOptions(type: string) {
  return request.get<DictOption[]>('/dict/options', { params: { type } }).then((response) => response.data)
}

export function createDictType(data: DictTypePayload) {
  return request.post<DictTypeRow>('/dict/saveType', data).then((response) => response.data)
}

export function updateDictType(id: number, data: DictTypePayload) {
  return request.put<DictTypeRow>(`/dict/updateType/id/${id}`, data).then((response) => response.data)
}

export function updateDictTypeStatus(id: number, status: number) {
  return request.patch<DictTypeRow>(`/dict/typeStatus/id/${id}`, { status }).then((response) => response.data)
}

export function deleteDictType(id: number) {
  return request.delete<never>(`/dict/deleteType/id/${id}`).then((response) => response.data)
}

export function fetchDictData(params: { page: number; limit: number; type_id?: number; keyword?: string }) {
  return request.get<PageResult<DictDataRow>>('/dict/data', { params }).then((response) => response.data)
}

export function createDictData(data: DictDataPayload) {
  return request.post<DictDataRow>('/dict/saveData', data).then((response) => response.data)
}

export function updateDictData(id: number, data: DictDataPayload) {
  return request.put<DictDataRow>(`/dict/updateData/id/${id}`, data).then((response) => response.data)
}

export function updateDictDataStatus(id: number, status: number) {
  return request.patch<DictDataRow>(`/dict/dataStatus/id/${id}`, { status }).then((response) => response.data)
}

export function deleteDictData(id: number) {
  return request.delete<never>(`/dict/deleteData/id/${id}`).then((response) => response.data)
}
