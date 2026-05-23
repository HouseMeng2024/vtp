import { defineStore } from 'pinia'
import { fetchSiteConfig } from '../api/config'
import type { SiteConfigPayload, SystemConfigOptions, UploadFileOptions } from '../types/auth'

export interface VisitedView {
  path: string
  title: string
}

export type LayoutMode = 'side' | 'top' | 'mix'

export interface ProjectConfig {
  themeColor: string
  layoutMode: LayoutMode
  darkMode: boolean
  grayMode: boolean
  weakMode: boolean
  compactMode: boolean
  fixedHeader: boolean
  showLogo: boolean
  showTags: boolean
  showBreadcrumb: boolean
}

export interface SiteConfig {
  adminTitle: string
  siteLogo: string
  siteDescription: string
}

const CONFIG_KEY = 'vtp_project_config'
const SITE_CONFIG_KEY = 'vtp_site_config'

const defaultProjectConfig: ProjectConfig = {
  themeColor: '#409eff',
  layoutMode: 'side',
  darkMode: false,
  grayMode: false,
  weakMode: false,
  compactMode: false,
  fixedHeader: true,
  showLogo: true,
  showTags: true,
  showBreadcrumb: true,
}

const defaultSiteConfig: SiteConfig = {
  adminTitle: 'VTP Admin',
  siteLogo: '/logo.svg',
  siteDescription: '通用后台管理系统',
}

const defaultSystemConfigOptions: SystemConfigOptions = {
  types: [],
  option_types: [],
}

const defaultUploadFileOptions: UploadFileOptions = {
  extensions: [],
  image_extensions: [],
  categories: [],
  accept: '',
  image_accept: '',
}

function loadProjectConfig(): ProjectConfig {
  const config = localStorage.getItem(CONFIG_KEY)

  if (!config) {
    return { ...defaultProjectConfig }
  }

  try {
    return {
      ...defaultProjectConfig,
      ...JSON.parse(config),
    }
  } catch {
    return { ...defaultProjectConfig }
  }
}

function loadCachedSiteConfig(): SiteConfig {
  const config = localStorage.getItem(SITE_CONFIG_KEY)

  if (!config) {
    return { ...defaultSiteConfig }
  }

  try {
    return {
      ...defaultSiteConfig,
      ...JSON.parse(config),
    }
  } catch {
    return { ...defaultSiteConfig }
  }
}

export const useAppStore = defineStore('app', {
  state: () => ({
    sidebarCollapsed: false,
    visitedViews: [{ path: '/dashboard', title: '控制台' }] as VisitedView[],
    projectConfig: loadProjectConfig(),
    siteConfig: loadCachedSiteConfig(),
    systemConfigOptions: { ...defaultSystemConfigOptions },
    uploadFileOptions: { ...defaultUploadFileOptions },
  }),
  actions: {
    toggleSidebar() {
      this.sidebarCollapsed = !this.sidebarCollapsed
    },
    addVisitedView(view: VisitedView) {
      if (this.visitedViews.some((item) => item.path === view.path)) {
        return
      }

      this.visitedViews.push(view)
    },
    removeVisitedView(path: string) {
      this.visitedViews = this.visitedViews.filter((item) => item.path !== path)
    },
    setProjectConfig(config: Partial<ProjectConfig>) {
      this.projectConfig = {
        ...this.projectConfig,
        ...config,
      }
      this.persistProjectConfig()
      this.applyProjectConfig()
    },
    resetProjectConfig() {
      this.projectConfig = { ...defaultProjectConfig }
      this.persistProjectConfig()
      this.applyProjectConfig()
    },
    persistProjectConfig() {
      localStorage.setItem(CONFIG_KEY, JSON.stringify(this.projectConfig))
    },
    applyProjectConfig() {
      const root = document.documentElement
      const body = document.body

      root.style.setProperty('--el-color-primary', this.projectConfig.themeColor)
      root.style.setProperty('--primary-color', this.projectConfig.themeColor)

      root.classList.toggle('dark', this.projectConfig.darkMode)
      body.classList.toggle('project-dark', this.projectConfig.darkMode)
      body.classList.toggle('project-gray', this.projectConfig.grayMode)
      body.classList.toggle('project-weak', this.projectConfig.weakMode)
      body.classList.toggle('project-compact', this.projectConfig.compactMode)
    },
    async loadSiteConfig() {
      const values = await fetchSiteConfig()

      this.setSiteConfig(values)
    },
    setSiteConfig(values: SiteConfigPayload) {
      this.siteConfig = {
        adminTitle: values.admin_title || defaultSiteConfig.adminTitle,
        siteLogo: values.site_logo || defaultSiteConfig.siteLogo,
        siteDescription: values.site_description || defaultSiteConfig.siteDescription,
      }

      localStorage.setItem(SITE_CONFIG_KEY, JSON.stringify(this.siteConfig))
      document.title = this.siteConfig.adminTitle
    },
    setBackendOptions(configOptions: SystemConfigOptions, fileOptions: UploadFileOptions) {
      this.systemConfigOptions = configOptions
      this.uploadFileOptions = fileOptions
    },
  },
})
