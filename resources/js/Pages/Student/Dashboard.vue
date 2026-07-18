<template>
    <div class="min-h-screen bg-slate-50 relative overflow-hidden">

        <div class="pointer-events-none fixed inset-0 overflow-hidden -z-10">
            <div class="absolute w-96 h-96 rounded-full blur-3xl opacity-20 blob-a" style="background:#003399;"></div>
            <div class="absolute w-96 h-96 rounded-full blur-3xl opacity-20 blob-b" style="background:#FFCC00;"></div>
        </div>

        <header class="border-b border-slate-200 bg-white/80 backdrop-blur relative z-10">
            <div class="max-w-[1440px] mx-auto px-6 lg:px-10 py-4 flex items-center justify-between">
                <div>
                    <div class="text-lg font-semibold text-[#003399]">Sir Francisco</div>
                    <div class="text-xs text-slate-400">Class portal</div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-xs text-slate-400 tabular-nums">{{ liveClock }}</div>
                    <Link
                        href="/login"
                        class="bg-[#003399] text-white text-xs font-medium px-4 py-2 rounded-lg hover:opacity-90 transition"
                    >
                        Login
                    </Link>
                </div>
            </div>
        </header>

        <main class="max-w-[1440px] mx-auto px-6 lg:px-10 py-8 grid grid-cols-1 lg:grid-cols-3 gap-6 relative z-10 items-start">

            <div class="lg:col-span-2 space-y-4">

                <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1 w-fit flex-wrap">
                    <button
                        v-for="s in sections"
                        :key="s.id"
                        @click="activeSectionId = s.id; currentIndex = 0"
                        class="text-xs font-medium px-4 py-1.5 rounded-md transition"
                        :class="activeSectionId === s.id ? 'bg-white text-[#003399] shadow-sm' : 'text-slate-500'"
                    >
                        {{ s.subject ? `${s.subject} - ${s.name}` : s.name }}
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm hover:shadow-md transition">
                        <div class="text-sm font-semibold text-slate-700 mb-3">Announcements — {{ activeSectionLabel }}</div>
                        <p v-if="filteredAnnouncements.length === 0" class="text-xs text-slate-400">
                            Wala pang announcement.
                        </p>
                        <div v-for="a in filteredAnnouncements" :key="a.id" class="text-sm text-slate-600 py-1.5 border-b border-slate-50 last:border-0">
                            {{ a.title }}
                        </div>
                    </div>

                    <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm hover:shadow-md transition">
                        <div class="text-sm font-semibold text-slate-700 mb-3">Calendar</div>
                        <p v-if="calendarEvents.length === 0" class="text-xs text-slate-400">
                            Walang naka-schedule na no-class ngayon.
                        </p>
                        <div v-for="event in calendarEvents" :key="event.id" class="text-sm text-slate-600 py-1.5 border-b border-slate-50 last:border-0">
                            {{ event.title }} — {{ formatEventDate(event.event_date) }}
                        </div>
                        <div class="text-[11px] text-slate-400 mt-3 pt-2 border-t border-slate-100">
                            Last updated: {{ formattedUpdate }}
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <button class="bg-white border border-slate-200 rounded-xl p-4 text-left shadow-sm hover:shadow-lg hover:scale-[1.02] hover:border-[#003399]/30 transition transform flex items-start gap-3">
                        <span class="shrink-0 w-8 h-8 rounded-lg bg-[#E6F1FB] flex items-center justify-center text-[#003399]">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        </span>
                        <div>
                            <div class="text-sm font-medium text-slate-800">View my grades</div>
                            <div class="text-xs text-slate-400 mt-0.5">Password required</div>
                        </div>
                    </button>

                    <button class="bg-white border border-slate-200 rounded-xl p-4 text-left shadow-sm hover:shadow-lg hover:scale-[1.02] hover:border-[#003399]/30 transition transform flex items-start gap-3">
                        <span class="shrink-0 w-8 h-8 rounded-lg bg-[#E6F1FB] flex items-center justify-center text-[#003399]">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </span>
                        <div>
                            <div class="text-sm font-medium text-slate-800">Change password</div>
                            <div class="text-xs text-slate-400 mt-0.5">Verify muna current mo</div>
                        </div>
                    </button>

                    <button class="bg-white border border-slate-200 rounded-xl p-4 text-left shadow-sm hover:shadow-lg hover:scale-[1.02] hover:border-[#003399]/30 transition transform flex items-start gap-3">
                        <span class="shrink-0 w-8 h-8 rounded-lg bg-[#E6F1FB] flex items-center justify-center text-[#003399]">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18M8 2v4M16 2v4M16 16l4 4m0-4l-4 4"/></svg>
                        </span>
                        <div>
                            <div class="text-sm font-medium text-slate-800">Inform sir absent</div>
                            <div class="text-xs text-slate-400 mt-0.5">Auto-fill section mo</div>
                        </div>
                    </button>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm hover:shadow-md transition">
                    <div class="text-sm font-semibold text-slate-700 mb-3">This week's topics — {{ activeSectionLabel }}</div>
                    <p v-if="filteredTopics.length === 0" class="text-xs text-slate-400">
                        Wala pang naka-post na topic ngayong linggo.
                    </p>
                    <div v-for="t in filteredTopics" :key="t.id" class="flex items-center justify-between py-2 border-b border-slate-50 last:border-0">
                        <div>
                            <div class="text-sm text-slate-700">{{ t.title }}</div>
                            <div class="text-xs text-slate-400">{{ formatEventDate(t.date_covered) }}</div>
                        </div>
                        <span
                            class="text-[11px] font-medium px-2 py-0.5 rounded-full"
                            :class="t.status === 'done' ? 'bg-[#EAF3DE] text-[#3B6D11]' : t.status === 'ongoing' ? 'bg-[#FAEEDA] text-[#854F0B]' : 'bg-slate-100 text-slate-500'"
                        >
                            {{ t.status }}
                        </span>
                    </div>
                </div>
            </div>

            <div
                class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm"
                @mouseenter="paused = true"
                @mouseleave="paused = false"
            >
                <div class="text-xs font-semibold text-slate-500 mb-3">Top 10 — {{ activeSectionLabel }}</div>

                <p v-if="filteredStudents.length === 0" class="text-xs text-slate-400 text-center py-6">
                    Wala pang grades na na-record para sa section na ito.
                </p>

                <template v-else>
                    <div
                        class="border border-slate-200 rounded-xl p-5 text-center cursor-pointer hover:shadow-lg transition overflow-hidden"
                        @click="showFullList = true"
                    >
                        <Transition :name="slideDirection" mode="out-in">
                            <div :key="currentIndex">
                                <div
                                    class="w-full aspect-square rounded-lg flex items-center justify-center text-5xl font-semibold"
                                    :style="{ background: '#FFCC00', color: '#412402' }"
                                >
                                    {{ initials(currentStudent.name) }}
                                </div>
                                <div class="text-xs font-medium text-[#003399] mt-4">top {{ currentIndex + 1 }}</div>
                                <div class="text-base font-semibold text-slate-800 mt-1">{{ currentStudent.name }}</div>
                                <div class="text-sm text-slate-400 mt-0.5">{{ currentStudent.grade }} average</div>
                            </div>
                        </Transition>

                        <div class="flex items-center justify-center gap-1.5 mt-4">
                            <button
                                v-for="(s, i) in filteredStudents"
                                :key="s.name"
                                @click.stop="goTo(i)"
                                class="rounded-full transition"
                                :class="i === currentIndex ? 'w-2.5 h-2.5 bg-[#FFCC00]' : 'w-2 h-2 bg-slate-200'"
                            ></button>
                        </div>
                    </div>

                    <button @click="showFullList = !showFullList" class="w-full text-xs text-[#003399] font-medium mt-4 hover:underline">
                        {{ showFullList ? 'Hide full list' : 'View full list' }}
                    </button>

                    <div v-if="showFullList" class="mt-2 divide-y divide-slate-100 border-t border-slate-100">
                        <div v-for="(s, i) in filteredStudents" :key="s.name" class="flex items-center gap-2 py-2">
                            <span class="text-xs text-slate-400 w-4">{{ i + 1 }}</span>
                            <div class="text-sm text-slate-700 flex-1 truncate">{{ s.name }}</div>
                            <div class="text-xs font-semibold text-[#003399]">{{ s.grade }}</div>
                        </div>
                    </div>
                </template>
            </div>
        </main>

        <div class="fixed bottom-6 right-6 z-50">
            <div
                v-if="chatOpen"
                class="mb-3 w-72 bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden"
            >
                <div class="bg-[#003399] text-white text-sm font-medium px-4 py-3 flex items-center justify-between">
                    Ask Sir Francisco
                    <button @click="chatOpen = false" class="text-white/70 hover:text-white">✕</button>
                </div>
                <div class="p-4 space-y-2">
                    <p class="text-xs text-slate-400">Kailangan mag-sign in muna para makapagtanong.</p>
                    <input placeholder="Student number" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" />
                    <input type="password" placeholder="Password" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" />
                    <button class="w-full bg-[#003399] text-white text-sm font-medium py-2 rounded-lg">Sign in to chat</button>
                </div>
            </div>

            <button
                @click="chatOpen = !chatOpen"
                class="w-14 h-14 rounded-full bg-[#003399] text-white shadow-lg hover:shadow-xl hover:scale-105 transition transform flex items-center justify-center"
            >
                <svg v-if="!chatOpen" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-8.5 8.5 8.5 8.5 0 0 1-4-1L3 20l1-5.5A8.38 8.38 0 0 1 3 11.5 8.5 8.5 0 0 1 11.5 3 8.38 8.38 0 0 1 21 11.5Z"/></svg>
                <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    sections: { type: Array, default: () => [] },
    announcementsBySection: { type: Object, default: () => ({}) },
    topicsBySection: { type: Object, default: () => ({}) },
    globalCalendarEvents: { type: Array, default: () => [] },
    calendarEventsBySection: { type: Object, default: () => ({}) },
    top10BySection: { type: Object, default: () => ({}) },
    lastCalendarUpdate: { type: String, default: null },
});

