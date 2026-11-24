<template>
  <div
    class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 border-t-4 p-4 sm:p-5 shadow-xs hover:shadow-card transition-all flex flex-col justify-between overflow-hidden group"
    :class="accentBorderColor"
  >
    <div class="mb-2">
      <span class="text-[11px] sm:text-xs font-semibold text-slate-500 dark:text-slate-400 tracking-wider uppercase truncate block" :title="cleanedTitle">
        {{ cleanedTitle }}
      </span>
    </div>
    
    <div class="my-1.5">
      <div class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight leading-none break-words">
        {{ cleanedValue }}
      </div>
    </div>

    <p v-if="cleanedSubtext" class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 font-medium truncate mt-1 flex items-center gap-1.5" :title="cleanedSubtext">
      <span>{{ cleanedSubtext }}</span>
    </p>
  </div>
</template>

<script setup lang="ts">
const props = withDefaults(
  defineProps<{
    title: string
    value: string | number
    subtext?: string
    color?: 'green' | 'orange' | 'blue' | 'red' | 'purple' | 'gray'
  }>(),
  {
    color: 'blue'
  }
)

function stripAwkwardParentheses(str: string | number): string {
  if (typeof str !== 'string') return String(str)
  return str
    .replace(/\s*\((auto-detect|otomatis|wali kelas|sekretaris kelas|siswa|khusus superadmin|semester berjalan|realisasi hari ini)\)/gi, '')
    .trim()
}

const cleanedTitle = computed(() => stripAwkwardParentheses(props.title))
const cleanedValue = computed(() => stripAwkwardParentheses(props.value))
const cleanedSubtext = computed(() => props.subtext ? stripAwkwardParentheses(props.subtext) : '')

const accentBorderColor = computed(() => {
  switch (props.color) {
    case 'green': return 'border-t-emerald-500'
    case 'orange': return 'border-t-amber-500'
    case 'red': return 'border-t-rose-500'
    case 'purple': return 'border-t-indigo-500'
    case 'gray': return 'border-t-slate-400'
    case 'blue':
    default: return 'border-t-primary'
  }
})
</script>
