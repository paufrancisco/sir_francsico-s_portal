<template>
    <AdminLayout>
        <main class="w-full px-6 lg:px-10 py-8 space-y-4">

            <div class="flex items-center justify-between">
                <div>
                    <div class="text-lg font-semibold text-slate-800">Seating Arrangement</div>
                    <div class="text-xs text-slate-400">I-click ang box para mag-assign o mag-adjust ng aura points.</div>
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

            <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1 w-fit">
                <button
                    @click="switchLayout('lecture')"
                    class="text-xs font-medium px-4 py-1.5 rounded-md transition"
                    :class="layout === 'lecture' ? 'bg-white text-[#003399] shadow-sm' : 'text-slate-500'"
                >
                    Lecture Room
                </button>
                <button
                    @click="switchLayout('comlab')"
                    class="text-xs font-medium px-4 py-1.5 rounded-md transition"
                    :class="layout === 'comlab' ? 'bg-white text-[#003399] shadow-sm' : 'text-slate-500'"
                >
                    Comlab
                </button>
            </div>

            <!--
                FIX: tinanggal ang overflow-x-auto at min-width:max-content na siyang dahilan
                ng horizontal scroll. Ang bawat group ngayon ay gumagamit ng CSS grid na "fr"
                units (hindi fixed px), kaya awtomatikong bumabagay ang laki ng seat boxes
                sa lapad ng screen — lumalaki sila pero hindi lalagpas sa container.
            -->
            <!-- Lecture layout: 2 groups of 5 columns x 5 rows, may space sa pagitan (aisle) -->
            <div v-if="layout === 'lecture'" class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
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
                            @click="openSeatModal(pos)"
                        />
                    </div>
                </div>
            </div>

            <!-- Comlab layout: col 1 alone, col 2 alone, col 3-4 magkasama, may space sa pagitan ng bawat grupo -->
            <div v-else class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
                <div class="flex w-full gap-8">
                    <div class="flex-1" :style="comlabGroupStyle(1)">
                        <SeatBox
                            v-for="pos in groupPositions(1, 1, 10, 0)"
                            :key="pos"
                            :seat="seats[pos]"
                            @click="openSeatModal(pos)"
                        />
                    </div>
                    <div class="flex-1" :style="comlabGroupStyle(2)">
                        <SeatBox
                            v-for="pos in groupPositions(2, 1, 10, 10)"
                            :key="pos"
                            :seat="seats[pos]"
                            @click="openSeatModal(pos)"
                        />
                    </div>
                    <div style="flex: 2 1 0%;" :style="comlabGroupStyle(3)">
                        <SeatBox
                            v-for="pos in groupPositions(3, 2, 10, 20)"
                            :key="pos"
                            :seat="seats[pos]"
                            @click="openSeatModal(pos)"
                        />
                    </div>
                </div>
            </div>

            <div class="text-xs text-slate-400">
                {{ unassignedStudents.length }} estudyante na hindi pa naka-assign sa layout na ito.
            </div>
        </main>

        <!-- Seat modal -->
        <div v-if="modalOpen" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50 px-4" @click.self="closeModal">
            <div class="bg-white rounded-xl p-5 w-full max-w-sm shadow-xl">

                <div class="flex items-center justify-between mb-3">
                    <div class="text-sm font-semibold text-slate-700">Seat #{{ activePosition + 1 }}</div>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <!-- Kung may laman na ang seat -->
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
                                    {{ photoUploading ? '...' : 'Palitan' }}
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

                    <div class="flex items-center justify-between bg-slate-50 rounded-lg px-4 py-3 mb-4">
                        <span class="text-xs text-slate-500">Aura Points</span>
                        <div class="flex items-center gap-3">
                            <button @click="adjustAura(-1)" class="w-7 h-7 rounded-full border border-slate-200 text-slate-500 hover:bg-white transition">−</button>
                            <span class="text-sm font-bold text-[#003399] w-8 text-center">{{ activeSeat.student.aura_points }}</span>
                            <button @click="adjustAura(1)" class="w-7 h-7 rounded-full border border-slate-200 text-slate-500 hover:bg-white transition">+</button>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button @click="showReassign = true" class="flex-1 border border-slate-200 text-slate-600 text-xs font-medium py-2 rounded-lg">
                            Palitan ng estudyante
                        </button>
                        <button @click="unassignSeat" class="flex-1 bg-red-50 text-red-600 text-xs font-medium py-2 rounded-lg">
                            Alisin sa seat
                        </button>
                    </div>
                </template>

                <!-- Blangko pa ang seat, o gustong palitan -->
                <template v-if="!activeSeat?.student || showReassign">
                    <div class="mt-3 pt-3 border-t border-slate-100" v-if="activeSeat?.student">
                        <div class="text-xs text-slate-500 mb-2">Piliin ang bagong estudyante:</div>
                    </div>

                    <input
                        v-model="studentSearch"
                        type="text"
                        placeholder="Search student number o pangalan..."
                        class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 mb-2 sticky top-0 bg-white z-10"
                    />

                    <!--
                        FIX: pinaliit ang max-height (max-h-48 -> max-h-56 pero fixed na height talaga,
                        hindi basta grumagrow), at overflow-y-scroll (hindi auto) para laging
                        makikita ang scrollbar. Dinagdag din ang overscroll-contain para hindi
                        mag-scroll ang buong modal/page pag naabot na ang dulo ng listahan.
                    -->
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
                            Wala nang available na estudyante.
                        </p>
                    </div>
                </template>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
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

