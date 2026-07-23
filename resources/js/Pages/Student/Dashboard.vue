<template>
    <div
        class="portal-root min-h-screen relative overflow-hidden transition-colors duration-300"
        :class="isDarkMode ? 'dark' : ''"
    >
        <!-- Ambient background: paper grain + soft ink blobs -->
        <div class="pointer-events-none fixed inset-0 -z-10 bg-[var(--page-bg)]">
            <div class="absolute inset-0 grain-layer"></div>
            <div class="absolute w-[28rem] h-[28rem] rounded-full blur-3xl opacity-[0.16] blob-a" style="background:#8A8D91;"></div>
            <div class="absolute w-[26rem] h-[26rem] rounded-full blur-3xl opacity-[0.18] blob-b" style="background:var(--gold);"></div>
        </div>

        <header class="relative z-10">
            <div class="max-w-[1440px] mx-auto px-6 lg:px-10 pt-6 flex flex-wrap items-center justify-between gap-4">

                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold shrink-0" style="background:var(--gold); color:var(--navy-deep); font-family:var(--font-display);">SF</div>
                    <div class="leading-tight">
                        <div class="text-[15px] font-semibold tracking-tight text-[var(--text-heading)]" style="font-family:var(--font-display);">Sir Francisco</div>
                        <div class="text-[10px] uppercase tracking-[0.14em] text-[var(--text-muted)]">Class portal</div>
                    </div>
                </div>

                <!-- Folder-tab section switcher -->
                <nav class="flex items-end gap-0.5 overflow-x-auto max-w-full">
                    <button
                        v-for="s in sections"
                        :key="s.id"
                        @click="activeSectionId = s.id; currentIndex = 0"
                        class="folder-tab shrink-0 text-[11px] font-semibold px-4 pt-2 pb-2 transition-all whitespace-nowrap"
                        :class="activeSectionId === s.id ? 'folder-tab--active' : 'folder-tab--idle'"
                        style="font-family:var(--font-display);"
                    >
                        {{ s.subject ? `${s.subject} · ${s.name}` : s.name }}
                    </button>
                </nav>

                <div class="flex items-center gap-3">
                    <div
                        class="text-[11px] font-semibold tabular-nums rounded-full px-3 py-1.5 border"
                        style="font-family:var(--font-mono); background:var(--chip-bg); color:var(--text-secondary); border-color:var(--surface-border);"
                    >{{ liveClock }}</div>
                    <button
                        @click="toggleDarkMode"
                        class="w-9 h-9 rounded-full flex items-center justify-center transition border"
                        :class="isDarkMode ? 'text-[var(--gold)]' : 'text-[var(--text-heading)]'"
                        style="background:var(--surface); border-color:var(--surface-border);"
                        aria-label="Toggle dark mode"
                    >
                        <svg v-if="isDarkMode" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                        <svg v-else width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/></svg>
                    </button> 
                </div>
            </div>
        </header>

        <main class="max-w-[1440px] mx-auto px-6 lg:px-10 py-8 grid grid-cols-1 lg:grid-cols-3 gap-5 relative z-10 items-start">

            <div class="lg:col-span-2 space-y-5">

                <!-- Snapshot ledger -->
                <div class="ink-panel rounded-[1.75rem] p-6 lg:p-7 relative overflow-hidden">
                    <div class="absolute -top-16 -right-16 w-64 h-64 rounded-full blur-2xl opacity-20" style="background:var(--gold);"></div>
                    <div class="flex items-center justify-between mb-6 relative">
                        <div>
                            <div class="text-[10px] uppercase tracking-[0.16em] text-white/40 mb-1">Today's snapshot</div>
                            <div class="text-lg font-semibold text-white" style="font-family:var(--font-display);">{{ activeSectionLabel }}</div>
                        </div>
                        <div class="text-[11px] text-white/40 tabular-nums" style="font-family:var(--font-mono);">{{ liveClock }}</div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 relative">
                        <div class="ledger-tile ledger-tile--white">
                            <div class="ledger-tile__icon" style="background:rgba(244,114,182,0.14); color:#DB2777;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18M8 2v4M16 2v4"/></svg>
                            </div>
                            <div class="text-[10px] uppercase tracking-wide" style="color:var(--text-secondary);">Next event</div>
                            <div v-if="nextEvent" class="text-sm font-semibold mt-1 truncate" style="color:var(--text-heading);">{{ nextEvent.title }}</div>
                            <div v-if="nextEvent" class="text-[11px] mt-0.5" style="font-family:var(--font-mono); color:var(--text-secondary);">{{ formatEventDate(nextEvent.event_date) }}</div>
                            <div v-else class="text-sm font-medium mt-1" style="color:var(--text-muted);">Wala pang naka-schedule</div>
                        </div>
                        <div class="ledger-tile ledger-tile--white">
                            <div class="ledger-tile__icon" style="background:rgba(247,177,37,0.18); color:#9A6B00;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                            </div>
                            <div class="text-[10px] uppercase tracking-wide" style="color:var(--text-secondary);">Latest announcement</div>
                            <div v-if="latestAnnouncement" class="text-sm font-semibold mt-1 truncate" style="color:var(--text-heading);">{{ latestAnnouncement.title }}</div>
                            <div v-if="latestAnnouncement" class="text-[11px] mt-0.5" style="font-family:var(--font-mono); color:var(--text-secondary);">{{ formatPostedDate(latestAnnouncement.created_at) }}</div>
                            <div v-else class="text-sm font-medium mt-1" style="color:var(--text-muted);">Wala pang announcement</div>
                        </div>
                        <div class="ledger-tile ledger-tile--white">
                            <div class="ledger-tile__icon" style="background:rgba(49,162,76,0.16); color:#227A38;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l2.9 6.3L21 9l-4.6 4.5L17.8 20 12 16.8 6.2 20l1.4-6.5L3 9l6.1-.7z"/></svg>
                            </div>
                            <div class="text-[10px] uppercase tracking-wide" style="color:var(--text-secondary);">Top student</div>
                            <div v-if="filteredStudents.length" class="text-sm font-semibold mt-1 truncate" style="color:var(--text-heading);">{{ filteredStudents[0].name }}</div>
                            <div v-if="filteredStudents.length" class="text-[11px] mt-0.5" style="font-family:var(--font-mono); color:var(--text-secondary);">Grade: {{ filteredStudents[0].grade }}</div>
                            <div v-else class="text-sm font-medium mt-1" style="color:var(--text-muted);">Wala pang grades</div>
                        </div>
                    </div>

                    <div class="mt-4 text-[10px] text-white/30" style="font-family:var(--font-mono);">Last updated: {{ formattedUpdate }}</div>
                </div>

                <!-- Quick actions -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <button @click="openGradesModal" class="action-tile action-tile--ink">
                        <span class="action-tile__icon" style="background:rgba(255,255,255,0.14);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        </span>
                        <div class="text-sm font-semibold" style="font-family:var(--font-display);">View my grades</div>
                        <div class="text-[11px] text-white/50 mt-0.5">Password required</div>
                    </button>

                    <button class="action-tile action-tile--surface">
                    <span class="action-tile__icon" style="background:var(--chip-bg); color:var(--text-heading);">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                        <div class="text-sm font-semibold text-[var(--text-heading)]" style="font-family:var(--font-display);">Change password</div>
                        <div class="text-[11px] text-[var(--text-muted)] mt-0.5">Verify muna current mo</div>
                    </button>

                    <button class="action-tile action-tile--gold">
                        <span class="action-tile__icon" style="background:rgba(255,255,255,0.35); color:var(--navy-deep);">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18M8 2v4M16 2v4M16 16l4 4m0-4l-4 4"/></svg>
                        </span>
                        <div class="text-sm font-semibold" style="color:var(--navy-deep); font-family:var(--font-display);">Inform sir absent</div>
                        <div class="text-[11px] mt-0.5" style="color:var(--navy-deep); opacity:0.65;">Auto-fill section mo</div>
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-5 gap-4">
                    <div class="sm:col-span-3 surface-card p-5">
                        <div class="card-heading">Recent announcements</div>
                        <p v-if="filteredAnnouncements.length === 0" class="text-xs text-[var(--text-muted)] mt-1">
                            Wala pang announcement as of {{ todayFormatted }}.
                        </p>
                        <div v-else class="mt-2 divide-y divide-[var(--surface-border-soft)]">
                            <div
                                v-for="a in filteredAnnouncements"
                                :key="a.id"
                                class="py-2 flex items-center gap-2 flex-wrap"
                            >
                                <span class="w-1.5 h-1.5 rounded-full shrink-0" style="background:var(--gold);"></span>
                                <span class="text-sm font-medium text-[var(--text-body)] shrink-0">{{ a.title }}</span>
                                <span v-if="a.body" class="text-xs text-[var(--text-secondary)] truncate">{{ a.body }}</span>
                                <span class="flex-1"></span>
                                <span class="text-[10px] text-[var(--text-muted)] whitespace-nowrap shrink-0" style="font-family:var(--font-mono);">{{ formatPostedDate(a.created_at) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="sm:col-span-2 surface-card p-5">
                        <div class="card-heading">This week's topics</div>
                        <p v-if="filteredTopics.length === 0" class="text-xs text-[var(--text-muted)] mt-1">
                            Wala pang naka-post na topic ngayong linggo.
                        </p>
                        <div v-for="t in filteredTopics" :key="t.id" class="flex items-center justify-between py-2.5 border-b border-[var(--surface-border-soft)] last:border-0">
                            <div>
                                <div class="text-sm text-[var(--text-body)]">{{ t.title }}</div>
                                <div class="text-[11px] text-[var(--text-muted)]" style="font-family:var(--font-mono);">{{ formatEventDate(t.date_covered) }}</div>
                            </div>
                            <span
                                class="text-[10px] font-semibold px-2.5 py-1 rounded-full shrink-0 ml-2 uppercase tracking-wide"
                                :class="t.status === 'done' ? 'status-done' : t.status === 'ongoing' ? 'status-ongoing' : 'status-pending'"
                            >
                                {{ t.status }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- FAQ -->
                <div class="surface-card p-5">
                    <div class="card-heading">Frequently Asked Questions</div>
                    <div class="divide-y divide-[var(--surface-border-soft)] mt-2">
                        <div v-for="(faq, i) in faqs" :key="i" class="py-2.5">
                            <button
                                @click="openFaqIndex = openFaqIndex === i ? null : i"
                                class="w-full flex items-center justify-between text-left"
                            >
                                <span class="text-sm text-[var(--text-body)] font-medium pr-3">{{ faq.q }}</span>
                                <svg
                                    width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    class="shrink-0 transition-transform text-[var(--text-muted)]"
                                    :class="openFaqIndex === i ? 'rotate-180' : ''"
                                >
                                    <path d="M6 9l6 6 6-6"/>
                                </svg>
                            </button>
                            <div v-if="openFaqIndex === i" class="text-xs text-[var(--text-secondary)] mt-2 pr-6 leading-relaxed">
                                {{ faq.a }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right column: class ledger -->
            <div class="space-y-5">

                <div
                    class="ink-panel rounded-[1.75rem] p-5"
                    @mouseenter="paused = true"
                    @mouseleave="paused = false"
                >
                    <div class="text-[10px] uppercase tracking-[0.16em] text-white/40 mb-3">Class ledger — {{ activeSectionLabel }}</div>

                    <p v-if="filteredStudents.length === 0" class="text-xs text-white/35 text-center py-8">
                        Wala pang grades na na-record para sa section na ito.
                    </p>

                    <template v-else>
                        <div
                            class="rounded-3xl p-5 text-center cursor-pointer transition overflow-hidden relative"
                            style="background:rgba(255,255,255,0.03);"
                            @click="showFullList = true"
                        >
                            <!-- ribbon banner for rank 1-3 -->
                            <Transition :name="slideDirection" mode="out-in">
                                <div :key="currentIndex">
                                    <div
                                        v-if="currentIndex < 3"
                                        class="rank-ribbon"
                                        :class="{
                                            'rank-ribbon--gold': currentIndex === 0,
                                            'rank-ribbon--silver': currentIndex === 1,
                                            'rank-ribbon--bronze': currentIndex === 2,
                                        }"
                                    >
                                        {{ currentIndex === 0 ? 'TOP 1' : currentIndex === 1 ? 'TOP 2' : 'TOP 3' }}
                                    </div>

                                    <div
                                        class="w-36 h-36 rounded-2xl overflow-hidden flex items-center justify-center text-4xl font-bold mx-auto ring-4"
                                        style="font-family:var(--font-display);"
                                        :class="{
                                            'ring-[var(--gold)] shadow-lg': currentIndex === 0,
                                            'ring-slate-300 shadow-md': currentIndex === 1,
                                            'ring-[#C08A4E] shadow-md': currentIndex === 2,
                                            'ring-white/15': currentIndex > 2,
                                        }"
                                        :style="!currentStudent.photo_url ? { background: 'var(--gold)', color: 'var(--navy-deep)' } : {}"
                                    >
                                        <img
                                            v-if="currentStudent.photo_url"
                                            :src="currentStudent.photo_url"
                                            :alt="currentStudent.name"
                                            class="w-full h-full object-cover"
                                        />
                                        <span v-else>{{ initials(currentStudent.name) }}</span>
                                    </div>

                                    <div class="text-[10px] uppercase tracking-wide mt-4" style="color:var(--gold);">Rank {{ currentIndex + 1 }}</div>
                                    <div class="text-base font-semibold mt-1 text-white" style="font-family:var(--font-display);">{{ currentStudent.name }}</div>
                                    <div class="text-2xl font-semibold mt-1 text-white tabular-nums" style="font-family:var(--font-mono); color:var(--gold);">{{ currentStudent.grade }}</div>
                                </div>
                            </Transition>

                            <div class="flex items-center justify-center gap-1.5 mt-4">
                                <button
                                    v-for="(s, i) in filteredStudents"
                                    :key="s.name"
                                    @click.stop="goTo(i)"
                                    class="rounded-full transition"
                                    :class="i === currentIndex ? 'w-2.5 h-2.5' : 'w-2 h-2 bg-white/20'"
                                    :style="i === currentIndex ? { background: 'var(--gold)' } : {}"
                                ></button>
                            </div>
                        </div>

                        <button @click="showFullList = !showFullList" class="w-full text-xs font-semibold mt-4 hover:underline" style="color:var(--gold);">
                            {{ showFullList ? 'Hide full list' : 'View full list' }}
                        </button>

                        <div v-if="showFullList" class="mt-2 border-t border-white/10">
                            <div v-for="(s, i) in filteredStudents" :key="s.name" class="ledger-row py-2">
                                <span class="text-[11px] text-white/35 w-5 shrink-0" style="font-family:var(--font-mono);">{{ i + 1 }}</span>
                                <div class="w-6 h-6 rounded-full overflow-hidden shrink-0 flex items-center justify-center text-[10px] font-semibold" :style="!s.photo_url ? { background: 'var(--gold)', color: 'var(--navy-deep)' } : {}">
                                    <img v-if="s.photo_url" :src="s.photo_url" :alt="s.name" class="w-full h-full object-cover" />
                                    <span v-else>{{ initials(s.name) }}</span>
                                </div>
                                <div class="text-sm text-white/80 shrink-0">{{ s.name }}</div>
                                <span class="leader"></span>
                                <div class="text-xs font-semibold shrink-0 tabular-nums" style="font-family:var(--font-mono); color:var(--gold);">{{ s.grade }}</div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </main>

        <div class="fixed bottom-6 right-6 z-50">
            <div
                v-if="chatOpen"
                class="mb-3 w-72 surface-card overflow-hidden flex flex-col shadow-xl"
                style="max-height: 420px; border-radius: 1.5rem;"
            >
                <div class="text-white text-sm font-semibold px-4 py-3 flex items-center justify-between shrink-0" style="background:var(--navy); font-family:var(--font-display);">
                    Tanong kay Sir Francisco
                    <button @click="chatOpen = false" class="text-white/70 hover:text-white">✕</button>
                </div>

                <!-- Force change password (first login) -->
                <div v-if="chatMustChangePassword" class="p-4 space-y-2">
                    <p class="text-sm font-semibold text-[var(--text-heading)]" style="font-family:var(--font-display);">Baguhin muna ang password mo</p>
                    <p class="text-xs text-[var(--text-muted)]">First time login mo — kailangan mo munang baguhin ang password bago makapagtanong.</p>
                    <div class="relative">
                        <input v-model="newPasswordForm.new_password" :type="showNewPassword ? 'text' : 'password'" placeholder="Bagong password" class="portal-input pr-9" />
                        <button type="button" @click="showNewPassword = !showNewPassword" tabindex="-1" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--text-muted)] hover:text-[var(--text-body)]">
                            <svg v-if="showNewPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.77 21.77 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.77 21.77 0 0 1-3.22 4.53M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
                            <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <div class="relative">
                        <input v-model="newPasswordForm.confirm_password" :type="showConfirmPassword ? 'text' : 'password'" placeholder="Kumpirmahin ang bagong password" class="portal-input pr-9" />
                        <button type="button" @click="showConfirmPassword = !showConfirmPassword" tabindex="-1" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--text-muted)] hover:text-[var(--text-body)]">
                            <svg v-if="showConfirmPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.77 21.77 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.77 21.77 0 0 1-3.22 4.53M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
                            <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <p v-if="passwordChangeError" class="text-xs text-red-500">{{ passwordChangeError }}</p>
                    <div class="flex gap-2">
                        <button @click="submitPasswordChange" :disabled="passwordChangeLoading" class="flex-1 text-white text-sm font-semibold py-2 rounded-xl disabled:opacity-50" style="background:var(--navy);">
                            {{ passwordChangeLoading ? 'Ina-update...' : 'I-update ang password' }}
                        </button>
                        <button @click="cancelPasswordChange" class="text-xs text-[var(--text-muted)] px-3">Cancel</button>
                    </div>
                </div>

                <!-- Sign-in -->
                <div v-else-if="!chatStudent" class="p-4 space-y-2">
                    <p class="text-xs text-[var(--text-muted)]">Kailangan mag-sign in muna para makapagtanong.</p>
                    <input v-model="chatLogin.student_number" placeholder="Student number" class="portal-input" />
                    <div class="relative">
                        <input v-model="chatLogin.password" :type="showChatLoginPassword ? 'text' : 'password'" placeholder="Password" class="portal-input pr-9" />
                        <button type="button" @click="showChatLoginPassword = !showChatLoginPassword" tabindex="-1" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--text-muted)] hover:text-[var(--text-body)]">
                            <svg v-if="showChatLoginPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.77 21.77 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.77 21.77 0 0 1-3.22 4.53M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
                            <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <p v-if="chatLoginError" class="text-xs text-red-500">{{ chatLoginError }}</p>
                    <button @click="signInChat" :disabled="chatLoginLoading" class="w-full text-white text-sm font-semibold py-2 rounded-xl disabled:opacity-50" style="background:var(--navy);">
                        {{ chatLoginLoading ? 'Checking...' : 'Sign in to chat' }}
                    </button>
                </div>

                <!-- Chat -->
                <template v-else>
                    <div ref="chatScrollEl" class="flex-1 overflow-y-auto px-3 py-2 space-y-2">
                        <div
                            v-for="m in chatMessages"
                            :key="m.id"
                            :class="m.sender === 'student' ? 'ml-auto text-white' : 'mr-auto'"
                            :style="m.sender === 'student' ? { background: 'var(--navy)' } : { background: 'var(--chip-bg)', color: 'var(--text-body)' }"
                            class="max-w-[85%] text-xs rounded-xl px-3 py-2 whitespace-pre-wrap"
                        >
                            <div v-if="m.sender !== 'student'" class="text-[10px] font-medium mb-0.5" :style="{ color: m.sender === 'admin' ? 'var(--navy)' : 'var(--text-muted)' }">
                                {{ m.sender === 'admin' ? 'Sir Francisco' : 'AI Assistant' }}
                            </div>
                            {{ m.body }}
                        </div>
                        <p v-if="chatMessages.length === 0" class="text-xs text-[var(--text-muted)] text-center py-4">
                            Magtanong ka na, sasagutin ka agad ng AI assistant.
                        </p>
                        <div v-if="chatSending" class="mr-auto max-w-[85%] text-xs rounded-xl px-3 py-2.5 flex items-center gap-1" style="background:var(--chip-bg);">
                            <span class="typing-dot"></span>
                            <span class="typing-dot"></span>
                            <span class="typing-dot"></span>
                        </div>
                    </div>
                    <form @submit.prevent="sendChatMessage" class="p-3 border-t border-[var(--surface-border-soft)] flex gap-2 shrink-0">
                        <input
                            v-model="chatInput"
                            type="text"
                            placeholder="Type your question..."
                            class="portal-input flex-1"
                            :disabled="chatSending"
                        />
                        <button type="submit" :disabled="chatSending || !chatInput.trim()" class="text-white text-sm font-semibold px-3 py-2 rounded-xl disabled:opacity-50" style="background:var(--navy);">
                            Send
                        </button>
                    </form>
                </template>
            </div>

            <button
                @click="chatOpen = !chatOpen"
                class="w-14 h-14 rounded-full text-white shadow-lg hover:shadow-xl hover:scale-105 transition transform flex items-center justify-center"
                style="background:var(--navy);"
            >
                <svg v-if="!chatOpen" width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-8.5 8.5 8.5 8.5 0 0 1-4-1L3 20l1-5.5A8.38 8.38 0 0 1 3 11.5 8.5 8.5 0 0 1 11.5 3 8.38 8.38 0 0 1 21 11.5Z"/></svg>
                <svg v-else width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>

            <!-- View my grades modal -->
            <div v-if="gradesModalOpen" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4">
                <div class="surface-card w-full max-w-sm shadow-xl p-5" style="border-radius: 1.5rem;">

                    <!-- Force change password (first login) -->
                    <template v-if="gradesMustChangePassword">
                        <div class="flex items-center justify-between mb-3">
                            <div class="text-sm font-semibold text-[var(--text-heading)]" style="font-family:var(--font-display);">Baguhin muna ang password mo</div>
                            <button @click="closeGradesModal" class="text-[var(--text-muted)] hover:text-[var(--text-body)]">✕</button>
                        </div>
                        <p class="text-xs text-[var(--text-muted)] mb-3">First time login mo — kailangan mo munang baguhin ang password bago makita ang grades mo.</p>
                        <div class="space-y-3">
                            <div class="relative">
                                <input v-model="newPasswordForm.new_password" :type="showNewPassword ? 'text' : 'password'" placeholder="Bagong password" class="portal-input pr-9" />
                                <button type="button" @click="showNewPassword = !showNewPassword" tabindex="-1" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--text-muted)] hover:text-[var(--text-body)]">
                                    <svg v-if="showNewPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.77 21.77 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.77 21.77 0 0 1-3.22 4.53M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
                                    <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                            <div class="relative">
                                <input v-model="newPasswordForm.confirm_password" :type="showConfirmPassword ? 'text' : 'password'" placeholder="Kumpirmahin ang bagong password" class="portal-input pr-9" />
                                <button type="button" @click="showConfirmPassword = !showConfirmPassword" tabindex="-1" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--text-muted)] hover:text-[var(--text-body)]">
                                    <svg v-if="showConfirmPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.77 21.77 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.77 21.77 0 0 1-3.22 4.53M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
                                    <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                            <p v-if="passwordChangeError" class="text-xs text-red-500">{{ passwordChangeError }}</p>
                            <div class="flex gap-2">
                                <button
                                    @click="submitPasswordChange"
                                    :disabled="passwordChangeLoading"
                                    class="flex-1 text-white text-sm font-semibold py-2 rounded-xl disabled:opacity-50"
                                    style="background:var(--navy);"
                                >
                                    {{ passwordChangeLoading ? 'Ina-update...' : 'I-update ang password' }}
                                </button>
                                <button @click="cancelPasswordChange" class="text-xs text-[var(--text-muted)] px-3">Cancel</button>
                            </div>
                        </div>
                    </template>

                    <!-- Sign-in form -->
                    <template v-else-if="!gradesResult">
                        <div class="flex items-center justify-between mb-3">
                            <div class="text-sm font-semibold text-[var(--text-heading)]" style="font-family:var(--font-display);">View my grades</div>
                            <button @click="closeGradesModal" class="text-[var(--text-muted)] hover:text-[var(--text-body)]">✕</button>
                        </div>
                        <form @submit.prevent="submitGradesLogin" class="space-y-3">
                            <input
                                v-model="gradesForm.student_number"
                                type="text"
                                placeholder="Student number"
                                class="portal-input"
                            />
                            <div class="relative">
                                <input
                                    v-model="gradesForm.password"
                                    :type="showGradesLoginPassword ? 'text' : 'password'"
                                    placeholder="Password"
                                    class="portal-input pr-9"
                                />
                                <button type="button" @click="showGradesLoginPassword = !showGradesLoginPassword" tabindex="-1" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[var(--text-muted)] hover:text-[var(--text-body)]">
                                    <svg v-if="showGradesLoginPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.77 21.77 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.77 21.77 0 0 1-3.22 4.53M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>
                                    <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                            <p v-if="gradesError" class="text-xs text-red-500">{{ gradesError }}</p>
                            <button
                                type="submit"
                                :disabled="gradesLoading"
                                class="w-full text-white text-sm font-semibold py-2 rounded-xl disabled:opacity-50"
                                style="background:var(--navy);"
                            >
                                {{ gradesLoading ? 'Checking...' : 'Sign in' }}
                            </button>
                        </form>
                    </template>

                    <!-- Grades result -->
                    <template v-else>
                        <div class="flex items-center justify-between mb-1">
                            <div>
                                <div class="text-[10px] uppercase tracking-[0.14em] text-[var(--text-muted)]">Report of grades</div>
                                <div class="text-sm font-semibold text-[var(--text-heading)]" style="font-family:var(--font-display);">{{ gradesResult.name }}</div>
                            </div>
                            <button @click="closeGradesModal" class="text-[var(--text-muted)] hover:text-[var(--text-body)]">✕</button>
                        </div>

                        <!-- Period tabs + loading indicator -->
                        <div class="flex items-center gap-2 mb-3">
                            <div class="flex items-center gap-1 rounded-lg p-1 w-fit" style="background: rgba(255,255,255,0.08);">
                                <button
                                    v-for="p in periods"
                                    :key="p.value"
                                    @click="switchGradesPeriod(p.value)"
                                    :disabled="gradesLoading"
                                    class="text-xs font-medium px-3 py-1 rounded-md transition disabled:opacity-50"
                                    :style="gradesPeriod === p.value
                                        ? 'background: rgba(255,255,255,0.15); color: #ffffff !important;'
                                        : 'color: rgba(255,255,255,0.5) !important;'"
                                >
                                    {{ p.label }}
                                </button>
                            </div>
                            <svg
                                v-if="gradesLoading"
                                width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" class="animate-spin"
                                style="color: rgba(255,255,255,0.6);"
                            >
                                <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/>
                            </svg>
                        </div>

                        <!-- Content area with fade + spinner overlay while loading -->
                        <div class="relative" style="min-height: 80px;">
                            <div :class="gradesLoading ? 'opacity-30 pointer-events-none transition' : 'transition'">
                                <p v-if="gradesResult.items.length === 0" class="text-xs text-[var(--text-muted)] py-4">
                                    Wala ka pang na-record na grades.
                                </p>

                                <div v-else class="mt-3 border-t border-[var(--surface-border-soft)]">
                                    <div
                                        v-for="item in gradesResult.items"
                                        :key="item.category + item.title"
                                        class="ledger-row py-2 text-sm border-b border-[var(--surface-border-soft)]"
                                    >
                                        <span class="text-[var(--text-body)] shrink-0">{{ item.title }}</span>
                                        <span class="leader leader--light"></span>

                                        <template v-if="gradesResult.scores[item.category + '|' + item.title]">
                                            <!-- inline editing this row -->
                                            <template v-if="showRecheckForm && editingItemKey === item.category + '|' + item.title">
                                                <input
                                                    v-model="editDraftScore"
                                                    type="number" step="0.01" min="0"
                                                    class="w-14 text-xs text-right rounded px-1.5 py-0.5"
                                                    style="border: 1px solid var(--surface-border); background: var(--surface); color: var(--text-body);"
                                                />
                                                <span class="text-xs text-[var(--text-muted)] shrink-0">/{{ gradesResult.scores[item.category + '|' + item.title].max_score }}</span>
                                                <button type="button" @click="confirmEditItem(item)" class="shrink-0 hover:opacity-70" style="color: var(--teal, #1a7f37);" title="Kumpirmahin">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                                </button>
                                                <button type="button" @click="cancelEditItem" class="shrink-0 text-[var(--text-muted)] hover:opacity-70" title="Kanselahin">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                                </button>
                                            </template>

                                            <!-- already has a pending edit — show original → proposal comparison -->
                                            <template v-else-if="showRecheckForm && editedItems[item.category + '|' + item.title]">
                                                <span class="text-xs text-[var(--text-muted)] line-through shrink-0" style="font-family:var(--font-mono);">
                                                    {{ gradesResult.scores[item.category + '|' + item.title].score }}
                                                </span>
                                                <span class="text-xs text-[var(--text-muted)] shrink-0">→</span>
                                                <span class="font-medium text-xs shrink-0 tabular-nums" style="font-family:var(--font-mono); color: #cf222e;">
                                                    {{ editedItems[item.category + '|' + item.title].claimed_score }}/{{ gradesResult.scores[item.category + '|' + item.title].max_score }}
                                                </span>
                                                <button type="button" @click="startEditItem(item)" class="shrink-0 text-[var(--text-muted)] hover:text-[var(--text-body)]" title="I-edit ulit">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                                </button>
                                                <button type="button" @click="removeEditedItem(item)" class="shrink-0 text-[var(--text-muted)] hover:opacity-70" title="I-undo">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
                                                </button>
                                            </template>

                                            <!-- normal display -->
                                            <template v-else>
                                                <span class="font-medium text-[var(--text-body)] shrink-0 tabular-nums" style="font-family:var(--font-mono);">
                                                    {{ gradesResult.scores[item.category + '|' + item.title].score }}/{{ gradesResult.scores[item.category + '|' + item.title].max_score }}
                                                </span>
                                                <button v-if="showRecheckForm" type="button" @click="startEditItem(item)" class="shrink-0 text-[var(--text-muted)] hover:text-[var(--text-body)]" title="I-edit">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                                                </button>
                                            </template>
                                        </template>

                                        <span v-else class="text-[var(--text-muted)] shrink-0">—</span>
                                    </div>
                                </div>


                                <div v-if="gradesResult.breakdown" class="mt-3 pt-3 border-t border-[var(--surface-border-soft)] space-y-1.5">
                                    <div
                                        v-for="cat in gradesResult.breakdown"
                                        :key="cat.category"
                                        class="flex items-center justify-between text-xs"
                                    >
                                        <span class="text-[var(--text-secondary)]">{{ cat.label }} ({{ cat.weight_percent }}%)</span>
                                        <span class="font-medium text-[var(--text-body)] tabular-nums" style="font-family:var(--font-mono);">
                                            <template v-if="cat.avg_percent !== null">
                                                {{ cat.avg_percent }}% → {{ cat.contribution }} pts
                                            </template>
                                            <span v-else class="text-[var(--text-muted)]">walang grade</span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="gradesLoading" class="absolute inset-0 flex items-center justify-center">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" class="animate-spin" style="color: rgba(255,255,255,0.7);">
                                    <path d="M21 12a9 9 0 1 1-6.219-8.56" stroke-linecap="round"/>
                                </svg>
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-100">
                            <span class="text-sm font-semibold" style="color: #3b82f6 !important;">Total</span>
                            <span class="text-lg font-bold" style="color: #3b82f6 !important;">{{ gradesResult.total_percentage }}%</span>
                        </div>

                        <!-- Confirm / Recheck actions -->
                        <div class="mt-4 pt-3 border-t border-[var(--surface-border-soft)]">

                            <!-- deadline notice -->
                            <div v-if="gradesResult.correction_locked && correctionUiState !== 'pending'" class="text-xs mb-3" style="color:#cf222e;">
                                Tapos na ang deadline para sa grade correction requests{{ gradesResult.correction_deadline ? ' (' + formatEventDate(gradesResult.correction_deadline) + ')' : '' }}. Makipag-ugnayan na lang kay Sir Francisco.
                            </div>

                            <!-- pending correction: summary + cancel/edit -->
                            <template v-if="correctionUiState === 'pending'">
                                <div class="text-xs font-semibold mb-2" style="color: var(--gold);">Naka-pending na recheck request mo</div>
                                <div class="space-y-1 text-xs mb-2">
                                    <div v-for="edit in gradesResult.pending_correction.edited_items" :key="edit.category + edit.title" class="flex items-center justify-between">
                                        <span class="text-[var(--text-secondary)]">{{ edit.title }}</span>
                                        <span class="tabular-nums" style="font-family:var(--font-mono);">
                                            <span class="text-[var(--text-muted)] line-through">{{ gradesResult.scores[edit.category + '|' + edit.title]?.score }}</span>
                                            <span class="text-[var(--text-muted)]">→</span>
                                            <span class="font-medium" style="color:var(--text-body);">{{ edit.claimed_score }}</span>
                                        </span>
                                    </div>
                                </div>
                                <p v-if="gradesResult.pending_correction.notes" class="text-xs text-[var(--text-secondary)] mb-2">{{ gradesResult.pending_correction.notes }}</p>
                                <a v-if="gradesResult.pending_correction.attachment_url" :href="gradesResult.pending_correction.attachment_url" target="_blank" class="text-xs hover:underline" style="color:var(--gold);">Tingnan ang attachment</a>
                                <div class="flex gap-2 mt-3">
                                    <button
                                        v-if="!gradesResult.correction_locked"
                                        @click="startEditExistingCorrection"
                                        class="flex-1 border text-[var(--text-body)] text-xs font-semibold py-2 rounded-xl"
                                        style="border-color:var(--surface-border);"
                                    >
                                        I-edit ulit
                                    </button>
                                    <button
                                        @click="cancelCorrection"
                                        :disabled="correctionLoading"
                                        class="flex-1 text-xs font-semibold py-2 rounded-xl disabled:opacity-50"
                                        style="color:#cf222e; border:1px solid rgba(207,34,46,0.4);"
                                    >
                                        {{ correctionLoading ? 'Kinakansela...' : 'Kanselahin' }}
                                    </button>
                                </div>
                            </template>

                            <!-- idle: no active correction for this period -->
                            <div v-else-if="correctionUiState === 'idle' && !gradesResult.correction_locked" class="flex gap-2">
                                <button
                                    @click="submitCorrection('confirmed')"
                                    :disabled="correctionLoading"
                                    class="flex-1 text-white text-xs font-semibold py-2 rounded-xl disabled:opacity-50"
                                    style="background:var(--navy);"
                                >
                                    Tama ang grades ko
                                </button>
                                <button
                                    @click="showRecheckForm = true"
                                    class="flex-1 border text-[var(--text-body)] text-xs font-semibold py-2 rounded-xl"
                                    style="border-color:var(--surface-border);"
                                >
                                    May mali, i-recheck
                                </button>
                            </div>

                            <!-- editing: composing / updating a recheck request -->
                            <div v-else-if="correctionUiState === 'editing'" class="space-y-2">
                                <p class="text-xs text-[var(--text-muted)]">I-click ang pencil icon sa item na mali para i-edit ang score.</p>
                                <textarea
                                    v-model="recheckNotes"
                                    rows="2"
                                    placeholder="Karagdagang paliwanag (opsyonal)"
                                    class="portal-input text-xs"
                                ></textarea>

                                <div>
                                    <label class="text-xs text-[var(--text-secondary)] block mb-1">
                                        Patunay (larawan, max 10MB) <span v-if="!hasExistingAttachment">*</span>
                                    </label>
                                    <input type="file" accept="image/*" @change="onAttachmentChange" class="text-xs" />
                                    <p v-if="correctionAttachment" class="text-xs text-[var(--text-muted)] mt-1">{{ correctionAttachment.name }}</p>
                                    <p v-else-if="hasExistingAttachment" class="text-xs text-[var(--text-muted)] mt-1">May existing attachment ka na — puwede mo itong palitan o iwanan.</p>
                                    <p v-if="correctionAttachmentError" class="text-xs text-red-500 mt-1">{{ correctionAttachmentError }}</p>
                                </div>

                                <div v-if="Object.keys(editedItems).length > 0" class="p-2.5 rounded-xl text-xs" style="background: rgba(207,34,46,0.08); border: 1px solid rgba(207,34,46,0.25);">
                                    <div class="font-semibold mb-1" style="color: #cf222e;">Babaguhin sa recheck request:</div>
                                    <div v-for="edit in Object.values(editedItems)" :key="edit.category + edit.title" class="flex items-center justify-between py-0.5">
                                        <span class="text-[var(--text-secondary)]">{{ edit.title }}</span>
                                        <span class="tabular-nums" style="font-family:var(--font-mono);">
                                            <span class="text-[var(--text-muted)] line-through">{{ gradesResult.scores[edit.category + '|' + edit.title]?.score }}</span>
                                            <span class="text-[var(--text-muted)]">→</span>
                                            <span class="font-medium" style="color:var(--text-body);">{{ edit.claimed_score }}</span>
                                        </span>
                                    </div>
                                </div>

                                <div class="flex gap-2">
                                    <button
                                        @click="submitCorrection('recheck')"
                                        :disabled="correctionLoading"
                                        class="flex-1 text-white text-xs font-semibold py-2 rounded-xl disabled:opacity-50"
                                        style="background:var(--navy);"
                                    >
                                        {{ correctionLoading ? 'Nagpo-process...' : 'I-submit ang recheck' }}
                                    </button>
                                    <button
                                        @click="cancelEditingRecheckForm"
                                        class="text-xs text-[var(--text-muted)] px-3"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </div>

                            <p v-if="correctionSuccessMessage" class="text-xs font-medium mt-2" style="color: var(--teal, #1a7f37);">{{ correctionSuccessMessage }}</p>
                            <p v-if="correctionError" class="text-xs text-red-500 mt-2">{{ correctionError }}</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import axios from 'axios';

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

