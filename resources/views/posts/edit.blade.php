<!DOCTYPE html>
<html lang="en-US" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Post - {{ env('APP_NAME') }}</title>

    <x-assets />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script src="https://cdn.tiny.cloud/1/m2xixv22ghwppb8hhphusr4p397c1u9p22bxtnkwcpuhfmys/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            tinymce.init({
                selector: '#body',
                skin: 'oxide-dark',
                content_css: 'dark',
                plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
                toolbar: 'undo redo | blocks | bold italic underline | bullist numlist blockquote | link image media table | alignleft aligncenter alignright | removeformat code',
                height: 560,
                menubar: false,
                branding: false,
                resize: true,
                automatic_uploads: true,
                images_upload_url: "{{ route('posts.editor-image-upload') }}",
                images_upload_credentials: true,
                file_picker_types: 'image',
                images_upload_handler: function (blobInfo, progress) {
                    return new Promise(function (resolve, reject) {
                        const xhr = new XMLHttpRequest();
                        xhr.withCredentials = true;
                        xhr.open('POST', "{{ route('posts.editor-image-upload') }}");

                        xhr.setRequestHeader(
                            'X-CSRF-TOKEN',
                            document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        );

                        xhr.upload.onprogress = function (e) {
                            progress(e.loaded / e.total * 100);
                        };

                        xhr.onload = function () {
                            if (xhr.status === 403 || xhr.status === 419) {
                                reject({ message: 'CSRF token mismatch.', remove: true });
                                return;
                            }
                            if (xhr.status < 200 || xhr.status >= 300) {
                                reject('HTTP Error: ' + xhr.status);
                                return;
                            }
                            const json = JSON.parse(xhr.responseText);
                            if (!json || typeof json.location !== 'string') {
                                reject('Invalid JSON: ' + xhr.responseText);
                                return;
                            }
                            resolve(json.location);
                        };

                        xhr.onerror = function () {
                            reject('Image upload failed due to a XHR Transport error. Code: ' + xhr.status);
                        };

                        const formData = new FormData();
                        formData.append('file', blobInfo.blob(), blobInfo.filename());
                        xhr.send(formData);
                    });
                },
                setup: function (editor) {
                    editor.on('change keyup', function () {
                        editor.save();
                    });
                }
            });

            const form = document.getElementById('post-form');
            const titleInput = document.getElementById('title');
            const slugInput = document.getElementById('slug');
            
            let slugTouched = true; 

            function slugify(text) {
                return text
                    .toString()
                    .toLowerCase()
                    .trim()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }

            function updatePermalinkPreview() {
                document.getElementById('slug-preview').textContent = slugInput.value || 'your-post-slug';
            }

            titleInput.addEventListener('input', function () {
                if (!slugTouched) {
                    slugInput.value = slugify(this.value);
                    updatePermalinkPreview();
                }
            });

            slugInput.addEventListener('input', function () {
                slugTouched = true;
                this.value = slugify(this.value);
                updatePermalinkPreview();
            });

            form.addEventListener('submit', function () {
                tinymce.triggerSave();
            });

            updatePermalinkPreview();

            window.toggleNewCategory = function () {
                document.getElementById('new-category-box').classList.toggle('hidden');
            };

            window.previewFeaturedImage = function (event) {
                const file = event.target.files[0];
                const preview = document.getElementById('featured-image-preview');
                const placeholder = document.getElementById('featured-image-placeholder');
                const fileName = document.getElementById('featured-image-name');

                if (file) {
                    preview.src = URL.createObjectURL(file);
                    preview.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                    fileName.textContent = file.name;
                } else {
                    const originalImage = preview.getAttribute('data-original');
                    if(originalImage) {
                        preview.src = originalImage;
                        preview.classList.remove('hidden');
                        placeholder.classList.add('hidden');
                        fileName.textContent = 'Keep existing image';
                    } else {
                        preview.src = '';
                        preview.classList.add('hidden');
                        placeholder.classList.remove('hidden');
                        fileName.textContent = 'No file selected';
                    }
                }
            };

            window.createCategory = async function () {
                const input = document.getElementById('new_category');
                const parent = document.getElementById('parent_category_id');
                const select = document.getElementById('categories');
                const message = document.getElementById('category-message');
                const name = input.value.trim();

                if (!name) {
                    message.textContent = 'Category name is required.';
                    message.className = 'mt-2 text-xs text-rose-400';
                    return;
                }

                try {
                    const response = await fetch("{{ route('posts.ajax-store') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            name: name,
                            parent_id: parent.value || null
                        })
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Failed to add category.');
                    }

                    const option = document.createElement('option');
                    option.value = data.id;
                    option.textContent = data.name;
                    option.selected = true;
                    select.appendChild(option);

                    input.value = '';
                    parent.value = '';
                    message.textContent = 'Category added successfully.';
                    message.className = 'mt-2 text-xs text-emerald-400';
                } catch (error) {
                    message.textContent = error.message;
                    message.className = 'mt-2 text-xs text-rose-400';
                }
            };
        });
    </script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-200 font-sans">
    <x-navbar />

    <main class="flex-grow pt-8 pb-32 sm:pt-12 relative overflow-hidden text-slate-300" x-data="{ 
        isSaving: false, 
        toast: { visible: false, message: '', type: 'success' }, 
        showToast(msg, type = 'success') { 
            this.toast.message = msg; 
            this.toast.type = type; 
            this.toast.visible = true; 
            setTimeout(() => this.toast.visible = false, 3000); 
        }, 
        async submitForm(e) { 
            if(this.isSaving) return; 
            this.isSaving = true; 
            let formData = new FormData(e.target); 
            try { 
                let response = await fetch(e.target.action, { 
                    method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } 
                }); 
                let resData = await response.json().catch(() => ({})); 
                if (response.ok) { 
                    this.showToast('Post updated successfully!', 'success'); 
                } else { 
                    if (response.status === 422 && resData.errors) { 
                        const firstKey = Object.keys(resData.errors)[0]; 
                        this.showToast(resData.errors[firstKey][0], 'error'); 
                    } else { 
                        this.showToast(resData.message || 'Failed to update post.', 'error'); 
                    } 
                } 
            } catch (err) { 
                this.showToast('Network error occurred.', 'error'); 
            } finally { 
                this.isSaving = false; 
            } 
        } 
    }">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-[500px] bg-cyan-500/10 blur-[120px] pointer-events-none rounded-full" aria-hidden="true"></div>

        <div x-cloak x-show="toast.visible" 
            x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-y-[-1rem] scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" 
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" 
            :class="toast.type === 'success' ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : 'bg-red-500/10 border-red-500/30 text-red-400'" 
            class="fixed top-24 right-4 sm:right-8 z-[100] px-5 py-3.5 rounded-2xl border font-bold text-sm flex items-center gap-3 shadow-[0_10px_40px_rgba(0,0,0,0.5)] backdrop-blur-xl">
            <svg x-show="toast.type === 'success'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <svg x-show="toast.type === 'error'" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span x-text="toast.message"></span>
        </div>

        <div class="max-w-[90rem] mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="mb-8 flex flex-wrap items-end justify-between gap-6 border-b border-white/10 pb-6">
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-500 uppercase tracking-widest">
                        <a href="{{ route('posts.index') }}" class="hover:text-cyan-400 transition-colors">Admin Panel</a>
                        <span class="text-slate-700">&bull;</span>
                        <span class="text-slate-400">Posts</span>
                        <span class="text-slate-700">&bull;</span>
                        <span class="text-slate-500 bg-slate-800/50 px-2 py-0.5 rounded-md border border-white/5">#{{ $post->id }}</span>
                    </div>
                    <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tight leading-none">
                        Edit <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500 drop-shadow-sm">Post</span>
                    </h1>
                    <p class="text-sm text-slate-400 font-medium pt-1">Update your article, manage settings, and publish from one screen.</p>
                </div>
                <a href="{{ route('posts.index') }}" class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-400 hover:text-white bg-slate-900 border border-white/10 hover:border-white/20 hover:bg-slate-800 transition-all shadow-lg">
                    Back to Posts
                </a>
            </div>

            @if($errors->any())
                <div class="mb-8 bg-red-500/10 border border-red-500/30 rounded-2xl p-5 shadow-lg backdrop-blur-sm">
                    <div class="flex items-center gap-2 text-red-400 font-bold mb-3 text-sm uppercase tracking-wider">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Please fix the following errors:
                    </div>
                    <ul class="list-inside list-disc space-y-1 text-sm text-red-300/80 font-medium ml-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="post-form" action="{{ route('posts.update', $post->id) }}" method="POST" enctype="multipart/form-data" @submit.prevent="submitForm">
                @csrf
                @method('PATCH')
                
                <div class="flex flex-col lg:grid lg:grid-cols-12 gap-8 lg:gap-10">
                    
                    <div class="lg:col-span-8 space-y-8">
                        
                        <div class="bg-slate-900/60 border border-white/10 p-6 sm:p-8 rounded-[2rem] shadow-xl backdrop-blur-md space-y-6 relative overflow-hidden group">
                            <div class="absolute -top-24 -right-24 w-48 h-48 bg-cyan-500/10 blur-[80px] group-hover:bg-cyan-500/20 transition-colors"></div>

                            <div class="space-y-6">
                                <div>
                                    <label for="title" class="block text-xs font-bold text-slate-400 mb-2 uppercase tracking-wider">Post Title</label>
                                    <input type="text" name="title" id="title" value="{{ old('title', $post->title) }}" required placeholder="Add title" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-5 py-4 text-white font-black text-2xl sm:text-3xl focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner placeholder-slate-700">
                                </div>

                                <div x-data="{ slug: '{{ old('slug', $post->slug) }}', generateSlug() { this.slug = document.getElementById('title').value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, ''); } }">
                                    <label for="slug" class="flex justify-between items-center text-xs font-bold text-slate-400 mb-2 uppercase tracking-wider">
                                        Permalink
                                        <button type="button" @click="generateSlug()" class="text-[10px] text-cyan-400 hover:text-cyan-300 bg-cyan-500/10 px-2 py-1 rounded border border-cyan-500/20 transition-colors">Auto-generate</button>
                                    </label>
                                    <div class="flex flex-col sm:flex-row overflow-hidden rounded-xl border border-slate-800 bg-slate-950 focus-within:ring-2 focus-within:ring-cyan-500/50 transition-all shadow-inner">
                                        <span class="px-4 py-3.5 text-sm font-medium text-slate-500 border-b sm:border-b-0 sm:border-r border-slate-800 bg-slate-900/50 flex-shrink-0">{{ url('/posts') }}/</span>
                                        <input type="text" name="slug" id="slug" x-model="slug" required class="flex-1 bg-transparent px-4 py-3.5 text-sm text-cyan-300 font-mono focus:outline-none placeholder-slate-700">
                                    </div>
                                </div>

                                <div class="pt-2">
                                    <label for="body" class="block text-xs font-bold text-slate-400 mb-2 uppercase tracking-wider flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span> Content Body
                                    </label>
                                    <textarea name="body" id="body" rows="18" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-5 text-slate-200 text-sm leading-relaxed resize-y focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner placeholder-slate-700">{{ old('body', $post->body) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-900/60 border border-white/10 p-6 sm:p-8 rounded-[2rem] shadow-xl backdrop-blur-md space-y-6 relative overflow-hidden group">
                            <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-purple-500/10 blur-[100px] pointer-events-none"></div>

                            <h2 class="text-xl font-black text-white flex items-center gap-3 border-b border-white/5 pb-4">
                                <span class="w-1.5 h-6 bg-gradient-to-b from-purple-400 to-indigo-600 rounded-full shadow-[0_0_10px_rgba(168,85,247,0.5)]"></span>
                                Meta & SEO Summary
                            </h2>

                            <div class="space-y-6">
                                <div>
                                    <label for="excerpt" class="block text-sm font-bold text-slate-400 mb-2">Short Excerpt</label>
                                    <textarea name="excerpt" id="excerpt" rows="3" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-4 text-white text-sm resize-y focus:ring-2 focus:ring-purple-500/50 outline-none transition-all shadow-inner placeholder-slate-600" placeholder="Write a short summary for preview...">{{ old('excerpt', $post->excerpt) }}</textarea>
                                </div>

                                <div>
                                    <label for="keywords" class="block text-sm font-bold text-slate-400 mb-2">SEO Keywords</label>
                                    <textarea name="keywords" id="keywords" rows="2" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-4 text-white text-sm resize-none focus:ring-2 focus:ring-purple-500/50 outline-none transition-all shadow-inner placeholder-slate-600" placeholder="anime, wallpaper, gaming">{{ old('keywords', $post->keywords) }}</textarea>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="lg:col-span-4 relative">
                        <div class="sticky top-24 space-y-6">
                            
                            <div class="bg-slate-900/40 border border-white/10 p-6 rounded-3xl shadow-xl backdrop-blur-md space-y-6 relative overflow-hidden">
                                <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/5 blur-[50px] pointer-events-none"></div>
                                
                                <h3 class="text-xs font-black uppercase tracking-widest text-emerald-400 flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Publish Setup
                                </h3>

                                <div class="space-y-4">
                                    <div>
                                        <label for="status" class="block text-[11px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider">Status</label>
                                        <div class="relative">
                                            <select id="status" name="status" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white font-bold appearance-none cursor-pointer focus:ring-2 focus:ring-emerald-500/50 outline-none transition-all shadow-inner">
                                                <option value="draft" @selected(old('status', $post->status) == 'draft')>📝 Draft (Hidden)</option>
                                                <option value="published" @selected(old('status', $post->status) == 'published')>✅ Published (Live)</option>
                                            </select>
                                            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-500"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg></div>
                                        </div>
                                    </div>

                                    <div>
                                        <label for="published_at" class="block text-[11px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider">Publish Date & Time</label>
                                        <input type="datetime-local" name="published_at" id="published_at" value="{{ old('published_at', $post->published_at ? \Carbon\Carbon::parse($post->published_at)->format('Y-m-d\TH:i') : '') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:ring-2 focus:ring-emerald-500/50 outline-none transition-all shadow-inner [color-scheme:dark]">
                                    </div>
                                </div>
                            </div>

                            <div class="bg-slate-900/40 border border-white/10 p-4 sm:p-5 rounded-[2rem] shadow-xl backdrop-blur-md">
                                <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-400 pb-3 border-b border-white/5 mb-4 flex items-center gap-2">
                                    <span class="w-1 h-1 rounded-full bg-slate-500"></span> Featured Image
                                </h3>
                                
                                <div class="p-2">
                                    <label for="featured_image" class="block cursor-pointer">
                                        <div class="rounded-xl border border-dashed border-slate-700 bg-slate-950/70 p-3 transition-colors hover:border-cyan-500">
                                            <div class="mb-3 flex h-44 items-center justify-center overflow-hidden rounded-lg bg-slate-900">
                                                <img id="featured-image-preview" data-original="{{ $post->featured_image }}" src="{{ $post->featured_image }}" class="{{ $post->featured_image ? '' : 'hidden' }} h-full w-full object-cover" alt="Preview">
                                                <span id="featured-image-placeholder" class="{{ $post->featured_image ? 'hidden' : '' }} text-sm text-slate-500">Upload image preview</span>
                                            </div>
                                            <div class="text-sm font-medium text-slate-200">Change Featured Image</div>
                                            <div id="featured-image-name" class="mt-1 text-xs text-slate-500">Keep existing image or upload new</div>
                                        </div>
                                    </label>
                                    <input type="file" name="featured_image" id="featured_image" accept="image/*" onchange="previewFeaturedImage(event)" class="hidden">
                                </div>

                                <script>
                                    function previewFeaturedImage(event) {
                                        const file = event.target.files[0];
                                        if (file) {
                                            const preview = document.getElementById('featured-image-preview');
                                            const placeholder = document.getElementById('featured-image-placeholder');
                                            const fileName = document.getElementById('featured-image-name');
                                            
                                            preview.src = URL.createObjectURL(file);
                                            preview.classList.remove('hidden');
                                            if(placeholder) placeholder.classList.add('hidden');
                                            if(fileName) fileName.textContent = file.name;
                                        }
                                    }
                                </script>
                            </div>

                            <div class="bg-slate-900/40 border border-white/10 p-6 rounded-3xl shadow-xl backdrop-blur-md space-y-5 relative overflow-hidden" x-data="{ showNew: false }">
                                <div class="flex items-center justify-between border-b border-white/5 pb-3">
                                    <h3 class="text-xs font-black uppercase tracking-widest text-slate-300 flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Categories
                                    </h3>
                                    <button type="button" @click="showNew = !showNew" class="text-[10px] font-bold bg-cyan-500/10 border border-cyan-500/20 px-2 py-1 rounded text-cyan-400 hover:text-white hover:bg-cyan-500 transition-colors uppercase tracking-widest">+ Add New</button>
                                </div>

                                <div class="space-y-4 pt-1">
                                    <div>
                                        <label for="categories" class="block text-[11px] font-bold text-slate-400 mb-1.5 uppercase tracking-wider">Select Categories</label>
                                        <select name="categories[]" id="categories" multiple class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white font-medium focus:ring-2 focus:ring-cyan-500/50 outline-none transition-all shadow-inner min-h-[120px]">
                                            @php
                                                $selectedCats = old('categories', $post->category_ids ?? []);
                                            @endphp
                                            @if(isset($categories))
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}" @selected(in_array($category->id, $selectedCats))>{{ $category->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <p class="mt-2 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Hold CTRL (Win) or CMD (Mac) to select multiple</p>
                                    </div>

                                    <div x-show="showNew" x-collapse x-cloak class="space-y-3 bg-slate-950/60 border border-slate-800 p-4 rounded-2xl shadow-inner mt-2">
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 border-b border-slate-800 pb-2">Quick Add Category</p>
                                        
                                        <input type="text" id="new_category" placeholder="New category name" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-cyan-500 focus:outline-none transition-all shadow-inner">
                                        
                                        <div class="relative">
                                            <select id="parent_category_id" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-sm text-slate-300 appearance-none cursor-pointer focus:border-cyan-500 focus:outline-none transition-all shadow-inner">
                                                <option value="">No parent (Top Level)</option>
                                                @if(isset($categories))
                                                    @foreach($categories as $category)
                                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none text-slate-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg></div>
                                        </div>

                                        <button type="button" onclick="typeof createCategory === 'function' ? createCategory() : alert('Category functionality running...')" class="w-full bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 rounded-xl px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider transition-colors shadow-sm mt-1">
                                            Add Category
                                        </button>
                                        <p id="category-message" class="text-[10px] text-center font-bold text-emerald-400 mt-2 empty:hidden"></p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="fixed bottom-0 left-0 right-0 z-50 bg-slate-950/80 backdrop-blur-xl border-t border-white/10 py-4 shadow-[0_-10px_40px_rgba(0,0,0,0.5)]">
            <div class="max-w-[90rem] mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-end gap-4">
                <div class="flex items-center w-full sm:w-auto gap-4">
                    <a href="{{ route('posts.index') }}" class="flex-1 sm:flex-none text-center px-6 py-3 rounded-xl font-bold text-slate-400 hover:text-white bg-slate-900 border border-white/5 hover:border-white/20 transition-all text-sm">Cancel</a>
                    <button type="submit" form="post-form" :disabled="isSaving" class="flex-1 sm:flex-none px-8 py-3 rounded-xl font-black text-slate-950 bg-gradient-to-r from-cyan-400 to-blue-500 hover:from-cyan-300 hover:to-blue-400 shadow-[0_0_20px_rgba(34,211,238,0.2)] hover:shadow-[0_0_30px_rgba(34,211,238,0.4)] transition-all flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-wait">
                        <svg x-cloak x-show="isSaving" class="w-5 h-5 animate-spin text-slate-950" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        <span x-text="isSaving ? 'Saving...' : 'Save Changes'"></span>
                    </button>
                </div>
            </div>
        </div>
    </main>

    <x-footer />
</body>
</html>