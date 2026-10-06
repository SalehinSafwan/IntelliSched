@extends('layouts.app')

@section('title', 'Schedule Generation Wizard')

@section('content')

<div class="mb-4">
    <a href="{{ route('schedules.index') }}" class="text-muted text-decoration-none" style="font-size: 0.88rem;">
        <i class="bi bi-arrow-left me-1"></i> Back to Schedule Engine
    </a>
    <h2 class="brand-font fw-bold text-white mt-2 mb-1">IntelliSched CP-SAT Routine Generator</h2>
    <p class="text-muted" style="font-size: 0.88rem;">Automated routine optimization wizard leveraging Google OR-Tools CP-SAT integer linear programming.</p>
</div>

<!-- Step Navigation Header -->
<div class="wizard-steps">
    <div class="wizard-step-item active" id="stepIndicator1">
        <div class="step-num">1</div>
        <div class="step-label">Select Target Batch</div>
    </div>
    <div class="wizard-step-item" id="stepIndicator2">
        <div class="step-num">2</div>
        <div class="step-label">Review Input Data</div>
    </div>
    <div class="wizard-step-item" id="stepIndicator3">
        <div class="step-num">3</div>
        <div class="step-label">Optimization Profile</div>
    </div>
    <div class="wizard-step-item" id="stepIndicator4">
        <div class="step-num">4</div>
        <div class="step-label">Generate & Solver</div>
    </div>
</div>

<!-- Wizard Step 1: Select Batch & Sections -->
<div class="glass-card mb-4" id="stepContent1">
    <h4 class="brand-font fw-bold text-white mb-3"><i class="bi bi-1-circle-fill text-indigo me-2"></i> Step 1 of 4 — Target Selection</h4>
    
    <div class="row g-4 mb-4">
        <div class="col-12 col-md-6">
            <label class="form-label-custom">Academic Term <span class="text-danger">*</span></label>
            <select id="termSelect" class="form-select form-select-custom">
                <option value="Fall 2026" selected>Fall 2026 (Active Term)</option>
                <option value="Spring 2027">Spring 2027</option>
            </select>
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label-custom">Target Academic Batch <span class="text-danger">*</span></label>
            <select id="batchSelect" class="form-select form-select-custom">
                <option value="CSE 47" selected>CSE 47th Batch (6th Semester)</option>
                <option value="CSE 48">CSE 48th Batch (5th Semester)</option>
                <option value="CSE 49">CSE 49th Batch (4th Semester)</option>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label-custom mb-2">Select Included Sections <span class="text-danger">*</span></label>
            <div class="d-flex flex-wrap gap-3">
                <label class="d-flex align-items-center gap-2 p-3 rounded cursor-pointer" style="background: rgba(99, 102, 241, 0.15); border: 1px solid rgba(99, 102, 241, 0.3); min-width: 180px;">
                    <input type="checkbox" checked style="width: 18px; height: 18px; accent-color: var(--primary);">
                    <div>
                        <div class="fw-bold text-white">Section A</div>
                        <div class="text-muted" style="font-size: 0.75rem;">60 Students Enrolled</div>
                    </div>
                </label>

                <label class="d-flex align-items-center gap-2 p-3 rounded cursor-pointer" style="background: rgba(99, 102, 241, 0.15); border: 1px solid rgba(99, 102, 241, 0.3); min-width: 180px;">
                    <input type="checkbox" checked style="width: 18px; height: 18px; accent-color: var(--primary);">
                    <div>
                        <div class="fw-bold text-white">Section B</div>
                        <div class="text-muted" style="font-size: 0.75rem;">60 Students Enrolled</div>
                    </div>
                </label>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end">
        <button class="btn-indigo" onclick="goToStep(2)">
            Next Step: Review Data <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </div>
</div>

