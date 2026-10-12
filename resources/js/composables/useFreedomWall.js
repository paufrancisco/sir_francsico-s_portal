import { reactive, ref } from 'vue';

// GET  /portal/freedom-wall?section_id=ID&client_id=X -> { posts: [{ id, body, color, created_at, author, likes_count, likers, replies_count, liked }] }
// POST /portal/freedom-wall { student_number, password, body, color } -> { message, post }
// POST /portal/freedom-wall/{id}/like { student_number, password, liked, client_id } -> { liked, likes_count, likers }
// GET  /portal/freedom-wall/{id}/replies -> { replies: [{ id, body, author, created_at }] }
// POST /portal/freedom-wall/{id}/reply { student_number, password, body } -> { reply, replies_count }
const WALL_URL = '/portal/freedom-wall';
export const WALL_MAX_LENGTH = 280;
export const WALL_REPLY_MAX_LENGTH = 200;
const AUTH_IDLE_MS = 5 * 60 * 1000;

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

async function postJson(url, payload) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', ...csrfHeaders() },
        body: JSON.stringify(payload),
    });
    const data = await res.json().catch(() => ({}));
    return { res, data };
}

// Anonymous device id (kept so old anonymous likes from this device can be turned into named ones)
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

// Hover text for the like button: who liked the note
export function likersTitle(post) {
    const names = post.likers ?? [];
    const others = Math.max(0, (post.likes_count ?? 0) - names.length);
    if (!names.length && !others) return 'No likes yet';
    if (!names.length) return `${others} anonymous like${others > 1 ? 's' : ''}`;
    const list = names.join(', ');
    return others ? `${list} and ${others} other${others > 1 ? 's' : ''}` : list;
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

    // True when an admin session is active: likes and replies are posted as Sir Francisco
    const isTeacher = ref(false);

    async function checkTeacher() {
        try {
            const res = await fetch(`${WALL_URL}/me`, { headers: { Accept: 'application/json' } });
            const data = res.ok ? await res.json() : {};
            isTeacher.value = !!data.teacher;
        } catch (e) {
            isTeacher.value = false;
        }
    }

    // ---- Sign-in for likes and replies: kept in memory only, expires after 5 idle minutes
    const wallAuth = reactive({ student_number: '', password: '', lastUsed: 0 });
    const authModalOpen = ref(false);
    const authLoading = ref(false);
    const authError = ref('');
    const authForm = reactive({ student_number: '', password: '', show: false });
    let pendingAction = null;

    const isSignedIn = () =>
        !!(wallAuth.student_number && wallAuth.password && Date.now() - wallAuth.lastUsed < AUTH_IDLE_MS);

    // Credentials sent with likes and replies; empty in teacher mode so the server uses the admin session
    function creds() {
        if (isTeacher.value && !isSignedIn()) return { student_number: '', password: '' };
        return { student_number: wallAuth.student_number, password: wallAuth.password };
    }

    function touchAuth() {
        wallAuth.lastUsed = Date.now();
    }

    function signOut() {
        wallAuth.student_number = '';
        wallAuth.password = '';
        wallAuth.lastUsed = 0;
    }

    function resetAuthForm() {
        authForm.student_number = '';
        authForm.password = '';
        authForm.show = false;
        authError.value = '';
    }

    // An action returns an error string only when the login was rejected; any other result means it handled itself.
    async function withAuth(action) {
        // A logged-in admin acts as Sir Francisco, no student sign-in needed
        if (isTeacher.value && !isSignedIn()) {
            await action();
            return;
        }
        if (!isSignedIn()) {
            pendingAction = action;
            resetAuthForm();
            authModalOpen.value = true;
            return;
        }
        const authErr = await action();
        if (authErr) {
            signOut();
            pendingAction = action;
            resetAuthForm();
            authError.value = authErr;
            authModalOpen.value = true;
        }
    }

    function closeAuthModal() {
        authModalOpen.value = false;
        pendingAction = null;
        resetAuthForm();
    }

    async function submitAuth() {
        authError.value = '';
        if (!authForm.student_number.trim() || !authForm.password) {
            authError.value = 'Enter your student number and password.';
            return;
        }
        wallAuth.student_number = authForm.student_number.trim();
        wallAuth.password = authForm.password;
        wallAuth.lastUsed = Date.now();

        const action = pendingAction;
        authLoading.value = true;
        let err = null;
        try {
            err = action ? await action() : null;
        } finally {
            authLoading.value = false;
        }
        if (err) {
            signOut();
            authError.value = err;
            return;
        }
        authModalOpen.value = false;
        pendingAction = null;
        resetAuthForm();
    }

    // ---- Wall
    async function loadWall(sectionId) {
        checkTeacher();
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
            const { res, data } = await postJson(WALL_URL, {
                student_number: wallForm.student_number.trim(),
                password: wallForm.password,
                body: wallForm.body.trim(),
                color: wallForm.color,
            });
            if (!res.ok) {
                wallError.value = apiError(data, 'Could not post. Check your login and try again.', res);
                return;
            }
            wallSuccess.value = data.message || 'Posted on the wall.';
            // A successful post also counts as signing in for likes and replies
            wallAuth.student_number = wallForm.student_number.trim();
            wallAuth.password = wallForm.password;
            touchAuth();
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

    // ---- Likes (named)
    function toggleLike(post) {
        if (post._liking) return;
        const want = !post.liked;
        return withAuth(async () => {
            post._liking = true;
            try {
                const { res, data } = await postJson(`${WALL_URL}/${post.id}/like`, {
                    ...creds(),
                    liked: want,
                    client_id: getClientId(),
                });
                if (!res.ok) {
                    return data.errors?.student_number?.[0] ?? null;
                }
                touchAuth();
                post.liked = data.liked;
                post.likes_count = data.likes_count;
                post.likers = data.likers ?? [];
            } catch (e) {
                // network error: leave the note as it was
            } finally {
                post._liking = false;
            }
            return null;
        });
    }

    // ---- Reply thread (opens on click)
    const threadPost = ref(null);
    const threadReplies = ref([]);
    const threadLoading = ref(false);
    const threadText = ref('');
    const threadError = ref('');
    const threadSending = ref(false);

    async function openThread(post) {
        threadPost.value = post;
        threadReplies.value = [];
        threadText.value = '';
        threadError.value = '';
        threadLoading.value = true;
        try {
            const res = await fetch(`${WALL_URL}/${post.id}/replies`, { headers: { Accept: 'application/json' } });
            if (!res.ok) throw new Error('load failed');
            const data = await res.json();
            if (threadPost.value?.id === post.id) threadReplies.value = data.replies ?? [];
        } catch (e) {
            if (threadPost.value?.id === post.id) threadError.value = "Couldn't load the replies.";
        } finally {
            if (threadPost.value?.id === post.id) threadLoading.value = false;
        }
    }

    function closeThread() {
        threadPost.value = null;
        threadReplies.value = [];
        threadText.value = '';
        threadError.value = '';
    }

    function sendReply() {
        const post = threadPost.value;
        const body = threadText.value.trim();
        threadError.value = '';
        if (!post) return;
        if (!body) {
            threadError.value = 'Write something first.';
            return;
        }
        return withAuth(async () => {
            threadSending.value = true;
            try {
                const { res, data } = await postJson(`${WALL_URL}/${post.id}/reply`, {
                    ...creds(),
                    body,
                });
                if (!res.ok) {
                    const authErr = data.errors?.student_number?.[0];
                    if (authErr) return authErr;
                    threadError.value = apiError(data, 'Could not send the reply.', res);
                    return null;
                }
                touchAuth();
                if (threadPost.value?.id === post.id) {
                    threadReplies.value = [...threadReplies.value, data.reply];
                    threadText.value = '';
                }
                post.replies_count = data.replies_count;
            } catch (e) {
                threadError.value = 'Network error. Please try again.';
            } finally {
                threadSending.value = false;
            }
            return null;
        });
    }

    async function deletePost(post) {
        if (!window.confirm('Delete this note? This cannot be undone.')) return;
        try {
            const res = await fetch(`/paulo/freedom-wall/${post.id}`, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', ...csrfHeaders() },
            });
            if (!res.ok) throw new Error('delete failed');
            wallPosts.value = wallPosts.value.filter((p) => p.id !== post.id);
        } catch (e) {
            window.alert('Could not delete the note. Make sure you are logged in as admin.');
        }
    }

    return {
        wallPosts, wallStatus, wallModalOpen, wallLoading, wallError, wallSuccess, deletePost, isTeacher,
        showWallPassword, wallForm,
        loadWall, openWallModal, closeWallModal, submitWallPost, toggleLike,
        authModalOpen, authLoading, authError, authForm, submitAuth, closeAuthModal,
        threadPost, threadReplies, threadLoading, threadText, threadError, threadSending,
        openThread, closeThread, sendReply,
    };
}
