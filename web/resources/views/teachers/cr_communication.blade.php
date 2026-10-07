@extends('layouts.app')

@section('title', 'CR & ACR Communication Center')

@section('content')

<!-- Page Header -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h1 class="h3 fw-bold mb-0 text-white brand-font">CR & ACR Communication Center</h1>
            <span class="badge bg-indigo text-white px-3 py-1 rounded-pill" style="font-size: 0.78rem;">Teacher Portal</span>
        </div>
        <p class="text-muted mb-0 mt-1" style="font-size: 0.88rem;">
            Direct communication, notice broadcasting, and section representative directory for Batches 2k21 to 2k25.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-indigo" data-bs-toggle="modal" data-bs-target="#broadcastNoticeModal">
            <i class="bi bi-megaphone-fill me-1"></i> Broadcast Notice to Reps
        </button>
    </div>
</div>

<!-- Overview Quick Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="glass-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="stat-lbl">Target Batches</span>
                <div class="stat-icon-wrapper stat-icon-primary" style="width: 38px; height: 38px; font-size: 1.1rem;">
                    <i class="bi bi-collection-fill"></i>
                </div>
            </div>
            <div class="stat-val text-white" style="font-size: 1.7rem;">5 Batches</div>
            <small class="text-muted" style="font-size: 0.75rem;">2k21, 2k22, 2k23, 2k24, 2k25</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="glass-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="stat-lbl">Class Reps (CRs)</span>
                <div class="stat-icon-wrapper stat-icon-success" style="width: 38px; height: 38px; font-size: 1.1rem;">
                    <i class="bi bi-person-badge-fill"></i>
                </div>
            </div>
            <div class="stat-val text-emerald" style="font-size: 1.7rem; color: #6ee7b7;">10 CRs</div>
            <small class="text-muted" style="font-size: 0.75rem;">2 per batch (1 Sec A, 1 Sec B)</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="glass-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="stat-lbl">Assistant CRs (ACRs)</span>
                <div class="stat-icon-wrapper stat-icon-accent" style="width: 38px; height: 38px; font-size: 1.1rem;">
                    <i class="bi bi-person-hearts"></i>
                </div>
            </div>
            <div class="stat-val text-violet" style="font-size: 1.7rem; color: #c4b5fd;">10 ACRs</div>
            <small class="text-muted" style="font-size: 0.75rem;">2 per batch (1 Sec A, 1 Sec B)</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="glass-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="stat-lbl">Total Representative Network</span>
                <div class="stat-icon-wrapper stat-icon-warning" style="width: 38px; height: 38px; font-size: 1.1rem;">
                    <i class="bi bi-diagram-3-fill"></i>
                </div>
            </div>
            <div class="stat-val text-warning" style="font-size: 1.7rem; color: #fde68a;">20 Reps</div>
            <small class="text-muted" style="font-size: 0.75rem;">10 Sections Total</small>
        </div>
    </div>
</div>

<!-- Interactive Batch & Section Filter Bar -->
<div class="glass-card p-3 mb-4">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        
        <!-- Batch Filter Pills -->
        <div class="d-flex align-items-center gap-1 flex-wrap">
            <span class="fw-semibold text-white me-2" style="font-size: 0.85rem;"><i class="bi bi-filter me-1"></i> Select Batch:</span>
            
            <a href="{{ route('teacher.cr-communication', ['batch' => 'all', 'section' => $selectedSection]) }}" 
               class="btn btn-sm {{ $selectedBatch === 'all' ? 'btn-indigo' : 'btn-glass' }} rounded-pill px-3" style="font-size: 0.82rem;">
                All Batches
            </a>
            
            @foreach(['2k21', '2k22', '2k23', '2k24', '2k25'] as $b)
                <a href="{{ route('teacher.cr-communication', ['batch' => $b, 'section' => $selectedSection]) }}" 
                   class="btn btn-sm {{ $selectedBatch === $b ? 'btn-indigo' : 'btn-glass' }} rounded-pill px-3" style="font-size: 0.82rem;">
                    Batch {{ $b }}
                </a>
            @endforeach
        </div>

        <!-- Section Filter Pills -->
        <div class="d-flex align-items-center gap-2">
            <span class="fw-semibold text-white" style="font-size: 0.85rem;">Section:</span>
            <div class="btn-group btn-group-sm" role="group">
                <a href="{{ route('teacher.cr-communication', ['batch' => $selectedBatch, 'section' => 'all']) }}" 
                   class="btn {{ $selectedSection === 'all' ? 'btn-indigo' : 'btn-glass' }}">All Sections</a>
                <a href="{{ route('teacher.cr-communication', ['batch' => $selectedBatch, 'section' => 'Section A']) }}" 
                   class="btn {{ $selectedSection === 'Section A' ? 'btn-indigo' : 'btn-glass' }}">Sec A</a>
                <a href="{{ route('teacher.cr-communication', ['batch' => $selectedBatch, 'section' => 'Section B']) }}" 
                   class="btn {{ $selectedSection === 'Section B' ? 'btn-indigo' : 'btn-glass' }}">Sec B</a>
            </div>
        </div>

    </div>
