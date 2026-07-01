<?php
namespace app\view\form;

class RosterWizardModal {

    public static function render(): string {
        return <<<HTML
<style>
#wiz-overlay {
    display: none; position: fixed; inset: 0; z-index: 10000;
    background: rgba(0,0,0,0.55); overflow-y: auto;
    font-family: var(--fontfamily);
}
#wiz-panel {
    background: var(--volscolor3); margin: 2rem auto; width: 92%; max-width: 860px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.3);
    display: flex; flex-direction: column;
    max-height: 92vh;
}
#wiz-header {
    background: var(--volscolor4); color: var(--volscolor3); padding: 0.7rem 1.4rem;
    display: flex; justify-content: space-between; align-items: center;
}
#wiz-header h2 { margin: 0; font-size: 1.4rem; font-weight: 400; }
#wiz-close {
    background: var(--volscolor3); color: var(--volscolor4);
    border: none; cursor: pointer;
    font-size: 1.3rem; font-weight: bold; line-height: 1;
    padding: 0.2rem 0.6rem;
}
#wiz-close:hover { background: var(--volscolor1); }
#wiz-steps-bar {
    display: flex; background: var(--volscolor1); border-bottom: 1px solid #ccc; padding: 0;
}
.wiz-step-tab {
    flex: 1; text-align: center; padding: 0.55rem 0.3rem; font-size: 0.82rem;
    color: #888; border-right: 1px solid #ccc; cursor: default;
    transition: background 0.15s;
}
.wiz-step-tab:last-child { border-right: none; }
.wiz-step-tab.active { background: var(--volscolor3); color: var(--volscolor4); font-weight: bold; }
.wiz-step-tab.done   { color: var(--doitbg); background: var(--volscolor2); cursor: pointer; }
#wiz-body { padding: 1.4rem; flex: 1; min-height: 0; overflow-y: auto; }
.wiz-step-panel { display: none; }
.wiz-step-panel.active { display: block; }
.wiz-field-row {
    display: flex; align-items: center; margin-bottom: 0.7rem; gap: 0.6rem;
}
.wiz-field-row label { width: 210px; min-width: 210px; font-size: 0.92rem; color: #444; text-align: right; padding-right: 0.5rem; }
.wiz-field-row input[type=text],
.wiz-field-row input[type=date],
.wiz-field-row input[type=time],
.wiz-field-row input[type=number],
.wiz-field-row select { font-size: 0.92rem; padding: 0.28rem 0.45rem; border: 1px solid #aaa; }
.wiz-field-row input[type=checkbox] { width: 1.2rem; height: 1.2rem; }
.wiz-hint { font-size: 0.78rem; color: #777; margin-left: 0.3rem; }
.wiz-section-head {
    font-weight: bold; color: var(--volscolor4); border-bottom: 1px solid #ccc;
    margin: 1rem 0 0.6rem; padding-bottom: 0.3rem; font-size: 0.95rem;
}
.wiz-rec-panel { display: none; padding: 0.5rem 0 0.2rem 0; }
.wiz-rec-panel.active { display: block; }
.wiz-radio-row { display: flex; gap: 1.2rem; margin-bottom: 0.5rem; align-items: center; flex-wrap: wrap; }
.wiz-radio-row label { display: flex; align-items: center; gap: 0.3rem; font-size: 0.92rem; white-space: nowrap; }
.wiz-dow-row { display: flex; gap: 0.6rem; flex-wrap: wrap; margin-top: 0.4rem; }
.wiz-dow-row label { display: flex; align-items: center; gap: 0.25rem; font-size: 0.9rem; }
.wiz-task-list, .wiz-role-list, .wiz-alert-list {
    border: 1px solid #ccc; min-height: 60px; margin-bottom: 0.7rem; padding: 0.3rem;
}
.wiz-list-item {
    display: flex; align-items: center; padding: 0.35rem 0.5rem;
    border-bottom: 1px solid #eee; gap: 0.5rem; font-size: 0.9rem;
}
.wiz-list-item:last-child { border-bottom: none; }
.wiz-list-item.selected { background: var(--volscolor2); font-weight: bold; }
.wiz-list-item .wiz-item-name { flex: 1; }
.wiz-btn-trash {
    background: none; border: none; color: var(--dangerbg); cursor: pointer;
    font-size: 1.1rem; padding: 0 0.3rem; line-height: 1;
}
.wiz-btn-trash:hover { color: var(--dangerbghover); }
.wiz-two-col { display: flex; gap: 1rem; }
.wiz-col-left { width: 36%; min-width: 160px; }
.wiz-col-right { flex: 1; }
.wiz-panel-head { font-size: 0.82rem; color: #777; margin-bottom: 0.3rem; text-transform: uppercase; letter-spacing: 0.04em; }
.wiz-add-row { display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.7rem; flex-wrap: wrap; }
.wiz-add-row select, .wiz-add-row input { font-size: 0.9rem; padding: 0.25rem 0.4rem; border: 1px solid #aaa; }
.wiz-add-row select { min-width: 140px; }
.wiz-qty-lbl { font-size: 0.85rem; color: #555; }
.wiz-qty-input { width: 52px; }
.wiz-inline-form {
    background: var(--volscolor1); border: 1px solid #ccc;
    padding: 0.7rem 0.9rem; margin-bottom: 0.7rem; display: none;
}
.wiz-inline-form.active { display: block; }
.wiz-users-grid { overflow-x: auto; }
.wiz-users-grid table { border-collapse: collapse; font-size: 0.82rem; width: 100%; }
.wiz-users-grid th, .wiz-users-grid td { border: 1px solid #ddd; padding: 0.3rem 0.5rem; text-align: center; }
.wiz-users-grid th.role-col { text-align: left; min-width: 140px; }
.wiz-users-grid thead th { position: sticky; top: 0; background: var(--volscolor2); z-index: 1; }
.wiz-users-grid tr:nth-child(even) td { background: var(--volscolor1); }
.wiz-status { font-size: 0.88rem; margin-top: 0.5rem; padding: 0.4rem 0.6rem; }
.wiz-status.ok  { background: #d4edda; color: #155724; }
.wiz-status.err { background: var(--volslookatme); color: var(--volscolor4); }
.wiz-status.info { background: var(--volscolor2); color: #444; }
.wiz-note { font-size: 0.83rem; color: #666; margin-top: 0.4rem; }
.wiz-skip-link { font-size: 0.85rem; color: var(--doitbg); cursor: pointer; text-decoration: underline; margin-left: 0.8rem; }
.wiz-alert-row { display: flex; align-items: center; gap: 0.4rem; padding: 0.3rem 0; font-size: 0.88rem; border-bottom: 1px dashed #ddd; }
.wiz-alert-row:last-child { border-bottom: none; }
.wiz-role-group { margin-bottom: 0.8rem; }
.wiz-role-group-head { font-size: 0.88rem; font-weight: bold; color: #444; margin-bottom: 0.3rem; }
.wiz-empty { font-size: 0.85rem; color: #aaa; padding: 0.4rem; text-align: center; }
#wiz-footer {
    padding: 0.9rem 1.4rem; border-top: 1px solid #ccc;
    display: flex; justify-content: space-between; align-items: center;
    background: var(--volscolor2);
}
.wiz-nav-right { display: flex; gap: 0.6rem; align-items: center; }
.wiz-btn {
    padding: 0.45rem 1.1rem; border: none;
    cursor: pointer; font-size: 0.9rem; background: var(--neutralbg); color: #333;
}
.wiz-btn:hover { background: var(--neutralbghover); }
.wiz-btn-primary { background: var(--doitbg); color: var(--volscolor3); }
.wiz-btn-primary:hover { background: var(--doitbghover); }
.wiz-btn-secondary { background: var(--neutralbg); color: #333; }
.wiz-btn-secondary:hover { background: var(--neutralbghover); }
.wiz-btn-success { background: var(--doitbg); color: var(--volscolor3); }
.wiz-btn-success:hover { background: var(--doitbghover); }
.wiz-btn:disabled { opacity: 0.45; cursor: default; }
</style>

<div id="wiz-overlay" class="nondatainput">
  <div id="wiz-panel">
    <div id="wiz-header">
      <h2>Create New Roster</h2>
      <button type="button" id="wiz-close" title="Close wizard">&times;</button>
    </div>

    <div id="wiz-steps-bar">
      <div class="wiz-step-tab active" data-step="1">1. Roster</div>
      <div class="wiz-step-tab" data-step="2">2. Tasks</div>
      <div class="wiz-step-tab" data-step="3">3. Roles</div>
      <div class="wiz-step-tab" data-step="4">4. Alerts</div>
      <div class="wiz-step-tab" data-step="5">5. Users</div>
      <div class="wiz-step-tab" data-step="6">6. Sessions</div>
    </div>

    <div id="wiz-body">

      <!-- ===== STEP 1: ROSTER ===== -->
      <div id="wiz-step-1" class="wiz-step-panel active">
        <div class="wiz-section-head">Roster Details</div>
        <div class="wiz-field-row">
          <label>Roster Name *</label>
          <input type="text" id="wiz-name" maxlength="100" style="width:220px">
        </div>
        <div class="wiz-field-row">
          <label>Start Date</label>
          <input type="date" id="wiz-startdate">
        </div>
        <div class="wiz-field-row">
          <label>End Date</label>
          <input type="date" id="wiz-enddate">
        </div>
        <div class="wiz-field-row">
          <label>Max Columns</label>
          <input type="number" id="wiz-maxcolumns" min="1" style="width:70px">
          <span class="wiz-hint">Maximum tasks across roster page</span>
        </div>
        <div class="wiz-field-row">
          <label>Session Depth</label>
          <input type="number" id="wiz-sessiondepth" min="1" style="width:70px">
          <span class="wiz-hint">Sessions displayed per task</span>
        </div>
        <div class="wiz-field-row">
          <label>Lead Time (weeks)</label>
          <input type="number" id="wiz-leadtime" min="0" style="width:70px">
          <span class="wiz-hint">Weeks ahead to create sessions</span>
        </div>
        <div class="wiz-field-row">
          <label>Published Lead Time (weeks)</label>
          <input type="number" id="wiz-publishedleadtime" min="0" style="width:70px">
          <span class="wiz-hint">Weeks ahead to publish sessions</span>
        </div>
        <div class="wiz-field-row">
          <label>Auto-extend Tasks</label>
          <input type="checkbox" id="wiz-autoextendtasks">
          <span class="wiz-hint">Auto-generate sessions within lead time</span>
        </div>
        <div id="wiz-step1-status"></div>
      </div>

      <!-- ===== STEP 2: TASKS ===== -->
      <div id="wiz-step-2" class="wiz-step-panel">
        <div class="wiz-section-head">Add Tasks</div>
        <div style="display:flex; gap:1.2rem; flex-wrap:wrap;">
          <div style="flex:1; min-width:280px;">
            <div class="wiz-panel-head">New Task</div>
            <div class="wiz-field-row">
              <label>Name *</label>
              <input type="text" id="wiz-task-name" maxlength="100" style="width:200px">
            </div>
            <div class="wiz-field-row">
              <label>Start Time *</label>
              <input type="time" id="wiz-task-starttime">
            </div>
            <div class="wiz-field-row">
              <label>End Time *</label>
              <input type="time" id="wiz-task-endtime">
            </div>
            <div class="wiz-field-row">
              <label>Task Group</label>
              <input type="number" id="wiz-task-taskgroup" min="1" style="width:55px">
              <span class="wiz-hint">Group number (1,2…)</span>
            </div>
            <div class="wiz-field-row">
              <label>Group Position</label>
              <input type="number" id="wiz-task-groupindex" min="1" style="width:55px">
            </div>
            <div class="wiz-field-row">
              <label>Cells per Row</label>
              <input type="number" id="wiz-task-cellsperrow" min="1" max="6" style="width:55px">
            </div>
            <div class="wiz-section-head" style="margin-top:0.8rem;">Recurrence</div>
            <div class="wiz-radio-row">
              <label><input type="radio" name="wiz-recurrence" value="Once-only" checked> Once-only</label>
              <label><input type="radio" name="wiz-recurrence" value="Daily"> Daily</label>
              <label><input type="radio" name="wiz-recurrence" value="Weekly"> Weekly</label>
              <label><input type="radio" name="wiz-recurrence" value="Monthly"> Monthly</label>
            </div>
            <div id="wiz-rec-daily" class="wiz-rec-panel">
              <div class="wiz-radio-row">
                <label><input type="radio" name="wiz-dailyopt" value="0" checked>
                  Every <input type="number" id="wiz-dailyinterval" min="1" value="1" style="width:50px"> day(s)
                </label>
                <label><input type="radio" name="wiz-dailyopt" value="1"> Every weekday</label>
              </div>
            </div>
            <div id="wiz-rec-weekly" class="wiz-rec-panel">
              <div class="wiz-field-row" style="margin-bottom:0.3rem;">
                <label style="width:auto;text-align:left;">Every</label>
                <input type="number" id="wiz-weeklyinterval" min="1" value="1" style="width:50px">
                <label style="width:auto;text-align:left;margin-left:0.3rem;">week(s) on the:</label>
                <select id="wiz-weeklyindex" style="display:none;margin-left:0.4rem;font-size:0.9rem;padding:0.25rem 0.4rem;border:1px solid #aaa;border-radius:3px;"></select>
              </div>
              <div class="wiz-dow-row">
                <label><input type="checkbox" class="wiz-dow" value="2"> Mon</label>
                <label><input type="checkbox" class="wiz-dow" value="4"> Tue</label>
                <label><input type="checkbox" class="wiz-dow" value="8"> Wed</label>
                <label><input type="checkbox" class="wiz-dow" value="16"> Thu</label>
                <label><input type="checkbox" class="wiz-dow" value="32"> Fri</label>
                <label><input type="checkbox" class="wiz-dow" value="64"> Sat</label>
                <label><input type="checkbox" class="wiz-dow" value="1"> Sun</label>
              </div>
            </div>
            <div id="wiz-rec-monthly" class="wiz-rec-panel">
              <div class="wiz-radio-row" style="flex-direction:column; align-items:flex-start; gap:0.5rem;">
                <label><input type="radio" name="wiz-monthlyopt" value="0" checked>
                  Day <input type="number" id="wiz-monthlydayofmonth" min="1" max="31" value="1" style="width:50px">
                  of every <input type="number" id="wiz-monthlyinterval0" min="1" value="1" style="width:50px"> month(s)
                </label>
                <label><input type="radio" name="wiz-monthlyopt" value="1">
                  The
                  <select id="wiz-monthlywhichdow">
                    <option value="0">first</option><option value="1">second</option>
                    <option value="2">third</option><option value="3">fourth</option><option value="4">last</option>
                  </select>
                  <select id="wiz-monthlydow">
                    <option value="0">day</option><option value="1">weekday</option>
                    <option value="2">weekend day</option><option value="3">Sunday</option>
                    <option value="4">Monday</option><option value="5">Tuesday</option>
                    <option value="6">Wednesday</option><option value="7">Thursday</option>
                    <option value="8">Friday</option><option value="9">Saturday</option>
                  </select>
                  of every <input type="number" id="wiz-monthlyinterval1" min="1" value="1" style="width:50px"> month(s)
                </label>
              </div>
            </div>
            <div style="margin-top:0.8rem;">
              <button type="button" class="wiz-btn wiz-btn-primary" id="wiz-btn-add-task">Add Task</button>
            </div>
            <div id="wiz-step2-status"></div>
          </div>
          <div style="flex:1; min-width:200px;">
            <div class="wiz-panel-head">Tasks Added</div>
            <div id="wiz-tasks-list" class="wiz-task-list">
              <div class="wiz-empty">No tasks yet.</div>
            </div>
          </div>
        </div>
      </div>

      <!-- ===== STEP 3: ROLES ===== -->
      <div id="wiz-step-3" class="wiz-step-panel">
        <div class="wiz-section-head">Assign Roles to Tasks</div>
        <div class="wiz-two-col">
          <div class="wiz-col-left">
            <div class="wiz-panel-head">Tasks</div>
            <div id="wiz-tasks-for-roles" class="wiz-task-list"></div>
          </div>
          <div class="wiz-col-right">
            <div id="wiz-role-panel-head" class="wiz-panel-head">Select a task to assign roles</div>
            <div id="wiz-linked-roles" class="wiz-role-list">
              <div class="wiz-empty">No roles linked yet.</div>
            </div>
            <div id="wiz-role-controls" style="display:none;">
              <div class="wiz-add-row">
                <select id="wiz-role-select"><option value="">— pick a role —</option></select>
                <span class="wiz-qty-lbl">min</span>
                <input type="number" id="wiz-min-qty" class="wiz-qty-input" value="1" min="1">
                <span class="wiz-qty-lbl">max</span>
                <input type="number" id="wiz-max-qty" class="wiz-qty-input" value="1" min="1">
                <button type="button" class="wiz-btn wiz-btn-primary" id="wiz-btn-add-role">Add</button>
              </div>
              <button type="button" class="wiz-btn wiz-btn-secondary" id="wiz-btn-show-create-role" style="font-size:0.85rem;">+ Create New Role</button>
              <div id="wiz-create-role-form" class="wiz-inline-form">
                <div class="wiz-field-row">
                  <label style="width:120px;">Name *</label>
                  <input type="text" id="wiz-new-role-name" maxlength="64" style="width:180px">
                </div>
                <div class="wiz-field-row">
                  <label style="width:120px;">Roster cell text</label>
                  <input type="text" id="wiz-new-role-cellname" maxlength="64" style="width:180px">
                </div>
                <div class="wiz-field-row">
                  <label style="width:120px;">Cell position</label>
                  <input type="number" id="wiz-new-role-index" style="width:60px">
                </div>
                <div style="display:flex; gap:0.5rem; margin-top:0.5rem;">
                  <button type="button" class="wiz-btn wiz-btn-success" id="wiz-btn-do-create-role">Create Role</button>
                  <button type="button" class="wiz-btn wiz-btn-secondary" id="wiz-btn-cancel-create-role">Cancel</button>
                </div>
                <div class="wiz-note">Page actions for new roles must be set up in Role Admin after the wizard.</div>
              </div>
            </div>
            <div id="wiz-step3-status"></div>
          </div>
        </div>
      </div>

      <!-- ===== STEP 4: ALERTS ===== -->
      <div id="wiz-step-4" class="wiz-step-panel">
        <div class="wiz-section-head">Booking Alerts <span class="wiz-skip-link" id="wiz-skip-4">(skip this step)</span></div>
        <div class="wiz-two-col">
          <div class="wiz-col-left">
            <div class="wiz-panel-head">Tasks</div>
            <div id="wiz-tasks-for-alerts" class="wiz-task-list"></div>
          </div>
          <div class="wiz-col-right">
            <div id="wiz-alert-panel-head" class="wiz-panel-head">Select a task to manage alerts</div>
            <div id="wiz-alerts-container">
              <div class="wiz-empty">No task selected.</div>
            </div>
            <div id="wiz-step4-status"></div>
          </div>
        </div>
      </div>

      <!-- ===== STEP 5: USERS ===== -->
      <div id="wiz-step-5" class="wiz-step-panel">
        <div class="wiz-section-head">Assign Users to Roles <span class="wiz-skip-link" id="wiz-skip-5">(skip this step)</span></div>
        <p class="wiz-note">Check each user who should be assigned to a role. Users with a role can be booked for that role in sessions.</p>
        <div id="wiz-users-grid" class="wiz-users-grid">
          <div class="wiz-empty">Loading…</div>
        </div>
        <div id="wiz-step5-status"></div>
      </div>

      <!-- ===== STEP 6: SESSIONS ===== -->
      <div id="wiz-step-6" class="wiz-step-panel">
        <div class="wiz-section-head">Build Sessions</div>
        <p>Click <strong>Build Sessions</strong> to generate sessions for all tasks based on their recurrence rules and the roster's lead time settings.</p>
        <p class="wiz-note">Sessions are created from each task's start date out to the lead time weeks you configured. You can re-run "Build Sessions" from the task admin page at any time.</p>
        <div style="margin: 1rem 0;">
          <button type="button" class="wiz-btn wiz-btn-success" id="wiz-btn-build-sessions">Build Sessions</button>
          <span class="wiz-skip-link" id="wiz-skip-6">(skip — do later)</span>
        </div>
        <div id="wiz-build-result"></div>
        <div id="wiz-step6-status"></div>
      </div>

    </div><!-- #wiz-body -->

    <div id="wiz-footer">
      <button type="button" class="wiz-btn wiz-btn-secondary" id="wiz-btn-back">&#8592; Back</button>
      <div class="wiz-nav-right">
        <span id="wiz-step-indicator" style="font-size:0.85rem;color:#777;">Step 1 of 6</span>
        <button type="button" class="wiz-btn wiz-btn-primary" id="wiz-btn-next">Next &#8594;</button>
      </div>
    </div>
  </div><!-- #wiz-panel -->
</div><!-- #wiz-overlay -->
HTML;
    }

    public static function renderscript(): string {
        return <<<'JS'
(function() {
"use strict";

// ── State ────────────────────────────────────────────────────────────────────
const wiz = {
    step: 1,
    total: 6,
    rosterId: null,
    pageNumber: null,
    rosterName: '',
    isEditing: false,
    tasks: [],        // [{id, name, recurrence, roles:[{id,role_id,role_name,min_quantity,max_quantity,alerts:[{id,period,level}]}]}]
    allRoles: [],     // [{id,name}]
    allUsers: [],     // [{id,name}]
    userRoles: {},    // {role_id:[user_id,...]}
    selectedTaskId: null,
    initLoaded: false,
};

// ── Utility ──────────────────────────────────────────────────────────────────
function showStatus(elId, msg, type='info') {
    const el = document.getElementById(elId);
    if (!el) return;
    el.className = 'wiz-status ' + type;
    el.textContent = msg;
    el.style.display = msg ? 'block' : 'none';
}
function clearStatus(elId) { showStatus(elId, '', 'info'); }

async function wizAjax(action, data) {
    const raw = await doServerRequest('', JSON.stringify(data), action);
    return JSON.parse(raw);
}

function escHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Open / Close ─────────────────────────────────────────────────────────────
// Prevent Enter key inside the wizard from submitting the outer CRUD form
document.getElementById('wiz-overlay').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault();
    }
});

document.getElementById('wiz-close').addEventListener('click', wizClose);
function wizClose() {
    if (wiz.rosterId && !wiz.isEditing) {
        if (!confirm('The roster has been partially created. Close the wizard?\n(The roster will remain and can be edited via the normal admin forms.)')) return;
    }
    document.getElementById('wiz-overlay').style.display = 'none';
    resetWizard();
}

function resetWizard() {
    wiz.step = 1; wiz.rosterId = null; wiz.pageNumber = null; wiz.rosterName = ''; wiz.isEditing = false;
    wiz.tasks = []; wiz.selectedTaskId = null; wiz.initLoaded = false;
    updateStepUI(1);
    ['wiz-step1-status','wiz-step2-status','wiz-step3-status',
     'wiz-step4-status','wiz-step5-status','wiz-step6-status',
     'wiz-build-result'].forEach(clearStatus);
    document.getElementById('wiz-tasks-list').innerHTML = '<div class="wiz-empty">No tasks yet.</div>';
    document.getElementById('wiz-name').value = '';
}

window.openRosterWizard = async function(rosterId = null, rosterName = '') {
    resetWizard();
    document.getElementById('wiz-overlay').style.display = 'block';
    // The form framework disables all inputs inside #editarea on page load;
    // #wiz-overlay.nondatainput exempts them, but re-enable here as belt-and-suspenders
    jQuery('#wiz-overlay input, #wiz-overlay select, #wiz-overlay textarea, #wiz-overlay button')
        .prop('disabled', false);
    if (rosterId) {
        wiz.isEditing = true;
        wiz.rosterId  = rosterId;
        wiz.rosterName = rosterName;
        document.getElementById('wiz-name').value = rosterName;
        showStatus('wiz-step1-status', 'Loading roster data…', 'info');
        try {
            const res = await wizAjax('wizard_get_full_data', { roster_id: rosterId });
            if (res.success) {
                const r = res.roster;
                document.getElementById('wiz-name').value             = r.name              || '';
                document.getElementById('wiz-startdate').value        = r.startdate         || '';
                document.getElementById('wiz-enddate').value          = r.enddate           || '';
                document.getElementById('wiz-maxcolumns').value       = r.maxcolumns        || '';
                document.getElementById('wiz-sessiondepth').value     = r.sessiondepth      || '';
                document.getElementById('wiz-leadtime').value         = r.leadtime          || '';
                document.getElementById('wiz-publishedleadtime').value= r.publishedleadtime || '';
                document.getElementById('wiz-autoextendtasks').checked= !!r.autoextendtasks;
                wiz.pageNumber = r.page_number;
                wiz.rosterName = r.name;
                wiz.tasks = res.tasks || [];
                renderTasksList();
                showStatus('wiz-step1-status', 'Editing existing roster — page ' + r.page_number + '.', 'ok');
            } else {
                showStatus('wiz-step1-status', res.error || 'Could not load roster data.', 'err');
            }
        } catch(e) {
            showStatus('wiz-step1-status', 'Server error: ' + e, 'err');
        }
    }
    document.getElementById('wiz-name').focus();
};

// ── Step tabs (click to jump back to completed steps) ────────────────────────
document.querySelectorAll('.wiz-step-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        const s = parseInt(this.dataset.step);
        if (this.classList.contains('done') || this.classList.contains('active')) {
            wizGoToStep(s);
        }
    });
});

// ── Navigation ───────────────────────────────────────────────────────────────
document.getElementById('wiz-btn-back').addEventListener('click', () => {
    if (wiz.step > 1) wizGoToStep(wiz.step - 1);
});
document.getElementById('wiz-btn-next').addEventListener('click', () => {
    wizNextStep();
});
document.getElementById('wiz-skip-4').addEventListener('click', () => wizGoToStep(5));
document.getElementById('wiz-skip-5').addEventListener('click', () => wizGoToStep(6));
document.getElementById('wiz-skip-6').addEventListener('click', () => {
    if (wiz.rosterId) { wizDone(); }
});

async function wizNextStep() {
    if (wiz.step === 1) {
        if (!wiz.rosterId) {
            const ok = await wizStep1Save();
            if (!ok) return;
        }
        wizGoToStep(2);
    } else if (wiz.step === 2) {
        if (wiz.tasks.length === 0) {
            showStatus('wiz-step2-status', 'Please add at least one task before continuing.', 'err');
            return;
        }
        wizGoToStep(3);
    } else if (wiz.step === 3) {
        wizGoToStep(4);
    } else if (wiz.step === 4) {
        wizGoToStep(5);
    } else if (wiz.step === 5) {
        wizGoToStep(6);
    } else if (wiz.step === 6) {
        wizDone();
    }
}

function wizDone() {
    const verb    = wiz.isEditing ? 'updated' : 'created';
    const verbCap = verb.charAt(0).toUpperCase() + verb.slice(1);
    jQuery.volsdialog(
        'OKMSG',
        '<p>Roster &ldquo;' + escHtml(wiz.rosterName || '') + '&rdquo; has been ' + verb
            + ' (page&nbsp;' + (wiz.pageNumber || '?') + ').</p>',
        function() { location.reload(); },
        null,
        'Roster ' + verbCap
    );
}

function wizGoToStep(n) {
    wiz.step = n;
    updateStepUI(n);
    if (n === 3) renderStep3();
    if (n === 4) renderStep4();
    if (n === 5) renderStep5();   // async, updates DOM when ready
    if (n === 6) { clearStatus('wiz-step6-status'); clearStatus('wiz-build-result'); }
}

function updateStepUI(step) {
    document.querySelectorAll('.wiz-step-panel').forEach(p => p.classList.remove('active'));
    const panel = document.getElementById('wiz-step-' + step);
    if (panel) panel.classList.add('active');
    document.querySelectorAll('.wiz-step-tab').forEach(tab => {
        const s = parseInt(tab.dataset.step);
        tab.classList.remove('active','done');
        if (s === step) tab.classList.add('active');
        else if (s < step) tab.classList.add('done');
    });
    document.getElementById('wiz-step-indicator').textContent = 'Step ' + step + ' of ' + wiz.total;
    document.getElementById('wiz-btn-back').disabled = (step === 1);
    const nextBtn = document.getElementById('wiz-btn-next');
    nextBtn.textContent = step === wiz.total ? 'Done ✓' : 'Next →';
}

// ── Step 1: Create Roster ────────────────────────────────────────────────────
async function wizStep1Save() {
    const name = document.getElementById('wiz-name').value.trim();
    if (!name) { showStatus('wiz-step1-status','Roster name is required.','err'); return false; }
    clearStatus('wiz-step1-status');
    try {
        const res = await wizAjax('wizard_create_roster', {
            name,
            startdate:          document.getElementById('wiz-startdate').value,
            enddate:            document.getElementById('wiz-enddate').value,
            maxcolumns:         document.getElementById('wiz-maxcolumns').value,
            sessiondepth:       document.getElementById('wiz-sessiondepth').value,
            leadtime:           document.getElementById('wiz-leadtime').value,
            publishedleadtime:  document.getElementById('wiz-publishedleadtime').value,
            autoextendtasks:    document.getElementById('wiz-autoextendtasks').checked ? 1 : 0,
        });
        if (!res.success) { showStatus('wiz-step1-status', res.error || 'Failed to create roster.','err'); return false; }
        wiz.rosterId    = res.roster_id;
        wiz.pageNumber  = res.page_number;
        wiz.rosterName  = name;
        showStatus('wiz-step1-status', 'Roster created — page number ' + res.page_number + '.', 'ok');
        return true;
    } catch(e) { showStatus('wiz-step1-status', 'Server error: ' + e,'err'); return false; }
}

// ── Step 2: Tasks ────────────────────────────────────────────────────────────
jQuery("input[name='wiz-recurrence']").on('change', function() {
    document.querySelectorAll('.wiz-rec-panel').forEach(p => p.classList.remove('active'));
    const val = this.value;
    if (val === 'Daily')   document.getElementById('wiz-rec-daily').classList.add('active');
    if (val === 'Weekly')  document.getElementById('wiz-rec-weekly').classList.add('active');
    if (val === 'Monthly') document.getElementById('wiz-rec-monthly').classList.add('active');
});

function updateWizWeeklyIndex(curVal) {
    const interval = parseInt(document.getElementById('wiz-weeklyinterval').value) || 1;
    const sel = document.getElementById('wiz-weeklyindex');
    const prev = (curVal !== undefined) ? curVal : (parseInt(sel.value) || 0);
    if (interval > 1) {
        const words = ['First','Second','Third','Fourth','Fifth','Sixth','Seventh','Eighth'];
        let html = '';
        for (let i = 0; i < interval; i++) {
            const label = words[i] || (i + 1) + 'th';
            html += `<option value="${i}"${i === prev ? ' selected' : ''}>${label}</option>`;
        }
        sel.innerHTML = html;
        sel.style.display = '';
    } else {
        sel.innerHTML = '';
        sel.style.display = 'none';
    }
}
document.getElementById('wiz-weeklyinterval').addEventListener('change', () => updateWizWeeklyIndex());

document.getElementById('wiz-btn-add-task').addEventListener('click', async () => {
    const name = document.getElementById('wiz-task-name').value.trim();
    if (!name) { showStatus('wiz-step2-status','Task name is required.','err'); return; }
    const starttime = document.getElementById('wiz-task-starttime').value;
    if (!starttime) { showStatus('wiz-step2-status','Start time is required — e.g. 9:00 AM.','err'); return; }
    const endtime = document.getElementById('wiz-task-endtime').value;
    if (!endtime) { showStatus('wiz-step2-status','End time is required — e.g. 5:00 PM.','err'); return; }
    clearStatus('wiz-step2-status');

    const recurrence = document.querySelector('input[name="wiz-recurrence"]:checked')?.value || 'Once-only';
    let weeklydow = 0;
    document.querySelectorAll('.wiz-dow:checked').forEach(cb => { weeklydow += parseInt(cb.value); });

    const taskData = {
        roster_id:         wiz.rosterId,
        name,
        starttime:         document.getElementById('wiz-task-starttime').value,
        endtime:           document.getElementById('wiz-task-endtime').value,
        taskgroup:         document.getElementById('wiz-task-taskgroup').value,
        groupindex:        document.getElementById('wiz-task-groupindex').value,
        cellsperrow:       document.getElementById('wiz-task-cellsperrow').value,
        recurrence,
        dailyoption:       document.querySelector('input[name="wiz-dailyopt"]:checked')?.value ?? '0',
        dailyinterval:     document.getElementById('wiz-dailyinterval').value,
        weeklyinterval:    document.getElementById('wiz-weeklyinterval').value,
        weeklydow:         weeklydow,
        weeklyindex:       parseInt(document.getElementById('wiz-weeklyindex').value) || 0,
        monthlyoption:     document.querySelector('input[name="wiz-monthlyopt"]:checked')?.value ?? '0',
        monthlydayofmonth: document.getElementById('wiz-monthlydayofmonth').value,
        monthlyinterval0:  document.getElementById('wiz-monthlyinterval0').value,
        monthlywhichdow:   document.getElementById('wiz-monthlywhichdow').value,
        monthlydow:        document.getElementById('wiz-monthlydow').value,
        monthlyinterval1:  document.getElementById('wiz-monthlyinterval1').value,
    };
    try {
        const res = await wizAjax('wizard_add_task', taskData);
        if (!res.success) { showStatus('wiz-step2-status', res.error || 'Failed to add task.','err'); return; }
        wiz.tasks.push({ id: res.task_id, name: res.task_name, recurrence, roles: [] });
        renderTasksList();
        // Reset task name
        document.getElementById('wiz-task-name').value = '';
        showStatus('wiz-step2-status','Task "' + res.task_name + '" added.','ok');
    } catch(e) { showStatus('wiz-step2-status','Server error: ' + e,'err'); }
});

function renderTasksList() {
    const el = document.getElementById('wiz-tasks-list');
    if (!wiz.tasks.length) { el.innerHTML = '<div class="wiz-empty">No tasks yet.</div>'; return; }
    el.innerHTML = wiz.tasks.map(t =>
        `<div class="wiz-list-item">
            <span class="wiz-item-name">${escHtml(t.name)}</span>
            <small style="color:#888">${escHtml(t.recurrence)}</small>
            <button class="wiz-btn-trash" data-tid="${t.id}" title="Remove task">&#x1F5D1;</button>
         </div>`
    ).join('');
    el.querySelectorAll('.wiz-btn-trash').forEach(btn => {
        btn.addEventListener('click', async function() {
            const tid = parseInt(this.dataset.tid);
            if (!confirm('Remove this task?')) return;
            try {
                const res = await wizAjax('wizard_remove_task', { task_id: tid });
                if (!res.success) { alert(res.error || 'Could not remove task.'); return; }
                wiz.tasks = wiz.tasks.filter(t => t.id !== tid);
                renderTasksList();
            } catch(e) { alert('Server error: ' + e); }
        });
    });
}

// ── Step 3: Roles ────────────────────────────────────────────────────────────
function renderStep3() {
    ensureInitData().then(() => {
        const leftEl = document.getElementById('wiz-tasks-for-roles');
        leftEl.innerHTML = wiz.tasks.map(t =>
            `<div class="wiz-list-item${t.id === wiz.selectedTaskId ? ' selected' : ''}" data-tid="${t.id}">
                <span class="wiz-item-name">${escHtml(t.name)}</span>
             </div>`
        ).join('') || '<div class="wiz-empty">No tasks.</div>';
        leftEl.querySelectorAll('.wiz-list-item').forEach(row => {
            row.addEventListener('click', function() {
                leftEl.querySelectorAll('.wiz-list-item').forEach(r => r.classList.remove('selected'));
                this.classList.add('selected');
                wiz.selectedTaskId = parseInt(this.dataset.tid);
                renderLinkedRoles();
            });
        });
        if (wiz.selectedTaskId) renderLinkedRoles();
    });
}

function renderLinkedRoles() {
    const task = wiz.tasks.find(t => t.id === wiz.selectedTaskId);
    if (!task) return;
    document.getElementById('wiz-role-panel-head').textContent = 'Roles for: ' + task.name;
    document.getElementById('wiz-role-controls').style.display = 'block';

    // Build role dropdown (exclude already linked)
    const linkedIds = new Set(task.roles.map(r => r.role_id));
    const select = document.getElementById('wiz-role-select');
    select.innerHTML = '<option value="">— pick a role —</option>' +
        wiz.allRoles.filter(r => !linkedIds.has(r.id)).map(r =>
            `<option value="${r.id}">${escHtml(r.name)}</option>`
        ).join('');

    // Render linked roles list
    const el = document.getElementById('wiz-linked-roles');
    if (!task.roles.length) { el.innerHTML = '<div class="wiz-empty">No roles linked yet.</div>'; return; }
    el.innerHTML = task.roles.map(r =>
        `<div class="wiz-list-item">
            <span class="wiz-item-name">${escHtml(r.role_name)}</span>
            <small style="color:#777">min ${r.min_quantity} / max ${r.max_quantity}</small>
            <button class="wiz-btn-trash" data-trid="${r.id}" title="Remove role">&#x1F5D1;</button>
         </div>`
    ).join('');
    el.querySelectorAll('.wiz-btn-trash').forEach(btn => {
        btn.addEventListener('click', async function() {
            const trid = parseInt(this.dataset.trid);
            try {
                const res = await wizAjax('wizard_remove_task_role', { task_role_id: trid });
                if (!res.success) { alert(res.error || 'Could not remove role.'); return; }
                task.roles = task.roles.filter(r => r.id !== trid);
                renderLinkedRoles();
            } catch(e) { alert('Server error: ' + e); }
        });
    });
}

document.getElementById('wiz-btn-add-role').addEventListener('click', async () => {
    const role_id = parseInt(document.getElementById('wiz-role-select').value);
    if (!role_id || !wiz.selectedTaskId) { showStatus('wiz-step3-status','Select a role.','err'); return; }
    clearStatus('wiz-step3-status');
    try {
        const res = await wizAjax('wizard_add_task_role', {
            task_id:      wiz.selectedTaskId,
            role_id,
            min_quantity: document.getElementById('wiz-min-qty').value,
            max_quantity: document.getElementById('wiz-max-qty').value,
        });
        if (!res.success) { showStatus('wiz-step3-status', res.error || 'Failed.','err'); return; }
        const task = wiz.tasks.find(t => t.id === wiz.selectedTaskId);
        task.roles.push({ id: res.task_role_id, role_id: res.role_id, role_name: res.role_name,
                          min_quantity: res.min_quantity, max_quantity: res.max_quantity, alerts: [] });
        renderLinkedRoles();
    } catch(e) { showStatus('wiz-step3-status','Server error: ' + e,'err'); }
});

document.getElementById('wiz-btn-show-create-role').addEventListener('click', () => {
    document.getElementById('wiz-create-role-form').classList.add('active');
});
document.getElementById('wiz-btn-cancel-create-role').addEventListener('click', () => {
    document.getElementById('wiz-create-role-form').classList.remove('active');
});
document.getElementById('wiz-btn-do-create-role').addEventListener('click', async () => {
    const name = document.getElementById('wiz-new-role-name').value.trim();
    if (!name) { alert('Role name is required.'); return; }
    try {
        const res = await wizAjax('wizard_create_role', {
            name,
            cellname:    document.getElementById('wiz-new-role-cellname').value.trim(),
            rosterindex: document.getElementById('wiz-new-role-index').value,
        });
        if (!res.success) { alert(res.error || 'Failed to create role.'); return; }
        wiz.allRoles.push({ id: res.role_id, name: res.role_name });
        wiz.allRoles.sort((a,b) => a.name.localeCompare(b.name));
        document.getElementById('wiz-create-role-form').classList.remove('active');
        document.getElementById('wiz-new-role-name').value = '';
        document.getElementById('wiz-new-role-cellname').value = '';
        document.getElementById('wiz-new-role-index').value = '';
        renderLinkedRoles();
        showStatus('wiz-step3-status','Role "' + res.role_name + '" created.','ok');
    } catch(e) { alert('Server error: ' + e); }
});

// ── Step 4: Alerts ────────────────────────────────────────────────────────────
function renderStep4() {
    const leftEl = document.getElementById('wiz-tasks-for-alerts');
    leftEl.innerHTML = wiz.tasks.map(t =>
        `<div class="wiz-list-item${t.id === wiz.selectedTaskId ? ' selected' : ''}" data-tid="${t.id}">
            <span class="wiz-item-name">${escHtml(t.name)}</span>
         </div>`
    ).join('') || '<div class="wiz-empty">No tasks.</div>';
    leftEl.querySelectorAll('.wiz-list-item').forEach(row => {
        row.addEventListener('click', function() {
            leftEl.querySelectorAll('.wiz-list-item').forEach(r => r.classList.remove('selected'));
            this.classList.add('selected');
            wiz.selectedTaskId = parseInt(this.dataset.tid);
            renderAlertsForTask();
        });
    });
    if (wiz.selectedTaskId) renderAlertsForTask();
}

function renderAlertsForTask() {
    const task = wiz.tasks.find(t => t.id === wiz.selectedTaskId);
    if (!task) return;
    document.getElementById('wiz-alert-panel-head').textContent = 'Alerts for: ' + task.name;
    const container = document.getElementById('wiz-alerts-container');
    if (!task.roles.length) {
        container.innerHTML = '<div class="wiz-empty">No roles linked to this task.</div>';
        return;
    }
    container.innerHTML = task.roles.map(tr => `
        <div class="wiz-role-group">
            <div class="wiz-role-group-head">${escHtml(tr.role_name)}</div>
            <div id="wiz-alerts-${tr.id}">
                ${renderAlertRows(tr)}
            </div>
            <div class="wiz-add-row" style="margin-top:0.3rem;">
                <span style="font-size:0.85rem;color:#555;">Send alert if fewer than</span>
                <input type="number" id="wiz-new-alert-lev-${tr.id}" min="1" value="1" style="width:50px" placeholder="n">
                <span style="font-size:0.85rem;color:#555;">bookings</span>
                <input type="number" id="wiz-new-alert-per-${tr.id}" min="1" value="7" style="width:55px" placeholder="days">
                <span style="font-size:0.85rem;color:#555;">days before</span>
                <button type="button" class="wiz-btn wiz-btn-primary" style="padding:0.25rem 0.7rem;font-size:0.85rem;"
                    onclick="wizAddAlert(${tr.id})">+ Alert</button>
            </div>
        </div>`
    ).join('');
}

function renderAlertRows(tr) {
    if (!tr.alerts || !tr.alerts.length) return '';
    return tr.alerts.map(a =>
        `<div class="wiz-alert-row" id="wiz-alert-row-${a.id}">
            <span>Fewer than <strong>${a.level}</strong> bookings <strong>${a.period}</strong> days before</span>
            <button class="wiz-btn-trash" onclick="wizRemoveAlert(${a.id},${tr.id})" title="Remove alert">&#x1F5D1;</button>
         </div>`
    ).join('');
}

window.wizAddAlert = async function(task_role_id) {
    const level  = document.getElementById('wiz-new-alert-lev-' + task_role_id).value;
    const period = document.getElementById('wiz-new-alert-per-' + task_role_id).value;
    if (!level || !period) { showStatus('wiz-step4-status','Level and period are required.','err'); return; }
    clearStatus('wiz-step4-status');
    try {
        const res = await wizAjax('wizard_save_alert', { task_role_id, level, period });
        if (!res.success) { showStatus('wiz-step4-status', res.error || 'Failed.','err'); return; }
        // Add to state
        for (const task of wiz.tasks) {
            const tr = task.roles.find(r => r.id === task_role_id);
            if (tr) { tr.alerts = tr.alerts || []; tr.alerts.push({ id: res.alert_id, level, period }); break; }
        }
        renderAlertsForTask();
        showStatus('wiz-step4-status','Alert added.','ok');
    } catch(e) { showStatus('wiz-step4-status','Server error: ' + e,'err'); }
};

window.wizRemoveAlert = async function(alert_id, task_role_id) {
    try {
        const res = await wizAjax('wizard_remove_alert', { alert_id });
        if (!res.success) { alert(res.error || 'Could not remove alert.'); return; }
        for (const task of wiz.tasks) {
            const tr = task.roles.find(r => r.id === task_role_id);
            if (tr) { tr.alerts = tr.alerts.filter(a => a.id !== alert_id); break; }
        }
        renderAlertsForTask();
    } catch(e) { alert('Server error: ' + e); }
};

// ── Step 5: Users ─────────────────────────────────────────────────────────────
async function renderStep5() {
    await ensureInitData();
    const allRolesInRoster = [];
    for (const task of wiz.tasks) {
        for (const tr of task.roles) {
            if (!allRolesInRoster.find(r => r.role_id === tr.role_id)) {
                allRolesInRoster.push({ role_id: tr.role_id, role_name: tr.role_name });
            }
        }
    }
    if (!allRolesInRoster.length) {
        document.getElementById('wiz-users-grid').innerHTML = '<div class="wiz-empty">No roles have been linked to tasks yet.</div>';
        return;
    }
    const users = wiz.allUsers;
    let html = '<table><thead><tr><th class="role-col">User</th>';
    allRolesInRoster.forEach(r => { html += `<th>${escHtml(r.role_name)}</th>`; });
    html += '</tr></thead><tbody>';
    users.forEach(u => {
        html += `<tr><td class="role-col">${escHtml(u.name)}</td>`;
        allRolesInRoster.forEach(r => {
            const assigned = wiz.userRoles[r.role_id] || [];
            const checked = assigned.includes(u.id) ? 'checked' : '';
            html += `<td><input type="checkbox" class="wiz-user-role-cb" ${checked}
                data-uid="${u.id}" data-rid="${r.role_id}"></td>`;
        });
        html += '</tr>';
    });
    html += '</tbody></table>';
    document.getElementById('wiz-users-grid').innerHTML = html;
    document.querySelectorAll('.wiz-user-role-cb').forEach(cb => {
        cb.addEventListener('change', async function() {
            const uid = parseInt(this.dataset.uid);
            const rid = parseInt(this.dataset.rid);
            const action = this.checked ? 'wizard_assign_user' : 'wizard_remove_user_role';
            try {
                const res = await wizAjax(action, { user_id: uid, role_id: rid });
                if (!res.success) {
                    this.checked = !this.checked; // revert
                    showStatus('wiz-step5-status', res.error || 'Failed.','err');
                    return;
                }
                if (!wiz.userRoles[rid]) wiz.userRoles[rid] = [];
                if (this.checked) {
                    if (!wiz.userRoles[rid].includes(uid)) wiz.userRoles[rid].push(uid);
                } else {
                    wiz.userRoles[rid] = wiz.userRoles[rid].filter(id => id !== uid);
                }
            } catch(e) {
                this.checked = !this.checked;
                showStatus('wiz-step5-status','Server error: ' + e,'err');
            }
        });
    });
}

// ── Step 6: Build Sessions ────────────────────────────────────────────────────
document.getElementById('wiz-btn-build-sessions').addEventListener('click', async () => {
    clearStatus('wiz-build-result');
    clearStatus('wiz-step6-status');
    try {
        const res = await wizAjax('wizard_build_sessions', { roster_id: wiz.rosterId });
        if (!res.success) { showStatus('wiz-step6-status', res.error || 'Failed.','err'); return; }
        const msg = 'Sessions built for ' + res.tasks_processed + ' task(s). Use the Task admin page to review or extend further.';
        showStatus('wiz-build-result', msg, 'ok');
    } catch(e) { showStatus('wiz-step6-status','Server error: ' + e,'err'); }
});

// ── Init data load ────────────────────────────────────────────────────────────
async function ensureInitData() {
    if (wiz.initLoaded) return;
    try {
        const res = await wizAjax('wizard_get_init_data', { roster_id: wiz.rosterId || 0 });
        if (res.success) {
            wiz.allRoles = res.all_roles || [];
            wiz.allUsers = res.all_users || [];
            wiz.userRoles = {};
            (res.all_user_roles || []).forEach(ur => {
                const rid = ur.role_id;
                if (!wiz.userRoles[rid]) wiz.userRoles[rid] = [];
                wiz.userRoles[rid].push(parseInt(ur.user_id));
            });
            wiz.initLoaded = true;
        }
    } catch(e) { console.error('Could not load init data:', e); }
}

})();
JS;
    }
}
