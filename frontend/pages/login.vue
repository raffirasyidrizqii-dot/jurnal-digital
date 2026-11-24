<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-100 to-blue-50/60 dark:from-slate-950 dark:to-slate-900 flex flex-col justify-center items-center p-3.5 py-6 sm:py-8 relative">
    <!-- Dark Mode Toggle Button at Top-Right -->
    <button
      type="button"
      @click="uiStore.toggleDark"
      class="absolute top-4 right-4 p-2.5 rounded-xl bg-white/80 dark:bg-slate-850/80 backdrop-blur-xs border border-gray-200 dark:border-slate-700 text-gray-600 dark:text-slate-300 hover:text-gray-900 dark:hover:text-white transition shadow-2xs cursor-pointer"
      :title="uiStore.isDark ? 'Ganti ke Mode Terang' : 'Ganti ke Mode Gelap'"
    >
      <Sun v-if="uiStore.isDark" class="w-4 h-4 text-amber-400" />
      <Moon v-else class="w-4 h-4 text-slate-600 dark:text-slate-300" />
    </button>

    <!-- Auth Card -->
    <div class="w-full max-w-md bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl p-5 sm:p-8 shadow-md text-center my-auto">
      <!-- Header -->
      <div class="mb-6 text-center">
        <img src="/images/logo-smkn71.png" alt="Logo SMKN 71 Jakarta" class="w-14 h-14 mx-auto mb-3 object-contain" />
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Masuk ke Akun Anda</h1>
        <p class="text-xs sm:text-sm text-gray-500 dark:text-slate-400 mt-1.5 font-medium">Jurnal Kelas Digital SMKN 71 Jakarta</p>
      </div>

      <!-- Error Alert -->
      <div v-if="errorMessage" class="mb-5 p-3 rounded-xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/50 text-red-700 dark:text-red-300 text-xs text-left flex items-center gap-2">
        <span class="text-red-500">⚠</span>
        {{ errorMessage }}
      </div>

      <!-- Form -->
      <form @submit.prevent="handleLogin" class="space-y-4 text-left">
        <div>
          <label class="block text-[11px] font-bold text-gray-400 dark:text-slate-400 uppercase tracking-wider mb-2">
            Alamat Email Akun
          </label>
          <input
            v-model="email"
            type="email"
            required
            id="login-email"
            placeholder=""
            class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
          />
        </div>

        <div>
          <label class="block text-[11px] font-bold text-gray-400 dark:text-slate-400 uppercase tracking-wider mb-2">
            Kata Sandi
          </label>
          <div class="relative">
            <input
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              required
              id="login-password"
              placeholder="••••••••••••"
              class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all pr-11"
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 dark:text-slate-400 hover:text-gray-600 dark:hover:text-slate-200 p-1"
            >
              <EyeOff v-if="showPassword" class="w-4 h-4" />
              <Eye v-else class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Point 2: Cloudflare Turnstile Anti-Bot Widget (Visible) -->
        <div v-show="turnstileSiteKey" class="flex justify-center my-2 min-h-[65px]">
          <div ref="turnstileContainer" id="turnstile-widget"></div>
        </div>

        <!-- Submit Button -->
        <button
          type="submit"
          :disabled="isLoading"
          id="login-submit-btn"
          class="w-full py-3.5 px-4 bg-primary hover:bg-primary-hover active:scale-[0.99] text-white rounded-xl text-sm font-semibold transition-all shadow-sm flex items-center justify-center gap-2 mt-1 disabled:opacity-70 cursor-pointer"
        >
          <span v-if="isLoading" class="flex items-center gap-2">
            <span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            Memproses...
          </span>
          <span v-else>Masuk Ke Dashboard</span>
        </button>

        <!-- Forgot Password Link & Back to Home -->
        <div class="flex items-center justify-between pt-1">
          <NuxtLink
            to="/"
            class="inline-block text-xs text-gray-500 dark:text-slate-400 hover:text-gray-800 dark:hover:text-slate-200 font-medium py-1.5 transition cursor-pointer"
          >
            ← Kembali ke Beranda
          </NuxtLink>
          <NuxtLink
            to="/forgot-password"
            class="inline-block text-xs text-primary dark:text-blue-400 hover:underline font-semibold py-1.5 px-3 rounded-lg hover:bg-blue-50/50 dark:hover:bg-blue-950/40 transition cursor-pointer"
          >
            Lupa kata sandi?
          </NuxtLink>
        </div>

        <!-- AKSES CEPAT MODE DEMO -->
        <div v-if="appConfig?.enable_demo_login" class="mt-6 pt-5 border-t border-slate-200 dark:border-slate-800">
          <p class="text-xs font-semibold text-slate-500 text-center mb-3">
            Akses Cepat Mode Demo
          </p>
          <div class="grid grid-cols-3 gap-2">
            <button
              type="button"
              @click="fillDemoAccount('admin@smkn71.sch.id')"
              class="px-2 py-1.5 text-[11px] font-medium rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 transition cursor-pointer"
            >
              Superadmin
            </button>
            <button
              type="button"
              @click="fillDemoAccount('fadilah@smkn71.sch.id')"
              class="px-2 py-1.5 text-[11px] font-medium rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 transition cursor-pointer"
            >
              Wali Kelas
            </button>
            <button
              type="button"
              @click="fillDemoAccount('nurina@smkn71.sch.id')"
              class="px-2 py-1.5 text-[11px] font-medium rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 transition cursor-pointer"
            >
              Guru Pengajar
            </button>
            <button
              type="button"
              @click="fillDemoAccount('siswa3@smkn71.sch.id')"
              class="px-2 py-1.5 text-[11px] font-medium rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 transition cursor-pointer"
            >
              Sekretaris
            </button>
            <button
              type="button"
              @click="fillDemoAccount('siswa1@smkn71.sch.id')"
              class="px-2 py-1.5 text-[11px] font-medium rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 transition cursor-pointer"
            >
              Siswa
            </button>
            <button
              type="button"
              @click="fillDemoAccount('ahmad@sch.id')"
              class="px-2 py-1.5 text-[11px] font-medium rounded-lg bg-purple-50 hover:bg-purple-100 text-purple-700 transition cursor-pointer"
            >
              Orang Tua
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  Eye,
  EyeOff,
  Sun,
  Moon,
} from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useAppConfigStore } from '~/stores/appConfig'
import { useUiStore } from '~/stores/ui'

