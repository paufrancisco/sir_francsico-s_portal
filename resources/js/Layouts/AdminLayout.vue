<template>
    <div class="min-h-screen bg-slate-50 flex">
        <aside
            class="bg-white border-r border-slate-200 transition-all duration-200 flex flex-col sticky top-0 h-screen"
            :class="collapsed ? 'w-16' : 'w-56'"
        >
            <div class="flex items-center justify-between px-4 py-4 border-b border-slate-100">
                <div v-if="!collapsed">
                    <div class="text-sm font-semibold text-[#003399]">Sir Francisco</div>
                    <div class="text-xs text-slate-400">Admin panel</div>
                </div>
                <button @click="toggle" class="text-slate-400 hover:text-[#003399] p-1 shrink-0">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path v-if="collapsed" d="M9 18l6-6-6-6" />
                        <path v-else d="M15 18l-6-6 6-6" />
                    </svg>
                </button>
            </div>

            <nav class="flex-1 py-3 space-y-1 px-2">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition"
                    :class="isActive(item.href) ? 'bg-[#E6F1FB] text-[#003399] font-medium' : 'text-slate-500 hover:bg-slate-50'"
                    :title="collapsed ? item.label : ''"
                >
                    <span class="shrink-0 w-4 h-4" v-html="item.icon"></span>
                    <span v-if="!collapsed">{{ item.label }}</span>
                </Link>
            </nav>
        </aside>

        <div class="flex-1 min-w-0 flex flex-col">
            <header class="bg-white border-b border-slate-200 sticky top-0 z-20">
                <div class="flex items-center justify-end px-6 py-3">
                    <div class="relative">
                        <button
                            @click="profileOpen = !profileOpen"
                            class="flex items-center gap-2 text-sm text-slate-600 hover:text-[#003399] transition"
                        >
                            <span class="w-9 h-9 rounded-full bg-[#E6F1FB] text-[#003399] flex items-center justify-center text-xs font-semibold overflow-hidden border border-slate-200">
                                {{ userInitials }}
                            </span>
                            <span class="font-medium">{{ userName }}</span>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M6 9l6 6 6-6" />
                            </svg>
                        </button>

                        <div
                            v-if="profileOpen"
                            class="absolute right-0 mt-2 w-44 bg-white border border-slate-200 rounded-lg shadow-lg py-1 z-30"
                        >
                            <Link href="/profile" class="block px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">
                                Profile
                            </Link>
                            <button
                                @click="logout"
                                class="w-full text-left px-4 py-2 text-sm text-red-500 hover:bg-slate-50"
                            >
                                Logout
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <div class="flex-1 min-w-0">
                <slot />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const collapsed = ref(false);
const profileOpen = ref(false);

onMounted(() => {
    const saved = localStorage.getItem('admin_sidebar_collapsed');
    if (saved !== null) collapsed.value = saved === 'true';
});

const toggle = () => {
    collapsed.value = !collapsed.value;
    localStorage.setItem('admin_sidebar_collapsed', collapsed.value);
};

const page = usePage();
const isActive = (href) => (href === '/paulo' ? page.url === '/paulo' : page.url.startsWith(href));

const userName = ref(page.props.auth?.user?.name ?? 'Admin');
const userInitials = ref(
    (page.props.auth?.user?.name ?? 'A')
        .split(' ')
        .map((w) => w.charAt(0))
        .slice(0, 2)
        .join('')
        .toUpperCase()
);

const logout = () => {
    router.post('/logout');
};

const closeOnOutsideClick = (e) => {
    if (!e.target.closest('.relative')) profileOpen.value = false;
};
onMounted(() => document.addEventListener('click', closeOnOutsideClick));
onUnmounted(() => document.removeEventListener('click', closeOnOutsideClick));

const navItems = [
    { href: '/paulo', label: 'Dashboard', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>' },
    { href: '/paulo/sections', label: 'Sections', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>' },
    { href: '/paulo/grade-corrections', label: 'Grade Correction', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>' },
    { href: '/paulo/seating', label: 'Seating Arrangement', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>' },
    { href: '/paulo/faqs', label: 'FAQ', icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>' },
];
</script>