<!-- Wizard Step 2: Review Input -->
<div class="glass-card mb-4 d-none" id="stepContent2">
    <h4 class="brand-font fw-bold text-white mb-3"><i class="bi bi-2-circle-fill text-indigo me-2"></i> Step 2 of 4 — Input Readiness Verification</h4>
    
    <div class="row g-4 mb-4">
        <div class="col-12 col-md-6">
            <div class="p-3 rounded" style="background: rgba(15, 20, 35, 0.6); border: 1px solid var(--border-color);">
                <h6 class="fw-bold text-white mb-3">Academic Parameters Summary</h6>
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary" style="font-size: 0.88rem;">
                    <span class="text-muted">Enrolled Courses</span>
                    <span class="fw-bold text-white">8 Courses (5 Theory, 3 Lab)</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary" style="font-size: 0.88rem;">
                    <span class="text-muted">Active Sections</span>
                    <span class="fw-bold text-white">2 Sections (A & B)</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary" style="font-size: 0.88rem;">
                    <span class="text-muted">Qualified Teachers</span>
                    <span class="fw-bold text-white">12 Available Faculty</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary" style="font-size: 0.88rem;">
                    <span class="text-muted">Eligible Rooms</span>
                    <span class="fw-bold text-white">7 Rooms (5 Theory, 2 Lab)</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom border-secondary" style="font-size: 0.88rem;">
                    <span class="text-muted">Weekly Time Slots</span>
                    <span class="fw-bold text-white">45 Available Slots</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="p-3 rounded" style="background: rgba(15, 20, 35, 0.6); border: 1px solid var(--border-color);">
                <h6 class="fw-bold text-white mb-3">Integrity & Constraint Verification</h6>
                <div class="d-flex align-items-center justify-content-between py-2 border-bottom border-secondary">
                    <span class="text-muted" style="font-size: 0.88rem;">Teacher Availability Grid</span>
                    <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i> Verified Ready</span>
                </div>
                <div class="d-flex align-items-center justify-content-between py-2 border-bottom border-secondary">
                    <span class="text-muted" style="font-size: 0.88rem;">Room Capacity & Eligibility</span>
                    <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i> Verified Ready</span>
                </div>
                <div class="d-flex align-items-center justify-content-between py-2 border-bottom border-secondary">
                    <span class="text-muted" style="font-size: 0.88rem;">Course Credit Hours</span>
                    <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i> Verified Ready</span>
                </div>
                <div class="d-flex align-items-center justify-content-between py-2 border-bottom border-secondary">
                    <span class="text-muted" style="font-size: 0.88rem;">Teacher Suitability Matrix</span>
                    <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i> 100% Complete</span>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <button class="btn-glass" onclick="goToStep(1)"><i class="bi bi-arrow-left me-1"></i> Back</button>
        <button class="btn-indigo" onclick="goToStep(3)">Next Step: Optimization Profile <i class="bi bi-arrow-right ms-1"></i></button>
    </div>
</div>

