<x-app-layout>
    {{-- Edit course: admin academy-linked course ko business product ki tarah polish karta hai. --}}
    <x-slot name="header">
        <div>
            <p class="eyebrow">Edit Course</p>
            <h1 class="admin-page-title">{{ $course->title }}</h1>
            <p class="admin-page-subtitle">Update business-side pricing, featured placement and catalog metadata. Academy learning content is not changed from this panel.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="app-container admin-shell">
            <x-admin.sidebar />

            <div class="space-y-6">
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="mini-stat">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Academy Course</p>
                        <p class="mt-3 text-2xl font-black text-slate-950 dark:text-white">AC-{{ $course->moodle_course_id }}</p>
                        <p class="mt-1 text-xs font-bold text-slate-500 dark:text-slate-400">{{ $course->moodle_shortname ?? 'No shortname' }}</p>
                    </div>
                    <div class="mini-stat">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Current Price</p>
                        <p class="mt-3 text-2xl font-black text-blue-700 dark:text-blue-300">{{ $course->currency }} {{ number_format((float) $course->price) }}</p>
                    </div>
                    <div class="mini-stat">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Catalog Visibility</p>
                        <p class="mt-3 text-2xl font-black text-slate-950 dark:text-white">{{ ucfirst($course->status) }}</p>
                    </div>
                    <div class="mini-stat md:col-span-3">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Access Window</p>
                        <p class="mt-3 text-2xl font-black text-indigo-700 dark:text-indigo-300">{{ $course->accessLabel() }}</p>
                        <p class="mt-1 text-xs font-bold text-slate-500 dark:text-slate-400">
                            Payment approve hone ke baad ANME Academy access isi duration ke sath activate hogi.
                        </p>
                    </div>
                    <div class="mini-stat md:col-span-3">
                        <p class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Public Approval</p>
                        <p class="mt-3 text-2xl font-black {{ $course->isPubliclyAvailable() ? 'text-green-700 dark:text-green-300' : 'text-orange-600 dark:text-orange-300' }}">
                            {{ $course->isPubliclyAvailable() ? 'Allowed on public site' : 'Not visible to students yet' }}
                        </p>
                        <p class="mt-1 text-xs font-bold text-slate-500 dark:text-slate-400">
                            Public page needs: Published status + admin approval + academy visible.
                        </p>
                    </div>
                </div>

                <x-ui.card>
                    <div class="mb-6 rounded-3xl border border-blue-100 bg-blue-50/70 p-5 text-sm font-semibold text-slate-700 dark:border-blue-900 dark:bg-blue-950/30 dark:text-slate-200">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-ui.badge :variant="$course->moodle_visible ? 'green' : 'red'">
                                Academy {{ $course->moodle_visible ? 'Visible' : 'Hidden' }}
                            </x-ui.badge>
                            <x-ui.badge variant="blue">
                                {{ $course->moodle_synced_at ? 'Last synced '.$course->moodle_synced_at->diffForHumans() : 'Not synced yet' }}
                            </x-ui.badge>
                            @if($course->moodle_format)
                                <x-ui.badge variant="slate">{{ $course->moodle_format }}</x-ui.badge>
                            @endif
                        </div>
                        <p class="mt-3">This panel controls business fields like price, featured placement, title and checkout visibility. ANME Academy controls learning content and course availability metadata.</p>
                    </div>

                    <form method="POST" action="{{ route('admin.courses.update', $course) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        <div class="grid gap-5 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <x-input-label for="title" value="Course title" />
                                <x-text-input id="title" name="title" class="mt-2 block w-full" value="{{ old('title', $course->title) }}" placeholder="Enter course title" />
                                <x-input-error :messages="$errors->get('title')" class="mt-2" />
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="short_description" value="Short description" />
                                <textarea id="short_description" name="short_description" rows="4" class="admin-input mt-2 block w-full" placeholder="Enter short course description">{{ old('short_description', $course->short_description) }}</textarea>
                                <x-input-error :messages="$errors->get('short_description')" class="mt-2" />
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="thumbnail" value="Course cover image (optional)" />
                                <div class="mt-2 grid gap-4 rounded-3xl border border-dashed border-blue-200 bg-blue-50/50 p-4 dark:border-blue-900 dark:bg-blue-950/20 lg:grid-cols-[220px_1fr]">
                                    <div class="overflow-hidden rounded-2xl border border-white/70 bg-slate-950 shadow-lg dark:border-slate-800">
                                        @if($course->thumbnail)
                                            <img src="{{ url('storage/'.ltrim($course->thumbnail, '/')) }}" alt="{{ $course->title }} cover image" class="h-36 w-full object-cover">
                                        @else
                                            <div class="grid h-36 place-items-center bg-gradient-to-br from-slate-950 via-indigo-900 to-blue-700 text-white">
                                                <x-ui.icon name="academy" class="h-10 w-10" />
                                            </div>
                                        @endif
                                    </div>

                                    <div class="min-w-0">
                                        <input id="thumbnail" name="thumbnail" type="file" accept="image/jpeg,image/png,image/webp" class="admin-input block w-full cursor-pointer file:mr-4 file:rounded-xl file:border-0 file:bg-blue-700 file:px-4 file:py-2 file:text-sm file:font-black file:text-white hover:file:bg-blue-800" />
                                        <p class="mt-2 text-xs font-semibold leading-5 text-slate-500 dark:text-slate-400">Recommended: 1200×675 JPG/PNG/WebP, max 3MB. Blank chhorne par current image same rahegi.</p>
                                        @if($course->thumbnail)
                                            <label class="mt-3 flex items-center gap-2 text-sm font-bold text-red-600 dark:text-red-300">
                                                <input type="checkbox" name="remove_thumbnail" value="1" class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                                                Remove current image
                                            </label>
                                        @endif
                                        <x-input-error :messages="$errors->get('thumbnail')" class="mt-2" />
                                    </div>
                                </div>
                            </div>

                            <div>
                                <x-input-label for="category_id" value="Category" />
                                <select id="category_id" name="category_id" class="admin-input mt-2 block w-full">
                                    <option value="">No category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" @selected(old('category_id', $course->category_id) == $category->id)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="status" value="Status" />
                                <select id="status" name="status" class="admin-input mt-2 block w-full">
                                    @foreach(['published', 'draft', 'archived'] as $status)
                                        <option value="{{ $status }}" @selected(old('status', $course->status) === $status)>{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="price" value="Price" />
                                <x-text-input id="price" name="price" type="number" step="0.01" class="mt-2 block w-full" value="{{ old('price', $course->price) }}" placeholder="Enter course price" />
                                <x-input-error :messages="$errors->get('price')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="currency" value="Currency" />
                                <x-text-input id="currency" name="currency" maxlength="3" class="mt-2 block w-full uppercase" value="{{ old('currency', $course->currency) }}" placeholder="Enter currency code" />
                                <x-input-error :messages="$errors->get('currency')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="level" value="Level" />
                                <x-text-input id="level" name="level" class="mt-2 block w-full" value="{{ old('level', $course->level) }}" placeholder="Enter course level" />
                            </div>

                            <div>
                                <x-input-label for="duration" value="Duration" />
                                <x-text-input id="duration" name="duration" class="mt-2 block w-full" value="{{ old('duration', $course->duration) }}" placeholder="Enter course duration" />
                            </div>

                            <div>
                                <x-input-label for="access_duration_days" value="Course access duration (days)" />
                                <x-text-input id="access_duration_days" name="access_duration_days" type="number" min="0" max="3650" class="mt-2 block w-full" value="{{ old('access_duration_days', $course->access_duration_days) }}" placeholder="0 = lifetime access" />
                                <p class="mt-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                    0 ya blank = lifetime. Example: 30, 90, 365 days subscription access.
                                </p>
                                <x-input-error :messages="$errors->get('access_duration_days')" class="mt-2" />
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="instructor_name" value="Instructor name" />
                                <x-text-input id="instructor_name" name="instructor_name" class="mt-2 block w-full" value="{{ old('instructor_name', $course->instructor_name) }}" placeholder="Enter instructor name" />
                            </div>

                            <label class="md:col-span-2 flex items-center gap-3 rounded-3xl border border-blue-100 bg-blue-50/70 p-5 font-bold text-slate-700 dark:border-blue-900 dark:bg-blue-950/30 dark:text-slate-200">
                                <input type="checkbox" name="is_featured" value="1" class="rounded border-slate-300 text-blue-700 focus:ring-blue-500" @checked(old('is_featured', $course->is_featured))>
                                <span>
                                    <span class="block font-black text-slate-950 dark:text-white">Feature this course</span>
                                    <span class="mt-1 block text-sm font-semibold text-slate-600 dark:text-slate-300">Featured courses get stronger visibility on the public home page.</span>
                                </span>
                            </label>

                            <label class="md:col-span-2 flex items-start gap-3 rounded-3xl border border-green-100 bg-green-50/80 p-5 font-bold text-slate-700 dark:border-green-900 dark:bg-green-950/30 dark:text-slate-200">
                                <input type="hidden" name="is_admin_approved" value="0">
                                <input type="checkbox" name="is_admin_approved" value="1" class="mt-1 rounded border-slate-300 text-green-700 focus:ring-green-500" @checked(old('is_admin_approved', $course->is_admin_approved))>
                                <span>
                                    <span class="block font-black text-slate-950 dark:text-white">Allow this course on public website</span>
                                    <span class="mt-1 block text-sm font-semibold text-slate-600 dark:text-slate-300">Ye admin approval hai. Iske bina course catalog, detail page aur checkout mein students ko nahi dikhega.</span>
                                </span>
                            </label>
                        </div>

                        <div class="mt-8 flex flex-wrap gap-3">
                            <x-ui.button type="submit">Save Changes</x-ui.button>
                            <x-ui.button href="{{ route('admin.courses.index') }}" variant="secondary">Cancel</x-ui.button>
                            @if($course->isPubliclyAvailable())
                                <x-ui.button href="{{ route('courses.show', $course) }}" variant="dark">View Public Page</x-ui.button>
                            @else
                                <span class="inline-flex min-h-11 items-center rounded-2xl bg-orange-50 px-5 py-3 text-sm font-black text-orange-700 dark:bg-orange-950/40 dark:text-orange-200">
                                    Public page locked until approval
                                </span>
                            @endif
                        </div>
                    </form>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-app-layout>
