<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { activityTypes, timeAgo } from '@/composables/activityTypes';

const page = usePage();
const open = ref(false);

const unread = computed(() => page.props.activity?.unread ?? 0);
const recent = computed(() => page.props.activity?.recent ?? []);

const markAllRead = () => {
    router.post('/paulo/activity/read', {}, { preserveScroll: true, preserveState: true });
};

let timer;
const onDocClick = (e) => {
    if (!e.target.closest('.notif-bell')) open.value = false;
};

onMounted(() => {
    timer = setInterval(() => {
        if (document.visibilityState === 'visible') router.reload({ only: ['activity'] });
    }, 30000);
    document.addEventListener('click', onDocClick);
});

onUnmounted(() => {
    clearInterval(timer);
    document.removeEventListener('click', onDocClick);
});
</script>

<template>
    <div class="notif-bell" style="position: relative">
        <button
            @click="open = !open"
            title="Student activity"
            class="w-9 h-9 rounded-full flex items-center justify-center text-slate-500 hover:text-[var(--navy)] hover:bg-white/70 transition"
            style="position: relative"
        >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
            </svg>
            <span
                v-if="unread > 0"
                class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-semibold flex items-center justify-center"
            >{{ unread > 99 ? '99+' : unread }}</span>
        </button>

        <div
            v-if="open"
            class="absolute right-0 mt-2 w-80 bg-white border border-slate-200 rounded-xl shadow-lg z-30 overflow-hidden"
        >
            <div class="flex items-center justify-between px-4 py-2.5 border-b border-slate-100">
                <span class="text-sm font-semibold text-slate-700">Student activity</span>
                <button v-if="unread > 0" @click="markAllRead" class="text-xs text-[var(--navy)] hover:underline">Mark all read</button>
            </div>

            <div class="max-h-96 overflow-y-auto divide-y divide-slate-100">
                <div
                    v-for="a in recent"
                    :key="a.id"
                    class="px-4 py-2.5"
                    :class="a.read_at ? '' : 'bg-amber-50/60'"
                >
                    <div class="flex items-center gap-2 mb-0.5">
                        <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-full" :class="activityTypes[a.type]?.cls ?? 'bg-slate-100 text-slate-600'">
                            {{ activityTypes[a.type]?.label ?? a.type }}
                        </span>
                        <span class="text-[10px] text-slate-400">{{ timeAgo(a.created_at) }}</span>
                    </div>
                    <div class="text-xs text-slate-600 break-words">{{ a.summary }}</div>
                </div>
                <div v-if="!recent.length" class="px-4 py-8 text-center text-xs text-slate-400">No activity yet.</div>
            </div>

            <Link
                href="/paulo/logs?tab=activity"
                class="block text-center text-xs text-[var(--navy)] font-medium py-2.5 border-t border-slate-100 hover:bg-slate-50"
                @click="open = false"
            >View all in Logs</Link>
        </div>
    </div>
</template>
