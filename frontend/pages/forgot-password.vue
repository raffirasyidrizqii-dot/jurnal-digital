<template>
  <div class="min-h-screen bg-[#FAFAFA] dark:bg-slate-950 flex flex-col justify-center items-center p-4 relative">
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

    <div class="w-full max-w-md bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl p-5 sm:p-8 shadow-sm text-center">
      <!-- Icon Lock / Check -->
      <div
        v-if="currentStep !== 4"
        class="w-12 h-12 bg-blue-50 dark:bg-blue-950/40 text-primary dark:text-blue-300 rounded-2xl flex items-center justify-center mx-auto mb-4"
      >
        <Lock class="w-6 h-6" />
      </div>
      <div
        v-else
        class="w-12 h-12 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-2xl flex items-center justify-center mx-auto mb-4"
      >
        <CheckCircle2 class="w-7 h-7" />
      </div>

      <h1 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">
        {{ currentStep === 4 ? 'Kata Sandi Berhasil Diperbarui!' : 'Pemulihan Kata Sandi' }}
      </h1>
      <p class="text-xs text-gray-500 dark:text-slate-400 mt-1 mb-6">
        {{ currentStep === 4 ? 'Kata sandi akun Anda telah diperbarui. Silakan gunakan sandi baru untuk masuk.' : 'Verifikasi akun email & reset kata sandi via WhatsApp' }}
      </p>

      <!-- Stepper (Steps 1, 2, 3) -->
      <div v-if="currentStep < 4" class="flex items-center justify-center gap-3 mb-8">
        <div
          v-for="s in [1, 2, 3]"
          :key="s"
          class="flex items-center gap-3"
        >
          <div
            class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-colors"
            :class="[
              s === currentStep
                ? 'bg-primary text-white'
                : s < currentStep
                ? 'bg-blue-100 dark:bg-blue-950/60 text-primary dark:text-blue-300'
                : 'bg-gray-100 dark:bg-slate-800 text-gray-400 dark:text-slate-500'
            ]"
          >
            <Check v-if="s < currentStep" class="w-3.5 h-3.5" />
            <span v-else>{{ s }}</span>
          </div>
          <div v-if="s < 3" class="w-8 h-0.5" :class="s < currentStep ? 'bg-primary' : 'bg-gray-200 dark:bg-slate-800'"></div>
        </div>
      </div>

      <!-- Error / Success Notice -->
      <div v-if="message" class="mb-5 p-3 rounded-xl text-xs text-left" :class="isError ? 'bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800/50' : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50'">
        {{ message }}
      </div>

      <!-- STEP 1: Email Form -->
      <form v-if="currentStep === 1" @submit.prevent="handleStep1" class="space-y-5 text-left">
        <div>
          <label class="block text-[11px] font-bold text-gray-400 dark:text-slate-400 uppercase tracking-wider mb-2">
            Alamat Email Akun
          </label>
          <div class="relative">
            <input
              v-model="email"
              type="email"
              required
              placeholder=""
              class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
            />
          </div>
          <p class="text-[11px] text-gray-400 dark:text-slate-500 mt-2">
            Kami akan mendeteksi nomor WhatsApp yang terhubung dengan email ini untuk mengirimkan kode OTP pengaman.
          </p>
        </div>

        <!-- Cloudflare Turnstile Anti-Bot Container -->
        <div v-show="turnstileSiteKey" class="flex justify-center my-2">
          <div ref="turnstileContainer"></div>
        </div>

        <button
          type="submit"
          :disabled="isLoading"
          class="w-full py-3.5 px-4 bg-primary hover:bg-primary-hover text-white rounded-xl text-sm font-semibold transition-all shadow-sm flex items-center justify-center gap-2 cursor-pointer"
        >
          <span>{{ isLoading ? 'Memproses...' : 'Verifikasi Akun & Kirim OTP →' }}</span>
        </button>

        <div class="text-center pt-2">
          <NuxtLink to="/login" class="text-xs text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-200">
            ← Kembali ke Halaman Login
          </NuxtLink>
        </div>
      </form>

      <!-- STEP 2: OTP Verification -->
      <form v-if="currentStep === 2" @submit.prevent="handleStep2" class="space-y-5 text-left">
        <div class="bg-gray-50 dark:bg-slate-850 rounded-xl p-3 border border-gray-100 dark:border-slate-800 text-xs space-y-1">
          <p class="text-gray-500 dark:text-slate-400">Akun Email: <span class="font-semibold text-gray-800 dark:text-slate-200">{{ maskedEmail }}</span></p>
          <p class="text-gray-500 dark:text-slate-400">WhatsApp Tujuan: <span class="font-semibold text-gray-800 dark:text-slate-200">{{ maskedPhone }}</span></p>
        </div>

        <div>
          <div class="flex items-center justify-between mb-2">
            <label class="text-[11px] font-bold text-gray-400 dark:text-slate-400 uppercase tracking-wider">
              Kode Verifikasi (OTP)
            </label>
            <button type="button" @click="currentStep = 1" class="text-[11px] text-primary dark:text-blue-400 hover:underline font-semibold cursor-pointer">
              Ganti Email
            </button>
          </div>
          <input
            v-model="otp"
            type="text"
            maxlength="6"
            required
            placeholder=""
            class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-center tracking-widest text-lg font-bold text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
          />
          <div class="flex items-center justify-between mt-2 text-[11px] text-gray-400 dark:text-slate-500">
            <span>Kode berlaku selama 5 menit.</span>
            <button
              type="button"
              :disabled="cooldown > 0"
              @click="handleStep1"
              class="font-semibold text-primary dark:text-blue-400 disabled:text-gray-400 dark:disabled:text-slate-600 cursor-pointer"
            >
              {{ cooldown > 0 ? `Kirim ulang (${cooldown}s)` : 'Kirim ulang OTP' }}
            </button>
          </div>
        </div>

        <button
          type="submit"
          :disabled="isLoading"
          class="w-full py-3.5 px-4 bg-primary hover:bg-primary-hover text-white rounded-xl text-sm font-semibold transition-all shadow-sm flex items-center justify-center gap-2 cursor-pointer"
        >
          <span>{{ isLoading ? 'Memeriksa...' : 'Verifikasi Kode OTP' }}</span>
        </button>

        <div class="text-center pt-2">
          <NuxtLink to="/login" class="text-xs text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-200">
            ← Kembali ke Halaman Login
          </NuxtLink>
        </div>
      </form>

      <!-- STEP 3: New Password -->
      <form v-if="currentStep === 3" @submit.prevent="handleStep3" class="space-y-4 text-left">
        <div>
          <label class="block text-[11px] font-bold text-gray-400 dark:text-slate-400 uppercase tracking-wider mb-2">
            Kata Sandi Baru
          </label>
          <input
            v-model="newPassword"
            type="password"
            minlength="8"
            required
            placeholder="Minimal 8 karakter"
            class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
          />
        </div>

        <div>
          <label class="block text-[11px] font-bold text-gray-400 dark:text-slate-400 uppercase tracking-wider mb-2">
            Konfirmasi Kata Sandi Baru
          </label>
          <input
            v-model="newPasswordConfirmation"
            type="password"
            minlength="8"
            required
            placeholder="Ulangi kata sandi baru"
            class="w-full px-4 py-3 rounded-xl bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
          />
        </div>

        <button
          type="submit"
          :disabled="isLoading"
          class="w-full py-3.5 px-4 bg-primary hover:bg-primary-hover text-white rounded-xl text-sm font-semibold transition-all shadow-sm flex items-center justify-center gap-2 mt-2 cursor-pointer"
        >
          <span>{{ isLoading ? 'Menyimpan...' : 'Simpan Kata Sandi Baru' }}</span>
        </button>

        <div class="text-center pt-2">
          <NuxtLink to="/login" class="text-xs text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-200">
            ← Kembali ke Halaman Login
          </NuxtLink>
        </div>
      </form>

      <!-- STEP 4: Success Screen -->
      <div v-if="currentStep === 4" class="space-y-5 pt-2">
        <NuxtLink
          to="/login"
          class="block w-full py-3.5 px-4 bg-primary hover:bg-primary-hover text-white rounded-xl text-sm font-semibold transition-all shadow-sm text-center cursor-pointer"
        >
          Masuk ke Halaman Login →
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  Lock,
  Check,
  CheckCircle2,
  Sun,
  Moon,
} from 'lucide-vue-next'
import { useAuthStore } from '~/stores/auth'
import { useUiStore } from '~/stores/ui'

