<template>
    <AdminLayout>
        <main class="w-full px-6 lg:px-10 py-8 space-y-4">

            <div v-if="$page.props.flash?.success" class="bg-[#EAF3DE] text-[#3B6D11] text-sm rounded-lg px-4 py-2">
                {{ $page.props.flash.success }}
            </div>

            <div>
                <div class="text-lg font-semibold text-slate-800">{{ section.name }}</div>
                <div class="text-xs text-slate-400">{{ students.length }} students</div>
            </div>

            <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1 w-fit">
                <button
                    @click="activeTab = 'masterlist'"
                    class="text-xs font-medium px-4 py-1.5 rounded-md transition"
                    :class="activeTab === 'masterlist' ? 'bg-white text-[#003399] shadow-sm' : 'text-slate-500'"
                >
                    Masterlist
                </button>
                <button
                    @click="activeTab = 'grades'"
                    class="text-xs font-medium px-4 py-1.5 rounded-md transition"
                    :class="activeTab === 'grades' ? 'bg-white text-[#003399] shadow-sm' : 'text-slate-500'"
                >
                    Grades
                </button>
            </div>

            <!-- MASTERLIST TAB -->
            <div v-if="activeTab === 'masterlist'" class="space-y-3">
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
                        {{ photosImporting ? 'Ina-import...' : 'Import photos (ZIP)' }}
                        <input type="file" accept=".zip" class="hidden" @change="uploadPhotosZip" :disabled="photosImporting" />
                    </label>
                    <button v-if="!revealed" @click="showModal = true" class="border border-slate-200 text-xs font-medium px-4 py-2 rounded-lg text-slate-600">
                        Show passwords
                    </button>
                    <Link v-else :href="`/paulo/sections/${section.id}`" class="text-xs text-slate-400 hover:underline">Hide passwords</Link>
                </div>
                <p class="text-[11px] text-slate-400 -mt-1">
                    Photo ZIP: pangalanan ang bawat larawan gamit ang student number (e.g. <code>2021-0001.jpg</code>).
                </p>

                <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                    <p v-if="students.length === 0" class="text-xs text-slate-400 px-4 py-6">
                        Wala pang estudyante dito. I-click yung "Import students" para magdagdag.
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
                            <tr v-for="s in students" :key="s.id" class="border-t border-slate-100">
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
                                        title="Palitan ang picture"
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
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- GRADES TAB -->
            <div v-else class="space-y-3">
                <div class="flex justify-end gap-2">
                    <button
                        v-if="selectedGradeRows.length > 0"
                        @click="deleteSelectedGradeRows"
                        class="bg-red-50 text-red-600 text-xs font-medium px-4 py-2 rounded-lg hover:bg-red-100 transition"
                    >
                        Clear grades ({{ selectedGradeRows.length }})
                    </button>
                    <label class="bg-[#003399] text-white text-xs font-medium px-4 py-2 rounded-lg cursor-pointer" :class="{ 'opacity-50 cursor-not-allowed': periodLoading }">
                        {{ uploading ? 'Uploading...' : periodLoading ? 'Naglo-load...' : 'Import grades (Excel)' }}
                        <input type="file" accept=".xlsx,.xls,.csv" class="hidden" @change="uploadGrades" :disabled="uploading || periodLoading" />
                    </label>
                </div>
                <p class="text-[11px] text-slate-400">
                    Header format: <code>Long Quiz: Quiz 1 (50)</code>, <code>TP: Project 1 (100)</code>, <code>Exam: Midterm (100)</code> — Column A = Student Number.
                </p>

                <!-- Period tabs -->
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1 w-fit">
                        <button
                            v-for="p in periods"
                            :key="p.value"
                            @click="switchPeriod(p.value)"
                            :disabled="periodLoading"
                            class="text-xs font-medium px-4 py-1.5 rounded-md transition disabled:cursor-not-allowed"
                            :class="currentPeriod === p.value ? 'bg-white text-[#003399] shadow-sm' : 'text-slate-500'"
                        >
                            {{ p.label }}
                        </button>
                    </div>
                    <svg
                        v-if="periodLoading"
                        width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" class="animate-spin text-[#003399]"
                    >
                        <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/>
                    </svg>
                </div>

                <p class="text-[11px] text-slate-500">
                    Ia-import bilang <span class="font-semibold text-[#003399]">{{ periods.find(p => p.value === currentPeriod)?.label }}</span> — piliin muna ang tamang tab sa itaas bago mag-click ng "Import grades".
                </p>

                <p class="text-[11px] text-slate-400">
                    Total % = (Quiz % × 20%) + (TP % × 30%) + (Exam % × 50%)
                </p>

                <!-- Search + missing-grade filter -->
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
                            v-model="gradeSearch"
                            type="text"
                            placeholder="Search student number o pangalan..."
                            class="w-full text-sm border border-slate-200 rounded-lg pl-8 pr-3 py-1.5 focus:outline-none focus:border-[#003399]"
                        />
                    </div>
                    <select
                        v-model="missingOnly"
                        class="text-xs border border-slate-200 rounded-lg px-2 py-1.5 text-slate-600 bg-white"
                    >
                        <option :value="false">Lahat ng estudyante</option>
                        <option :value="true">May kulang na grade</option>
                    </select>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-x-auto max-w-full">
                    <p v-if="gradesBreakdown.length === 0" class="text-xs text-slate-400 px-4 py-6">
                        Wala pang na-import na grades.
                    </p>
                    <p v-else-if="filteredGradesBreakdown.length === 0" class="text-xs text-slate-400 px-4 py-6">
                        Walang tumugma na estudyante.
                    </p>
                    <table v-else class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-left text-xs text-slate-500">
                                <th class="px-3 py-2 w-8">
                                    <input
                                        type="checkbox"
                                        :checked="allGradeRowsSelected"
                                        @change="toggleSelectAllGradeRows"
                                        class="rounded border-slate-300"
                                    />
                                </th>
                                <th class="px-3 py-2">Rank</th>
                                <th class="px-3 py-2">ID No.</th>
                                <th class="px-3 py-2">Name</th>
                                <th v-for="item in gradeItems" :key="item.category + item.title" class="px-2 py-2 text-center w-16">
                                    {{ item.title }}
                                </th>
                                <th class="px-2 py-2 text-center w-16">Quiz %</th>
                                <th class="px-2 py-2 text-center w-16">TP %</th>
                                <th class="px-2 py-2 text-center w-16">Exam %</th>
                                <th class="px-3 py-2">Total %</th>
                                <th class="px-3 py-2 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in filteredGradesBreakdown" :key="row.id" class="border-t border-slate-100">
                                <td class="px-3 py-2">
                                    <input
                                        type="checkbox"
                                        :value="row.id"
                                        v-model="selectedGradeRows"
                                        class="rounded border-slate-300"
                                    />
                                </td>
                                <td class="px-3 py-2 text-slate-500">{{ row.rank }}</td>
                                <td class="px-3 py-2 text-slate-500 whitespace-nowrap">{{ row.student_number }}</td>
                                <td class="px-3 py-2 text-slate-700 font-medium whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5">
                                        <span
                                            :class="row.total_percentage < 60 ? 'text-red-600 bg-red-50 px-2 py-0.5 rounded-full' : ''"
                                        >
                                            {{ row.name }}
                                        </span>
                                        <span
                                            v-if="rowHasMissingGrade(row)"
                                            title="May kulang na grade"
                                            class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"
                                        ></span>
                                    </span>
                                </td>
                                <td v-for="item in gradeItems" :key="item.category + item.title" class="px-2 py-2 text-slate-600 text-center">
                                    <template v-if="row.scores[item.category + '|' + item.title]">
                                        <div
                                            class="leading-tight inline-block rounded px-1"
                                            :class="isLowScore(row.scores[item.category + '|' + item.title]) ? 'bg-red-50' : ''"
                                        >
                                            <div class="font-medium" :class="isLowScore(row.scores[item.category + '|' + item.title]) ? 'text-red-600' : ''">
                                                {{ row.scores[item.category + '|' + item.title].score }}
                                            </div>
                                            <div class="text-[10px]" :class="isLowScore(row.scores[item.category + '|' + item.title]) ? 'text-red-400' : 'text-slate-400'">
                                                /{{ row.scores[item.category + '|' + item.title].max_score }}
                                            </div>
                                        </div>
                                    </template>
                                    <span v-else class="text-slate-300">—</span>
                                </td>
                                <td class="px-2 py-2 text-center text-slate-500">
                                    <span v-if="row.category_percentages?.long_quiz !== null && row.category_percentages?.long_quiz !== undefined">{{ row.category_percentages.long_quiz }}%</span>
                                    <span v-else class="text-slate-300">—</span>
                                </td>
                                <td class="px-2 py-2 text-center text-slate-500">
                                    <span v-if="row.category_percentages?.tp !== null && row.category_percentages?.tp !== undefined">{{ row.category_percentages.tp }}%</span>
                                    <span v-else class="text-slate-300">—</span>
                                </td>
                                <td class="px-2 py-2 text-center text-slate-500">
                                    <span v-if="row.category_percentages?.exam !== null && row.category_percentages?.exam !== undefined">{{ row.category_percentages.exam }}%</span>
                                    <span v-else class="text-slate-300">—</span>
                                </td>
                                <td class="px-3 py-2 font-semibold whitespace-nowrap" :class="row.total_percentage < 60 ? 'text-red-600' : 'text-[#003399]'">{{ row.total_percentage }}%</td>
                                <td class="px-3 py-2">
                                    <div class="flex items-center justify-center gap-2">
                                        <button
                                            @click="openEditGrades(row)"
                                            title="Edit"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition"
                                        >
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                        </button>
                                        <button
                                            @click="deleteGradeRow(row)"
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
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <!-- Reveal password modal -->
        <div v-if="showModal" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50">
            <div class="bg-white rounded-xl p-5 w-80 shadow-xl">
                <div class="text-sm font-semibold text-slate-700 mb-3">Confirm admin password</div>
                <form @submit.prevent="submitReveal" class="space-y-3">
                    <input v-model="form.password" type="password" placeholder="Your login password" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2" />
                    <p v-if="form.errors.password" class="text-xs text-red-500">{{ form.errors.password }}</p>
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
                        <input v-model="editStudentForm.password" type="text" placeholder="Iwan blangko para hindi mabago" class="w-full text-sm border border-slate-200 rounded-lg px-3 py-2 mt-1" />
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
                        Pumili ng picture
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
                            Alisin
                        </button>
                        <button
                            @click="submitPhotoUpload"
                            :disabled="!photoFile || photoUploading"
                            class="flex-1 bg-[#003399] text-white text-xs font-medium px-4 py-2 rounded-lg disabled:opacity-50"
                        >
                            {{ photoUploading ? 'Ina-upload...' : 'I-save' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Grades modal -->
        <div v-if="editGradesModalOpen" class="fixed inset-0 bg-black/30 flex items-center justify-center z-50 px-4" @click.self="closeEditGrades">
            <div class="bg-white rounded-xl p-5 w-full max-w-sm shadow-xl">

                <div class="flex items-center justify-between mb-3">
                    <div class="text-sm font-semibold text-slate-700">{{ editStudentName }}</div>
                    <button @click="closeEditGrades" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>

                <p v-if="editGradesLoading" class="text-xs text-slate-400 py-4">Naglo-load...</p>

                <p v-else-if="editGrades.length === 0" class="text-xs text-slate-400 py-4">
                    Wala pang na-record na grades.
                </p>

                <div v-else class="divide-y divide-slate-100 border-t border-slate-100">
                    <div
                        v-for="g in editGrades"
                        :key="g.category + '|' + g.title"
                        class="flex items-center gap-2 py-2 text-sm"
                    >
                        <span class="flex-1 min-w-0 truncate text-slate-600">{{ g.title ?? g.category }}</span>
                        <input
                            type="number"
                            v-model="g.editValue"
                            :max="g.max_score"
                            min="0"
                            step="0.01"
                            style="width: 92px;"
                            class="grade-score-input shrink-0 px-1.5 text-right font-medium text-slate-700 bg-transparent border border-transparent hover:border-slate-200 focus:border-[#003399] focus:outline-none rounded transition"
                        />
                        <span class="w-14 shrink-0 text-right text-slate-400">/{{ g.max_score }}</span>
                        <button
                            @click="saveEditGrade(g)"
                            :disabled="editGradesSavingId === (g.category + '|' + g.title) || Number(g.editValue) === Number(g.score)"
                            title="Save"
                            class="shrink-0 text-slate-500 hover:text-[#003399] disabled:opacity-30 disabled:cursor-not-allowed transition"
                        >
                            <svg
                                v-if="editGradesSavingId !== (g.category + '|' + g.title)"
                                width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"
                            >
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/>
                                <path d="M17 21v-8H7v8"/>
                                <path d="M7 3v5h8"/>
                            </svg>
                            <svg
                                v-else
                                width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" class="animate-spin"
                            >
                                <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <p v-if="editGradesErrorMsg" class="text-xs text-red-500 mt-3">{{ editGradesErrorMsg }}</p>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';

const props = defineProps({
    section: Object,
    students: Array,
    revealed: Boolean,
    gradeItems: { type: Array, default: () => [] },
    gradesBreakdown: { type: Array, default: () => [] },
    currentPeriod: { type: String, default: 'prelim' },
});

// ---- Period tabs (Prelim / Midterm / Pre-Final / Finals) ----
const periods = [
    { value: 'prelim', label: 'Prelim' },
    { value: 'midterm', label: 'Midterm' },
    { value: 'prefinal', label: 'Pre-Final' },
    { value: 'finals', label: 'Finals' },
];

const periodLoading = ref(false);

const switchPeriod = (period) => {
    if (period === props.currentPeriod || periodLoading.value) return;

    periodLoading.value = true;
    router.get(`/paulo/sections/${props.section.id}`, { period }, {
        preserveScroll: true,
        preserveState: true,
        only: ['gradeItems', 'gradesBreakdown', 'currentPeriod'],
        onFinish: () => { periodLoading.value = false; },
    });
};

const activeTab = ref('masterlist');
const showModal = ref(false);
const uploading = ref(false);
const form = useForm({ password: '' });

const submitReveal = () => {
    form.transform((data) => ({
        ...data,
        period: props.currentPeriod,
    })).post(`/paulo/sections/${props.section.id}/students/reveal`, {
        onSuccess: () => { showModal.value = false; form.reset(); },
    });
};

const uploadGrades = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    uploading.value = true;
    router.post(`/paulo/sections/${props.section.id}/grades/import`, { file, period: props.currentPeriod }, {
        forceFormData: true,
        onFinish: () => { uploading.value = false; e.target.value = ''; },
    });
};

// ---- Masterlist: checkboxes + bulk delete ----
const selectedStudents = ref([]);

const allStudentsSelected = computed(() =>
    props.students.length > 0 && selectedStudents.value.length === props.students.length
);

const toggleSelectAllStudents = () => {
    selectedStudents.value = allStudentsSelected.value ? [] : props.students.map((s) => s.id);
};

const deleteSelectedStudents = () => {
    if (!confirm(`Sigurado ka bang tatanggalin ang ${selectedStudents.value.length} na napiling estudyante?`)) {
        return;
    }

    router.delete(`/paulo/sections/${props.section.id}/students`, {
        data: { student_ids: selectedStudents.value },
        preserveScroll: true,
        onSuccess: () => { selectedStudents.value = []; },
    });
};

const deleteStudent = (student) => {
    if (!confirm(`Sigurado ka bang tatanggalin si ${student.full_name}?`)) {
        return;
    }

    router.delete(`/paulo/sections/${props.section.id}/students/${student.id}`, {
        preserveScroll: true,
    });
};

// ---- Edit Student modal ----
const editStudentModalOpen = ref(false);
const editingStudentId = ref(null);
const editStudentForm = useForm({
    student_number: '',
    full_name: '',
    password: '',
});

const openEditStudent = (student) => {
    editingStudentId.value = student.id;
    editStudentForm.student_number = student.student_number;
    editStudentForm.full_name = student.full_name;
    editStudentForm.password = '';
    editStudentForm.clearErrors();
    editStudentModalOpen.value = true;
};

const closeEditStudent = () => {
    editStudentModalOpen.value = false;
};

const submitEditStudent = () => {
    editStudentForm.transform((data) => ({
        ...data,
        _method: 'patch',
    })).post(`/paulo/sections/${props.section.id}/students/${editingStudentId.value}`, {
        preserveScroll: true,
        onSuccess: () => { editStudentModalOpen.value = false; },
    });
};

// ---- Masterlist: bulk photo import (ZIP) ----
const photosImporting = ref(false);

const uploadPhotosZip = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    photosImporting.value = true;
    router.post(`/paulo/sections/${props.section.id}/students/photos/import`, { file }, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => { photosImporting.value = false; e.target.value = ''; },
    });
};

