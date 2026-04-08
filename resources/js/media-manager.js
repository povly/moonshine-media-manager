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
