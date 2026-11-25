<template>
  <section class="bulk">
    <header class="bulk__head">
      <h2>Bulk grade import</h2>
      <p>
        Paste your class sheet, choose the grading period, then copy the generated script
        and run it on your gradebook page.
      </p>
    </header>

    <div class="bulk__grid">
      <!-- Step 1: input -->
      <div class="card">
        <h3 class="card__title"><span class="step">1</span> Paste your grades</h3>

        <label class="field">
          <span class="field__label">Grading period</span>
          <select v-model.number="periodIndex" class="control">
            <option v-for="(name, i) in PERIODS" :key="name" :value="i">{{ name }}</option>
          </select>
        </label>

        <label class="field">
          <span class="field__label">Your first name</span>
          <input
            v-model="firstName"
            type="text"
            class="control"
            maxlength="40"
            autocomplete="given-name"
            placeholder="e.g. Paulo"
          />
          <span v-if="firstName.trim() && !nameValid" class="muted">Letters, spaces, hyphens and apostrophes only (2–40 characters).</span>
        </label>

        <label class="field">
          <span class="field__label">Student IDs and scores</span>
          <textarea
            v-model="raw"
            class="control control--area"
            wrap="soft"
            style="white-space:pre-wrap;overflow-x:hidden;"
            rows="10"
            spellcheck="false"
            placeholder="Paste rows from your sheet here (Student No., Name, PRELIMS, EQV, MIDTERM, EQV, ...). A simple 'student ID + score' list also works."
          ></textarea>
          <button type="button" @click="sheetOpen = true" style="display:block;margin-top:8px;padding:0;border:0;background:none;cursor:zoom-in;text-align:left;">
            <img src="/images/sheet-guide.png" alt="Select the student rows in your Summary sheet, then copy" style="max-width:280px;width:100%;border-radius:8px;border:1px solid rgba(255,255,255,.2);" />
            <small style="opacity:.7;">Example: select these rows in your sheet, then Ctrl+C. Click to enlarge</small>
          </button>
        </label>

        <label class="check">
          <input v-model="dryRun" type="checkbox" />
          <span>Dry run first (the script only logs what it would do)</span>
        </label>

        <div v-if="raw.trim()" class="summary">
          <span class="pill pill--ok">{{ readyRows.length }} ready</span>
          <span v-if="blankRows.length" class="pill pill--warn">{{ blankRows.length }} no {{ periodName }} grade yet</span>
          <span v-if="invalidRows.length" class="pill pill--bad">{{ invalidRows.length }} invalid</span>
          <span v-if="duplicateCount" class="pill pill--warn">{{ duplicateCount }} duplicate ID(s), last one used</span>
          <span v-if="ignoredLines" class="muted">{{ ignoredLines }} line(s) ignored (no student ID)</span>
        </div>
      </div>

      <!-- Step 2: preview -->
      <div class="card">
        <h3 class="card__title"><span class="step">2</span> Preview ({{ periodName }})</h3>

        <p v-if="!rows.length" class="muted">Nothing to preview yet.</p>

        <div v-else class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th class="num">{{ periodName }}</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in rows" :key="row.line" :class="'row--' + row.status">
                <td class="mono">{{ row.id }}</td>
                <td>{{ row.name || '—' }}</td>
                <td class="num mono">{{ row.score === null ? '—' : row.score }}</td>
                <td>
                  <span v-if="row.status === 'ready'">Ready</span>
                  <span v-else-if="row.status === 'blank'">No grade yet (skipped)</span>
                  <span v-else>Invalid score (skipped)</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Step 3: script -->
      <div class="card card--wide">
        <h3 class="card__title"><span class="step">3</span> Copy the script</h3>

        <div class="code">
          <button
            type="button"
            class="code__copy"
            :disabled="!canCopy"
            :title="copied ? 'Copied!' : 'Copy script'"
            aria-label="Copy script"
            @click="copyScript"
          >
            <svg v-if="!copied" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <rect x="9" y="9" width="11" height="11" rx="2" />
              <path d="M5 15V6a2 2 0 0 1 2-2h9" />
            </svg>
            <svg v-else viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ copied ? 'Copied' : 'Copy' }}</span>
          </button>
          <pre v-if="canCopy" class="code__body" @copy="logUse">{{ script }}</pre>
          <p v-else-if="!nameValid" class="muted code__empty">Enter your first name to unlock the script.</p>
          <p v-else class="muted code__empty">The script will appear here once there is at least one valid grade.</p>
        </div>

        <ol class="steps">
          <li>Go to your gradebook subject (the page that lists your students with the Prelim, Midterm, Pre-Final and Finals boxes).</li>
          <li>Press <kbd>F12</kbd> and open the <strong>Console</strong> tab.</li>
          <li>Paste the script into the console and press <kbd>Enter</kbd>. If your browser blocks pasting, type <code>allow pasting</code>, press <kbd>Enter</kbd>, then paste again.
            <button type="button" @click="guideOpen = true" style="display:block;margin-top:8px;padding:0;border:0;background:none;cursor:zoom-in;">
              <img src="/images/console-guide.png" alt="Paste the script in the Console tab, then press Enter" style="max-width:280px;width:100%;border-radius:8px;border:1px solid rgba(255,255,255,.2);" />
              <small style="opacity:.7;">Click to enlarge</small>
            </button>
          </li>
          <li>Double-check the grades on the page.</li>
          <li>Click <strong>Save</strong>.</li>
        </ol>
      </div>
    </div>
      <div v-if="guideOpen" @click="guideOpen = false" style="position:fixed;inset:0;z-index:50;background:rgba(0,0,0,.8);display:flex;align-items:center;justify-content:center;padding:16px;cursor:zoom-out;">
        <img src="/images/console-guide.png" alt="Console guide" style="max-width:100%;max-height:100%;border-radius:8px;" />
    </div>
      <div v-if="sheetOpen" @click="sheetOpen = false" style="position:fixed;inset:0;z-index:50;background:rgba(0,0,0,.8);display:flex;align-items:center;justify-content:center;padding:16px;cursor:zoom-out;">
        <img src="/images/sheet-guide.png" alt="Sheet guide" style="max-width:100%;max-height:100%;border-radius:8px;" />
    </div>
  </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import axios from 'axios'; // reminder: Breeze already ships axios + CSRF header handling