// ---- Masterlist: photo upload ----
const photoModalOpen = ref(false);
const photoStudent = ref(null);
const photoFile = ref(null);
const photoPreview = ref(null);
const photoUploading = ref(false);
const photoErrorMsg = ref('');

const initials = (name) => {
    if (!name) return '?';
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((n) => n[0].toUpperCase())
        .join('');
};

const openPhotoUpload = (student) => {
    photoStudent.value = student;
    photoFile.value = null;
    photoPreview.value = null;
    photoErrorMsg.value = '';
    photoModalOpen.value = true;
};

const closePhotoUpload = () => {
    photoModalOpen.value = false;
    photoStudent.value = null;
    photoFile.value = null;
    photoPreview.value = null;
};

const onPhotoSelected = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    photoErrorMsg.value = '';
    photoFile.value = file;
    photoPreview.value = URL.createObjectURL(file);
};

const submitPhotoUpload = () => {
    if (!photoFile.value || !photoStudent.value) return;

    photoUploading.value = true;
    photoErrorMsg.value = '';

    router.post(
        `/paulo/sections/${props.section.id}/students/${photoStudent.value.id}/photo`,
        { photo: photoFile.value },
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => { closePhotoUpload(); },
            onError: (errors) => {
                photoErrorMsg.value = errors.photo || 'Hindi na-upload ang picture.';
            },
            onFinish: () => { photoUploading.value = false; },
        }
    );
};

