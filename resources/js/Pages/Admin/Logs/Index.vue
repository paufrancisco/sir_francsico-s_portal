<script setup>
import { computed, reactive, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { activityTypes, timeAgo } from '@/composables/activityTypes';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    tab: String,
    filters: Object,
    rows: Object,
    sections: { type: Array, default: () => [] },
});

const page = usePage();
const unreadCount = computed(() => page.props.activity?.unread ?? 0);

const f = reactive({
    q: props.filters.q ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
    result: props.filters.result ?? '',
    type: props.filters.type ?? '',
    section_id: props.filters.section_id ?? '',
    unread: props.filters.unread ?? '',
});

const go = (extra = {}) => {
    const params = { tab: props.tab, ...f, ...extra };
    Object.keys(params).forEach((k) => (params[k] === '' || params[k] == null) && delete params[k]);
    router.get('/paulo/logs', params, { preserveState: true, preserveScroll: true, replace: true });
};

let timer;
watch(() => f.q, () => { clearTimeout(timer); timer = setTimeout(() => go(), 350); });
watch(() => [f.from, f.to, f.result, f.type, f.section_id, f.unread], () => go());

const switchTab = (t) => router.get('/paulo/logs', { tab: t });

const markAllRead = () => router.post('/paulo/activity/read', {}, { preserveScroll: true, preserveState: true });

const toggleWall = (id) => router.post(`/paulo/freedom-wall/${id}/toggle-hidden`, {}, { preserveScroll: true, preserveState: true });

const fmt = (iso) => iso ? new Date(iso).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '—';

const sourceLabel = { chat: 'AI chat', grades: 'Grades', appointments: 'Appointments' };

const tabs = [
    { k: 'logins', l: 'Logins' },
    { k: 'messages', l: 'Messages' },
    { k: 'activity', l: 'Activity' },
];

const placeholder = computed(() => ({
    logins: 'Search name or student number',
    messages: 'Search name, number, or message',
    activity: 'Search name, number, or details',
}[props.tab]));

const device = (ua) => {
    if (!ua) return '—';
    const os = /Windows/i.test(ua) ? 'Windows' : /Android/i.test(ua) ? 'Android' : /iPhone|iPad|iOS/i.test(ua) ? 'iOS' : /Mac OS/i.test(ua) ? 'macOS' : /Linux/i.test(ua) ? 'Linux' : 'Other';
    const br = /Edg\//i.test(ua) ? 'Edge' : /OPR\//i.test(ua) ? 'Opera' : /Chrome\//i.test(ua) ? 'Chrome' : /Firefox\//i.test(ua) ? 'Firefox' : /Safari\//i.test(ua) ? 'Safari' : 'Browser';
    return `${br} · ${os}`;
};
</script>

