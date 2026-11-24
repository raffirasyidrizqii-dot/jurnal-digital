<template>
  <div class="min-h-screen bg-[#FAFAFA] dark:bg-slate-950 dark:text-slate-100 print:bg-white flex flex-col antialiased transition-colors duration-200">
    <!-- Desktop Sidebar -->
    <div class="hidden md:block print:hidden">
      <AppSidebar />
    </div>

    <!-- Mobile Navigation -->
    <div class="print:hidden">
      <MobileNav />
    </div>

    <!-- Main Content Area -->
    <div
      class="flex-1 print:pl-0 print:md:pl-0 pb-28 sm:pb-24 md:pb-8 print:pb-0 flex flex-col min-w-0 max-w-full overflow-x-hidden print:overflow-visible transition-all duration-300 ease-in-out"
      :class="uiStore.isCollapsed ? 'md:pl-20' : 'md:pl-72'"
    >
      <slot />
    </div>

    <!-- Global Toast & Confirm Dialog -->
    <div class="print:hidden">
      <AppToast />
      <AppConfirmModal />
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import { useUiStore } from '~/stores/ui'

const authStore = useAuthStore()
const uiStore = useUiStore()

onMounted(() => {
  // If not logged in and not on login / forgot-password, redirect
  if (!authStore.isAuthenticated) {
    const route = useRoute()
    if (route.path !== '/' && route.path !== '/login' && !route.path.startsWith('/forgot-password')) {
      navigateTo('/login')
    }
  }
})
</script>