// ---- helpers:start (pure functions, no Vue; keep this block self-contained) ----
const PERIODS = ['Prelim', 'Midterm', 'Pre-Final', 'Finals'];

// Gradebook ID format = '0' + 10 digits starting with 2 (sheet may drop the leading 0)
const CELL_ID_RE = /^0?(2\d{9})$/;
const LINE_ID_RE = /(?:^|\D)(0?2\d{9})(?!\d)/;
const NUM_RE = /^-?\d+(\.\d+)?$/;
const BLANK_TOKENS = ['', '-', '–', '—'];

const toGradebookId = (digits) => '0' + digits.replace(/^0/, '');
const isGradeToken = (t) => NUM_RE.test(t) || BLANK_TOKENS.includes(t);

function parseSheet(text, periodIndex) {
  const rows = [];
  let ignored = 0;

  text.split(/\r?\n/).forEach((line, n) => {
    if (!line.trim()) return;

    let id = null;
    let tokens = [];
    const cells = line.split('\t').map((c) => c.trim());

    if (cells.length > 1) {
      // pasted from a spreadsheet: one cell per column
      const idx = cells.findIndex((c) => CELL_ID_RE.test(c));
      if (idx >= 0) {
        id = toGradebookId(cells[idx].match(CELL_ID_RE)[1]);
        tokens = cells.slice(idx + 1).filter((c) => c !== '');
      }
    } else {
      // plain chat-style line, e.g. "2000416700 88.44" or "2000416700, Alindog, Jaycee, 88.44"
      const m = line.match(LINE_ID_RE);
      if (m) {
        id = toGradebookId(m[1]);
        tokens = line
          .slice(m.index + m[0].length)
          .split(/[\s,;:=]+/)
          .filter((t) => t !== '');
      }
    }

    if (!id) {
      // rank-only rows (e.g. "39") are template noise, so they are not counted as ignored
      if (!/^[\d\s]*$/.test(line)) ignored++;
      return;
    }

    // leading non-grade tokens = student name
    const nameParts = [];
    while (tokens.length && !isGradeToken(tokens[0])) nameParts.push(tokens.shift());
    const name = nameParts.join(' ').replace(/\s+/g, ' ').trim();

    // one value = plain "ID + score"; otherwise full sheet where each period is a (grade, EQV) pair
    const token = tokens.length === 1 ? tokens[0] : tokens[periodIndex * 2];

    let score = null;
    let status = 'blank';
    if (token !== undefined && !BLANK_TOKENS.includes(token)) {
      if (NUM_RE.test(token) && parseFloat(token) >= 0 && parseFloat(token) <= 100) {
        score = parseFloat(token);
        status = 'ready';
      } else if (NUM_RE.test(token)) {
        status = 'invalid';
      }
      // anything else (e.g. INC) is treated as blank
    }

    rows.push({ line: n, id, name, score, status });
  });

  return { rows, ignored };
}