<template>
    <div class="p-4 md:p-6 space-y-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-slate-800">Logs</h1>
                <p class="text-xs text-slate-500">Student logins, posted messages, and activity.</p>
            </div>
            <button
                v-if="tab === 'activity' && unreadCount > 0"
                @click="markAllRead"
                class="px-3 py-1.5 text-sm rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50"
            >Mark all read ({{ unreadCount }})</button>
        </div>

        <div class="flex gap-2">
            <button
                v-for="t in tabs"
                :key="t.k"
                @click="switchTab(t.k)"
                class="px-4 py-1.5 rounded-full text-sm font-medium border transition flex items-center gap-2"
                :class="tab === t.k ? 'bg-[var(--navy)] text-white border-transparent' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
            >
                {{ t.l }}
                <span
                    v-if="t.k === 'activity' && unreadCount > 0"
                    class="min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-semibold flex items-center justify-center"
                >{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
            </button>
        </div>

        <div class="flex flex-wrap gap-2 items-center">
            <input v-model="f.q" type="search" :placeholder="placeholder"
                class="px-3 py-1.5 text-sm rounded-lg border border-slate-200 bg-white w-64" />
            <input v-model="f.from" type="date" class="px-2 py-1.5 text-sm rounded-lg border border-slate-200 bg-white" />
            <span class="text-xs text-slate-400">to</span>
            <input v-model="f.to" type="date" class="px-2 py-1.5 text-sm rounded-lg border border-slate-200 bg-white" />

            <select v-if="tab === 'logins'" v-model="f.result" class="px-2 py-1.5 text-sm rounded-lg border border-slate-200 bg-white">
                <option value="">All results</option>
                <option value="success">Success</option>
                <option value="failed">Failed</option>
            </select>

            <template v-if="tab === 'activity'">
                <select v-model="f.type" class="px-2 py-1.5 text-sm rounded-lg border border-slate-200 bg-white">
                    <option value="">All activity</option>
                    <option v-for="(t, key) in activityTypes" :key="key" :value="key">{{ t.label }}</option>
                </select>
                <select v-model="f.section_id" class="px-2 py-1.5 text-sm rounded-lg border border-slate-200 bg-white">
                    <option value="">All sections</option>
                    <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
                <select v-model="f.unread" class="px-2 py-1.5 text-sm rounded-lg border border-slate-200 bg-white">
                    <option value="">Read and unread</option>
                    <option value="1">Unread only</option>
                </select>
            </template>
        </div>

        <div class="bg-white border border-slate-200 rounded-xl overflow-x-auto">
            <table v-if="tab === 'logins'" class="w-full text-sm">
                <thead class="text-left text-xs text-slate-500 bg-slate-50">
                    <tr>
                        <th class="px-3 py-2">When</th><th class="px-3 py-2">Student</th><th class="px-3 py-2">Section</th>
                        <th class="px-3 py-2">Via</th><th class="px-3 py-2">Result</th><th class="px-3 py-2">Device</th><th class="px-3 py-2">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="r in rows.data" :key="r.id">
                        <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ fmt(r.at) }}</td>
                        <td class="px-3 py-2">
                            <div class="font-medium text-slate-700">{{ r.student_name ?? 'Unknown' }}</div>
                            <div class="text-xs text-slate-400">{{ r.student_number }}</div>
                        </td>
                        <td class="px-3 py-2 text-slate-600">{{ r.section ?? '—' }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ sourceLabel[r.source] ?? r.source }}</td>
                        <td class="px-3 py-2">
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full"
                                :class="r.success ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600'">
                                {{ r.success ? 'Success' : 'Failed' }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ device(r.user_agent) }}</td>
                        <td class="px-3 py-2 text-slate-500">{{ r.ip ?? '—' }}</td>
                    </tr>
                    <tr v-if="!rows.data.length"><td colspan="7" class="px-3 py-8 text-center text-slate-400">No logs yet.</td></tr>
                </tbody>
            </table>

            <table v-else-if="tab === 'messages'" class="w-full text-sm">
                <thead class="text-left text-xs text-slate-500 bg-slate-50">
                    <tr><th class="px-3 py-2">When</th><th class="px-3 py-2">Posted by</th><th class="px-3 py-2">Section</th><th class="px-3 py-2">Message</th><th class="px-3 py-2">Status</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="r in rows.data" :key="r.id">
                        <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ fmt(r.at) }}</td>
                        <td class="px-3 py-2">
                            <div class="font-medium text-slate-700">{{ r.student_name ?? 'Deleted student' }}</div>
                            <div class="text-xs text-slate-400">{{ r.student_number }}</div>
                        </td>
                        <td class="px-3 py-2 text-slate-600">{{ r.section ?? '—' }}</td>
                        <td class="px-3 py-2 text-slate-700 max-w-md break-words">{{ r.body }}</td>
                        <td class="px-3 py-2">
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full"
                                :class="r.hidden ? 'bg-slate-200 text-slate-600' : 'bg-emerald-100 text-emerald-700'">
                                {{ r.hidden ? 'Hidden' : 'Visible' }}
                            </span>
                        </td>
                    </tr>
                    <tr v-if="!rows.data.length"><td colspan="5" class="px-3 py-8 text-center text-slate-400">No messages found.</td></tr>
                </tbody>
            </table>

            <table v-else class="w-full text-sm">
                <thead class="text-left text-xs text-slate-500 bg-slate-50">
                    <tr>
                        <th class="px-3 py-2">When</th><th class="px-3 py-2">Student</th><th class="px-3 py-2">Section</th>
                        <th class="px-3 py-2">Type</th><th class="px-3 py-2">Details</th><th class="px-3 py-2">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="r in rows.data" :key="r.id" :class="r.read ? '' : 'log-row-unread'">
                        <td class="px-3 py-2 whitespace-nowrap text-slate-600">
                            <div>{{ fmt(r.at) }}</div>
                            <div class="text-xs text-slate-400">{{ timeAgo(r.at) }}</div>
                        </td>
                        <td class="px-3 py-2">
                            <div class="font-medium text-slate-700">{{ r.student_name ?? 'Deleted student' }}</div>
                            <div class="text-xs text-slate-400">{{ r.student_number }}</div>
                        </td>
                        <td class="px-3 py-2 text-slate-600">{{ r.section ?? '—' }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full" :class="[activityTypes[r.type]?.cls ?? 'bg-slate-100 text-slate-600', 'log-badge--' + r.type]">
                                {{ activityTypes[r.type]?.label ?? r.type }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-slate-700 max-w-md break-words">
                            {{ r.summary }}
                            <button
                                v-if="r.wall_hidden !== null && r.wall_hidden !== undefined"
                                type="button"
                                @click="toggleWall(r.wall_post_id)"
                                class="ml-2 text-xs font-medium px-2 py-0.5 rounded-full border"
                                :class="r.wall_hidden ? 'border-emerald-400 text-emerald-500' : 'border-red-400 text-red-500'"
                            >{{ r.wall_hidden ? 'Unhide' : 'Hide' }}</button>
                            <span v-if="r.wall_hidden" class="ml-1 text-xs text-slate-400">(hidden)</span>
                        </td>
                        <td class="px-3 py-2">
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full"
                                :class="r.read ? 'bg-slate-100 text-slate-500' : 'bg-amber-100 text-amber-700 log-badge--unread'">
                                {{ r.read ? 'Read' : 'Unread' }}
                            </span>
                        </td>
                    </tr>
                    <tr v-if="!rows.data.length"><td colspan="6" class="px-3 py-8 text-center text-slate-400">No activity found.</td></tr>
                </tbody>
            </table>
        </div>

        <div v-if="rows.links && rows.last_page > 1" class="flex flex-wrap gap-1">
            <template v-for="(l, i) in rows.links" :key="i">
                <Link v-if="l.url" :href="l.url" preserve-scroll preserve-state
                    class="px-3 py-1 text-sm rounded-lg border"
                    :class="l.active ? 'bg-[var(--navy)] text-white border-transparent' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                    v-html="l.label" />
                <span v-else class="px-3 py-1 text-sm rounded-lg border border-slate-100 text-slate-300" v-html="l.label" />
            </template>
        </div>
    </div>
</template>

<style>
.log-row-unread { box-shadow: inset 3px 0 0 #f5c542; background-color: rgba(245, 197, 66, 0.07) !important; }
.log-row-unread td { color: inherit; }
.log-badge { display: inline-block; font-size: 0.75rem; font-weight: 600; padding: 0.125rem 0.6rem; border-radius: 999px; border: 1px solid transparent; }
.log-badge--wall_post { background: rgba(236, 72, 153, 0.18) !important; color: #f9a8d4 !important; border-color: rgba(236, 72, 153, 0.35); }
.log-badge--classmate_message { background: rgba(56, 189, 248, 0.18) !important; color: #7dd3fc !important; border-color: rgba(56, 189, 248, 0.35); }
.log-badge--unread { background: rgba(245, 197, 66, 0.2) !important; color: #fcd34d !important; border-color: rgba(245, 197, 66, 0.4); }
</style>