// Buo ng listahan ng position indices para sa isang grid group.
// groupIndex: pang-ilang group (1 o 2), cols/rows: laki ng group,
// offsetOverride: dagdag base offset kung maraming groups na magkaibang laki (para sa comlab)
const groupPositions = (groupIndex, cols, rows, offsetOverride = null) => {
    const perGroup = cols * rows;
    const offset = offsetOverride !== null ? offsetOverride : (groupIndex - 1) * perGroup;
    return Array.from({ length: perGroup }, (_, i) => offset + i);
};

// FIX: gumagamit na ngayon ng "fr" units (hindi fixed px) para awtomatikong
// bumagay ang laki ng bawat seat box sa available width — walang horizontal
// scroll at mas malaki ang boxes sa malalapad na screen. Ang "aisle" ay hindi
// na marginLeft; nasa gap-8 na ng parent flex container (tingnan sa template).
// Grid style para sa Lecture layout — 5 columns x 5 rows bawat group.
const lectureGroupStyle = () => ({
    display: 'grid',
    gridTemplateColumns: 'repeat(5, minmax(0, 1fr))',
    gridAutoRows: 'minmax(130px, 1fr)',
    gap: '12px',
    alignItems: 'stretch',
    justifyItems: 'stretch',
});

// Grid style para sa Comlab layout — column 1 mag-isa (1 col x 10 rows),
// column 2 mag-isa rin, column 3 dalawang-column na grupo (2 cols x 10 rows).
// Ang bawat group div ay may flex-1 (o flex:2 para sa group 3) sa template
// para proportional ang lapad base sa bilang ng columns nito.
const comlabGroupStyle = (groupIndex) => ({
    display: 'grid',
    gridTemplateColumns: groupIndex === 3 ? 'repeat(2, minmax(0, 1fr))' : 'repeat(1, minmax(0, 1fr))',
    gridAutoRows: 'minmax(130px, 1fr)',
    gap: '12px',
    alignItems: 'stretch',
    justifyItems: 'stretch',
});

const switchSection = (sectionId) => {
    router.get('/paulo/seating', { section_id: sectionId, layout: props.layout }, { preserveState: true });
};

const switchLayout = (newLayout) => {
    router.get('/paulo/seating', { section_id: props.activeSectionId, layout: newLayout }, { preserveState: true });
};

const openSeatModal = (position) => {
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
        layout: props.layout,
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
        layout: props.layout,
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

// URL pattern base sa routes/web.php: POST /paulo/sections/{section}/students/{student}/photo
// (naka-prefix('paulo') ang buong admin route group, hindi '/admin')
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

const surnameFirst = (name) => name; // full_name mo ay naka-store na bilang "Apelido, Pangalan"
</script>