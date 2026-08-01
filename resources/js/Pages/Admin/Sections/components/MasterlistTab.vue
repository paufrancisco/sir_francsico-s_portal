<template>
    <div class="space-y-3">
        <div class="flex items-center gap-2">
            <div class="relative flex-1">
                <svg
                    width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400"
                >
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
                <input
                    v-model="studentSearch"
                    type="text"
                    placeholder="Search by student number or name..."
                    class="w-full text-sm border border-slate-200 rounded-lg pl-8 pr-3 py-1.5 focus:outline-none focus:border-[#003399]"
                />
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <button
                v-if="selectedStudents.length > 0"
                @click="deleteSelectedStudents"
                class="bg-red-50 text-red-600 text-xs font-medium px-4 py-2 rounded-lg hover:bg-red-100 transition"
            >
                Delete selected ({{ selectedStudents.length }})
            </button>
            <Link :href="`/paulo/sections/${section.id}/students/import`" class="bg-[#003399] text-white text-xs font-medium px-4 py-2 rounded-lg">
                Import students
            </Link>
            <label class="border border-slate-200 text-xs font-medium px-4 py-2 rounded-lg text-slate-600 cursor-pointer hover:bg-slate-50 transition">
                {{ photosImporting ? 'Importing...' : 'Import photos (ZIP)' }}
                <input type="file" accept=".zip" class="hidden" @change="uploadPhotosZip" :disabled="photosImporting" />
            </label>
            <button v-if="!revealed" @click="showModal = true" class="border border-slate-200 text-xs font-medium px-4 py-2 rounded-lg text-slate-600">
                Show passwords
            </button>
            <Link v-else :href="`/paulo/sections/${section.id}`" class="text-xs text-slate-400 hover:underline">Hide passwords</Link>
        </div>
        <p class="text-[11px] text-slate-400 -mt-1">
            Photo ZIP: name each photo using the student number (e.g. <code>2021-0001.jpg</code>).
        </p>

        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <p v-if="students.length === 0" class="text-xs text-slate-400 px-4 py-6">
                No students here yet. Click "Import students" to add some.
            </p>
            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-left text-xs text-slate-500">
                        <th class="px-4 py-2 w-8">
                            <input
                                type="checkbox"
                                :checked="allStudentsSelected"
                                @change="toggleSelectAllStudents"
                                class="rounded border-slate-300"
                            />
                        </th>
                        <th class="px-4 py-2 w-14">Photo</th>
                        <th class="px-4 py-2">Student number</th>
                        <th class="px-4 py-2">Name</th>
                        <th class="px-4 py-2">Password</th>
                        <th class="px-4 py-2 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="s in filteredStudents" :key="s.id" class="border-t border-slate-100">
                        <td class="px-4 py-2">
                            <input
                                type="checkbox"
                                :value="s.id"
                                v-model="selectedStudents"
                                class="rounded border-slate-300"
                            />
                        </td>
                        <td class="px-4 py-2">
                            <button
                                @click="openPhotoUpload(s)"
                                title="Change picture"
                                class="block w-9 h-9 rounded-full overflow-hidden border border-slate-200 bg-slate-100 hover:opacity-80 transition shrink-0"
                            >
                                <img
                                    v-if="s.photo_url"
                                    :src="s.photo_url"
                                    :alt="s.full_name"
                                    class="w-full h-full object-cover"
                                />
                                <span v-else class="w-full h-full flex items-center justify-center text-slate-400 text-[10px] font-medium">
                                    {{ initials(s.full_name) }}
                                </span>
                            </button>
                        </td>
                        <td class="px-4 py-2 text-slate-700">{{ s.student_number }}</td>
                        <td class="px-4 py-2 text-slate-700">{{ s.full_name }}</td>
                        <td class="px-4 py-2 font-mono text-[#003399]">{{ revealed ? s.password : '••••••' }}</td>
                        <td class="px-4 py-2">
                            <div class="flex items-center justify-center gap-2">
                                <button
                                    @click="openEditStudent(s)"
                                    title="Edit"
                                    class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition"
                                >
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>
                                <button
                                    @click="deleteStudent(s)"
                                    title="Delete"
                                    class="p-1.5 rounded-lg text-red-500 hover:text-red-700 hover:bg-red-50 transition"
                                >
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 6h18"/>
                                        <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/>
                                        <path d="M10 11v6M14 11v6"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="students.length > 0 && filteredStudents.length === 0">
                        <td colspan="6" class="text-center text-slate-400 text-sm py-8">
                            No students found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Reveal password modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl p-5 w-80 shadow-xl">
                <div class="text-sm font-semibold text-slate-700 mb-3">Confirm admin password</div>
                <form @submit.prevent="submitReveal" class="space-y-3">
                    <input v-model="revealForm.password" type="password" placeholder="Your login password" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" />
                    <p v-if="revealForm.errors.password" class="text-xs text-red-500">{{ revealForm.errors.password }}</p>
                    <div class="flex gap-2">
                        <button type="submit" class="bg-[#003399] text-white text-sm font-medium px-4 py-2 rounded-lg flex-1">Confirm</button>
                        <button type="button" @click="showModal = false" class="text-sm text-slate-500 px-3 py-2">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Student modal -->
        <div v-if="editStudentModalOpen" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50 px-4" @click.self="closeEditStudent">
            <div class="bg-white rounded-xl p-5 w-full max-w-xs shadow-xl">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-sm font-semibold text-slate-700">Edit student</div>
                    <button @click="closeEditStudent" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <form @submit.prevent="submitEditStudent" class="space-y-3">
                    <div>
                        <label class="text-xs text-slate-500">Student number</label>
                        <input v-model="editStudentForm.student_number" type="text" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 mt-1" />
                        <p v-if="editStudentForm.errors.student_number" class="text-xs text-red-500 mt-1">{{ editStudentForm.errors.student_number }}</p>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">Full name</label>
                        <input v-model="editStudentForm.full_name" type="text" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 mt-1" />
                        <p v-if="editStudentForm.errors.full_name" class="text-xs text-red-500 mt-1">{{ editStudentForm.errors.full_name }}</p>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">New password (optional)</label>
                        <input v-model="editStudentForm.password" type="text" placeholder="Leave blank to keep it unchanged" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 mt-1" />
                        <p v-if="editStudentForm.errors.password" class="text-xs text-red-500 mt-1">{{ editStudentForm.errors.password }}</p>
                    </div>
                    <button type="submit" :disabled="editStudentForm.processing" class="w-full bg-[#003399] text-white text-sm font-medium py-2 rounded-lg disabled:opacity-50">
                        {{ editStudentForm.processing ? 'Saving...' : 'Save changes' }}
                    </button>
                </form>
            </div>
        </div>

        <!-- Photo Upload modal -->
        <div v-if="photoModalOpen" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50 px-4" @click.self="closePhotoUpload">
            <div class="bg-white rounded-xl p-5 w-full max-w-xs shadow-xl">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-sm font-semibold text-slate-700">{{ photoStudent?.full_name }}</div>
                    <button @click="closePhotoUpload" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <div class="flex flex-col items-center gap-3">
                    <div class="w-28 h-28 rounded-full overflow-hidden border border-slate-200 bg-slate-100">
                        <img
                            v-if="photoPreview || photoStudent?.photo_url"
                            :src="photoPreview || photoStudent?.photo_url"
                            class="w-full h-full object-cover"
                        />
                        <span v-else class="w-full h-full flex items-center justify-center text-slate-400 text-lg font-medium">
                            {{ initials(photoStudent?.full_name) }}
                        </span>
                    </div>

                    <label class="border border-slate-200 text-xs font-medium px-4 py-2 rounded-lg text-slate-600 cursor-pointer hover:bg-slate-50 transition">
                        Choose a picture
                        <input type="file" accept="image/*" class="hidden" @change="onPhotoSelected" />
                    </label>

                    <p v-if="photoErrorMsg" class="text-xs text-red-500">{{ photoErrorMsg }}</p>

                    <div class="flex gap-2 w-full mt-1">
                        <button
                            v-if="photoStudent?.photo_url"
                            @click="removePhoto"
                            :disabled="photoUploading"
                            class="flex-1 border border-red-200 text-red-600 text-xs font-medium px-4 py-2 rounded-lg hover:bg-red-50 transition disabled:opacity-50"
                        >
                            Remove
                        </button>
                        <button
                            @click="submitPhotoUpload"
                            :disabled="!photoFile || photoUploading"
                            class="flex-1 bg-[#003399] text-white text-xs font-medium px-4 py-2 rounded-lg disabled:opacity-50"
                        >
                            {{ photoUploading ? 'Uploading...' : 'Save' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useRevealPassword } from '@/composables/useRevealPassword';
import { useStudentPhoto } from '@/composables/useStudentPhoto';
import { useMasterlist } from '@/composables/useMasterlist';

const props = defineProps({
    section: Object,
    students: Array,
    revealed: Boolean,
    currentPeriod: String,
});

const sectionId = props.section.id;
const studentsRef = computed(() => props.students);
const currentPeriodRef = computed(() => props.currentPeriod);

const { showModal, form: revealForm, submitReveal } = useRevealPassword(sectionId, currentPeriodRef);

const {
    photosImporting,
    uploadPhotosZip,
    photoModalOpen,
    photoStudent,
    photoFile,
    photoPreview,
    photoUploading,
    photoErrorMsg,
    openPhotoUpload,
    closePhotoUpload,
    onPhotoSelected,
    submitPhotoUpload,
    removePhoto,
} = useStudentPhoto(sectionId);

const {
    studentSearch,
    filteredStudents,
    selectedStudents,
    allStudentsSelected,
    toggleSelectAllStudents,
    deleteSelectedStudents,
    deleteStudent,
    editStudentModalOpen,
    editStudentForm,
    openEditStudent,
    closeEditStudent,
    submitEditStudent,
    initials,
} = useMasterlist(sectionId, studentsRef);
</script>