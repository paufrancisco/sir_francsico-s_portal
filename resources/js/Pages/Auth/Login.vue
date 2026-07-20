<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

// ---- Dark mode state (parehong localStorage key ng class portal, para consistent) ----
const isDarkMode = ref(false);

onMounted(() => {
    const stored = localStorage.getItem('portalTheme');
    if (stored) isDarkMode.value = stored === 'dark';
    else isDarkMode.value = window.matchMedia?.('(prefers-color-scheme: dark)').matches ?? false;
});

const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value;
    localStorage.setItem('portalTheme', isDarkMode.value ? 'dark' : 'light');
};
</script>

<template>
    <Head title="Log in" />

    <div
        class="portal-root min-h-screen relative overflow-hidden flex items-center justify-center px-4 transition-colors duration-300"
        :class="isDarkMode ? 'dark' : ''"
    >
        <!-- Ambient background: paper grain + soft ink blobs (parehong sa portal) -->
        <div class="pointer-events-none fixed inset-0 -z-10 bg-[var(--page-bg)]">
            <div class="absolute inset-0 grain-layer"></div>
            <div class="absolute w-[28rem] h-[28rem] rounded-full blur-3xl opacity-[0.16] blob-a" style="background:#8A8D91;"></div>
            <div class="absolute w-[26rem] h-[26rem] rounded-full blur-3xl opacity-[0.18] blob-b" style="background:var(--gold);"></div>
        </div>

        <button
            @click="toggleDarkMode"
            class="fixed top-6 right-6 w-9 h-9 rounded-full flex items-center justify-center transition border z-10"
            :class="isDarkMode ? 'text-[var(--gold)]' : 'text-[var(--text-heading)]'"
            style="background:var(--surface); border-color:var(--surface-border);"
            aria-label="Toggle dark mode"
        >
            <svg v-if="isDarkMode" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
            <svg v-else width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/></svg>
        </button>

        <div class="w-full max-w-sm relative z-10">

            <!-- Branding, parehong hitsura ng header sa class portal -->
            <div class="flex flex-col items-center gap-2.5 mb-6">
                <div class="w-12 h-12 rounded-full flex items-center justify-center text-base font-bold shrink-0" style="background:var(--gold); color:var(--navy-deep); font-family:var(--font-display);">SF</div>
                <div class="text-center leading-tight">
                    <div class="text-[16px] font-semibold tracking-tight text-[var(--text-heading)]" style="font-family:var(--font-display);">Sir Francisco</div>
                    <div class="text-[10px] uppercase tracking-[0.14em] text-[var(--text-muted)]">Admin panel</div>
                </div>
            </div>

            <div class="ink-panel rounded-[1.75rem] p-6 lg:p-7 relative overflow-hidden">
                <div class="absolute -top-16 -right-16 w-64 h-64 rounded-full blur-2xl opacity-20" style="background:var(--gold);"></div>

                <div class="relative">
                    <div class="text-[10px] uppercase tracking-[0.16em] text-white/40 mb-1">Welcome back</div>
                    <div class="text-lg font-semibold text-white mb-5" style="font-family:var(--font-display);">Sign in to continue</div>

                    <div v-if="status" class="mb-4 text-sm font-medium rounded-xl px-3 py-2" style="background:rgba(63,185,80,0.14); color:#3FB950;">
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label for="email" class="block text-[11px] uppercase tracking-wide text-white/50 mb-1.5">Email</label>
                            <input
                                id="email"
                                type="email"
                                class="portal-input portal-input--dark"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="username"
                            />
                            <p v-if="form.errors.email" class="text-xs mt-1.5" style="color:#F85149;">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label for="password" class="block text-[11px] uppercase tracking-wide text-white/50 mb-1.5">Password</label>
                            <input
                                id="password"
                                type="password"
                                class="portal-input portal-input--dark"
                                v-model="form.password"
                                required
                                autocomplete="current-password"
                            />
                            <p v-if="form.errors.password" class="text-xs mt-1.5" style="color:#F85149;">{{ form.errors.password }}</p>
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="remember"
                                    v-model="form.remember"
                                    class="rounded border-white/20 bg-white/5"
                                    style="accent-color: var(--gold);"
                                />
                                <span class="text-xs text-white/60">Remember me</span>
                            </label>

                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-xs font-medium hover:underline"
                                style="color:var(--gold);"
                            >
                                Forgot password?
                            </Link>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full text-sm font-semibold py-2.5 rounded-xl transition disabled:opacity-50 mt-2"
                            style="background:var(--gold); color:var(--navy-deep); font-family:var(--font-display);"
                        >
                            {{ form.processing ? 'Signing in...' : 'Log in' }}
                        </button>
                    </form>
                </div>
            </div>

            <div class="text-center text-[11px] text-[var(--text-muted)] mt-5" style="font-family:var(--font-mono);">
                Sir Francisco's Class Portal
            </div>
        </div>
    </div>
</template>

<style scoped>
.portal-root {
    font-family: var(--font-body);

    --font-display: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    --font-body: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    --font-mono: ui-monospace, 'SF Mono', 'Cascadia Code', Menlo, monospace;

    /* GitHub Light tokens — pareho sa class portal */
    --navy: #24292f;
    --navy-deep: #1b1f24;
    --gold: #0969da;
    --teal: #1a7f37;
    --coral: #cf222e;

    --page-bg: #f6f8fa;
    --surface: #ffffff;
    --surface-border: #d0d7de;
    --surface-border-soft: #d8dee4;
    --text-heading: #1f2328;
    --text-body: #1f2328;
    --text-secondary: #59636e;
    --text-muted: #6e7781;
    --chip-bg: #eaeef2;
}
.portal-root.dark {
    --navy: #21262d;
    --navy-deep: #161b22;
    --gold: #58a6ff;
    --teal: #3fb950;
    --coral: #f85149;

    --page-bg: #0d1117;
    --surface: #161b22;
    --surface-border: #30363d;
    --surface-border-soft: #21262d;
    --text-heading: #e6edf3;
    --text-body: #c9d1d9;
    --text-secondary: #8b949e;
    --text-muted: #6e7681;
    --chip-bg: #21262d;
}

.grain-layer {
    background-image: radial-gradient(rgba(20, 33, 61, 0.05) 1px, transparent 1px);
    background-size: 18px 18px;
}
.portal-root.dark .grain-layer {
    background-image: radial-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px);
}
.blob-a { top: -6rem; left: -6rem; animation: float-a 16s ease-in-out infinite; }
.blob-b { bottom: -6rem; right: -6rem; animation: float-b 18s ease-in-out infinite; }
@keyframes float-a { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(50px, 35px); } }
@keyframes float-b { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(-40px, -25px); } }

.ink-panel {
    background: linear-gradient(160deg, var(--navy), var(--navy-deep));
    box-shadow: 0 20px 45px -20px rgba(10, 18, 48, 0.45);
}

.portal-input--dark {
    width: 100%;
    font-size: 0.875rem;
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 0.75rem;
    padding: 0.6rem 0.85rem;
    background: rgba(255,255,255,0.05);
    color: #fff;
}
.portal-input--dark:focus {
    outline: 2px solid var(--gold);
    outline-offset: 1px;
}
.portal-input--dark::placeholder {
    color: rgba(255,255,255,0.35);
}

@media (prefers-reduced-motion: reduce) {
    .blob-a, .blob-b { animation: none; }
}
</style>