definePageMeta({
  layout: false,
})

const uiStore = useUiStore()
const authStore = useAuthStore()
const runtimeConfig = useRuntimeConfig()
const turnstileSiteKey = computed(() => runtimeConfig.public.turnstileSiteKey || '')
const turnstileContainer = ref<HTMLElement | null>(null)
const turnstileToken = ref<string>('')
const turnstileWidgetId = ref<string | null>(null)

const currentStep = ref(1)
const email = ref('')
const maskedEmail = ref('')
const maskedPhone = ref('')
const otp = ref('')
const newPassword = ref('')
const newPasswordConfirmation = ref('')
const cooldown = ref(0)
const isLoading = ref(false)
const message = ref('')
const isError = ref(false)
let timer: any = null

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
          message.value = ''
          isError.value = false
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
  if (turnstileSiteKey.value) {
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

function startCooldown(sec: number) {
  cooldown.value = sec
  if (timer) clearInterval(timer)
  timer = setInterval(() => {
    if (cooldown.value > 0) cooldown.value--
    else clearInterval(timer)
  }, 1000)
}

async function handleStep1() {
  isLoading.value = true
  message.value = ''
  isError.value = false
  try {
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
        message.value = 'Mohon selesaikan verifikasi keamanan anti-bot (Cloudflare Turnstile) di atas sebelum mengirim OTP.'
        isError.value = true
        isLoading.value = false
        return
      }
    }

    const res = await $fetch<any>(`${authStore.apiBase}/auth/forgot-password/step-1`, {
      method: 'POST',
      body: {
        email: email.value,
        turnstile_token: turnstileToken.value,
      }
    })
    maskedEmail.value = res.masked_email
    maskedPhone.value = res.masked_phone
    startCooldown(res.cooldown || 60)
    currentStep.value = 2
  } catch (e: any) {
    isError.value = true
    message.value = e?.data?.message || 'Gagal mengirim OTP.'
    if (typeof window !== 'undefined') {
      const win = window as any
      if (win.turnstile && turnstileWidgetId.value) {
        try {
          win.turnstile.reset(turnstileWidgetId.value)
        } catch (err) {}
        turnstileToken.value = ''
      }
    }
  } finally {
    isLoading.value = false
  }
}

async function handleStep2() {
  isLoading.value = true
  message.value = ''
  isError.value = false
  try {
    await $fetch<any>(`${authStore.apiBase}/auth/forgot-password/step-2`, {
      method: 'POST',
      body: { email: email.value, otp: otp.value }
    })
    currentStep.value = 3
  } catch (e: any) {
    isError.value = true
    message.value = e?.data?.message || 'Kode OTP tidak valid.'
  } finally {
    isLoading.value = false
  }
}

async function handleStep3() {
  if (newPassword.value !== newPasswordConfirmation.value) {
    isError.value = true
    message.value = 'Konfirmasi kata sandi tidak cocok.'
    return
  }

  isLoading.value = true
  message.value = ''
  isError.value = false
  try {
    await $fetch<any>(`${authStore.apiBase}/auth/forgot-password/step-3`, {
      method: 'POST',
      body: {
        email: email.value,
        password: newPassword.value,
        password_confirmation: newPasswordConfirmation.value
      }
    })
    currentStep.value = 4
  } catch (e: any) {
    isError.value = true
    message.value = e?.data?.message || 'Gagal memperbarui kata sandi.'
  } finally {
    isLoading.value = false
  }
}
</script>
