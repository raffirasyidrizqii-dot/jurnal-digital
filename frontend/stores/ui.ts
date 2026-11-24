import { defineStore } from 'pinia'

export const useUiStore = defineStore('ui', () => {
  const isCollapsed = ref(false)
  const isDark = ref(false)

  // Inisialisasi dari localStorage di client
  if (import.meta.client) {
    try {
      const saved = localStorage.getItem('sidebar_collapsed')
      if (saved !== null) {
        isCollapsed.value = saved === 'true'
      }

      const savedTheme = localStorage.getItem('theme')
      if (savedTheme === 'dark') {
        isDark.value = true
        document.documentElement.classList.add('dark')
      } else {
        isDark.value = false
        document.documentElement.classList.remove('dark')
      }
    } catch (e) {
      // ignore
    }
  }

  function toggleSidebar() {
    isCollapsed.value = !isCollapsed.value
    if (import.meta.client) {
      try {
        localStorage.setItem('sidebar_collapsed', String(isCollapsed.value))
      } catch (e) {
        // ignore
      }
    }
  }

  function setSidebar(val: boolean) {
    isCollapsed.value = val
    if (import.meta.client) {
      try {
        localStorage.setItem('sidebar_collapsed', String(val))
      } catch (e) {
        // ignore
      }
    }
  }

  function toggleDark() {
    isDark.value = !isDark.value
    if (import.meta.client) {
      try {
        if (isDark.value) {
          document.documentElement.classList.add('dark')
          localStorage.setItem('theme', 'dark')
        } else {
          document.documentElement.classList.remove('dark')
          localStorage.setItem('theme', 'light')
        }
      } catch (e) {
        // ignore
      }
    }
  }

  return {
    isCollapsed,
    isDark,
    toggleSidebar,
    setSidebar,
    toggleDark,
  }
})