<!-- Wizard Step 3: Scheduling Optimization Preferences -->
<div class="glass-card mb-4 d-none" id="stepContent3">
    <h4 class="brand-font fw-bold text-white mb-2"><i class="bi bi-3-circle-fill text-indigo me-2"></i> Step 3 of 4 — Soft Constraint Optimization Profile</h4>
    <p class="text-muted mb-4" style="font-size: 0.85rem;">Select an optimization profile or fine-tune individual weight priorities for the scoring engine.</p>

    <!-- Preset Profiles -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-3">
            <label class="glass-card card-hoverable p-3 h-100 text-center cursor-pointer border-accent">
                <input type="radio" name="opt_profile" value="recommended" checked style="accent-color: var(--accent);">
                <div class="fw-bold text-white mt-2">Recommended</div>
                <div class="text-muted" style="font-size: 0.74rem;">Balanced student comfort & teacher preferences</div>
            </label>
        </div>
        <div class="col-12 col-md-3">
            <label class="glass-card card-hoverable p-3 h-100 text-center cursor-pointer">
                <input type="radio" name="opt_profile" value="student" style="accent-color: var(--accent);">
                <div class="fw-bold text-white mt-2">Student Comfort</div>
                <div class="text-muted" style="font-size: 0.74rem;">Minimizes gaps & avoids late evening classes</div>
            </label>
        </div>
        <div class="col-12 col-md-3">
            <label class="glass-card card-hoverable p-3 h-100 text-center cursor-pointer">
                <input type="radio" name="opt_profile" value="teacher" style="accent-color: var(--accent);">
                <div class="fw-bold text-white mt-2">Teacher Assignment</div>
                <div class="text-muted" style="font-size: 0.74rem;">Maximizes teacher course suitability scores</div>
            </label>
        </div>
        <div class="col-12 col-md-3">
            <label class="glass-card card-hoverable p-3 h-100 text-center cursor-pointer">
                <input type="radio" name="opt_profile" value="custom" style="accent-color: var(--accent);">
                <div class="fw-bold text-white mt-2">Custom Weights</div>
                <div class="text-muted" style="font-size: 0.74rem;">Manual sliders configuration</div>
            </label>
        </div>
    </div>

    <!-- Soft Constraints Sliders -->
    <div class="p-4 rounded-4 mb-4" style="background: rgba(15, 20, 35, 0.6); border: 1px solid var(--border-color);">
        <h6 class="fw-bold text-white mb-3">Soft Constraint Priority Sliders</h6>
        <div class="row g-4">
            <div class="col-12 col-md-6">
                <label class="form-label-custom d-flex justify-content-between">
                    <span>Student Sequential Class Grouping</span> <strong class="text-indigo">High (90%)</strong>
                </label>
                <div class="progress" style="height: 8px; background: rgba(255,255,255,0.08);">
                    <div class="progress-bar bg-indigo" style="width: 90%;"></div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label-custom d-flex justify-content-between">
                    <span>Avoid Large Student Gap Hours</span> <strong class="text-indigo">High (85%)</strong>
                </label>
                <div class="progress" style="height: 8px; background: rgba(255,255,255,0.08);">
                    <div class="progress-bar bg-indigo" style="width: 85%;"></div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label-custom d-flex justify-content-between">
                    <span>Lab Morning / Continuous Slot Preference</span> <strong class="text-info">High (80%)</strong>
                </label>
                <div class="progress" style="height: 8px; background: rgba(255,255,255,0.08);">
                    <div class="progress-bar bg-info" style="width: 80%;"></div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label-custom d-flex justify-content-between">
                    <span>Teacher-Course Suitability Matching</span> <strong class="text-success">Maximum (100%)</strong>
                </label>
                <div class="progress" style="height: 8px; background: rgba(255,255,255,0.08);">
                    <div class="progress-bar bg-success" style="width: 100%;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <button class="btn-glass" onclick="goToStep(2)"><i class="bi bi-arrow-left me-1"></i> Back</button>
        <button class="btn-indigo" onclick="goToStep(4)">Next Step: Run Generator <i class="bi bi-arrow-right ms-1"></i></button>
    </div>
</div>

