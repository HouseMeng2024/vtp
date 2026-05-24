import request from '../utils/request'

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

export interface CodeGeneratorStatus {
  enabled: boolean
  super_admin: boolean
  writable: boolean
  message: string
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

export function fetchRecentCodeGenerate() {
  return request.get<CodeGeneratorResult | null>('/code_generator/recent').then((response) => response.data)
}

export function fetchCodeGeneratorStatus() {
  return request.get<CodeGeneratorStatus>('/code_generator/status').then((response) => response.data)
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