// ---- Dark mode state ----
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
// ---- End dark mode state ----

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

// ---- Snapshot stats (derived, no backend change needed) ----
const nextEvent = computed(() => {
    const now = new Date();
    const upcoming = calendarEvents.value
        .filter((e) => new Date(e.event_date) >= now)
        .sort((a, b) => new Date(a.event_date) - new Date(b.event_date));
    return upcoming[0] ?? null;
});

const latestAnnouncement = computed(() => filteredAnnouncements.value[0] ?? null);

watch(activeSectionId, () => { currentIndex.value = 0; });

const goTo = (i) => {
    slideDirection.value = i > currentIndex.value ? 'slide-next' : 'slide-prev';
    currentIndex.value = i;
};

// ---- Chat widget state ----
const chatStudent = ref(null);
const chatLogin = ref({ student_number: '', password: '' });
const chatLoginError = ref('');
const chatLoginLoading = ref(false);
const showChatLoginPassword = ref(false);
const chatMustChangePassword = ref(false);
const chatMessages = ref([]);
const chatInput = ref('');
const chatSending = ref(false);
const chatScrollEl = ref(null);
let chatPollTimer;

const scrollChatToBottom = () => {
    nextTick(() => {
        if (chatScrollEl.value) chatScrollEl.value.scrollTop = chatScrollEl.value.scrollHeight;
    });
};