const removePhoto = () => {
    if (!photoStudent.value) return;
    if (!confirm('Sigurado ka bang aalisin ang picture?')) return;

    photoUploading.value = true;
    router.delete(`/paulo/sections/${props.section.id}/students/${photoStudent.value.id}/photo`, {
        preserveScroll: true,
        onSuccess: () => { closePhotoUpload(); },
        onFinish: () => { photoUploading.value = false; },
    });
};

// ---- Grades tab: search + missing-grade filter ----
const gradeSearch = ref('');
const missingOnly = ref(false);

const rowHasMissingGrade = (row) =>
    props.gradeItems.some((item) => {
        const entry = row.scores[item.category + '|' + item.title];
        return !entry || Number(entry.score) === 0;
    });

const isLowScore = (entry) => {
    if (!entry || Number(entry.max_score) === 0) return false;
    return (Number(entry.score) / Number(entry.max_score)) * 100 < 60;
};

const filteredGradesBreakdown = computed(() => {
    const q = gradeSearch.value.trim().toLowerCase();

    return props.gradesBreakdown.filter((row) => {
        const matchesSearch = !q
            || row.name.toLowerCase().includes(q)
            || String(row.student_number ?? '').toLowerCase().includes(q);
        const matchesFilter = !missingOnly.value || rowHasMissingGrade(row);
        return matchesSearch && matchesFilter;
    });
});