<!-- Wizard Step 4: Generation Progress & Simulation -->
<div class="glass-card mb-4 d-none" id="stepContent4">
    <h4 class="brand-font fw-bold text-white mb-2"><i class="bi bi-4-circle-fill text-indigo me-2"></i> Step 4 of 4 — Execute CP-SAT Optimization Solver</h4>
    <p class="text-muted mb-4" style="font-size: 0.85rem;">Ready to run Google OR-Tools CP-SAT engine for CSE 47 (Sections A & B).</p>

    <!-- Pre-run Summary Box -->
    <div id="preRunBox" class="p-4 rounded-4 mb-4 text-center" style="background: rgba(15, 20, 35, 0.6); border: 1px solid var(--border-color);">
        <i class="bi bi-cpu-fill text-indigo mb-2" style="font-size: 3rem; display: block;"></i>
        <h5 class="fw-bold text-white mb-1">Ready to Generate Routine</h5>
        <p class="text-muted" style="font-size: 0.85rem;">Batch: CSE 47 · Sections: A, B · Courses: 8 · Teachers: 12 · Rooms: 7</p>
        <button class="btn-indigo px-4 py-2 text-uppercase font-monospace mt-2" onclick="startGenerationSimulation()">
            <i class="bi bi-play-circle-fill me-2"></i> Launch Optimization Solver
        </button>
    </div>

    <!-- Live Solver Logs Simulation Box -->
    <div id="solverProgressBox" class="d-none mb-4">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="fw-bold text-white" style="font-size: 0.9rem;">Solver Progress</span>
            <span class="text-indigo font-monospace fw-bold" id="simPercent">0%</span>
        </div>
        <div class="progress mb-3" style="height: 10px; background: rgba(255,255,255,0.08); border-radius: 6px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-indigo" id="simProgressBar" style="width: 0%;"></div>
        </div>

        <div class="p-3 rounded font-monospace" style="background: #060911; border: 1px solid var(--border-color); height: 180px; overflow-y: auto; font-size: 0.78rem; color: #a5b4fc;" id="simLogConsole">
            <!-- Simulated logs will appear here -->
        </div>
    </div>

    <!-- Success Output Card -->
    <div id="successCard" class="d-none p-4 rounded-4 mb-4" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(5, 150, 105, 0.1)); border: 1px solid rgba(16, 185, 129, 0.4);">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <div class="badge bg-success mb-2 p-2" style="font-size: 0.85rem;">
                    <i class="bi bi-check-circle-fill me-1"></i> Schedule Generated Successfully!
                </div>
                <h4 class="brand-font fw-bold text-white mb-1">Optimal Routine Run #5</h4>
                <div class="text-muted" style="font-size: 0.85rem;">
                    Constraint Score: <span class="fw-bold text-success">91.4%</span> · Hard Conflicts: <span class="fw-bold text-white">0</span> · Total Classes Scheduled: <span class="fw-bold text-white">36</span>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('schedules.index', ['view' => 'section']) }}" class="btn-indigo">
                    <i class="bi bi-eye-fill me-1"></i> View Routine Timetable
                </a>
                <a href="{{ route('schedules.index') }}" class="btn-glass">
                    <i class="bi bi-floppy-fill me-1"></i> Save to Database
                </a>
                <button class="btn-glass text-warning" onclick="startGenerationSimulation()">
                    <i class="bi bi-arrow-repeat me-1"></i> Regenerate
                </button>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between" id="wizardFooterButtons">
        <button class="btn-glass" onclick="goToStep(3)"><i class="bi bi-arrow-left me-1"></i> Back</button>
    </div>
</div>

@push('scripts')
<script>
    function goToStep(step) {
        for (let i = 1; i <= 4; i++) {
            document.getElementById('stepContent' + i).classList.add('d-none');
            const ind = document.getElementById('stepIndicator' + i);
            ind.classList.remove('active', 'completed');
            if (i < step) ind.classList.add('completed');
            if (i === step) ind.classList.add('active');
        }
        document.getElementById('stepContent' + step).classList.remove('d-none');
    }

    function startGenerationSimulation() {
        document.getElementById('preRunBox').classList.add('d-none');
        document.getElementById('successCard').classList.add('d-none');
        document.getElementById('solverProgressBox').classList.remove('d-none');
        
        const log = document.getElementById('simLogConsole');
        const bar = document.getElementById('simProgressBar');
        const pct = document.getElementById('simPercent');
        
        log.innerHTML = '';
        bar.style.width = '0%';
        pct.textContent = '0%';

        const logs = [
            { t: 300,  p: '15%', msg: '[SYS] Initializing academic data payload...' },
            { t: 800,  p: '30%', msg: '[SYS] Loading 8 courses, 12 teacher profiles, 7 room specs...' },
            { t: 1400, p: '50%', msg: '[CHECK] Verifying teacher availability matrices & workload limits...' },
            { t: 2000, p: '70%', msg: '[SCORING] Calculating teacher-course suitability weights...' },
            { t: 2700, p: '85%', msg: '[CP-SAT] Building Integer Linear Programming constraints...' },
            { t: 3400, p: '95%', msg: '[CP-SAT] Solving optimization model... Iteration 420 (Cost = 0)...' },
            { t: 4000, p: '100%', msg: '[SUCCESS] Optimal feasible schedule found! Score: 91.4%, Hard Conflicts: 0.' }
        ];

        logs.forEach(item => {
            setTimeout(() => {
                log.innerHTML += `<div>${item.msg}</div>`;
                log.scrollTop = log.scrollHeight;
                bar.style.width = item.p;
                pct.textContent = item.p;
                
                if (item.p === '100%') {
                    setTimeout(() => {
                        document.getElementById('solverProgressBox').classList.add('d-none');
                        document.getElementById('successCard').classList.remove('d-none');
                    }, 500);
                }
            }, item.t);
        });
    }
</script>
@endpush

@endsection