function buildScript(gradeMap, periodIndex, dry) {
  const count = Object.keys(gradeMap).length;
  return [
    '(() => {',
    '  // Period: ' + PERIODS[periodIndex] + ' | Students in this script: ' + count,
    '  const DRY = ' + (dry ? 'true' : 'false') + '; // true = log only, false = fill the boxes',
    '  const PERIOD = ' + periodIndex + '; // 0 = PRELIM, 1 = MIDTERM, 2 = PREFIN, 3 = FINALS',
    '  const G = ' + JSON.stringify(gradeMap) + ';',
    '',
    '  // Find the document (the page or an iframe) that contains the student IDs',
    '  const docs = [document];',
    '  for (let k = 0; k < docs.length; k++) {',
    "    docs[k].querySelectorAll('iframe,frame').forEach(f => {",
    '      try { if (f.contentDocument) docs.push(f.contentDocument); } catch (e) {}',
    '    });',
    '  }',
    '  const doc = docs.find(d => d.body && /02\\d{9}/.test(d.body.innerText));',
    "  console.log('Documents found:', docs.length, '| has student IDs:', !!doc);",
    "  if (!doc) { console.log('No document with student IDs found. Nothing changed.'); return; }",
    '',
    '  const Ev = doc.defaultView.Event;',
    '  const ids = [...new Set(doc.body.innerText.match(/02\\d{9}/g) || [])];',
    "  const inputs = [...doc.querySelectorAll('input')].filter(i =>",
    "    !['checkbox','hidden','submit','button','image','radio'].includes((i.type || 'text').toLowerCase()) &&",
    '    i.offsetParent !== null);',
    "  console.log('IDs on page:', ids.length, '| input boxes:', inputs.length, '(expected 4 per student)');",
    '',
    '  // Safety check: stop if the box count does not match the student count',
    '  if (inputs.length !== ids.length * 4) {',
    "    console.log('Counts do not match. Nothing changed.');",
    '    return;',
    '  }',
    '',
    '  let ok = 0;',
    '  const untouched = [];',
    '  ids.forEach((id, i) => {',
    '    if (!(id in G)) { untouched.push(id); return; }',
    '    const box = inputs[i * 4 + PERIOD]; // order per student: PRELIM, MIDTERM, PREFIN, FINALS',
    "    console.log(id, '->', G[id], DRY ? '(dry run)' : '');",
    '    if (!DRY) {',
    '      box.focus();',
    '      box.value = G[id];',
    "      box.dispatchEvent(new Ev('input', {bubbles: true}));",
    "      box.dispatchEvent(new Ev('change', {bubbles: true}));",
    '      box.blur();',
    '    }',
    '    ok++;',
    '  });',
    '  const notOnPage = Object.keys(G).filter(id => !ids.includes(id));',
    "  console.log('Filled:', ok, '| On page but not in your data (left as is):', untouched, '| In your data but not on page:', notOnPage);",
    "  console.log(DRY ? 'Dry run only. Nothing was changed.' : 'Done. Double-check the grades, then click Save.');",
    '})();',
    '',
  ].join('\n');
}
// ---- helpers:end ----

const raw = ref('');
const periodIndex = ref(1); // default Midterm; keep in sync with the default grading-period setting
const dryRun = ref(false);
const copied = ref(false);
const guideOpen = ref(false);
const sheetOpen = ref(false);

// remember the name on this browser so it is not retyped every time
const NAME_KEY = 'bulk_import_first_name';
function loadName() {
  try {
    return localStorage.getItem(NAME_KEY) || '';
  } catch (e) {
    return '';
  }
}
const firstName = ref(loadName());
watch(firstName, (v) => {
  try {
    localStorage.setItem(NAME_KEY, v.trim());
  } catch (e) {
    /* storage can be blocked; ignore */
  }
});

