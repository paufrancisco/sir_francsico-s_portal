import { reactive, ref, computed, onBeforeUnmount } from 'vue';

// GET  /portal/posts?section_id=ID&before=ID&client_id=X -> { posts, has_more, next_before }
// POST /portal/posts (multipart) { student_number, password, caption, layout, section_id, images[] } -> { message, post }
// POST /portal/posts/{id}/react { student_number, password, reaction, client_id } -> { liked, my_reaction, reaction_counts, likes_count, likers }
const POSTS_URL = '/portal/posts';
export const POST_MAX_CAPTION = 500;
export const POST_MAX_IMAGES = 6;
const MAX_RAW_BYTES = 10 * 1024 * 1024; // largest original a student may pick
const MAX_UPLOAD_BYTES = 5 * 1024 * 1024; // server limit per image
const MAX_SIDE = 1600;
const JPEG_QUALITY = 0.82;

function csrfHeaders() {
    const meta = document.querySelector('meta[name="csrf-token"]')?.content;
    if (meta) return { 'X-CSRF-TOKEN': meta };
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    return match ? { 'X-XSRF-TOKEN': decodeURIComponent(match[1]) } : {};
}

function apiError(data, fallback, res) {
    if (res?.status === 429) return 'Too many attempts. Please wait a moment and try again.';
    if (res?.status === 413) return 'The photos are too large to upload. Try fewer photos.';
    return Object.values(data.errors ?? {})[0]?.[0] || data.message || fallback;
}

async function postJson(url, payload, method = 'POST') {
    const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', ...csrfHeaders() },
        body: JSON.stringify(payload),
    });
    const data = await res.json().catch(() => ({}));
    return { res, data };
}

// Same device id the Freedom Wall uses
function getClientId() {
    try {
        let id = localStorage.getItem('wall_client_id');
        if (!id) {
            id = (crypto.randomUUID ? crypto.randomUUID() : String(Date.now()) + Math.random().toString(36).slice(2)).replace(/[^a-zA-Z0-9-]/g, '');
            localStorage.setItem('wall_client_id', id);
        }
        return id;
    } catch (e) {
        return 'anon-' + Math.random().toString(36).slice(2, 12);
    }
}

async function loadBitmap(file) {
    if (window.createImageBitmap) {
        try {
            return await createImageBitmap(file, { imageOrientation: 'from-image' });
        } catch (e) {
            // fall back to an <img> element below
        }
    }
    return await new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = () => { URL.revokeObjectURL(url); resolve(img); };
        img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('decode failed')); };
        img.src = url;
    });
}

// Resize to at most MAX_SIDE px and re-encode as JPEG, so uploads stay small and the free server does no image work
async function compressImage(file) {
    const bitmap = await loadBitmap(file);
    const width = bitmap.naturalWidth || bitmap.width;
    const height = bitmap.naturalHeight || bitmap.height;
    const scale = Math.min(1, MAX_SIDE / Math.max(width, height));
    const w = Math.max(1, Math.round(width * scale));
    const h = Math.max(1, Math.round(height * scale));

    const canvas = document.createElement('canvas');
    canvas.width = w;
    canvas.height = h;
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, w, h);
    ctx.drawImage(bitmap, 0, 0, w, h);
    if (bitmap.close) bitmap.close();

    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', JPEG_QUALITY));
    if (!blob) throw new Error('encode failed');

    // Keep the original when it is already small and no larger than the re-encoded copy
    const keepable = ['image/jpeg', 'image/png', 'image/webp'].includes(file.type) && file.size <= MAX_UPLOAD_BYTES;
    if (scale === 1 && keepable && blob.size >= file.size) return file;

    if (blob.size > MAX_UPLOAD_BYTES) throw new Error('still too large');
    const name = (file.name || 'photo').replace(/\.[^.]+$/, '') + '.jpg';
    return new File([blob], name, { type: 'image/jpeg' });
}

export function timeAgo(value) {
    const t = new Date(value).getTime();
    if (Number.isNaN(t)) return '';
    const s = Math.max(0, Math.floor((Date.now() - t) / 1000));
    if (s < 60) return 'Just now';
    if (s < 3600) return Math.floor(s / 60) + 'm';
    if (s < 86400) return Math.floor(s / 3600) + 'h';
    if (s < 7 * 86400) return Math.floor(s / 86400) + 'd';
    return new Date(t).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
}

// Collage layouts the author can pick, depending on how many photos are attached
export function layoutChoices(n) {
    if (n < 2) return [];
    if (n === 2) return [{ key: 'auto', label: 'Side by side' }, { key: 'rows', label: 'Stacked' }];
    if (n === 3) {
        return [
            { key: 'auto', label: 'Big left' },
            { key: 'feature-top', label: 'Big top' },
            { key: 'grid', label: 'Three across' },
            { key: 'rows', label: 'Stacked' },
        ];
    }
    return [
        { key: 'auto', label: 'Grid' },
        { key: 'feature-left', label: 'Big left' },
        { key: 'feature-top', label: 'Big top' },
    ];
}