// ---- Grades tab: checkboxes + bulk delete ----
const selectedGradeRows = ref([]);

const allGradeRowsSelected = computed(() =>
    filteredGradesBreakdown.value.length > 0
    && filteredGradesBreakdown.value.every((r) => selectedGradeRows.value.includes(r.id))
);

const toggleSelectAllGradeRows = () => {
    if (allGradeRowsSelected.value) {
        const filteredIds = new Set(filteredGradesBreakdown.value.map((r) => r.id));
        selectedGradeRows.value = selectedGradeRows.value.filter((id) => !filteredIds.has(id));
    } else {
        const currentIds = new Set(selectedGradeRows.value);
        filteredGradesBreakdown.value.forEach((r) => currentIds.add(r.id));
        selectedGradeRows.value = Array.from(currentIds);
    }
};

const deleteSelectedGradeRows = () => {
    if (!confirm(`Sigurado ka bang tatanggalin ang grades ng ${selectedGradeRows.value.length} na napiling estudyante?`)) {
        return;
    }

    router.delete(`/paulo/sections/${props.section.id}/grades`, {
        data: { student_ids: selectedGradeRows.value },
        preserveScroll: true,
        onSuccess: () => { selectedGradeRows.value = []; },
    });
};

// ---- Edit Grades modal (per row sa Grades tab) ----
const editGradesModalOpen = ref(false);
const editGradesLoading = ref(false);
const editStudentName = ref('');
const editGrades = ref([]);
const editGradesSavingId = ref(null);
const editGradesErrorMsg = ref('');