const signInChat = async () => {
    chatLoginLoading.value = true;
    chatLoginError.value = '';
    try {
        const { data } = await axios.post('/portal/chat/verify', chatLogin.value);
        if (data.must_change_password) {
            chatMustChangePassword.value = true;
            passwordChangeContext.value = 'chat';
            return;
        }
        chatStudent.value = data;
        await loadChatHistory();
        chatPollTimer = setInterval(loadChatHistory, 6000);
    } catch (err) {
        chatLoginError.value = err.response?.data?.message ?? 'May error, subukan ulit.';
    } finally {
        chatLoginLoading.value = false;
    }
};

const loadChatHistory = async () => {
    if (!chatStudent.value) return;
    const { data } = await axios.get('/portal/chat/history', {
        params: { student_id: chatStudent.value.student_id },
    });
    chatMessages.value = data.messages;
    scrollChatToBottom();
};

const sendChatMessage = async () => {
    if (!chatInput.value.trim()) return;
    chatSending.value = true;
    const body = chatInput.value;
    chatInput.value = '';
    scrollChatToBottom();
    try {
        const { data } = await axios.post('/portal/chat/send', {
            student_id: chatStudent.value.student_id,
            body,
        });
        chatMessages.value.push(...data.messages);
        scrollChatToBottom();
    } catch (err) {
        chatMessages.value.push({ id: Date.now(), sender: 'ai', body: 'May error, subukan ulit mamaya.' });
    } finally {
        chatSending.value = false;
    }
};
// ---- End chat widget state ----