</div>

<!-- Representatives Directory Grid -->
@foreach($filteredReps as $batchKey => $batchData)
    <div class="mb-5">
        
        <!-- Batch Header Banner -->
        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom" style="border-color: rgba(255,255,255,0.12) !important;">
            <div class="d-flex align-items-center gap-2">
                <div class="p-2 rounded-3 bg-indigo text-white d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div>
                    <h2 class="h5 fw-bold mb-0 text-white brand-font">{{ $batchData['name'] }}</h2>
                    <span class="text-muted" style="font-size: 0.78rem;">4 Representatives (2 CRs + 2 ACRs across Section A & Section B)</span>
                </div>
            </div>
            
            <button class="btn btn-sm btn-glass" onclick="openBroadcastModalForBatch('{{ $batchData['batch'] }}')">
                <i class="bi bi-send-fill me-1"></i> Broadcast to Batch {{ $batchKey }}
            </button>
        </div>

        <!-- Sections & Rep Cards -->
        <div class="row g-4">
            @foreach($batchData['sections'] as $secName => $reps)
                @if($selectedSection === 'all' || $selectedSection === $secName)
                    <div class="col-12 col-lg-6">
                        <div class="glass-card h-100 p-4" style="background: rgba(19, 27, 46, 0.7); border: 1px solid rgba(139, 92, 246, 0.2);">
                            
                            <!-- Section Title Bar -->
                            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom border-secondary" style="border-color: rgba(255,255,255,0.08) !important;">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge {{ $secName === 'Section A' ? 'bg-primary' : 'bg-success' }} px-3 py-1 rounded-pill" style="font-size: 0.8rem;">
                                        {{ $secName }}
                                    </span>
                                    <span class="text-white fw-semibold" style="font-size: 0.88rem;">1 CR + 1 ACR</span>
                                </div>
                                <span class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-shield-check text-emerald me-1"></i> Verified Reps</span>
                            </div>

                            <!-- Representative Items -->
                            <div class="d-flex flex-column gap-3">
                                @foreach($reps as $rep)
                                    <div class="p-3 rounded-3" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); transition: all 0.2s ease;">
                                        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
                                            
                                            <!-- Rep Info -->
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" 
                                                     style="width: 48px; height: 48px; background: {{ $rep['avatar_bg'] }}; font-size: 1.1rem; flex-shrink: 0;">
                                                    {{ strtoupper(substr($rep['name'], 0, 1)) }}
                                                </div>
                                                <div>
                                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                                        <span class="fw-bold text-white" style="font-size: 0.98rem;">{{ $rep['name'] }}</span>
                                                        <span class="badge {{ $rep['role'] === 'CR' ? 'bg-indigo' : 'bg-purple' }} px-2 py-0.5" style="font-size: 0.72rem; background: {{ $rep['role'] === 'CR' ? '#6366f1' : '#a855f7' }};">
                                                            {{ $rep['role'] }} — {{ $rep['role_title'] }}
                                                        </span>
                                                    </div>
                                                    <div class="text-muted mt-1 d-flex align-items-center gap-3 flex-wrap" style="font-size: 0.78rem;">
                                                        <span><i class="bi bi-card-heading me-1 text-dim"></i> ID: {{ $rep['student_id'] }}</span>
                                                        <span><i class="bi bi-envelope me-1 text-dim"></i> {{ $rep['email'] }}</span>
                                                    </div>
                                                    <div class="text-muted mt-0.5" style="font-size: 0.78rem;">
                                                        <i class="bi bi-telephone me-1 text-dim"></i> {{ $rep['phone'] }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Rep Quick Actions -->
                                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                                <button type="button" 
                                                        class="btn btn-sm btn-indigo" 
                                                        style="font-size: 0.78rem;"
                                                        onclick="openDirectMessageModal('{{ $rep['name'] }}', '{{ $rep['email'] }}', '{{ $rep['role'] }}', '{{ $rep['batch'] }}', '{{ $rep['section'] }}')">
                                                    <i class="bi bi-chat-dots-fill me-1"></i> Message
                                                </button>
                                                <a href="mailto:{{ $rep['email'] }}" class="btn btn-sm btn-glass" style="font-size: 0.78rem;" title="Send Direct Email">
                                                    <i class="bi bi-envelope-fill"></i>
                                                </a>
                                                <a href="tel:{{ $rep['phone'] }}" class="btn btn-sm btn-glass" style="font-size: 0.78rem;" title="Call Representative">
                                                    <i class="bi bi-telephone-fill"></i>
                                                </a>
                                            </div>

                                        </div>
                                    </div>
                                @endforeach
                            </div>

                        </div>
                    </div>
                @endif
            @endforeach
        </div>

    </div>
@endforeach

<!-- Recent Broadcast Communication Logs Section -->
<div class="glass-card p-4 mt-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h3 class="h6 fw-bold mb-0 text-white brand-font"><i class="bi bi-journal-text me-2 text-indigo"></i> Recent Announcement & Communication Logs</h3>
            <span class="text-muted" style="font-size: 0.78rem;">History of notices sent by teachers to CRs & ACRs</span>
        </div>
        <span class="badge bg-indigo px-3 py-1" style="font-size: 0.75rem;">3 Logged Notices</span>
    </div>

    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead>
                <tr>
                    <th>Ref ID</th>
                    <th>Target Batch & Section</th>
                    <th>Recipient Roles</th>
                    <th>Notice Subject & Category</th>
                    <th>Sent Timestamp</th>
                    <th>Delivery Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($communicationLogs as $log)
                    <tr>
                        <td class="fw-semibold text-white font-monospace" style="font-size: 0.82rem;">{{ $log['id'] }}</td>
                        <td>
                            <span class="badge bg-indigo me-1">{{ $log['target_batch'] }}</span>
                            <span class="text-white" style="font-size: 0.82rem;">{{ $log['target_section'] }}</span>
                        </td>
                        <td><span class="text-white" style="font-size: 0.82rem;">{{ $log['target_roles'] }}</span></td>
                        <td>
                            <div class="fw-semibold text-white" style="font-size: 0.88rem;">{{ $log['subject'] }}</div>
                            <span class="badge bg-secondary" style="font-size: 0.7rem;">{{ $log['category'] }}</span>
                        </td>
                        <td class="text-muted" style="font-size: 0.82rem;">{{ $log['sent_at'] }}</td>
                        <td>
                            <span class="badge bg-success text-white px-2 py-1" style="font-size: 0.75rem;">
                                <i class="bi bi-check-all me-1"></i> {{ $log['status'] }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal 1: Broadcast Notice to Batch/Section CRs & ACRs -->
<div class="modal fade" id="broadcastNoticeModal" tabindex="-1" aria-labelledby="broadcastNoticeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="background: #131b2e; border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 20px; color: #fff;">
            
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title brand-font text-white d-flex align-items-center gap-2" id="broadcastNoticeModalLabel">
                    <i class="bi bi-megaphone-fill text-indigo"></i> Broadcast Notice to Class Representatives
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('teacher.cr-communication.send') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    
                    <div class="row g-3">
                        
                        <!-- Target Batch -->
                        <div class="col-md-6">
                            <label class="form-label-custom">Target Batch <span class="text-danger">*</span></label>
                            <select name="target_batch" id="targetBatchSelect" class="form-select form-select-custom" required>
                                <option value="All Batches">All Batches (2k21 - 2k25)</option>
                                <option value="Batch 2k21">Batch 2k21 (8th Semester)</option>
                                <option value="Batch 2k22">Batch 2k22 (6th Semester)</option>
                                <option value="Batch 2k23">Batch 2k23 (4th Semester)</option>
                                <option value="Batch 2k24">Batch 2k24 (2nd Semester)</option>
                                <option value="Batch 2k25">Batch 2k25 (1st Semester)</option>
                            </select>
                        </div>

                        <!-- Target Section -->
                        <div class="col-md-6">
                            <label class="form-label-custom">Target Section <span class="text-danger">*</span></label>
                            <select name="target_section" class="form-select form-select-custom" required>
                                <option value="All Sections (Sec A & B)">All Sections (Section A & B)</option>
                                <option value="Section A Only">Section A Only</option>
                                <option value="Section B Only">Section B Only</option>
                            </select>
                        </div>

                        <!-- Target Representatives -->
                        <div class="col-12">
                            <label class="form-label-custom">Target Representatives</label>
                            <div class="d-flex align-items-center gap-4 p-3 rounded" style="background: rgba(255,255,255,0.04); border: 1px solid var(--border-color);">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="target_roles[]" value="CR" id="roleCR" checked style="accent-color: var(--primary);">
                                    <label class="form-check-label text-white fw-semibold" for="roleCR">
                                        CRs (Class Representatives)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="target_roles[]" value="ACR" id="roleACR" checked style="accent-color: var(--accent);">
                                    <label class="form-check-label text-white fw-semibold" for="roleACR">
                                        ACRs (Assistant CRs)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Notice Category -->
                        <div class="col-md-6">
                            <label class="form-label-custom">Notice Category</label>
                            <select name="category" class="form-select form-select-custom">
                                <option value="Class Schedule / Makeup">Class Schedule / Makeup Class</option>
                                <option value="Exam & CT Syllabus">Exam & Class Test Syllabus</option>
                                <option value="Lab Report & Assignment">Lab Report / Assignment Notice</option>
                                <option value="General Announcement">General Academic Announcement</option>
                            </select>
                        </div>

                        <!-- Notice Priority -->
                        <div class="col-md-6">
                            <label class="form-label-custom">Priority Level</label>
                            <select name="priority" class="form-select form-select-custom">
                                <option value="Normal">Normal Notice</option>
                                <option value="High Priority">High Priority (Urgent)</option>
                            </select>
                        </div>

                        <!-- Subject -->
                        <div class="col-12">
                            <label class="form-label-custom">Subject / Headline <span class="text-danger">*</span></label>
                            <input type="text" name="subject" class="form-control form-control-custom" placeholder="e.g., CSE3201 Makeup Class Rescheduled to Thursday 2:00 PM" required>
                        </div>

                        <!-- Message Body -->
                        <div class="col-12">
                            <label class="form-label-custom">Notice Content / Instructions <span class="text-danger">*</span></label>
                            <textarea name="message_body" rows="4" class="form-control form-control-custom" placeholder="Write detailed instructions for the CRs & ACRs to convey to their respective class groups..." required></textarea>
                        </div>

                    </div>

                </div>

                <div class="modal-footer border-top border-secondary p-3">
                    <button type="button" class="btn btn-glass" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-indigo">
                        <i class="bi bi-send-fill me-1"></i> Send Announcement Broadcast
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<!-- Modal 2: Direct Message Modal to Single Representative -->
<div class="modal fade" id="directMessageModal" tabindex="-1" aria-labelledby="directMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: #131b2e; border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 20px; color: #fff;">
            
            <div class="modal-header border-bottom border-secondary">
                <h5 class="modal-title brand-font text-white d-flex align-items-center gap-2" id="directMessageModalLabel">
                    <i class="bi bi-chat-dots-fill text-indigo"></i> Direct Message to Representative
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('teacher.cr-communication.send') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    
                    <!-- Target Rep Badge Header -->
                    <div class="p-3 rounded mb-3" style="background: rgba(99, 102, 241, 0.12); border: 1px solid rgba(99, 102, 241, 0.3);">
                        <div class="fw-bold text-white" id="dmRepName">Md. Tanvir Hasan</div>
                        <div class="text-muted" style="font-size: 0.8rem;" id="dmRepMeta">CR · Batch 2k21 · Section A (tanvir.2k21.a@intellisched.edu)</div>
                    </div>

                    <input type="hidden" name="target_batch" id="dmTargetBatch" value="Batch 2k21">

                    <div class="mb-3">
                        <label class="form-label-custom">Subject <span class="text-danger">*</span></label>
                        <input type="text" name="subject" class="form-control form-control-custom" placeholder="Message subject..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-custom">Direct Message <span class="text-danger">*</span></label>
                        <textarea name="message_body" rows="4" class="form-control form-control-custom" placeholder="Type message here..." required></textarea>
                    </div>

                </div>

                <div class="modal-footer border-top border-secondary p-3">
                    <button type="button" class="btn btn-glass" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-indigo">
                        <i class="bi bi-send-fill me-1"></i> Send Direct Message
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

@push('scripts')
<script>
    function openBroadcastModalForBatch(batchName) {
        const select = document.getElementById('targetBatchSelect');
        if (select) {
            select.value = 'Batch ' + batchName;
        }
        const modal = new bootstrap.Modal(document.getElementById('broadcastNoticeModal'));
        modal.show();
    }

    function openDirectMessageModal(name, email, role, batch, section) {
        document.getElementById('dmRepName').textContent = name + ' (' + role + ')';
        document.getElementById('dmRepMeta').textContent = batch + ' · ' + section + ' (' + email + ')';
        document.getElementById('dmTargetBatch').value = batch;

        const modal = new bootstrap.Modal(document.getElementById('directMessageModal'));
        modal.show();
    }
</script>
@endpush

@endsection
