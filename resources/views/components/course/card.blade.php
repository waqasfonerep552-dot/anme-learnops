@props(['course'])

{{-- Reusable course card: home aur catalog dono jagah same business course design use hota hai. --}}
<article {{ $attributes->merge(['class' => 'group lift-card flex h-full flex-col overflow-hidden rounded-[1.75rem] border border-white/70 bg-white/85 shadow-xl shadow-slate-200/60 backdrop-blur-xl hover:shadow-blue-200/60 dark:border-slate-800 dark:bg-slate-900/80 dark:shadow-slate-950/40']) }}>
    <div class="relative h-44 overflow-hidden bg-gradient-to-br from-slate-950 via-indigo-900 to-blue-600">
        @if($course->thumbnail)
            <img src="{{ url('storage/'.ltrim($course->thumbnail, '/')) }}" alt="{{ $course->title }} cover image" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105">
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/50 to-slate-950/15"></div>
        @else
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(255,255,255,.25),transparent_18rem)]"></div>
        @endif
        <x-ui.badge variant="dark" class="absolute left-5 top-5 bg-white/15 text-white ring-white/20 backdrop-blur">
            {{ $course->category?->name ?? 'Training' }}
        </x-ui.badge>
        @if($course->is_featured)
            <x-ui.badge variant="orange" class="absolute right-5 top-5">
                <x-ui.icon name="star" class="h-3.5 w-3.5" />
                Featured
            </x-ui.badge>
        @endif
        <div class="absolute bottom-5 right-5 hidden sm:block">
            <x-ui.icon-tile name="academy" tone="blue" size="sm" />
        </div>
        <div class="absolute bottom-5 left-5 right-5">
            <p class="text-xs font-bold uppercase tracking-widest text-blue-100">{{ $course->level ?? 'Professional' }}</p>
            <h3 class="mt-1 line-clamp-2 pr-0 text-2xl font-black leading-tight text-white sm:pr-14">{{ $course->title }}</h3>
        </div>
    </div>

    <div class="flex grow flex-col p-6">
        <p class="grow text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $course->short_description }}</p>

        <div class="mt-5 grid grid-cols-2 gap-3">
            <div class="rounded-2xl bg-slate-50 p-3 dark:bg-slate-950/50">
                <p class="flex items-center gap-1.5 text-[11px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">
                    <x-ui.icon name="payment" class="h-3.5 w-3.5" />
                    Price
                </p>
                <p class="mt-1 font-black text-slate-950 dark:text-white">{{ $course->currency }} {{ number_format((float) $course->price) }}</p>
            </div>
            <div class="rounded-2xl bg-slate-50 p-3 dark:bg-slate-950/50">
                <p class="flex items-center gap-1.5 text-[11px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">
                    <x-ui.icon name="activity" class="h-3.5 w-3.5" />
                    Duration
                </p>
                <p class="mt-1 font-black text-slate-950 dark:text-white">{{ $course->duration ?? 'Self-paced' }}</p>
            </div>
        </div>

        <div class="mt-3 rounded-2xl border border-blue-100 bg-blue-50/80 px-4 py-3 text-sm font-black text-blue-800 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-200">
            <x-ui.icon name="check" class="mr-1.5 inline h-4 w-4" />
            {{ $course->accessLabel() }} after approval
        </div>

        <div class="mt-4 flex items-center justify-between rounded-2xl border border-orange-100 bg-orange-50/80 px-4 py-3 dark:border-orange-900 dark:bg-orange-950/30">
            <div class="flex items-center gap-1 text-orange-500">
                @for($star = 1; $star <= 5; $star++)
                    <span class="text-sm leading-none {{ $star <= round((float) ($course->approved_reviews_avg_rating ?? 0)) ? '' : 'opacity-25' }}">★</span>
                @endfor
            </div>
            <span class="text-xs font-black uppercase tracking-widest text-orange-700 dark:text-orange-200">
                {{ (int) ($course->approved_reviews_count ?? 0) > 0 ? number_format((float) $course->approved_reviews_avg_rating, 1).' rating' : 'New course' }}
            </span>
        </div>

        <div class="mt-5 grid gap-2 sm:grid-cols-2">
            <x-ui.button :href="route('courses.show', $course)" variant="secondary" class="flex-1 px-4 py-2.5">
                Details
                <x-ui.icon name="arrow" class="h-4 w-4" />
            </x-ui.button>
            <x-ui.button :href="route('checkout.show', $course)" class="flex-1 px-4 py-2.5">
                Enroll
                <x-ui.icon name="academy" class="h-4 w-4" />
            </x-ui.button>
        </div>
    </div>
</article>