// ---- Grades modal state ----
const gradesModalOpen = ref(false);
const gradesForm = ref({ student_number: '', password: '' });
const gradesError = ref('');
const gradesLoading = ref(false);
const showGradesLoginPassword = ref(false);
const gradesResult = ref(null);
const gradesPeriod = ref('prelim');
const gradesMustChangePassword = ref(false);

const periods = [
    { value: 'prelim', label: 'Prelim' },
    { value: 'midterm', label: 'Midterm' },
    { value: 'prefinal', label: 'Pre-Final' },
    { value: 'finals', label: 'Finals' },
];

const switchGradesPeriod = async (period) => {
    if (period === gradesPeriod.value || gradesLoading.value) return;
    gradesPeriod.value = period;
    cancelEditingRecheckForm();
    correctionSuccessMessage.value = '';
    gradesLoading.value = true;
    gradesError.value = '';
    try {
        const { data } = await axios.post('/portal/grades/verify', {
            ...gradesForm.value,
            period,
        });
        gradesResult.value = data;
    } catch (err) {
        gradesError.value = err.response?.data?.message ?? 'May error, subukan ulit.';
    } finally {
        gradesLoading.value = false;
    }
};

// ---- Grade correction state ----
const showRecheckForm = ref(false);
const recheckNotes = ref('');
const correctionLoading = ref(false);
const correctionError = ref('');
const correctionSuccessMessage = ref('');

