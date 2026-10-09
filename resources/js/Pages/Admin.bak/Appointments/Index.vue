<!-- resources/js/Pages/Admin/Appointments/Index.vue -->
<template>
    <AdminLayout>
        <main class="max-w-5xl mx-auto px-6 lg:px-10 py-8 space-y-6">

            <div class="flex items-center justify-between">
                <div>
                    <div class="text-lg font-semibold text-slate-800">Appointments</div>
                    <div class="text-xs text-slate-400 mt-0.5">Manage your available times and student requests</div>
                </div>
                <button
                    @click="openAddModal"
                    class="bg-[#003399] text-white text-sm font-medium px-4 py-2 rounded-lg hover:opacity-90 transition"
                >
                    + Add available time
                </button>
            </div>

            <div v-if="$page.props.flash?.success" class="bg-[#EAF3DE] text-[#3B6D11] text-sm rounded-lg px-4 py-2">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.errors?.availability" class="bg-red-50 text-red-600 text-sm rounded-lg px-4 py-2">
                {{ $page.props.errors.availability }}
            </div>

            <!-- Tabs -->
            <div class="flex items-center gap-1 bg-white border border-slate-200 rounded-lg p-1 w-fit">
                <button
                    @click="activeTab = 'requests'"
                    class="text-xs font-medium px-3 py-1.5 rounded-md transition"
                    :class="activeTab === 'requests' ? 'bg-[#E6F1FB] text-[#003399]' : 'text-slate-500'"
                >
                    Requests ({{ pendingCount }})
                </button>
                <button
                    @click="activeTab = 'slots'"
                    class="text-xs font-medium px-3 py-1.5 rounded-md transition"
                    :class="activeTab === 'slots' ? 'bg-[#E6F1FB] text-[#003399]' : 'text-slate-500'"
                >
                    Available Times ({{ slots.length }})
                </button>
            </div>

            <!-- Requests tab -->
            <div v-if="activeTab === 'requests'" class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <p v-if="requests.length === 0" class="text-xs text-slate-400 px-5 py-5">No appointment requests yet.</p>
                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-left text-xs text-slate-500">
                            <th class="px-5 py-2">Student</th>
                            <th class="px-5 py-2">Date &amp; time</th>
                            <th class="px-5 py-2">Reason</th>
                            <th class="px-5 py-2">Status</th>
                            <th class="px-5 py-2 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in requests" :key="r.id" class="border-t border-slate-100 align-top">
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-800">{{ r.student_name }}</div>
                                <div class="text-xs text-slate-400">{{ r.student_number }}</div>
                            </td>
                            <td class="px-5 py-3 text-slate-600 whitespace-nowrap">
                                {{ formatDate(r.appointment_date) }}<br>
                                <span class="text-xs text-slate-400">{{ r.start_time }}–{{ r.end_time }}</span>
                            </td>
                            <td class="px-5 py-3 text-slate-500">{{ r.reason || '—' }}</td>
                            <td class="px-5 py-3">
                                <span
                                    class="inline-block text-[11px] font-medium px-2 py-0.5 rounded-full whitespace-nowrap"
                                    :class="statusClass(r.status)"
                                >
                                    {{ r.status }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div v-if="r.status === 'pending'" class="flex items-center justify-center gap-2">
                                    <button
                                        @click="resolve(r, 'approved')"
                                        title="Approve"
                                        class="p-1.5 rounded-lg text-[#3B6D11] hover:bg-[#EAF3DE] transition"
                                    >
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                    </button>
                                    <button
                                        @click="resolve(r, 'declined')"
                                        title="Decline"
                                        class="p-1.5 rounded-lg text-red-500 hover:bg-red-50 transition"
                                    >
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <span v-else class="text-xs text-slate-300 flex items-center justify-center">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Available times tab -->
            <div v-if="activeTab === 'slots'" class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <p v-if="slots.length === 0" class="text-xs text-slate-400 px-5 py-5">No available times set yet.</p>
                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-left text-xs text-slate-500">
                            <th class="px-5 py-2">Date</th>
                            <th class="px-5 py-2">Time</th>
                            <th class="px-5 py-2">Notes</th>
                            <th class="px-5 py-2">Status</th>
                            <th class="px-5 py-2 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in slots" :key="s.id" class="border-t border-slate-100 align-top">
                            <td class="px-5 py-3 text-slate-800 font-medium whitespace-nowrap">{{ formatDate(s.date) }}</td>
                            <td class="px-5 py-3 text-slate-600 whitespace-nowrap">{{ s.start_time }}–{{ s.end_time }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ s.notes || '—' }}</td>
                            <td class="px-5 py-3">
                                <span
                                    class="inline-block text-[11px] font-medium px-2 py-0.5 rounded-full whitespace-nowrap"
                                    :class="s.is_booked ? 'bg-[#E6F1FB] text-[#003399]' : (s.is_active ? 'bg-[#EAF3DE] text-[#3B6D11]' : 'bg-slate-100 text-slate-500')"
                                >
                                    {{ s.is_booked ? 'Booked' : (s.is_active ? 'Open' : 'Inactive') }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <button
                                        v-if="!s.is_booked"
                                        @click="destroySlot(s)"
                                        title="Delete"
                                        class="p-1.5 rounded-lg text-red-500 hover:text-red-700 hover:bg-red-50 transition"
                                    >
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M3 6h18"/>
                                            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/>
                                            <path d="M10 11v6M14 11v6"/>
                                        </svg>
                                    </button>
                                    <span v-else class="text-xs text-slate-300">—</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>

        <!-- Add available time modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4" @click.self="closeModal">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-sm font-semibold text-slate-800">Add available time</div>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-600">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-3">
                    <div>
                        <label class="text-xs text-slate-500 block mb-1">Date</label>
                        <input v-model="form.date" type="date" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" />
                        <p v-if="form.errors.date" class="text-xs text-red-500 mt-1">{{ form.errors.date }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-slate-500 block mb-1">Start time</label>
                            <input v-model="form.start_time" type="time" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" />
                            <p v-if="form.errors.start_time" class="text-xs text-red-500 mt-1">{{ form.errors.start_time }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-slate-500 block mb-1">End time</label>
                            <input v-model="form.end_time" type="time" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" />
                            <p v-if="form.errors.end_time" class="text-xs text-red-500 mt-1">{{ form.errors.end_time }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500 block mb-1">Notes (optional)</label>
                        <textarea v-model="form.notes" rows="2" placeholder="e.g. Faculty room, consultation only" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2"></textarea>
                        <p v-if="form.errors.notes" class="text-xs text-red-500 mt-1">{{ form.errors.notes }}</p>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="closeModal" class="text-sm text-slate-500 px-4 py-2">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="bg-[#003399] text-white text-sm font-medium px-4 py-2 rounded-lg disabled:opacity-50">
                            Add
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    slots: { type: Array, default: () => [] },
    requests: { type: Array, default: () => [] },
});

const activeTab = ref('requests');

const pendingCount = computed(() => props.requests.filter((r) => r.status === 'pending').length);

const statusClass = (status) => ({
    pending: 'bg-slate-100 text-slate-500',
    approved: 'bg-[#EAF3DE] text-[#3B6D11]',
    declined: 'bg-red-50 text-red-600',
    cancelled: 'bg-slate-100 text-slate-400',
}[status] ?? 'bg-slate-100 text-slate-500');

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
};

const resolve = (r, status) => {
    const label = status === 'approved' ? 'approve' : 'decline';
    if (!confirm(`Are you sure you want to ${label} this appointment request?`)) return;
    router.patch(`/paulo/appointments/${r.id}/resolve`, { status });
};

const destroySlot = (s) => {
    if (!confirm('Are you sure you want to delete this available time slot?')) return;
    router.delete(`/paulo/availability/${s.id}`);
};

const showModal = ref(false);
const form = useForm({ date: '', start_time: '', end_time: '', notes: '' });

const openAddModal = () => {
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    form.post('/paulo/availability', { onSuccess: () => closeModal() });
};
</script>