const openEditGrades = async (row) => {
    editGradesModalOpen.value = true;
    editGradesLoading.value = true;
    editGradesErrorMsg.value = '';
    editStudentName.value = row.name;
    editGrades.value = [];

    try {
        const res = await axios.get(`/paulo/sections/${props.section.id}/students/${row.id}/grades`, {
            params: { period: props.currentPeriod },
        });
        editGrades.value = res.data.grades.map((g) => ({
            ...g,
            editValue: g.score ?? '',
        }));
    } catch (e) {
        editGradesErrorMsg.value = 'Hindi na-load ang grades ng estudyante.';
    } finally {
        editGradesLoading.value = false;
    }
};

const saveEditGrade = async (grade) => {
    const key = grade.category + '|' + grade.title;
    editGradesSavingId.value = key;
    editGradesErrorMsg.value = '';

    try {
        if (grade.id) {
            const res = await axios.patch(`/paulo/grades/${grade.id}`, { score: grade.editValue });
            grade.score = res.data.grade.score;
        } else {
            const res = await axios.post(`/paulo/grades`, {
                student_id: grade.student_id,
                section_id: grade.section_id,
                category: grade.category,
                period: grade.period ?? props.currentPeriod,
                title: grade.title,
                score: grade.editValue,
                max_score: grade.max_score,
            });
            grade.id = res.data.grade.id;
            grade.score = res.data.grade.score;
        }
        router.reload({ only: ['gradesBreakdown'] });
    } catch (e) {
        editGradesErrorMsg.value = e.response?.data?.message
            || Object.values(e.response?.data?.errors ?? {}).flat().join(' ')
            || 'Hindi na-save ang grade.';
    } finally {
        editGradesSavingId.value = null;
    }
};

const closeEditGrades = () => {
    editGradesModalOpen.value = false;
};

const deleteGradeRow = (row) => {
    if (!confirm(`Sigurado ka bang tatanggalin lahat ng grades ni ${row.name} sa section na ito?`)) {
        return;
    }

    router.delete(`/paulo/sections/${props.section.id}/students/${row.id}/grades`, {
        preserveScroll: true,
    });
};
</script>

<style scoped>
.grade-score-input {
    -moz-appearance: textfield;
}
.grade-score-input::-webkit-outer-spin-button,
.grade-score-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
</style>