definePageMeta({
  layout: false,
})

const uiStore = useUiStore()
const authStore = useAuthStore()
const appConfig = useAppConfigStore()
const runtimeConfig = useRuntimeConfig()
const turnstileSiteKey = computed(() => runtimeConfig.public.turnstileSiteKey || '')

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const isLoading = ref(false)
const errorMessage = ref('')
const turnstileContainer = ref<HTMLElement | null>(null)
const turnstileToken = ref<string>('')
const turnstileWidgetId = ref<string | null>(null)

function renderTurnstile() {
  if (typeof window === 'undefined' || !turnstileSiteKey.value) return
  const win = window as any
  if (win.turnstile && turnstileContainer.value && !turnstileWidgetId.value) {
    try {
      turnstileWidgetId.value = win.turnstile.render(turnstileContainer.value, {
        sitekey: turnstileSiteKey.value,
        theme: 'light',
        callback: (token: string) => {
          turnstileToken.value = token
          errorMessage.value = ''
        },
        'expired-callback': () => {
          turnstileToken.value = ''
        },
        'error-callback': () => {
          turnstileToken.value = ''
        },
      })
    } catch (e) {
      console.warn('Turnstile render note:', e)
    }
  }
}

onMounted(() => {
  appConfig.fetchConfig()

  if (turnstileSiteKey.value) {
    // Polling Turnstile script readiness
    const interval = setInterval(() => {
      const win = window as any
      if (win.turnstile && turnstileContainer.value) {
        renderTurnstile()
        clearInterval(interval)
      }
    }, 200)
    setTimeout(() => clearInterval(interval), 10000)
  }
})

async function handleLogin() {
  errorMessage.value = ''
  isLoading.value = true
  try {
    // If Turnstile is active but token is not yet ready, attempt execute / wait
    if (turnstileSiteKey.value && !turnstileToken.value && typeof window !== 'undefined') {
      const win = window as any
      if (win.turnstile && turnstileWidgetId.value) {
        try {
          win.turnstile.execute(turnstileWidgetId.value)
        } catch (e) {}
      }
      let waitCycles = 0
      while (!turnstileToken.value && waitCycles < 40) {
        await new Promise((r) => setTimeout(r, 100))
        waitCycles++
      }

      if (!turnstileToken.value) {
        errorMessage.value = 'Mohon selesaikan verifikasi keamanan anti-bot (Cloudflare Turnstile) di atas sebelum masuk.'
        isLoading.value = false
        return
      }
    }

    await authStore.login(email.value, password.value, turnstileToken.value)
    navigateTo('/dashboard')
  } catch (err: any) {
    errorMessage.value = err?.data?.message || 'Email atau kata sandi salah.'
    // Reset Turnstile on error
    if (typeof window !== 'undefined') {
      const win = window as any
      if (win.turnstile && turnstileWidgetId.value) {
        try {
          win.turnstile.reset(turnstileWidgetId.value)
        } catch (e) {}
        turnstileToken.value = ''
      }
    }
  } finally {
    isLoading.value = false
  }
}

const fillDemoAccount = (demoEmail: string) => {
  email.value = demoEmail
  password.value = 'password123'
}

function quickFill(userEmail: string, userPass: string) {
  email.value = userEmail
  password.value = userPass
  handleLogin()
}
</script>
