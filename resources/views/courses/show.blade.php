<x-app-layout>
    {{-- Course detail: business-side price/details show hoti hain, academy access purchase ke baad milta hai. --}}
    <x-slot name="header">
        <div class="grid gap-6 md:grid-cols-[1fr_auto] md:items-end">
            <div>
                <p class="eyebrow">{{ $course->category?->name ?? 'Training Course' }}</p>
                <h1 class="mt-2 max-w-4xl text-4xl font-black tracking-tight text-slate-950 dark:text-white">{{ $course->title }}</h1>
                <p class="mt-3 max-w-3xl text-slate-600 dark:text-slate-300">{{ $course->short_description }}</p>
            </div>
            <x-ui.button :href="route('checkout.show', $course)" variant="orange">
                Enroll for {{ $course->currency }} {{ number_format((float) $course->price) }}
            </x-ui.button>
        </div>
    </x-slot>

    <div class="app-container py-10">
        @if(session('status'))
            <div class="mb-6 rounded-3xl border border-green-100 bg-green-50 p-5 font-bold text-green-700 shadow-sm dark:border-green-900 dark:bg-green-950/50 dark:text-green-200">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
            <div class="space-y-6">
                <div class="premium-surface overflow-hidden p-0">
                    @if($course->thumbnail)
                        <img src="{{ url('storage/'.ltrim($course->thumbnail, '/')) }}" alt="{{ $course->title }} cover image" class="h-72 w-full object-cover">
                    @else
                        <div class="grid h-72 place-items-center bg-gradient-to-br from-slate-950 via-indigo-900 to-blue-700 text-white">
                            <div class="text-center">
                                <x-ui.icon-tile name="academy" tone="blue" size="lg" class="mx-auto" />
                                <p class="mt-4 text-sm font-black uppercase tracking-widest text-blue-100">ANME Academy Course</p>
                            </div>
                        </div>
                    @endif
                </div>

                <x-ui.card padding="p-8" id="reviews">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="eyebrow">Student Reviews</p>
                            <h2 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">Trusted learner feedback</h2>
                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Only admin-approved reviews appear publicly.</p>
                        </div>
                        <div class="rounded-[1.5rem] border border-orange-100 bg-orange-50 px-5 py-4 text-center dark:border-orange-900 dark:bg-orange-950/30">
                            <div class="flex justify-center gap-1 text-orange-500">
                                @for($star = 1; $star <= 5; $star++)
                                    <span class="text-lg leading-none {{ $star <= round((float) ($course->approved_reviews_avg_rating ?? 0)) ? '' : 'opacity-25' }}">★</span>
                                @endfor
                            </div>
                            <p class="mt-1 text-2xl font-black text-slate-950 dark:text-white">{{ (int) ($course->approved_reviews_count ?? 0) > 0 ? number_format((float) $course->approved_reviews_avg_rating, 1) : 'New' }}</p>
                            <p class="text-xs font-black uppercase tracking-widest text-slate-400">{{ (int) ($course->approved_reviews_count ?? 0) }} approved reviews</p>
                        </div>
                    </div>

                    <div class="mt-7 grid gap-4">
                        @forelse($course->approvedReviews as $review)
                            <article class="rounded-3xl border border-slate-100 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/40">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="flex items-start gap-3">
                                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-blue-50 text-sm font-black text-blue-700 ring-1 ring-blue-100 dark:bg-blue-950 dark:text-blue-200 dark:ring-blue-900">
                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($review->user?->name ?? 'S', 0, 1)) }}
                                        </span>
                                        <div>
                                            <h3 class="font-black text-slate-950 dark:text-white">{{ $review->title ?: 'Helpful course experience' }}</h3>
                                            <p class="mt-1 text-xs font-bold uppercase tracking-widest text-slate-400">{{ $review->user?->name ?? 'Student' }} &middot; {{ $review->reviewed_at?->format('d M Y') ?? $review->created_at->format('d M Y') }}</p>
                                        </div>
                                    </div>
                                    <div class="flex gap-1 text-orange-500">
                                        @for($star = 1; $star <= 5; $star++)
                                            <span class="text-base leading-none {{ $star <= $review->rating ? '' : 'opacity-25' }}">★</span>
                                        @endfor
                                    </div>
                                </div>
                                <p class="mt-4 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $review->body }}</p>
                            </article>
                        @empty
                            <x-ui.empty-state title="No public reviews yet" description="Approved student feedback will appear here after learners submit reviews." />
                        @endforelse
                    </div>

                    @auth
                        @if($canReview)
                            <div id="review-course" class="mt-8 rounded-[1.75rem] border border-blue-100 bg-blue-50/70 p-5 dark:border-blue-900 dark:bg-blue-950/30">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <h3 class="text-xl font-black text-slate-950 dark:text-white">{{ $existingReview ? 'Update your review' : 'Write a review' }}</h3>
                                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                            {{ $existingReview ? 'Current status: '.ucfirst($existingReview->status).'. Updating sends it back for approval.' : 'Share your learning experience. Admin approval is required before public display.' }}
                                        </p>
                                    </div>
                                    @if($existingReview)
                                        <x-ui.badge :variant="$existingReview->status === 'approved' ? 'green' : ($existingReview->status === 'rejected' ? 'red' : 'orange')">{{ $existingReview->status }}</x-ui.badge>
                                    @endif
                                </div>

                                <form method="POST" action="{{ route('courses.reviews.store', $course) }}" class="mt-5 grid gap-4">
                                    @csrf
                                    <div>
                                        <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">Rating</label>
                                        <div class="flex flex-wrap gap-2">
                                            @for($rating = 5; $rating >= 1; $rating--)
                                                <label class="cursor-pointer rounded-2xl border border-white bg-white px-4 py-3 text-sm font-black text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-orange-200 hover:text-orange-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100">
                                                    <input type="radio" name="rating" value="{{ $rating }}" class="mr-2 text-orange-500" @checked(old('rating', $existingReview?->rating ?? 5) == $rating)>
                                                    {{ $rating }} ★
                                                </label>
                                            @endfor
                                        </div>
                                        <x-input-error :messages="$errors->get('rating')" class="mt-2" />
                                    </div>

                                    <div class="grid gap-4 lg:grid-cols-2">
                                        <label>
                                            <span class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">Review title</span>
                                            <input name="title" value="{{ old('title', $existingReview?->title) }}" class="admin-input" placeholder="Example: Great practical course">
                                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                                        </label>
                                        <label>
                                            <span class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">Short message</span>
                                            <textarea name="body" rows="4" class="admin-input resize-y" placeholder="Write what helped you, course quality, teaching style, and your result...">{{ old('body', $existingReview?->body) }}</textarea>
                                            <x-input-error :messages="$errors->get('body')" class="mt-2" />
                                        </label>
                                    </div>

                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Your review stays private until admin approves it.</p>
                                        <x-ui.button type="submit">{{ $existingReview ? 'Update Review' : 'Submit Review' }}</x-ui.button>
                                    </div>
                                </form>
                            </div>
                        @else
                            <div class="mt-8 rounded-[1.75rem] border border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950/40">
                                <h3 class="font-black text-slate-950 dark:text-white">Want to review this course?</h3>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Reviews unlock after paid course access or active enrolment.</p>
                            </div>
                        @endif
                    @else
                        <div class="mt-8 rounded-[1.75rem] border border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950/40">
                            <h3 class="font-black text-slate-950 dark:text-white">Login to write a review</h3>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Students with paid course access can submit feedback for admin approval.</p>
                            <x-ui.button :href="route('login')" variant="secondary" class="mt-4">Login</x-ui.button>
                        </div>
                    @endauth
                </x-ui.card>

                <x-ui.card padding="p-8">
                    <h2 class="text-2xl font-black text-slate-950 dark:text-white">What you will learn</h2>
                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        @forelse(($course->outcomes ?? []) as $outcome)
                            <div class="flex gap-3 rounded-2xl bg-blue-50 p-4 dark:bg-blue-950/40">
                                <span class="mt-1 h-2.5 w-2.5 rounded-full bg-blue-700"></span>
                                <p class="font-semibold text-slate-700 dark:text-slate-200">{{ $outcome }}</p>
                            </div>
                        @empty
                            <p class="text-slate-500 dark:text-slate-400">Learning outcomes will be added soon.</p>
                        @endforelse
                    </div>
                </x-ui.card>

                <x-ui.card padding="p-8">
                    <h2 class="text-2xl font-black text-slate-950 dark:text-white">Course curriculum</h2>
                    <div class="mt-5 divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 dark:divide-slate-800 dark:border-slate-800">
                        @forelse(($course->curriculum ?? []) as $index => $lesson)
                            <div class="flex items-center gap-4 bg-white p-4 dark:bg-slate-950/40">
                                <span class="grid h-10 w-10 place-items-center rounded-xl bg-slate-950 text-sm font-black text-white dark:bg-blue-700">{{ $index + 1 }}</span>
                                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $lesson }}</span>
                            </div>
                        @empty
                            <div class="p-6 text-slate-500 dark:text-slate-400">Curriculum will be available soon.</div>
                        @endforelse
                    </div>
                </x-ui.card>

                <x-ui.card padding="p-8">
                    <h2 class="text-2xl font-black text-slate-950 dark:text-white">Requirements</h2>
                    <div class="mt-5 grid gap-3">
                        @forelse(($course->requirements ?? []) as $requirement)
                            <div class="flex gap-3 rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/40">
                                <x-ui.icon name="check" class="h-5 w-5 shrink-0 text-blue-700 dark:text-blue-300" />
                                <p class="font-semibold text-slate-700 dark:text-slate-200">{{ $requirement }}</p>
                            </div>
                        @empty
                            <p class="text-slate-500 dark:text-slate-400">No special requirements.</p>
                        @endforelse
                    </div>
                </x-ui.card>
            </div>

            <aside class="h-fit lg:sticky lg:top-28">
                <x-ui.card>
                    <x-ui.badge variant="orange">Course Access</x-ui.badge>
                    <div class="mt-4 text-4xl font-black text-slate-950 dark:text-white">{{ $course->currency }} {{ number_format((float) $course->price) }}</div>
                    <dl class="mt-6 space-y-4 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-slate-500 dark:text-slate-400">Level</dt><dd class="font-bold text-slate-900 dark:text-white">{{ $course->level ?? 'All levels' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500 dark:text-slate-400">Duration</dt><dd class="font-bold text-slate-900 dark:text-white">{{ $course->duration ?? 'Self-paced' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500 dark:text-slate-400">Access</dt><dd class="font-bold text-slate-900 dark:text-white">{{ $course->accessLabel() }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500 dark:text-slate-400">Instructor</dt><dd class="font-bold text-slate-900 dark:text-white">{{ $course->instructor_name ?? 'ANME Team' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500 dark:text-slate-400">Course code</dt><dd class="font-bold text-slate-900 dark:text-white">AC-{{ $course->moodle_course_id }}</dd></div>
                    </dl>
                    <x-ui.button :href="route('checkout.show', $course)" class="mt-6 w-full">Continue to Checkout</x-ui.button>
                    <div class="mt-5 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600 dark:bg-slate-950/40 dark:text-slate-300">
                        <strong class="text-slate-900 dark:text-white">Secure flow:</strong> ANME Academy access is triggered only after payment is marked paid.
                    </div>
                </x-ui.card>
            </aside>
        </div>
    </div>
</x-app-layout>