// keep in sync with the validation rule in BulkImportLogController
const nameValid = computed(() => /^\p{L}[\p{L} .'-]{1,39}$/u.test(firstName.value.trim()));

const periodName = computed(() => PERIODS[periodIndex.value]);
const parsed = computed(() => parseSheet(raw.value, periodIndex.value));
const rows = computed(() => parsed.value.rows);
const ignoredLines = computed(() => parsed.value.ignored);

const readyRows = computed(() => rows.value.filter((r) => r.status === 'ready'));
const blankRows = computed(() => rows.value.filter((r) => r.status === 'blank'));
const invalidRows = computed(() => rows.value.filter((r) => r.status === 'invalid'));
const duplicateCount = computed(() => rows.value.length - new Set(rows.value.map((r) => r.id)).size);

// last row wins when an ID is pasted twice
const gradeMap = computed(() => {
  const map = {};
  readyRows.value.forEach((r) => {
    map[r.id] = r.score;
  });
  return map;
});

const script = computed(() => buildScript(gradeMap.value, periodIndex.value, dryRun.value));

const canCopy = computed(() => nameValid.value && readyRows.value.length > 0);

// one record per unique name/period/count/dry-run combo, so repeated copies do not spam the log
let lastLoggedKey = '';
async function logUse() {
  if (!canCopy.value) return;

  const payload = {
    first_name: firstName.value.trim(),
    period: periodName.value,
    student_count: readyRows.value.length,
    dry_run: dryRun.value,
  };
  const key = JSON.stringify(payload);
  if (key === lastLoggedKey) return;
  lastLoggedKey = key;

  try {
    await axios.post('/bulk-import/log', payload);
  } catch (e) {
    // logging must never block the teacher from copying the script
  }
}

let copiedTimer = null;
async function copyScript() {
  if (!canCopy.value) return;
  logUse();

  try {
    await navigator.clipboard.writeText(script.value);
  } catch (e) {
    // fallback for non-secure contexts / older browsers
    const ta = document.createElement('textarea');
    ta.value = script.value;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
  }

  copied.value = true;
  clearTimeout(copiedTimer);
  copiedTimer = setTimeout(() => (copied.value = false), 2000);
}
</script>

<style scoped>
.bulk {
  --accent: #fde68a;
  --card-bg: rgba(255, 255, 255, 0.06);
  --card-border: rgba(255, 255, 255, 0.14);
  --muted: rgba(255, 255, 255, 0.62);
  color: #fff;
}

.bulk__head h2 {
  margin: 0 0 4px;
  font-size: 1.15rem;
}
.bulk__head p {
  margin: 0 0 16px;
  color: var(--muted);
  font-size: 0.9rem;
}

.bulk__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}
.card {
  background: var(--card-bg);
  border: 1px solid var(--card-border);
  border-radius: 14px;
  padding: 16px;
  backdrop-filter: blur(6px);
}
.card--wide {
  grid-column: 1 / -1;
}
.card__title {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0 0 12px;
  font-size: 0.98rem;
}
.step {
  display: inline-grid;
  place-items: center;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: var(--accent);
  color: #1b2a41;
  font-size: 0.78rem;
  font-weight: 700;
}

.field {
  display: block;
  margin-bottom: 12px;
}
.field__label {
  display: block;
  margin-bottom: 6px;
  font-size: 0.82rem;
  color: var(--muted);
}
.control {
  width: 100%;
  box-sizing: border-box;
  padding: 9px 11px;
  border-radius: 10px;
  border: 1px solid var(--card-border);
  background: rgba(0, 0, 0, 0.25);
  color: #fff;
  font: inherit;
}
.control:focus {
  outline: 2px solid var(--accent);
  outline-offset: 1px;
}
.control--area {
  min-height: 190px;
  resize: vertical;
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 0.78rem;
  white-space: pre;
}
select.control option {
  color: #111;
}

.check {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.85rem;
  color: var(--muted);
}

.summary {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  margin-top: 12px;
}
.pill {
  padding: 3px 10px;
  border-radius: 999px;
  font-size: 0.78rem;
  font-weight: 600;
}
.pill--ok {
  background: rgba(74, 222, 128, 0.18);
  color: #86efac;
}
.pill--warn {
  background: rgba(253, 230, 138, 0.18);
  color: var(--accent);
}
.pill--bad {
  background: rgba(248, 113, 113, 0.2);
  color: #fca5a5;
}
.muted {
  color: var(--muted);
  font-size: 0.82rem;
}

.table-wrap {
  max-height: 300px;
  overflow: auto;
  border-radius: 10px;
  border: 1px solid var(--card-border);
}
.table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8rem;
}
.table th,
.table td {
  padding: 6px 10px;
  text-align: left;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.table th {
  position: sticky;
  top: 0;
  background: #17314f;
  font-weight: 600;
}
.table .num {
  text-align: right;
}
.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
}
.row--blank td {
  color: var(--muted);
}
.row--invalid td {
  color: #fca5a5;
}

.code {
  position: relative;
  border-radius: 10px;
  border: 1px solid var(--card-border);
  background: rgba(0, 0, 0, 0.35);
}
.code__copy {
  position: absolute;
  top: 8px;
  right: 8px;
  z-index: 1;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  border-radius: 8px;
  border: 1px solid var(--card-border);
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
  font-size: 0.78rem;
  cursor: pointer;
}
.code__copy:hover:not(:disabled) {
  background: var(--accent);
  color: #1b2a41;
}
.code__copy:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.code__body {
  margin: 0;
  max-height: 260px;
  overflow: auto;
  padding: 14px 16px;
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 0.74rem;
  line-height: 1.5;
  white-space: pre;
}
.code__empty {
  margin: 0;
  padding: 18px 16px;
}

.steps {
  margin: 14px 0 0;
  padding-left: 20px;
  font-size: 0.88rem;
  line-height: 1.7;
}
.steps kbd,
.steps code {
  padding: 1px 6px;
  border-radius: 5px;
  background: rgba(255, 255, 255, 0.14);
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 0.8rem;
}

@media (max-width: 900px) {
  .bulk__grid {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>