// per-item edit state: key is `${category}|${title}`
const editedItems = ref({});
const editingItemKey = ref(null);
const editDraftScore = ref('');

// attachment (required proof, image only, max 10MB)
const correctionAttachment = ref(null);
const correctionAttachmentError = ref('');

const hasExistingAttachment = computed(() => !!gradesResult.value?.pending_correction?.attachment_url);

// idle | editing | pending
const correctionUiState = computed(() => {
    if (showRecheckForm.value) return 'editing';
    return gradesResult.value?.pending_correction ? 'pending' : 'idle';
});

const itemKeyOf = (item) => item.category + '|' + item.title;

const startEditItem = (item) => {
    const key = itemKeyOf(item);
    editingItemKey.value = key;
    editDraftScore.value = editedItems.value[key]
        ? editedItems.value[key].claimed_score
        : (gradesResult.value.scores[key]?.score ?? '');
};

const confirmEditItem = (item) => {
    const key = itemKeyOf(item);
    const val = parseFloat(editDraftScore.value);
    if (Number.isNaN(val) || val < 0) {
        correctionError.value = 'Hindi valid na score.';
        return;
    }
    editedItems.value = {
        ...editedItems.value,
        [key]: { category: item.category, title: item.title, claimed_score: val },
    };
    editingItemKey.value = null;
    editDraftScore.value = '';
    correctionError.value = '';
};

