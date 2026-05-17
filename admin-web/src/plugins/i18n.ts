import { createI18n } from 'vue-i18n'
import enUs from '../locales/en-us'
import zhCn from '../locales/zh-cn'

export type AppLocale = 'en-us' | 'zh-cn'

export const LOCALE_KEY = 'vtp_locale'
export const defaultLocale: AppLocale = 'en-us'
export const localeOptions: Array<{ label: string; value: AppLocale }> = [
  { label: 'English', value: 'en-us' },
  { label: 'Chinese', value: 'zh-cn' },
]

export const i18n = createI18n({
  legacy: false,
  globalInjection: true,
  locale: getStoredLocale(),
  fallbackLocale: defaultLocale,
  messages: {
    'en-us': enUs,
    'zh-cn': zhCn,
  },
})

export function getStoredLocale(): AppLocale {
  const locale = localStorage.getItem(LOCALE_KEY)

  return isAppLocale(locale) ? locale : defaultLocale
}

export function setI18nLocale(locale: AppLocale): void {
  localStorage.setItem(LOCALE_KEY, locale)
  i18n.global.locale.value = locale
  document.documentElement.lang = locale
}

export function t(key: string): string {
  return i18n.global.t(key)
}

function isAppLocale(locale: string | null): locale is AppLocale {
  return locale === 'en-us' || locale === 'zh-cn'
}