const showFullList = ref(false);
const chatOpen = ref(false);
const paused = ref(false);
const currentIndex = ref(0);
const slideDirection = ref('slide-next');

const activeSectionId = ref(props.sections[0]?.id ?? null);

const activeSectionLabel = computed(() => {
    const s = props.sections.find((sec) => sec.id === activeSectionId.value);
    if (!s) return '';
    return s.subject ? `${s.subject} - ${s.name}` : s.name;
});

const filteredAnnouncements = computed(() => props.announcementsBySection[activeSectionId.value] ?? []);
const filteredTopics = computed(() => props.topicsBySection[activeSectionId.value] ?? []);
const calendarEvents = computed(() => [
    ...(props.globalCalendarEvents ?? []),
    ...(props.calendarEventsBySection[activeSectionId.value] ?? []),
]);
const filteredStudents = computed(() => props.top10BySection[activeSectionId.value] ?? []);

const currentStudent = computed(() => filteredStudents.value[currentIndex.value] ?? filteredStudents.value[0]);

watch(activeSectionId, () => { currentIndex.value = 0; });

const goTo = (i) => {
    slideDirection.value = i > currentIndex.value ? 'slide-next' : 'slide-prev';
    currentIndex.value = i;
};

let autoTimer;
let clockTimer;