const cancelEditItem = () => {
    editingItemKey.value = null;
    editDraftScore.value = '';
};

const removeEditedItem = (item) => {
    const key = itemKeyOf(item);
    const copy = { ...editedItems.value };
    delete copy[key];
    editedItems.value = copy;
};

const onAttachmentChange = (e) => {
    correctionAttachmentError.value = '';
    const file = e.target.files[0];
    if (!file) {
        correctionAttachment.value = null;
        return;
    }
    if (!file.type.startsWith('image/')) {
        correctionAttachmentError.value = 'Larawan lang ang tinatanggap.';
        e.target.value = '';
        correctionAttachment.value = null;
        return;
    }
    if (file.size > 10 * 1024 * 1024) {
        correctionAttachmentError.value = 'Dapat hindi hihigit sa 10MB ang larawan.';
        e.target.value = '';
        correctionAttachment.value = null;
        return;
    }
    correctionAttachment.value = file;
};

const cancelEditingRecheckForm = () => {
    showRecheckForm.value = false;
    editedItems.value = {};
    editingItemKey.value = null;
    editDraftScore.value = '';
    recheckNotes.value = '';
    correctionAttachment.value = null;
    correctionAttachmentError.value = '';
    correctionError.value = '';
};

const startEditExistingCorrection = () => {
    const c = gradesResult.value.pending_correction;
    const map = {};
    (c.edited_items ?? []).forEach((item) => {
        const key = item.category + '|' + item.title;
        map[key] = { category: item.category, title: item.title, claimed_score: item.claimed_score };
    });
    editedItems.value = map;
    recheckNotes.value = c.notes ?? '';
    correctionAttachment.value = null;
    correctionAttachmentError.value = '';
    correctionError.value = '';
    correctionSuccessMessage.value = '';
    showRecheckForm.value = true;
};