export function usePortalPosts({ getSectionId, withAuth, creds, touchAuth }) {
    const posts = ref([]);
    const status = ref('idle'); // idle | loading | ready | error
    const hasMore = ref(false);
    const nextBefore = ref(null);
    const loadingMore = ref(false);

    // ---- Feed
    function reset() {
        posts.value = [];
        hasMore.value = false;
        nextBefore.value = null;
        status.value = 'idle';
    }

    async function load(fresh = true) {
        const sectionId = getSectionId();
        if (fresh) {
            status.value = 'loading';
        } else {
            if (loadingMore.value || !hasMore.value) return;
            loadingMore.value = true;
        }
        try {
            const params = new URLSearchParams();
            if (sectionId) params.set('section_id', sectionId);
            if (!fresh && nextBefore.value) params.set('before', nextBefore.value);
            params.set('client_id', getClientId());
            const res = await fetch(`${POSTS_URL}?${params}`, { headers: { Accept: 'application/json' } });
            if (!res.ok) throw new Error('load failed');
            const data = await res.json();
            // Ignore a response that arrives after the section was switched
            if (sectionId !== getSectionId()) return;
            posts.value = fresh ? (data.posts ?? []) : [...posts.value, ...(data.posts ?? [])];
            hasMore.value = !!data.has_more;
            nextBefore.value = data.next_before ?? null;
            status.value = 'ready';
        } catch (e) {
            if (fresh) status.value = 'error';
        } finally {
            loadingMore.value = false;
        }
    }

    const loadMore = () => load(false);

    // ---- Composer
    let seq = 0;
    const caption = ref('');
    const layout = ref('auto');
    const picked = ref([]); // [{ id, file, url }]
    const composing = ref(false);
    const posting = ref(false);
    const composeError = ref('');
    const composeNotice = ref('');

    const choices = computed(() => layoutChoices(picked.value.length));
    const effectiveLayout = computed(() =>
        choices.value.some((c) => c.key === layout.value) ? layout.value : 'auto'
    );

    function setLayout(key) {
        layout.value = key;
    }

    async function addFiles(fileList) {
        composeError.value = '';
        composeNotice.value = '';
        const incoming = Array.from(fileList ?? []);
        if (!incoming.length) return;

        const room = POST_MAX_IMAGES - picked.value.length;
        if (room <= 0) {
            composeError.value = `You can add up to ${POST_MAX_IMAGES} photos.`;
            return;
        }
        const skippedForRoom = incoming.length > room;

        composing.value = true;
        try {
            for (const file of incoming.slice(0, room)) {
                if (!file.type.startsWith('image/')) {
                    composeError.value = 'Only photos can be attached.';
                    continue;
                }
                if (file.size > MAX_RAW_BYTES) {
                    composeError.value = `"${file.name}" is larger than 10MB.`;
                    continue;
                }
                try {
                    const out = await compressImage(file);
                    picked.value.push({ id: ++seq, file: out, url: URL.createObjectURL(out) });
                } catch (e) {
                    composeError.value = `"${file.name}" could not be read. Use a JPG or PNG photo.`;
                }
            }
            if (skippedForRoom && !composeError.value) {
                composeError.value = `Only ${POST_MAX_IMAGES} photos per post, the extra photos were skipped.`;
            }
        } finally {
            composing.value = false;
        }
    }

    function removeFile(id) {
        const item = picked.value.find((p) => p.id === id);
        if (item) URL.revokeObjectURL(item.url);
        picked.value = picked.value.filter((p) => p.id !== id);
    }

    function clearComposer() {
        picked.value.forEach((p) => URL.revokeObjectURL(p.url));
        picked.value = [];
        caption.value = '';
        layout.value = 'auto';
        composeError.value = '';
    }

    function submitPost() {
        composeError.value = '';
        composeNotice.value = '';
        const text = caption.value.trim();
        if (!text && !picked.value.length) {
            composeError.value = 'Write something or add a photo first.';
            return;
        }
        if (composing.value || posting.value) return;
        const sectionId = getSectionId();

        return withAuth(async () => {
            posting.value = true;
            try {
                const form = new FormData();
                const c = creds();
                if (c.student_number) form.append('student_number', c.student_number);
                if (c.password) form.append('password', c.password);
                form.append('caption', text);
                form.append('layout', picked.value.length > 1 ? effectiveLayout.value : 'auto');
                if (sectionId) form.append('section_id', sectionId);
                picked.value.forEach((p) => form.append('images[]', p.file, p.file.name));

                const res = await fetch(POSTS_URL, {
                    method: 'POST',
                    headers: { Accept: 'application/json', ...csrfHeaders() },
                    body: form,
                });
                const data = await res.json().catch(() => ({}));

                if (!res.ok) {
                    const authErr = data.errors?.student_number?.[0];
                    if (authErr) return authErr;
                    composeError.value = apiError(data, 'Could not post. Please try again.', res);
                    return null;
                }

                touchAuth();
                if (!sectionId || data.post.section_id === Number(sectionId)) {
                    posts.value = [data.post, ...posts.value];
                } else {
                    composeNotice.value = 'Posted to your own class feed.';
                }
                clearComposer();
            } catch (e) {
                composeError.value = 'Network error. Please try again.';
            } finally {
                posting.value = false;
            }
            return null;
        });
    }

    // ---- Reactions (same picker behavior as the Freedom Wall)
    const pickerFor = ref(null);
    let pressTimer = null;
    let longPressed = false;

    function pressStart(post) {
        longPressed = false;
        clearTimeout(pressTimer);
        pressTimer = setTimeout(() => {
            longPressed = true;
            pickerFor.value = post.id;
        }, 450);
    }

    function pressEnd() {
        clearTimeout(pressTimer);
    }

    function closePicker() {
        pickerFor.value = null;
    }

    // reaction = null removes my reaction
    function setReaction(post, reaction) {
        if (post._reacting) return;
        return withAuth(async () => {
            post._reacting = true;
            try {
                const { res, data } = await postJson(`${POSTS_URL}/${post.id}/react`, {
                    ...creds(),
                    reaction,
                    client_id: getClientId(),
                });
                if (!res.ok) return data.errors?.student_number?.[0] ?? null;
                touchAuth();
                post.my_reaction = data.my_reaction ?? null;
                post.liked = !!data.liked;
                post.reaction_counts = data.reaction_counts ?? {};
                post.likes_count = data.likes_count ?? 0;
                post.likers = data.likers ?? [];
            } catch (e) {
                // network error: leave the post as it was
            } finally {
                post._reacting = false;
            }
            return null;
        });
    }

    function onLikeClick(post) {
        // The click that follows a long-press only opened the picker
        if (longPressed) {
            longPressed = false;
            return;
        }
        pickerFor.value = null;
        return setReaction(post, post.liked ? null : 'heart');
    }

    function pickReaction(post, key) {
        pickerFor.value = null;
        return setReaction(post, post.my_reaction === key ? null : key);
    }

    // ---- Lightbox
    const lightbox = reactive({ open: false, images: [], index: 0 });

    function openLightbox(post, index = 0) {
        lightbox.images = post.images;
        lightbox.index = index;
        lightbox.open = true;
    }

    function closeLightbox() {
        lightbox.open = false;
    }

    function stepLightbox(delta) {
        const n = lightbox.images.length;
        if (!n) return;
        lightbox.index = (lightbox.index + delta + n) % n;
    }

    function onKey(e) {
        if (!lightbox.open) return;
        if (e.key === 'Escape') closeLightbox();
        else if (e.key === 'ArrowRight') stepLightbox(1);
        else if (e.key === 'ArrowLeft') stepLightbox(-1);
    }

    // ---- Edit (author or teacher): caption only
    const editing = reactive({ id: null, text: '', saving: false, error: '' });

    function startEdit(post) {
        editing.id = post.id;
        editing.text = post.caption ?? '';
        editing.saving = false;
        editing.error = '';
    }

    function cancelEdit() {
        editing.id = null;
        editing.text = '';
        editing.saving = false;
        editing.error = '';
    }

    function saveEdit(post) {
        if (editing.id !== post.id || editing.saving) return;
        const text = editing.text.trim();
        if (!text && !post.images.length) {
            editing.error = 'A post with no photo needs a caption.';
            return;
        }
        return withAuth(async () => {
            editing.saving = true;
            editing.error = '';
            try {
                const { res, data } = await postJson(`${POSTS_URL}/${post.id}`, { ...creds(), caption: text }, 'PATCH');
                if (!res.ok) {
                    const authErr = data.errors?.student_number?.[0];
                    if (authErr) return authErr;
                    editing.error = apiError(data, 'Could not save. Please try again.', res);
                    return null;
                }
                touchAuth();
                post.caption = data.caption;
                cancelEdit();
            } catch (e) {
                editing.error = 'Network error. Please try again.';
            } finally {
                editing.saving = false;
            }
            return null;
        });
    }

    // ---- Admin
    async function removePost(post) {
        if (!window.confirm('Delete this post? This cannot be undone.')) return;
        try {
            const res = await fetch(`/paulo/portal-posts/${post.id}`, {
                method: 'DELETE',
                headers: { Accept: 'application/json', ...csrfHeaders() },
            });
            if (!res.ok) throw new Error('delete failed');
            posts.value = posts.value.filter((p) => p.id !== post.id);
        } catch (e) {
            window.alert('Could not delete the post. Make sure you are logged in as admin.');
        }
    }

    onBeforeUnmount(() => {
        clearTimeout(pressTimer);
        picked.value.forEach((p) => URL.revokeObjectURL(p.url));
    });

    return {
        posts, status, hasMore, loadingMore, reset, load, loadMore,
        caption, picked, composing, posting, composeError, composeNotice,
        choices, effectiveLayout, setLayout, addFiles, removeFile, submitPost,
        pickerFor, pressStart, pressEnd, closePicker, onLikeClick, pickReaction,
        lightbox, openLightbox, closeLightbox, stepLightbox, onKey,
        removePost, editing, startEdit, cancelEdit, saveEdit,
    };
}