onMounted(() => {
    autoTimer = setInterval(() => {
        if (paused.value || filteredStudents.value.length === 0) return;
        slideDirection.value = 'slide-next';
        currentIndex.value = (currentIndex.value + 1) % filteredStudents.value.length;
    }, 2200);

    clockTimer = setInterval(() => {
        liveClock.value = new Date().toLocaleString('en-PH', {
            weekday: 'short', month: 'short', day: 'numeric',
            hour: '2-digit', minute: '2-digit', second: '2-digit',
        });
    }, 1000);
});
onUnmounted(() => {
    clearInterval(autoTimer);
    clearInterval(clockTimer);
});

const liveClock = ref(new Date().toLocaleString('en-PH', {
    weekday: 'short', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit', second: '2-digit',
}));

const initials = (name) =>
    name.split(',')[0].trim().charAt(0) + (name.split(' ').pop()?.charAt(0) ?? '');

const formattedUpdate = computed(() => {
    if (!props.lastCalendarUpdate) return 'Wala pang update';
    const d = new Date(props.lastCalendarUpdate);
    return d.toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' });
});

const formatEventDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
};
</script>

<style scoped>
.blob-a {
    top: -6rem;
    left: -6rem;
    animation: float-a 14s ease-in-out infinite;
}
.blob-b {
    bottom: -6rem;
    right: -6rem;
    animation: float-b 16s ease-in-out infinite;
}
@keyframes float-a {
    0%, 100% { transform: translate(0, 0); }
    50% { transform: translate(60px, 40px); }
}
@keyframes float-b {
    0%, 100% { transform: translate(0, 0); }
    50% { transform: translate(-50px, -30px); }
}
.slide-next-enter-active,
.slide-next-leave-active,
.slide-prev-enter-active,
.slide-prev-leave-active {
    transition: all 0.35s ease;
}
.slide-next-enter-from { opacity: 0; transform: translateX(24px); }
.slide-next-leave-to { opacity: 0; transform: translateX(-24px); }
.slide-prev-enter-from { opacity: 0; transform: translateX(-24px); }
.slide-prev-leave-to { opacity: 0; transform: translateX(24px); }
</style>