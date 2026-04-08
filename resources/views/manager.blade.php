@props([
    'initial' => [],
])

<meta name="csrf-token" content="{{ csrf_token() }}">

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('mediaManager', (initial) => ({
        files: initial.files || [],
        path: initial.path || '/',
        view: initial.view || 'table',
        navigation: initial.navigation || {},
        urls: initial.urls || {},
        loading: false,
        jumpPath: initial.path || '/',

        renamePath: '',
        renameNew: '',
        deleteFiles: [],
        urlToShow: '',
        newFolderName: '',

        init() {
            window.addEventListener('popstate', (e) => {
                if (e.state && e.state.path !== undefined) {
                    this.loadFiles(e.state.path, e.state.view, false);
                }
            });
        },

        get csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        },

        get ajaxHeaders() {
            return {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
            };
        },

        async loadFiles(path, view, pushState = true) {
            this.loading = true;
            path = path || '/';
            view = view || this.view;

            try {
                const params = new URLSearchParams({ path, view });
                const resp = await fetch(this.urls.index + '?' + params.toString(), {
                    headers: this.ajaxHeaders,
                });
                const data = await resp.json();

                if (data.status) {
                    this.files = data.files;
                    this.navigation = data.navigation;
                    this.urls = data.urls;
                    this.path = data.path;
                    this.view = data.view;
                    this.jumpPath = data.path;

                    if (pushState) {
                        this.syncUrl(data.path, data.view);
                    }
                } else {
                    this.toast(data.message || 'Error', 'error');
                }
            } catch (e) {
                this.toast(e.message || 'Request failed', 'error');
            }

            this.loading = false;
        },

        navigate(itemPath) {
            this.loadFiles(itemPath);
        },

        refresh() {
            this.loadFiles(this.path);
        },

        switchView(viewType) {
            this.view = viewType;
            this.loadFiles(this.path, viewType);
        },

        quickJump() {
            const p = this.jumpPath.trim();
            if (p) {
                this.loadFiles(p);
            }
        },

        syncUrl(path, view) {
            const params = new URLSearchParams({ path, view });
            const url = this.urls.page + '?' + params.toString();
            history.pushState({ path, view }, '', url);
        },

        extractPathFromUrl(url) {
            try {
                const u = new URL(url, window.location.origin);
                return u.searchParams.get('path') || '/';
            } catch {
                return '/';
            }
        },

        openRenameModal(file) {
            this.renamePath = file.path;
            this.renameNew = file.path;
            window.MoonShine.ui.toggleModal('mm-rename');
        },

        openDeleteModal(file) {
            this.deleteFiles = [file.path];
            window.MoonShine.ui.toggleModal('mm-delete');
        },

        openUploadModal() {
            window.MoonShine.ui.toggleModal('mm-upload');
        },

        openNewFolderModal() {
            this.newFolderName = '';
            window.MoonShine.ui.toggleModal('mm-new-folder');
        },

        openUrlModal(file) {
            this.urlToShow = file.url || '';
            window.MoonShine.ui.toggleModal('mm-url');
        },

        async submitUpload() {
            const input = document.getElementById('mm-upload-input');
            if (!input || !input.files.length) return;

            const formData = new FormData();
            for (const f of input.files) {
                formData.append('files[]', f);
            }
            formData.append('dir', this.path);

            try {
                const resp = await fetch(this.urls.upload, {
                    method: 'POST',
                    body: formData,
                    headers: this.ajaxHeaders,
                });
                const data = await resp.json();

                if (data.status) {
                    this.toast(data.message || 'Uploaded', 'success');
                    window.MoonShine.ui.toggleModal('mm-upload');
                    input.value = '';
                    this.refresh();
                } else {
                    this.toast(data.message || 'Upload failed', 'error');
                }
            } catch (e) {
                this.toast(e.message || 'Upload failed', 'error');
            }
        },

        async submitDelete() {
            if (!this.deleteFiles.length) return;

            try {
                const params = new URLSearchParams();
                this.deleteFiles.forEach(f => params.append('files[]', f));

                const resp = await fetch(this.urls.delete, {
                    method: 'POST',
                    body: params,
                    headers: {
                        ...this.ajaxHeaders,
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                });
                const data = await resp.json();

                if (data.status) {
                    this.toast(data.message || 'Deleted', 'success');
                    window.MoonShine.ui.toggleModal('mm-delete');
                    this.deleteFiles = [];
                    this.refresh();
                } else {
                    this.toast(data.message || 'Delete failed', 'error');
                }
            } catch (e) {
                this.toast(e.message || 'Delete failed', 'error');
            }
        },

        async submitRename() {
            if (!this.renameNew.trim()) return;

            try {
                const params = new URLSearchParams({
                    path: this.renamePath,
                    new: this.renameNew,
                });

                const resp = await fetch(this.urls.move, {
                    method: 'POST',
                    body: params,
                    headers: {
                        ...this.ajaxHeaders,
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                });
                const data = await resp.json();

                if (data.status) {
                    this.toast(data.message || 'Renamed', 'success');
                    window.MoonShine.ui.toggleModal('mm-rename');
                    this.refresh();
                } else {
                    this.toast(data.message || 'Rename failed', 'error');
                }
            } catch (e) {
                this.toast(e.message || 'Rename failed', 'error');
            }
        },

        async submitNewFolder() {
            if (!this.newFolderName.trim()) return;

            try {
                const params = new URLSearchParams({
                    dir: this.path,
                    name: this.newFolderName,
                });

                const resp = await fetch(this.urls['new-folder'], {
                    method: 'POST',
                    body: params,
                    headers: {
                        ...this.ajaxHeaders,
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                });
                const data = await resp.json();

                if (data.status) {
                    this.toast(data.message || 'Folder created', 'success');
                    window.MoonShine.ui.toggleModal('mm-new-folder');
                    this.newFolderName = '';
                    this.refresh();
                } else {
                    this.toast(data.message || 'Failed to create folder', 'error');
                }
            } catch (e) {
                this.toast(e.message || 'Failed to create folder', 'error');
            }
        },

        download(file) {
            window.open(file.download, '_blank');
        },

        toast(message, type) {
            if (window.MoonShine && window.MoonShine.ui && window.MoonShine.ui.toast) {
                window.MoonShine.ui.toast(message, type);
            }
        },

        basename(path) {
            return path.split('/').filter(Boolean).pop() || path;
        },
    }));
});
</script>

