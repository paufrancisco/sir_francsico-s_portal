<template>
    <AdminLayout>
        <main class="w-full px-6 lg:px-10 py-8 space-y-4">

            <div class="flex items-center justify-between">
                <div>
                    <div class="text-lg font-semibold text-slate-800">Seating Arrangement</div>
                    <div class="text-xs text-slate-400">
                        {{ viewMode === 'summary' ? 'History ng aura points per date.' : 'Click a box to assign, o i-select para bulk-award ng points.' }}
                    </div>
                </div>

                <div class="relative">
                    <select
                        :value="activeSectionId"
                        @change="switchSection($event.target.value)"
                        class="appearance-none text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg pl-4 pr-10 py-2.5 shadow-sm cursor-pointer transition hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#003399]/20 focus:border-[#003399]/40"
                    >
                        <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                    <svg
                        class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"
                        width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    >
                        <path d="M6 9l6 6 6-6" />
                    </svg>
                </div>
            </div>

            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1 w-fit">
                    <button
                        @click="switchTab('lecture')"
                        class="text-xs font-medium px-4 py-1.5 rounded-md transition"
                        :class="viewMode === 'lecture' ? 'bg-white text-[#003399] shadow-sm' : 'text-slate-500'"
                    >
                        Lecture Room
                    </button>
                    <button
                        @click="switchTab('comlab')"
                        class="text-xs font-medium px-4 py-1.5 rounded-md transition"
                        :class="viewMode === 'comlab' ? 'bg-white text-[#003399] shadow-sm' : 'text-slate-500'"
                    >
                        Comlab
                    </button>
                    <button
                        @click="switchTab('summary')"
                        class="text-xs font-medium px-4 py-1.5 rounded-md transition"
                        :class="viewMode === 'summary' ? 'bg-white text-[#003399] shadow-sm' : 'text-slate-500'"
                    >
                        Lec Lab Summary
                    </button>
                </div>

                <!-- Selection controls, seating tabs lang -->
                <div v-if="viewMode !== 'summary'" class="flex items-center gap-2">
                    <button
                        @click="toggleSelectMode"
                        class="text-xs font-medium px-3 py-1.5 rounded-lg border transition"
                        :class="selectMode ? 'bg-[#003399] text-white border-[#003399]' : 'border-slate-200 text-slate-600'"
                    >
                        {{ selectMode ? 'Done selecting' : 'Select students' }}
                    </button>
                    <button
                        v-if="selectMode"
                        @click="selectAll"
                        class="text-xs font-medium px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600"
                    >
                        Select all
                    </button>
                    <button
                        v-if="selectMode && selectedIds.size > 0"
                        @click="clearSelection"
                        class="text-xs font-medium px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600"
                    >
                        Clear ({{ selectedIds.size }})
                    </button>
                </div>
            </div>

            <!-- Floating bulk-action bar -->
            <div
                v-if="viewMode !== 'summary' && selectMode && selectedIds.size > 0"
                class="sticky top-2 z-20 bg-white border border-[#003399]/20 rounded-xl shadow-lg px-4 py-3 flex items-center justify-between flex-wrap gap-3"
            >
                <div class="text-sm font-medium text-slate-700">
                    {{ selectedIds.size }} student(s) selected
                </div>
                <div class="flex items-center gap-2">
                    <button @click="bulkApply(1)" :disabled="bulkApplying" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-[#EAF3DE] text-[#3B6D11] disabled:opacity-50">
                        +1 pt
                    </button>
                    <button @click="bulkApply(5)" :disabled="bulkApplying" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-[#EAF3DE] text-[#3B6D11] disabled:opacity-50">
                        +5 pts
                    </button>
                    <button @click="bulkApply(-1)" :disabled="bulkApplying" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-red-50 text-red-600 disabled:opacity-50">
                        -1 pt
                    </button>
                </div>
            </div>

            <!-- Lecture layout -->
            <div v-if="viewMode === 'lecture'" class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
                <div class="flex w-full gap-8">
                    <div
                        v-for="groupIndex in 2"
                        :key="groupIndex"
                        class="flex-1"
                        :style="lectureGroupStyle()"
                    >
                        <SeatBox
                            v-for="pos in groupPositions(groupIndex, 5, 5)"
                            :key="pos"
                            :seat="seats[pos]"
                            :select-mode="selectMode"
                            :selected="seats[pos]?.student ? selectedIds.has(seats[pos].student.id) : false"
                            @click="openSeatModal(pos)"
                            @toggle-select="toggleSelect(seats[pos].student.id)"
                        />
                    </div>
                </div>
            </div>

            <!-- Comlab layout -->
            <div v-else-if="viewMode === 'comlab'" class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
                <div class="flex w-full gap-8">
                    <div class="flex-1" :style="comlabGroupStyle(1)">
                        <SeatBox
                            v-for="pos in groupPositions(1, 1, 10, 0)"
                            :key="pos"
                            :seat="seats[pos]"
                            :select-mode="selectMode"
                            :selected="seats[pos]?.student ? selectedIds.has(seats[pos].student.id) : false"
                            @click="openSeatModal(pos)"
                            @toggle-select="toggleSelect(seats[pos].student.id)"
                        />
                    </div>
                    <div class="flex-1" :style="comlabGroupStyle(2)">
                        <SeatBox
                            v-for="pos in groupPositions(2, 1, 10, 10)"
                            :key="pos"
                            :seat="seats[pos]"
                            :select-mode="selectMode"
                            :selected="seats[pos]?.student ? selectedIds.has(seats[pos].student.id) : false"
                            @click="openSeatModal(pos)"
                            @toggle-select="toggleSelect(seats[pos].student.id)"
                        />
                    </div>
                    <div style="flex: 2 1 0%;" :style="comlabGroupStyle(3)">
                        <SeatBox
                            v-for="pos in groupPositions(3, 2, 10, 20)"
                            :key="pos"
                            :seat="seats[pos]"
                            :select-mode="selectMode"
                            :selected="seats[pos]?.student ? selectedIds.has(seats[pos].student.id) : false"
                            @click="openSeatModal(pos)"
                            @toggle-select="toggleSelect(seats[pos].student.id)"
                        />
                    </div>
                </div>
            </div>

            <!-- Lec Lab Summary -->
            <div v-else class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-5 py-3 border-b border-slate-100">
                    <div class="text-sm font-semibold text-slate-700">Aura points history</div>
                    <button
                        @click="confirmReset"
                        :disabled="resetting"
                        class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-red-200 text-red-600 disabled:opacity-50"
                    >
                        {{ resetting ? 'Nagre-reset...' : 'Reset all' }}
                    </button>
                </div>

                <p v-if="summaryLoading" class="text-xs text-slate-400 text-center py-8">Naglo-load...</p>

                <p v-else-if="summaryRows.length === 0" class="text-xs text-slate-400 text-center py-8">
                    Wala pang na-record na aura points sa section na ito.
                </p>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 text-xs">
                            <tr>
                                <th class="text-left px-4 py-3 sticky left-0 bg-slate-50">Student</th>
                                <th v-for="d in summaryDates" :key="d" class="text-center px-3 py-3 whitespace-nowrap">
                                    {{ formatDateHeader(d) }}
                                </th>
                                <th class="text-center px-4 py-3 font-semibold">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="row in summaryRows" :key="row.id">
                                <td class="px-4 py-2.5 sticky left-0 bg-white">
                                    <div class="font-medium text-slate-700">{{ row.name }}</div>
                                    <div class="text-xs text-slate-400">{{ row.student_number }}</div>
                                </td>
                                <td
                                    v-for="d in summaryDates"
                                    :key="d"
                                    class="text-center px-3 py-2.5 tabular-nums"
                                    :class="pointsColor(row.by_date[d])"
                                >
                                    {{ row.by_date[d] !== null && row.by_date[d] !== undefined ? formatDelta(row.by_date[d]) : '—' }}
                                </td>
                                <td class="text-center px-4 py-2.5 font-semibold text-[#003399] tabular-nums">
                                    {{ row.total }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="viewMode !== 'summary'" class="text-xs text-slate-400">
                {{ unassignedStudents.length }} student(s) not yet assigned in this layout.
            </div>
        </main>

        <!-- Seat modal -->
        <div v-if="modalOpen" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50 px-4" @click.self="closeModal">
            <div class="bg-white rounded-xl p-5 w-full max-w-sm shadow-xl">

                <div class="flex items-center justify-between mb-3">
                    <div class="text-sm font-semibold text-slate-700">Seat #{{ activePosition + 1 }}</div>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <template v-if="activeSeat?.student">
                    <div class="flex items-center gap-3 mb-4">
                        <div
                            class="relative w-14 h-14 rounded-full overflow-hidden border border-slate-200 bg-slate-100 shrink-0 cursor-pointer group"
                            @click="triggerPhotoUpload"
                        >
                            <img v-if="activeSeat.student.photo_url" :src="activeSeat.student.photo_url" class="w-full h-full object-cover" />
                            <span v-else class="w-full h-full flex items-center justify-center text-slate-400 text-sm font-medium">
                                {{ initials(activeSeat.student.full_name) }}
                            </span>
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                <span class="text-white text-[9px] font-medium text-center leading-tight px-1">
                                    {{ photoUploading ? '...' : 'Change' }}
                                </span>
                            </div>
                            <input
                                ref="photoInputRef"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="onPhotoSelected"
                            />
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-slate-800">{{ surnameFirst(activeSeat.student.full_name) }}</div>
                            <div class="text-xs text-slate-400">{{ activeSeat.student.student_number }}</div>
                        </div>
                    </div>

                    <div class="bg-slate-50 rounded-lg px-4 py-3 mb-4">
                        <div class="flex items-center justify-between mb-2.5">
                            <span class="text-xs text-slate-500">Aura Points</span>
                            <div class="flex items-center gap-3">
                                <button @click="adjustAura(-1)" class="w-7 h-7 rounded-full border border-slate-200 text-slate-500 hover:bg-white transition">−</button>
                                <span class="text-sm font-bold text-[#003399] w-8 text-center">{{ activeSeat.student.aura_points }}</span>
                                <button @click="adjustAura(1)" class="w-7 h-7 rounded-full border border-slate-200 text-slate-500 hover:bg-white transition">+</button>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="adjustAura(1)" class="flex-1 text-xs font-semibold py-1.5 rounded-lg bg-[#EAF3DE] text-[#3B6D11]">
                                +1 quick
                            </button>
                            <button @click="adjustAura(5)" class="flex-1 text-xs font-semibold py-1.5 rounded-lg bg-[#EAF3DE] text-[#3B6D11]">
                                +5 quick
                            </button>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button @click="showReassign = true" class="flex-1 border border-slate-200 text-slate-600 text-xs font-medium py-2 rounded-lg">
                            Change student
                        </button>
                        <button @click="unassignSeat" class="flex-1 bg-red-50 text-red-600 text-xs font-medium py-2 rounded-lg">
                            Remove from seat
                        </button>
                    </div>
                </template>

                <template v-if="!activeSeat?.student || showReassign">
                    <div class="mt-3 pt-3 border-t border-slate-100" v-if="activeSeat?.student">
                        <div class="text-xs text-slate-500 mb-2">Choose a new student:</div>
                    </div>

                    <input
                        v-model="studentSearch"
                        type="text"
                        placeholder="Search student number or name..."
                        class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 mb-2 sticky top-0 bg-white z-10"
                    />

                    <div class="h-56 overflow-y-scroll divide-y divide-slate-100 border border-slate-100 rounded-lg overscroll-contain">
                        <button
                            v-for="s in filteredUnassigned"
                            :key="s.id"
                            @click="assignSeat(s.id)"
                            class="w-full flex items-center gap-2 px-3 py-2 hover:bg-slate-50 text-left transition"
                        >
                            <div class="w-8 h-8 rounded-full overflow-hidden border border-slate-200 bg-slate-100 shrink-0">
                                <img v-if="s.photo_url" :src="s.photo_url" class="w-full h-full object-cover" />
                                <span v-else class="w-full h-full flex items-center justify-center text-slate-400 text-[10px] font-medium">
                                    {{ initials(s.full_name) }}
                                </span>
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs font-medium text-slate-700 truncate">{{ surnameFirst(s.full_name) }}</div>
                                <div class="text-[11px] text-slate-400">{{ s.student_number }}</div>
                            </div>
                        </button>
                        <p v-if="filteredUnassigned.length === 0" class="text-xs text-slate-400 text-center py-4">
                            No more available students.
                        </p>
                    </div>
                </template>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SeatBox from '@/Components/SeatBox.vue';
import axios from 'axios';

const props = defineProps({
    sections: Array,
    activeSectionId: Number,
    layout: String,
    seats: Object,
    unassignedStudents: Array,
});

// ---- View tabs (lecture / comlab / summary) ----
const viewMode = ref(props.layout === 'comlab' ? 'comlab' : 'lecture');

const switchTab = (tab) => {
    viewMode.value = tab;
    selectMode.value = false;
    selectedIds.value = new Set();

    if (tab === 'summary') {
        loadSummary();
        return;
    }

    router.get('/paulo/seating', { section_id: props.activeSectionId, layout: tab }, { preserveState: true });
};
// ---- End view tabs ----

// ---- Selection mode (bulk aura points) ----
const selectMode = ref(false);
const selectedIds = ref(new Set());
const bulkApplying = ref(false);

const toggleSelectMode = () => {
    selectMode.value = !selectMode.value;
    if (!selectMode.value) selectedIds.value = new Set();
};

const toggleSelect = (studentId) => {
    const next = new Set(selectedIds.value);
    if (next.has(studentId)) next.delete(studentId);
    else next.add(studentId);
    selectedIds.value = next;
};

const selectAll = () => {
    const ids = Object.values(props.seats)
        .filter((s) => s?.student)
        .map((s) => s.student.id);
    selectedIds.value = new Set(ids);
};

const clearSelection = () => {
    selectedIds.value = new Set();
};

const bulkApply = async (delta) => {
    if (selectedIds.value.size === 0) return;
    bulkApplying.value = true;
    try {
        const { data } = await axios.post('/paulo/seating/aura/bulk', {
            student_ids: Array.from(selectedIds.value),
            delta,
        });
        // I-update ang local seats object para makita agad ang bagong points
        Object.values(props.seats).forEach((seat) => {
            if (seat?.student && data.updated[seat.student.id] !== undefined) {
                seat.student.aura_points = data.updated[seat.student.id];
            }
        });
    } catch (e) {
        alert(e.response?.data?.message ?? 'May error, subukan ulit.');
    } finally {
        bulkApplying.value = false;
    }
};
// ---- End selection mode ----

// ---- Lec Lab Summary ----
const summaryLoading = ref(false);
const summaryDates = ref([]);
const summaryRows = ref([]);
const resetting = ref(false);

const loadSummary = async () => {
    summaryLoading.value = true;
    try {
        const { data } = await axios.get('/paulo/seating/aura/summary', {
            params: { section_id: props.activeSectionId },
        });
        summaryDates.value = data.dates;
        summaryRows.value = data.rows;
    } catch (e) {
        alert('Hindi na-load ang summary.');
    } finally {
        summaryLoading.value = false;
    }
};

const formatDateHeader = (dateStr) => {
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
};

const formatDelta = (n) => (n > 0 ? `+${n}` : `${n}`);

const pointsColor = (n) => {
    if (n === null || n === undefined) return 'text-slate-300';
    if (n > 0) return 'text-[#3B6D11] font-medium';
    if (n < 0) return 'text-red-600 font-medium';
    return 'text-slate-400';
};

const confirmReset = () => {
    const confirmed = confirm('Sigurado ka bang gusto mong i-reset ang LAHAT ng aura points sa section na ito? Hindi na ito mababawi (pero mananatili ang history).');
    if (!confirmed) return;
    resetAll();
};

const resetAll = async () => {
    resetting.value = true;
    try {
        await axios.post('/paulo/seating/aura/reset', { section_id: props.activeSectionId });
        await loadSummary();
        router.reload({ only: ['seats', 'unassignedStudents'] });
    } catch (e) {
        alert('Hindi na-reset, subukan ulit.');
    } finally {
        resetting.value = false;
    }
};
// ---- End Lec Lab Summary ----

const modalOpen = ref(false);
const activePosition = ref(null);
const showReassign = ref(false);
const studentSearch = ref('');
const photoInputRef = ref(null);
const photoUploading = ref(false);

const activeSeat = computed(() => props.seats[activePosition.value] ?? null);

const filteredUnassigned = computed(() => {
    const q = studentSearch.value.trim().toLowerCase();
    if (!q) return props.unassignedStudents;
    return props.unassignedStudents.filter((s) =>
        s.full_name.toLowerCase().includes(q) || s.student_number.toLowerCase().includes(q)
    );
});

const groupPositions = (groupIndex, cols, rows, offsetOverride = null) => {
    const perGroup = cols * rows;
    const offset = offsetOverride !== null ? offsetOverride : (groupIndex - 1) * perGroup;
    return Array.from({ length: perGroup }, (_, i) => offset + i);
};

const lectureGroupStyle = () => ({
    display: 'grid',
    gridTemplateColumns: 'repeat(5, minmax(0, 1fr))',
    gridAutoRows: 'minmax(130px, 1fr)',
    gap: '12px',
    alignItems: 'stretch',
    justifyItems: 'stretch',
});

const comlabGroupStyle = (groupIndex) => ({
    display: 'grid',
    gridTemplateColumns: groupIndex === 3 ? 'repeat(2, minmax(0, 1fr))' : 'repeat(1, minmax(0, 1fr))',
    gridAutoRows: 'minmax(130px, 1fr)',
    gap: '12px',
    alignItems: 'stretch',
    justifyItems: 'stretch',
});

const switchSection = (sectionId) => {
    router.get('/paulo/seating', { section_id: sectionId, layout: viewMode.value === 'summary' ? 'lecture' : viewMode.value }, { preserveState: true });
};

const openSeatModal = (position) => {
    if (selectMode.value) return;
    activePosition.value = position;
    showReassign.value = false;
    studentSearch.value = '';
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    activePosition.value = null;
};

const assignSeat = (studentId) => {
    router.post('/paulo/seating/assign', {
        section_id: props.activeSectionId,
        layout: viewMode.value,
        position: activePosition.value,
        student_id: studentId,
    }, {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const unassignSeat = () => {
    router.post('/paulo/seating/unassign', {
        section_id: props.activeSectionId,
        layout: viewMode.value,
        position: activePosition.value,
    }, {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const adjustAura = async (delta) => {
    if (!activeSeat.value?.student) return;
    const { data } = await axios.patch(`/paulo/students/${activeSeat.value.student.id}/aura`, { delta });
    activeSeat.value.student.aura_points = data.aura_points;
};

const triggerPhotoUpload = () => {
    if (photoUploading.value) return;
    photoInputRef.value?.click();
};

const onPhotoSelected = async (event) => {
    const file = event.target.files?.[0];
    if (!file || !activeSeat.value?.student) return;

    const sectionId = activeSeat.value.student.section_id ?? props.activeSectionId;

    const formData = new FormData();
    formData.append('photo', file);

    photoUploading.value = true;
    try {
        const { data } = await axios.post(
            `/paulo/sections/${sectionId}/students/${activeSeat.value.student.id}/photo`,
            formData
        );
        activeSeat.value.student.photo_url = data.photo_url;
    } catch (error) {
        console.error('Photo upload failed:', error);
    } finally {
        photoUploading.value = false;
        event.target.value = '';
    }
};

const initials = (name) => {
    if (!name) return '?';
    return name.split(' ').filter(Boolean).slice(0, 2).map((n) => n[0].toUpperCase()).join('');
};

const surnameFirst = (name) => name;
</script>