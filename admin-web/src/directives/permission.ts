import type { App, DirectiveBinding } from 'vue'
import { useAuthStore } from '../stores/auth'

function hasPermission(permission: string | string[]) {
  const authStore = useAuthStore()
  const permissions = Array.isArray(permission) ? permission : [permission]

  return permissions.some((item) => authStore.hasPermission(item))
}

export function setupPermissionDirective(app: App) {
  app.directive('permission', {
    mounted(element: HTMLElement, binding: DirectiveBinding<string | string[]>) {
      if (!hasPermission(binding.value)) {
        element.parentNode?.removeChild(element)
      }
    },
  })
}
