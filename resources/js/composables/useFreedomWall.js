import { reactive, ref } from 'vue';

// GET  /portal/freedom-wall?section_id=ID -> { posts: [{ id, body, color, created_at }] }
// POST /portal/freedom-wall { student_number, password, body, color } -> { message, post }
const WALL_URL = '/portal/freedom-wall';
export const WALL_MAX_LENGTH = 280;

export const WALL_COLORS = [
    { key: 'yellow', bg: '#fff59d', ink: '#4a4000' },
    { key: 'pink', bg: '#f8bbd0', ink: '#4a1030' },
    { key: 'blue', bg: '#b3e5fc', ink: '#08334a' },
    { key: 'green', bg: '#c8e6c9', ink: '#10381a' },
    { key: 'orange', bg: '#ffe0b2', ink: '#4a2a00' },
    { key: 'purple', bg: '#e1bee7', ink: '#33103f' },
];

function csrfHeaders() {
    const meta = document.querySelector('meta[name="csrf-token"]')?.content;
    if (meta) return { 'X-CSRF-TOKEN': meta };
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    return match ? { 'X-XSRF-TOKEN': decodeURIComponent(match[1]) } : {};
}

function apiError(data, fallback, res) {
    if (res?.status === 429) return 'Too many attempts. Please wait a moment and try again.';
    return Object.values(data.errors ?? {})[0]?.[0] || data.message || fallback;
}

// Anonymous device id, so one device can like a note once
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

// Stable pseudo-random numbers from the post id, so a note keeps its spot after refresh
function seeded(id, salt) {
    const x = Math.sin(id * 12.9898 + salt * 78.233) * 43758.5453;
    return x - Math.floor(x);
}

export function stickerStyle(post) {
    const color = WALL_COLORS.find((c) => c.key === post.color) ?? WALL_COLORS[0];
    const rotate = (seeded(post.id, 3) - 0.5) * 12;
    const dx = (seeded(post.id, 1) - 0.5) * 14;
    const dy = (seeded(post.id, 2) - 0.5) * 10;
    return {
        transform: `translate(${dx.toFixed(0)}px, ${dy.toFixed(0)}px) rotate(${rotate.toFixed(1)}deg)`,
        background: color.bg,
        color: color.ink,
        zIndex: 1 + Math.floor(seeded(post.id, 4) * 5),
    };
}

export function useFreedomWall() {
    const wallPosts = ref([]);
    const wallStatus = ref('idle'); // idle | loading | ready | error
    const wallModalOpen = ref(false);
    const wallLoading = ref(false);
    const wallError = ref('');
    const wallSuccess = ref('');
    const showWallPassword = ref(false);
    const wallForm = reactive({ student_number: '', password: '', body: '', color: 'yellow' });

    async function loadWall(sectionId) {
        wallStatus.value = 'loading';
        try {
            const params = new URLSearchParams();
            if (sectionId) params.set('section_id', sectionId);
            params.set('client_id', getClientId());
            const res = await fetch(`${WALL_URL}?${params}`, { headers: { Accept: 'application/json' } });
            if (!res.ok) throw new Error('load failed');
            const data = await res.json();
            wallPosts.value = data.posts ?? [];
            wallStatus.value = 'ready';
        } catch (e) {
            wallStatus.value = 'error';
        }
    }

    function resetWallForm() {
        wallForm.student_number = '';
        wallForm.password = '';
        wallForm.body = '';
        wallForm.color = 'yellow';
        wallError.value = '';
        wallSuccess.value = '';
        showWallPassword.value = false;
    }

    function openWallModal() {
        resetWallForm();
        wallModalOpen.value = true;
    }

    function closeWallModal() {
        wallModalOpen.value = false;
        resetWallForm();
    }

    async function submitWallPost(currentSectionId) {
        wallError.value = '';
        if (!wallForm.student_number.trim() || !wallForm.password) {
            wallError.value = 'Enter your student number and password.';
            return;
        }
        if (!wallForm.body.trim()) {
            wallError.value = 'Write something first.';
            return;
        }

        wallLoading.value = true;
        try {
            const res = await fetch(WALL_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', ...csrfHeaders() },
                body: JSON.stringify({
                    student_number: wallForm.student_number.trim(),
                    password: wallForm.password,
                    body: wallForm.body.trim(),
                    color: wallForm.color,
                }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                wallError.value = apiError(data, 'Could not post. Check your login and try again.', res);
                return;
            }
            wallSuccess.value = data.message || 'Posted on the wall.';
            wallForm.password = '';
            wallForm.body = '';
            // Show the new sticker right away if it belongs to the section being viewed
            if (data.post && (!currentSectionId || data.post.section_id === currentSectionId)) {
                wallPosts.value = [data.post, ...wallPosts.value];
            }
        } catch (e) {
            wallError.value = 'Network error. Please try again.';
        } finally {
            wallLoading.value = false;
        }
    }

    async function toggleLike(post) {
        const was = !!post.liked;
        post.liked = !was;
        post.likes_count = Math.max(0, (post.likes_count ?? 0) + (was ? -1 : 1));
        try {
            const res = await fetch(`${WALL_URL}/${post.id}/like`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', ...csrfHeaders() },
                body: JSON.stringify({ client_id: getClientId() }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) throw new Error('like failed');
            post.liked = data.liked;
            post.likes_count = data.likes_count;
        } catch (e) {
            post.liked = was;
            post.likes_count = Math.max(0, (post.likes_count ?? 0) + (was ? 1 : -1));
        }
    }

    return {
        wallPosts, wallStatus, wallModalOpen, wallLoading, wallError, wallSuccess,
        showWallPassword, wallForm,
        loadWall, openWallModal, closeWallModal, submitWallPost, toggleLike,
    };
}