const refetchGradesResult = async () => {
    try {
        const { data } = await axios.post('/portal/grades/verify', {
            ...gradesForm.value,
            period: gradesPeriod.value,
        });
        if (!data.must_change_password) {
            gradesResult.value = data;
        }
    } catch (err) {
        // silent — keep the current view if refetch fails
    }
};

const cancelCorrection = async () => {
    const c = gradesResult.value?.pending_correction;
    if (!c) return;
    correctionLoading.value = true;
    correctionError.value = '';
    try {
        await axios.delete(`/portal/grades/correction/${c.id}`, {
            data: {
                student_number: gradesForm.value.student_number,
                password: gradesForm.value.password,
            },
        });
        correctionSuccessMessage.value = 'Nakansela na ang recheck request mo.';
        await refetchGradesResult();
    } catch (err) {
        correctionError.value = err.response?.data?.message ?? 'May error, subukan ulit.';
    } finally {
        correctionLoading.value = false;
    }
};

const submitCorrection = async (type) => {
    correctionError.value = '';

    if (type === 'recheck') {
        if (Object.keys(editedItems.value).length === 0) {
            correctionError.value = 'Mag-edit muna ng score na mali bago mag-submit.';
            return;
        }
        if (!correctionAttachment.value && !hasExistingAttachment.value) {
            correctionError.value = 'Maglagay ng larawan bilang patunay (attachment).';
            return;
        }
    }

    correctionLoading.value = true;
    try {
        const formData = new FormData();
        formData.append('student_number', gradesForm.value.student_number);
        formData.append('password', gradesForm.value.password);
        formData.append('type', type);
        formData.append('period', gradesPeriod.value);

        if (type === 'recheck') {
            formData.append('notes', recheckNotes.value);
            formData.append('edited_items', JSON.stringify(
                Object.values(editedItems.value).map((i) => ({
                    category: i.category,
                    title: i.title,
                    claimed_score: i.claimed_score,
                }))
            ));
            if (correctionAttachment.value) {
                formData.append('attachment', correctionAttachment.value);
            }
        }

        const { data } = await axios.post('/portal/grades/correction', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        correctionSuccessMessage.value = data.message;

        if (type === 'recheck') {
            cancelEditingRecheckForm();
        }

        await refetchGradesResult();
    } catch (err) {
        correctionError.value = err.response?.data?.message ?? 'May error, subukan ulit.';
    } finally {
        correctionLoading.value = false;
    }
};

const openGradesModal = () => {
    gradesModalOpen.value = true;
};

const closeGradesModal = () => {
    gradesModalOpen.value = false;
    gradesResult.value = null;
    gradesForm.value = { student_number: '', password: '' };
    gradesError.value = '';
    gradesPeriod.value = 'prelim';
    showGradesLoginPassword.value = false;
    gradesMustChangePassword.value = false;
    cancelEditingRecheckForm();
    correctionSuccessMessage.value = '';
    if (passwordChangeContext.value === 'grades') resetPasswordChangeForm();
};
const submitGradesLogin = async () => {
    gradesLoading.value = true;
    gradesError.value = '';
    gradesPeriod.value = 'prelim';
    try {
        const { data } = await axios.post('/portal/grades/verify', {
            ...gradesForm.value,
            period: 'prelim',
        });
        if (data.must_change_password) {
            gradesMustChangePassword.value = true;
            passwordChangeContext.value = 'grades';
            return;
        }
        gradesResult.value = data;
    } catch (err) {
        gradesError.value = err.response?.data?.message ?? 'May error, subukan ulit.';
    } finally {
        gradesLoading.value = false;
    }
};
// ---- End grades modal state ----

// ---- Force change password (first login) state ----
// Shared by both the grades sign-in and chat sign-in flows.
const passwordChangeContext = ref(null); // 'grades' | 'chat' | null
const newPasswordForm = ref({ new_password: '', confirm_password: '' });
const passwordChangeError = ref('');
const passwordChangeLoading = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const resetPasswordChangeForm = () => {
    passwordChangeContext.value = null;
    newPasswordForm.value = { new_password: '', confirm_password: '' };
    passwordChangeError.value = '';
    showNewPassword.value = false;
    showConfirmPassword.value = false;
};

const cancelPasswordChange = () => {
    if (passwordChangeContext.value === 'grades') {
        gradesMustChangePassword.value = false;
    } else if (passwordChangeContext.value === 'chat') {
        chatMustChangePassword.value = false;
    }
    resetPasswordChangeForm();
};

const submitPasswordChange = async () => {
    passwordChangeError.value = '';

    if (newPasswordForm.value.new_password.length < 8) {
        passwordChangeError.value = 'Dapat at least 8 characters ang bagong password.';
        return;
    }
    if (newPasswordForm.value.new_password !== newPasswordForm.value.confirm_password) {
        passwordChangeError.value = 'Hindi magkatugma ang bagong password.';
        return;
    }

    const context = passwordChangeContext.value;
    const studentNumber = context === 'chat' ? chatLogin.value.student_number : gradesForm.value.student_number;
    const currentPassword = context === 'chat' ? chatLogin.value.password : gradesForm.value.password;

    passwordChangeLoading.value = true;
    try {
        await axios.post('/portal/grades/change-password', {
            student_number: studentNumber,
            current_password: currentPassword,
            new_password: newPasswordForm.value.new_password,
            new_password_confirmation: newPasswordForm.value.confirm_password,
        });

        if (context === 'grades') {
            gradesForm.value.password = newPasswordForm.value.new_password;
            gradesMustChangePassword.value = false;
            resetPasswordChangeForm();
            await submitGradesLogin();
        } else if (context === 'chat') {
            chatLogin.value.password = newPasswordForm.value.new_password;
            chatMustChangePassword.value = false;
            resetPasswordChangeForm();
            await signInChat();
        }
    } catch (err) {
        passwordChangeError.value = err.response?.data?.message ?? 'May error, subukan ulit.';
    } finally {
        passwordChangeLoading.value = false;
    }
};
// ---- End force change password state ----

// ---- Announcements table state ----
const expandedAnnouncementId = ref(null);
// ---- End announcements table state ----

// ---- FAQ state ----
const openFaqIndex = ref(null);

const faqs = [
    {
        q: 'Paano ako makakita ng grades ko?',
        a: 'I-click yung "View my grades" button, tapos ilagay yung student number at password mo. Kailangan tama yung dalawa bago lumabas ang grades mo.',
    },
    {
        q: 'Paano kung mali yung grade ko?',
        a: 'Pagkatapos mong tingnan yung grades mo, may button na "May mali, i-recheck" — pindutin mo yun tapos ilagay yung specific na dahilan (hal. anong item, dapat ilan yung score).',
    },
    {
        q: 'Paano ako mag-inform na absent si sir?',
        a: 'I-click yung "Inform sir absent" card sa dashboard. Awtomatikong naka-fill na ang section mo, ikaw na lang mag-submit ng dahilan o detalye.',
    },
    {
        q: 'Paano gumagana ang chat / Ask Sir Francisco?',
        a: 'I-click yung chat bubble sa ibaba kanan. Mag-sign in ka gamit ang student number at password, tapos pwede ka nang magtanong — sasagutin ka ng AI assistant o ni sir mismo.',
    },
    {
        q: 'Bakit hindi ko makita yung Top 10 ranking ko?',
        a: 'Lalabas lang sa Top 10 kung may naka-record nang grades sa section mo. Kung wala pang laman, ibig sabihin wala pa nailalagay na grades para dyan.',
    },
];
// ---- End FAQ state ----

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
    clearInterval(chatPollTimer);
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

const todayFormatted = computed(() =>
    new Date().toLocaleString('en-PH', {
        month: 'long', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    })
);

const formatPostedDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleString('en-PH', { month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>

<style scoped>
.portal-root {
    font-family: var(--font-body);

    --font-display: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    --font-body: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    --font-mono: ui-monospace, 'SF Mono', 'Cascadia Code', Menlo, monospace;

    /* GitHub Light tokens */
    --navy: #24292f;        /* dark neutral panel (like GH header) */
    --navy-deep: #1b1f24;
    --gold: #0969da;        /* GH accent blue — links, primary CTAs */
    --teal: #1a7f37;        /* GH success green */
    --coral: #cf222e;       /* GH danger red */

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
    /* GitHub Dark tokens */
    --navy: #21262d;
    --navy-deep: #161b22;
    --gold: #58a6ff;        /* GH accent blue, dark mode */
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

/* ---- background texture ---- */
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

/* ---- folder tabs ---- */
.folder-tab {
    clip-path: polygon(10% 0, 90% 0, 100% 100%, 0% 100%);
    border-radius: 0.35rem 0.35rem 0 0;
}
.folder-tab--active {
    background: var(--gold);
    color: var(--navy-deep);
    transform: translateY(-2px);
    box-shadow: 0 -2px 10px rgba(0,0,0,0.08);
}
.folder-tab--idle {
    background: var(--chip-bg);
    color: var(--text-muted);
}
.folder-tab--idle:hover { color: var(--text-body); }

/* ---- panels & cards ---- */
.ink-panel {
    background: linear-gradient(160deg, var(--navy), var(--navy-deep));
    box-shadow: 0 20px 45px -20px rgba(10, 18, 48, 0.45);
}
.surface-card {
    background: var(--surface);
    border: 1px solid var(--surface-border);
    border-radius: 1.5rem;
    box-shadow: 0 1px 2px rgba(20,33,61,0.03);
    transition: box-shadow 0.2s ease;
}
.surface-card:hover { box-shadow: 0 8px 24px -12px rgba(20,33,61,0.12); }
.card-heading {
    font-family: var(--font-display);
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--text-heading);
}

.ledger-tile {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 1.1rem;
    padding: 1rem;
}
.ledger-tile--white {
    background: var(--chip-bg);
    border: 1px solid var(--surface-border);
}
.ledger-tile__icon {
    width: 2rem; height: 2rem;
    border-radius: 0.6rem;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 0.75rem;
}

/* ---- action tiles ---- */
.action-tile {
    border-radius: 1.5rem;
    padding: 1.25rem;
    text-align: left;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.action-tile:hover { transform: translateY(-2px); box-shadow: 0 12px 24px -14px rgba(20,33,61,0.25); }
.action-tile--ink { background: linear-gradient(135deg, var(--navy), var(--navy-deep)); color: #fff; }
.action-tile--surface { background: var(--surface); border: 1px solid var(--surface-border); }
.action-tile--gold { background: linear-gradient(135deg, #d4a72c, #9a6700); }
.action-tile__icon {
    width: 2.25rem; height: 2.25rem;
    border-radius: 0.75rem;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 1rem;
}

/* ---- status chips ---- */
.status-done { background: rgba(46,158,134,0.14); color: var(--teal); }
.status-ongoing { background: rgba(247,177,37,0.22); color: #8A6100; }
.status-pending { background: var(--chip-bg); color: var(--text-secondary); }
.portal-root.dark .status-ongoing { color: var(--gold); }

/* ---- ledger leader-line rows ---- */
.ledger-row { display: flex; align-items: baseline; gap: 0.5rem; }
.leader {
    flex: 1;
    border-bottom: 1px dotted rgba(255,255,255,0.22);
    height: 0;
    transform: translateY(-4px);
    min-width: 0.5rem;
}
.leader--light { border-bottom-color: var(--surface-border); }

/* ---- rank ribbon ---- */
.rank-ribbon {
    display: inline-flex;
    align-items: center;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.06em;
    padding: 0.3rem 0.9rem;
    margin-bottom: 0.9rem;
    clip-path: polygon(6% 0, 94% 0, 100% 50%, 94% 100%, 6% 100%, 0 50%);
}
.rank-ribbon--gold { background: var(--gold); color: var(--navy-deep); }
.rank-ribbon--silver { background: #C7CEDA; color: #2B3550; }
.rank-ribbon--bronze { background: #C08A4E; color: #2E1B08; }

/* ---- inputs ---- */
.portal-input {
    width: 100%;
    font-size: 0.875rem;
    border: 1px solid var(--surface-border);
    border-radius: 0.75rem;
    padding: 0.5rem 0.75rem;
    background: var(--surface);
    color: var(--text-body);
}
.portal-input:focus {
    outline: 2px solid var(--gold);
    outline-offset: 1px;
}

/* ---- carousel transition ---- */
.slide-next-enter-active, .slide-next-leave-active,
.slide-prev-enter-active, .slide-prev-leave-active {
    transition: all 0.35s ease;
}
.slide-next-enter-from { opacity: 0; transform: translateX(24px); }
.slide-next-leave-to { opacity: 0; transform: translateX(-24px); }
.slide-prev-enter-from { opacity: 0; transform: translateX(-24px); }
.slide-prev-leave-to { opacity: 0; transform: translateX(24px); }

/* ---- typing dots ---- */
.typing-dot {
    width: 6px; height: 6px;
    border-radius: 9999px;
    background: var(--text-muted);
    animation: typing-bounce 1.2s infinite ease-in-out;
}
.typing-dot:nth-child(2) { animation-delay: 0.15s; }
.typing-dot:nth-child(3) { animation-delay: 0.3s; }
@keyframes typing-bounce {
    0%, 60%, 100% { transform: translateY(0); opacity: 0.5; }
    30% { transform: translateY(-4px); opacity: 1; }
}

@media (prefers-reduced-motion: reduce) {
    .blob-a, .blob-b { animation: none; }
    .action-tile:hover { transform: none; }
}
</style>