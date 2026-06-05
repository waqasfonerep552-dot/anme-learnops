@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full min-w-0 rounded-2xl border-slate-200 bg-white/90 px-4 py-3 text-sm font-semibold text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 disabled:opacity-60 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-100 dark:focus:border-blue-400 dark:focus:ring-blue-950']) }}>