<div x-data="mediaManager({{ Js::from($initial) }})" x-init="init()">

    {{-- Toolbar --}}
    <div class="flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-2">
            <x-moonshine::link-button @click.prevent="refresh()" class="btn-warning">
                <x-moonshine::icon icon="arrow-path"/>
                {{ __('moonshine-media-manager::media-manager.refresh') }}
            </x-moonshine::link-button>

            <x-moonshine::link-button @click.prevent="openUploadModal()" class="btn-success">
                <x-moonshine::icon icon="cloud-arrow-up"/>
                {{ __('moonshine-media-manager::media-manager.upload') }}
            </x-moonshine::link-button>

            <x-moonshine::link-button @click.prevent="openNewFolderModal()" class="btn-secondary">
                <x-moonshine::icon icon="folder"/>
                {{ __('moonshine-media-manager::media-manager.new_folder') }}
            </x-moonshine::link-button>

            <x-moonshine::link-button
                @click.prevent="switchView('table')"
            >
                <x-moonshine::icon icon="list-bullet" x-bind:class="view === 'table' ? 'text-primary' : ''"/>
            </x-moonshine::link-button>

            <x-moonshine::link-button
                @click.prevent="switchView('list')"
            >
                <x-moonshine::icon icon="squares-2x2" x-bind:class="view === 'list' ? 'text-primary' : ''"/>
            </x-moonshine::link-button>
        </div>

        {{-- Quick Jump --}}
        <div class="flex">
            <x-moonshine::form.input
                x-model="jumpPath"
                @keydown.enter.prevent="quickJump()"
                placeholder="Path"
            />
            <x-moonshine::link-button @click.prevent="quickJump()">
                <x-moonshine::icon icon="arrow-small-right"/>
            </x-moonshine::link-button>
        </div>
    </div>

    {{-- Divider --}}
    <hr class="my-4" />

    {{-- Breadcrumbs --}}
    <nav class="flex items-center gap-2 flex-wrap mb-4">
        <template x-for="([url, label], index) in Object.entries(navigation)" :key="url">
            <div class="flex items-center gap-2">
                <a href="#" @click.prevent="navigate(extractPathFromUrl(url))" x-text="label" class="text-sm"></a>
                <span x-show="index < Object.entries(navigation).length - 1" class="text-xs text-gray-400">/</span>
            </div>
        </template>
    </nav>

    {{-- Loading overlay --}}
    <div x-show="loading" x-cloak class="flex justify-center py-12">
        <x-moonshine::loader />
    </div>

    {{-- Table View --}}
    <div x-show="view === 'table' && !loading">
        <x-moonshine::table>
            <x-slot:thead>
                <tr>
                    <th>{{ __('moonshine-media-manager::media-manager.name') }}</th>
                    <th>{{ __('moonshine-media-manager::media-manager.time') }}</th>
                    <th>{{ __('moonshine-media-manager::media-manager.size') }}</th>
                    <th></th>
                </tr>
            </x-slot:thead>
            <x-slot:tbody>
                <template x-for="file in files" :key="file.path">
                    <tr>
                        <td>
                            <a href="#"
                               @click.prevent="file.isDir && navigate(extractPathFromUrl(file.link))"
                               x-bind:title="file.path"
                               class="flex gap-2 items-center"
                            >
                                <span x-html="file.isDir ? '' : (file.type === 'image' && file.preview ? file.preview : '')"></span>
                                <x-moonshine::icon x-show="file.isDir" icon="folder" class="file-preview"/>
                                <template x-if="!file.isDir && file.type !== 'image'">
                                    <x-moonshine::icon icon="document" class="file-preview"/>
                                </template>
                                <div x-text="basename(file.path)"></div>
                            </a>
                        </td>
                        <td x-text="file.time"></td>
                        <td x-text="file.size"></td>
                        <td>
                            <div class="flex justify-end gap-2">
                                <x-moonshine::link-button
                                    x-show="!file.isDir"
                                    @click.prevent="download(file)"
                                    class="btn-sm btn-success"
                                >
                                    <x-moonshine::icon icon="cloud-arrow-down"/>
                                </x-moonshine::link-button>

                                <x-moonshine::link-button
                                    @click.prevent="openUrlModal(file)"
                                    class="btn-sm"
                                >
                                    <x-moonshine::icon icon="globe-alt"/>
                                </x-moonshine::link-button>

                                <x-moonshine::link-button
                                    @click.prevent="openRenameModal(file)"
                                    class="btn-sm btn-primary"
                                >
                                    <x-moonshine::icon icon="pencil"/>
                                </x-moonshine::link-button>

                                <x-moonshine::link-button
                                    @click.prevent="openDeleteModal(file)"
                                    class="btn-sm btn-error"
                                >
                                    <x-moonshine::icon icon="trash"/>
                                </x-moonshine::link-button>
                            </div>
                        </td>
                    </tr>
                </template>
            </x-slot:tbody>
        </x-moonshine::table>
    </div>

    {{-- List View --}}
    <div x-show="view === 'list' && !loading">
        <div class="flex flex-wrap gap-2.5 mt-4">
            <template x-for="file in files" :key="file.path">
                <div class="w-[150px] rounded-xl border border-gray-200 mb-2.5 p-2 flex flex-col items-center justify-between">
                    <div class="flex flex-col items-center w-full">
                        <span x-html="file.isDir ? '' : (file.type === 'image' && file.preview ? file.preview : '')"></span>
                        <x-moonshine::icon x-show="file.isDir" icon="folder" class="size-20"/>
                        <template x-if="!file.isDir && file.type !== 'image'">
                            <x-moonshine::icon icon="document" class="size-20"/>
                        </template>
                        <a href="#"
                           @click.prevent="file.isDir ? navigate(extractPathFromUrl(file.link)) : null"
                           x-bind:title="file.path"
                           class="font-bold text-gray-600 block w-full text-center overflow-hidden whitespace-nowrap text-ellipsis"
                           x-text="basename(file.path)"
                        ></a>
                        <span class="text-gray-400 text-xs" x-text="file.size"></span>
                    </div>

                    <x-moonshine::dropdown>
                        <div class="dropdown-menu">
                            <x-moonshine::link-button
                                x-show="!file.isDir"
                                @click.prevent="download(file)"
                                class="w-full"
                            >
                                <x-moonshine::icon icon="cloud-arrow-down"/>
                                {{ __('moonshine-media-manager::media-manager.download') }}
                            </x-moonshine::link-button>
                            <x-moonshine::link-button
                                @click.prevent="openUrlModal(file)"
                                class="w-full"
                            >
                                <x-moonshine::icon icon="globe-alt"/>
                                {{ __('moonshine-media-manager::media-manager.url') }}
                            </x-moonshine::link-button>
                            <x-moonshine::link-button
                                @click.prevent="openRenameModal(file)"
                                class="w-full btn-primary"
                            >
                                <x-moonshine::icon icon="pencil"/>
                                {{ __('moonshine-media-manager::media-manager.rename') }}
                            </x-moonshine::link-button>
                            <x-moonshine::link-button
                                @click.prevent="openDeleteModal(file)"
                                class="w-full btn-error"
                            >
                                <x-moonshine::icon icon="trash"/>
                                {{ __('moonshine-media-manager::media-manager.delete') }}
                            </x-moonshine::link-button>
                        </div>
                        <x-slot:toggler>
                            <x-moonshine::icon icon="ellipsis-horizontal"/>
                        </x-slot:toggler>
                    </x-moonshine::dropdown>
                </div>
            </template>
        </div>
    </div>

    {{-- Empty state --}}
    <div x-show="!loading && files.length === 0" class="text-center py-12 text-gray-400">
        Empty directory
    </div>

    {{-- Modal: Upload --}}
    <x-moonshine::modal name="mm-upload" :title="__('moonshine-media-manager::media-manager.upload')" :closeOutside="true">
        <form @submit.prevent="submitUpload()">
            <div class="flex flex-col gap-4">
                <div>
                    <input type="file" id="mm-upload-input" name="files[]" multiple required class="file-input" />
                </div>
                <x-moonshine::form.button type="submit">
                    {{ __('moonshine-media-manager::media-manager.submit') }}
                </x-moonshine::form.button>
            </div>
        </form>
    </x-moonshine::modal>

    {{-- Modal: Rename --}}
    <x-moonshine::modal name="mm-rename" :title="__('moonshine-media-manager::media-manager.rename')" :closeOutside="true">
        <form @submit.prevent="submitRename()">
            <div class="flex flex-col gap-4">
                <x-moonshine::form.input x-model="renameNew" placeholder="{{ __('moonshine-media-manager::media-manager.new_path') }}" />
                <x-moonshine::form.button type="submit">
                    {{ __('moonshine-media-manager::media-manager.submit') }}
                </x-moonshine::form.button>
            </div>
        </form>
    </x-moonshine::modal>

    {{-- Modal: New Folder --}}
    <x-moonshine::modal name="mm-new-folder" :title="__('moonshine-media-manager::media-manager.new_folder')" :closeOutside="true">
        <form @submit.prevent="submitNewFolder()">
            <div class="flex flex-col gap-4">
                <x-moonshine::form.input x-model="newFolderName" placeholder="{{ __('moonshine-media-manager::media-manager.name') }}" />
                <x-moonshine::form.button type="submit">
                    {{ __('moonshine-media-manager::media-manager.submit') }}
                </x-moonshine::form.button>
            </div>
        </form>
    </x-moonshine::modal>

    {{-- Modal: Delete Confirm --}}
    <x-moonshine::modal name="mm-delete" :title="__('moonshine-media-manager::media-manager.delete')" :closeOutside="true">
        <div class="flex flex-col gap-4">
            <p>{{ __('moonshine-media-manager::media-manager.confirm_message') }}</p>
            <div class="flex gap-2 justify-end">
                <x-moonshine::form.button @click.prevent="window.MoonShine.ui.toggleModal('mm-delete')" class="btn-secondary">
                    {{ __('moonshine-media-manager::media-manager.close') }}
                </x-moonshine::form.button>
                <x-moonshine::form.button @click.prevent="submitDelete()" class="btn-error">
                    {{ __('moonshine-media-manager::media-manager.delete') }}
                </x-moonshine::form.button>
            </div>
        </div>
    </x-moonshine::modal>

    {{-- Modal: URL --}}
    <x-moonshine::modal name="mm-url" :title="__('moonshine-media-manager::media-manager.url')" :closeOutside="true">
        <div class="flex flex-col gap-4">
            <div class="break-all select-all" x-text="urlToShow"></div>
            <div class="flex justify-end">
                <x-moonshine::form.button @click.prevent="window.MoonShine.ui.toggleModal('mm-url')">
                    {{ __('moonshine-media-manager::media-manager.close') }}
                </x-moonshine::form.button>
            </div>
        </div>
    </x-moonshine::modal>

</div>
