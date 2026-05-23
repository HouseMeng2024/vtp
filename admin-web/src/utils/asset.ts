const DEV_BACKEND_ORIGIN = 'http://127.0.0.1:8000'

/**
 * 规范化后端返回的资源地址，开发环境自动补齐接口服务域名。
 */
export function normalizeAssetUrl(url = '') {
  if (!url || /^https?:\/\//i.test(url) || url.startsWith('data:')) {
    return url
  }

  if (url === '/logo.svg') {
    return url
  }

  const origin = import.meta.env.DEV ? DEV_BACKEND_ORIGIN : ''

  return `${origin}${url}`
}
