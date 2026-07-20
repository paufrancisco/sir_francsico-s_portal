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

                <!-- Sign-in -->
                <div v-if="!chatStudent" class="p-4 space-y-2">
                    <p class="text-xs text-[var(--text-muted)]">Kailangan mag-sign in muna para makapagtanong.</p>
                    <input v-model="chatLogin.student_number" placeholder="Student number" class="portal-input" />
                    <input v-model="chatLogin.password" type="password" placeholder="Password" class="portal-input" />
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

                    <!-- Sign-in form -->
                    <template v-if="!gradesResult">
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
                            <input
                                v-model="gradesForm.password"
                                type="password"
                                placeholder="Password"
                                class="portal-input"
                            />
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
                                <span class="font-medium text-[var(--text-body)] shrink-0 tabular-nums" style="font-family:var(--font-mono);">
                                    <template v-if="gradesResult.scores[item.category + '|' + item.title]">
                                        {{ gradesResult.scores[item.category + '|' + item.title].score }}/{{ gradesResult.scores[item.category + '|' + item.title].max_score }}
                                    </template>
                                    <span v-else class="text-[var(--text-muted)]">—</span>
                                </span>
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

                        <div class="flex items-center justify-between mt-3 pt-3 border-t border-[var(--surface-border-soft)]">
                            <span class="text-sm font-semibold text-[var(--text-body)]">Total</span>
                            <span class="text-lg font-semibold tabular-nums" style="font-family:var(--font-mono); color:var(--navy);">{{ gradesResult.total_percentage }}%</span>
                        </div>

                        <!-- Confirm / Recheck actions -->
                        <div v-if="!correctionSubmitted" class="mt-4 pt-3 border-t border-[var(--surface-border-soft)]">
                            <div v-if="!showRecheckForm" class="flex gap-2">
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

                            <div v-else class="space-y-2">
                                <textarea
                                    v-model="recheckNotes"
                                    rows="3"
                                    placeholder="Ano ang mali sa grades mo? (hal. 'Mali yung score ko sa Quiz 2, dapat 18/20 hindi 15/20')"
                                    class="portal-input text-xs"
                                ></textarea>
                                <div class="flex gap-2">
                                    <button
                                        @click="submitCorrection('recheck')"
                                        :disabled="correctionLoading || !recheckNotes.trim()"
                                        class="flex-1 text-white text-xs font-semibold py-2 rounded-xl disabled:opacity-50"
                                        style="background:var(--navy);"
                                    >
                                        {{ correctionLoading ? 'Nagpo-process...' : 'I-submit ang recheck' }}
                                    </button>
                                    <button
                                        @click="showRecheckForm = false"
                                        class="text-xs text-[var(--text-muted)] px-3"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </div>

                            <p v-if="correctionError" class="text-xs text-red-500 mt-2">{{ correctionError }}</p>
                        </div>

                        <div v-else class="mt-4 pt-3 border-t border-[var(--surface-border-soft)] text-center">
                            <p class="text-xs font-semibold" style="color:var(--navy);">{{ correctionSuccessMessage }}</p>
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
const gradesResult = ref(null);

// ---- Grade correction state ----
const showRecheckForm = ref(false);
const recheckNotes = ref('');
const correctionLoading = ref(false);
const correctionError = ref('');
const correctionSubmitted = ref(false);
const correctionSuccessMessage = ref('');

const submitCorrection = async (type) => {
    correctionLoading.value = true;
    correctionError.value = '';
    try {
        const { data } = await axios.post('/portal/grades/correction', {
            student_number: gradesForm.value.student_number,
            password: gradesForm.value.password,
            type,
            notes: type === 'recheck' ? recheckNotes.value : null,
        });
        correctionSuccessMessage.value = data.message;
        correctionSubmitted.value = true;
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
    showRecheckForm.value = false;
    recheckNotes.value = '';
    correctionError.value = '';
    correctionSubmitted.value = false;
    correctionSuccessMessage.value = '';
};
const submitGradesLogin = async () => {
    gradesLoading.value = true;
    gradesError.value = '';
    try {
        const { data } = await axios.post('/portal/grades/verify', gradesForm.value);
        gradesResult.value = data;
    } catch (err) {
        gradesError.value = err.response?.data?.message ?? 'May error, subukan ulit.';
    } finally {
        gradesLoading.value = false;
    }
};
// ---- End grades modal state ----

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