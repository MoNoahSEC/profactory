@extends('layouts.app')

@section('title', 'تحضير الموظفين | لوحة المشرف')
@section('page_title', 'تحضير الموظفين')

@section('content')
<style>
    body { background-color: #f8fafc; }
    .glass-card { border: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); border-radius: 12px; }
    .worker-card { transition: all 0.2s; border-left: 4px solid #cbd5e1; }
    .worker-card.status-present { border-left-color: #10b981; background-color: #f0fdf4; }
    .worker-card.status-absent { border-left-color: #ef4444; background-color: #fef2f2; }
    .worker-card.status-half_day { border-left-color: #f59e0b; background-color: #fffbeb; }
    
    .floating-search {
        position: sticky;
        top: 60px;
        z-index: 100;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        padding: 10px 0;
        margin-bottom: 15px;
    }
    
    /* Hide sidebar and header on mobile for pure app experience */
    @media (max-width: 768px) {
        .app-header { display: none !important; }
        .sidebar { display: none !important; }
        .main-content { margin-left: 0 !important; padding-top: 10px !important; }
    }
    
    .stats-pill {
        padding: 8px 12px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 0.9rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
        text-align: center;
    }
    .stats-pill small { font-size: 0.7rem; font-weight: normal; margin-bottom: 2px; }
    .btn-action { width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; padding: 0; }
</style>

<!-- App Header Substitute -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0 text-dark"><i class="bi bi-people-fill text-primary me-2"></i> التحضير اليومي</h4>
    <form action="{{ route('supervisor.attendance.index') }}" method="GET" class="d-flex" id="dateForm">
        <input type="date" name="date" class="form-control fw-bold border-secondary text-center shadow-sm" value="{{ $date }}" onchange="document.getElementById('dateForm').submit()" style="max-width: 150px;">
    </form>
</div>

<!-- Stats Row -->
<div class="d-flex gap-2 mb-3">
    <div class="stats-pill bg-white border border-secondary text-dark">
        <small class="text-muted">الإجمالي</small>
        <span>{{ $totalWorkers }}</span>
    </div>
    <div class="stats-pill bg-success text-white">
        <small class="text-white-50">حضور</small>
        <span id="stat-present">{{ $present }}</span>
    </div>
    <div class="stats-pill bg-danger text-white">
        <small class="text-white-50">غياب</small>
        <span id="stat-absent">{{ $absent }}</span>
    </div>
    <div class="stats-pill bg-secondary text-white">
        <small class="text-white-50">لم يُسجل</small>
        <span id="stat-unmarked">{{ $unmarked }}</span>
    </div>
</div>

<!-- Floating Search Bar -->
<div class="floating-search">
    <div class="input-group input-group-lg shadow-sm">
        <span class="input-group-text bg-white border-0"><i class="bi bi-search text-muted"></i></span>
        <input type="text" id="workerSearch" class="form-control border-0" placeholder="ابحث عن موظف..." onkeyup="filterWorkers()">
        <button class="btn btn-primary px-3" onclick="clearSearch()"><i class="bi bi-x-lg"></i></button>
    </div>
</div>

<!-- Filter Tabs -->
<ul class="nav nav-pills nav-fill mb-4 bg-white rounded-pill shadow-sm p-1" id="filterTabs">
  <li class="nav-item">
    <button class="nav-link active rounded-pill fw-bold" onclick="setFilter('all')">الكل</button>
  </li>
  <li class="nav-item">
    <button class="nav-link rounded-pill text-secondary fw-bold" onclick="setFilter('unmarked')">لم يُسجل</button>
  </li>
</ul>

<!-- Bulk Action -->
@if($unmarked > 0)
<div class="text-end mb-3" id="bulkActionDiv">
    <form action="{{ route('supervisor.attendance.markAllAbsent') }}" method="POST" onsubmit="return confirm('هل أنت متأكد من تسجيل جميع الموظفين المتبقين ({{ $unmarked }}) كغائبين وإنهاء اليوم؟')">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}">
        <button type="submit" class="btn btn-outline-danger fw-bold rounded-pill"><i class="bi bi-journal-x me-1"></i> إغلاق اليوم (غياب الباقي)</button>
    </form>
</div>
@endif

<!-- Workers List -->
<div id="workersList" class="pb-5">
    @foreach($groupedWorkers as $factoryName => $workers)
        <div class="mb-4">
            <h5 class="fw-bold text-primary mb-3"><i class="bi bi-building me-2"></i>{{ $factoryName }}</h5>
            
            @foreach($workers as $worker)
                @php
                    $att = $attendances->get($worker->id);
                    $status = $att ? $att->status : 'unmarked';
                    $timeIn = $att && $att->time_in ? \Carbon\Carbon::parse($att->time_in)->format('h:i A') : null;
                    $timeOut = $att && $att->time_out ? \Carbon\Carbon::parse($att->time_out)->format('h:i A') : null;
                @endphp
                
                <div class="card glass-card worker-card status-{{ $status }} mb-3" data-name="{{ $worker->name }}" data-status="{{ $status }}">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h5 class="fw-bold mb-1 text-dark">{{ $worker->name }}</h5>
                                <div class="d-flex gap-2 text-muted small time-display">
                                    <span class="time-in-badge {{ $timeIn ? 'text-success fw-bold' : '' }}"><i class="bi bi-box-arrow-in-right"></i> {{ $timeIn ?? '--:--' }}</span>
                                    <span class="time-out-badge {{ $timeOut ? 'text-danger fw-bold' : '' }}"><i class="bi bi-box-arrow-left"></i> {{ $timeOut ?? '--:--' }}</span>
                                </div>
                            </div>
                            
                            <!-- Action Buttons for Unmarked -->
                            <div class="actions-unmarked {{ $status !== 'unmarked' ? 'd-none' : 'd-flex' }} gap-2">
                                <button class="btn btn-success btn-action shadow-sm" onclick="markAttendance({{ $worker->id }}, 'present', 'check_in')" title="حضور الآن"><i class="bi bi-check-lg fs-4"></i></button>
                                <button class="btn btn-danger btn-action shadow-sm" onclick="markAttendance({{ $worker->id }}, 'absent')" title="غياب"><i class="bi bi-x-lg fs-4"></i></button>
                                <button class="btn btn-warning text-dark btn-action shadow-sm" onclick="markAttendance({{ $worker->id }}, 'half_day')" title="نصف يوم"><i class="bi bi-circle-half fs-4"></i></button>
                            </div>

                            <!-- Action Buttons for Present -->
                            <div class="actions-present {{ $status !== 'present' && $status !== 'half_day' ? 'd-none' : 'd-block' }}">
                                @if(!$timeOut)
                                    <button class="btn btn-outline-danger btn-sm rounded-pill fw-bold" onclick="markAttendance({{ $worker->id }}, '{{ $status }}', 'check_out')">تسجيل انصراف</button>
                                @else
                                    <span class="badge bg-secondary rounded-pill px-3 py-2"><i class="bi bi-check2-all"></i> تم الانصراف</span>
                                @endif
                                <button class="btn btn-sm btn-link text-muted p-0 ms-2" onclick="undoAttendance({{ $worker->id }})"><i class="bi bi-arrow-counterclockwise fs-5"></i></button>
                            </div>

                            <!-- Action Buttons for Absent -->
                            <div class="actions-absent {{ $status !== 'absent' ? 'd-none' : 'd-block' }}">
                                <span class="badge bg-danger rounded-pill px-3 py-2"><i class="bi bi-x-circle"></i> غائب</span>
                                <button class="btn btn-sm btn-link text-muted p-0 ms-2" onclick="undoAttendance({{ $worker->id }})"><i class="bi bi-arrow-counterclockwise fs-5"></i></button>
                            </div>
                        </div>
                        
                        @if($att && $att->notes)
                        <div class="mt-2 text-muted small bg-light p-2 rounded notes-display">
                            <i class="bi bi-chat-left-text text-primary"></i> {{ $att->notes }}
                        </div>
                        @endif
                        
                        <div class="mt-2 text-end">
                            <button class="btn btn-link text-secondary p-0 text-decoration-none small" onclick="addNote({{ $worker->id }})"><i class="bi bi-pencil-square"></i> إضافة ملاحظة</button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- Note Modal -->
<div class="modal fade" id="noteModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
      <div class="modal-body p-4 text-center">
        <h5 class="fw-bold mb-3">إضافة ملاحظة للموظف</h5>
        <input type="hidden" id="noteWorkerId">
        <textarea id="noteText" class="form-control mb-3" rows="3" placeholder="مثال: متأخر ساعتين، مريض، استئذان..."></textarea>
        <div class="d-flex gap-2">
            <button class="btn btn-light w-50" data-bs-dismiss="modal">إلغاء</button>
            <button class="btn btn-primary w-50" onclick="saveNote()">حفظ</button>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
    let currentFilter = 'all';
    
    function filterWorkers() {
        const query = document.getElementById('workerSearch').value.toLowerCase();
        const cards = document.querySelectorAll('.worker-card');
        
        cards.forEach(card => {
            const name = card.getAttribute('data-name').toLowerCase();
            const status = card.getAttribute('data-status');
            
            const matchesQuery = name.includes(query);
            const matchesFilter = currentFilter === 'all' || status === currentFilter;
            
            if (matchesQuery && matchesFilter) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    function clearSearch() {
        document.getElementById('workerSearch').value = '';
        filterWorkers();
    }
    
    function setFilter(filter) {
        currentFilter = filter;
        
        // Update tabs UI
        document.querySelectorAll('#filterTabs .nav-link').forEach(tab => {
            tab.classList.remove('active', 'text-white');
            tab.classList.add('text-secondary');
        });
        event.target.classList.add('active', 'text-white');
        event.target.classList.remove('text-secondary');
        
        filterWorkers();
    }

    async function markAttendance(workerId, status, action = null) {
        const card = document.querySelector(`.worker-card:has(button[onclick*="markAttendance(${workerId}"])`) || document.querySelector(`.worker-card:nth-child(${workerId})`); // Fallback logic is weak, let's select by attribute later if needed. Actually we can find it via the dom structure.
        const parentCard = event.target.closest('.worker-card');
        
        // Optimistic UI update
        parentCard.style.opacity = '0.5';

        try {
            const response = await fetch("{{ route('supervisor.attendance.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    worker_id: workerId,
                    status: status,
                    date: '{{ $date }}',
                    action: action
                })
            });

            const result = await response.json();
            
            if (result.success) {
                updateCardUI(parentCard, result.data, workerId);
                recalculateStats();
            }
        } catch (error) {
            alert('حدث خطأ في الاتصال. يرجى التأكد من الإنترنت.');
        } finally {
            parentCard.style.opacity = '1';
        }
    }

    async function undoAttendance(workerId) {
        const parentCard = event.target.closest('.worker-card');
        parentCard.style.opacity = '0.5';

        try {
            const response = await fetch("{{ route('supervisor.attendance.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    worker_id: workerId,
                    status: 'unmarked',
                    date: '{{ $date }}'
                })
            });

            const result = await response.json();
            
            if (result.success) {
                updateCardUI(parentCard, { status: 'unmarked', time_in: null, time_out: null }, workerId);
                recalculateStats();
            }
        } catch (error) {
            alert('حدث خطأ في الاتصال.');
        } finally {
            parentCard.style.opacity = '1';
        }
    }

    function updateCardUI(card, data, workerId) {
        card.setAttribute('data-status', data.status);
        
        // Remove old status classes
        card.classList.remove('status-present', 'status-absent', 'status-half_day', 'status-unmarked');
        card.classList.add(`status-${data.status}`);

        // Toggle action sections
        const actsUnmarked = card.querySelector('.actions-unmarked');
        const actsPresent = card.querySelector('.actions-present');
        const actsAbsent = card.querySelector('.actions-absent');

        actsUnmarked.classList.add('d-none'); actsUnmarked.classList.remove('d-flex');
        actsPresent.classList.add('d-none'); actsPresent.classList.remove('d-block');
        actsAbsent.classList.add('d-none'); actsAbsent.classList.remove('d-block');

        if (data.status === 'unmarked') {
            actsUnmarked.classList.remove('d-none'); actsUnmarked.classList.add('d-flex');
        } else if (data.status === 'present' || data.status === 'half_day') {
            actsPresent.classList.remove('d-none'); actsPresent.classList.add('d-block');
            
            // Rebuild Present Actions inner HTML based on time_out
            if (!data.time_out) {
                actsPresent.innerHTML = `<button class="btn btn-outline-danger btn-sm rounded-pill fw-bold" onclick="markAttendance(${workerId}, '${data.status}', 'check_out')">تسجيل انصراف</button>
                                       <button class="btn btn-sm btn-link text-muted p-0 ms-2" onclick="undoAttendance(${workerId})"><i class="bi bi-arrow-counterclockwise fs-5"></i></button>`;
            } else {
                actsPresent.innerHTML = `<span class="badge bg-secondary rounded-pill px-3 py-2"><i class="bi bi-check2-all"></i> تم الانصراف</span>
                                       <button class="btn btn-sm btn-link text-muted p-0 ms-2" onclick="undoAttendance(${workerId})"><i class="bi bi-arrow-counterclockwise fs-5"></i></button>`;
            }
        } else if (data.status === 'absent') {
            actsAbsent.classList.remove('d-none'); actsAbsent.classList.add('d-block');
        }

        // Update Times
        const timeInEl = card.querySelector('.time-in-badge');
        const timeOutEl = card.querySelector('.time-out-badge');
        
        if(data.time_in) {
            timeInEl.innerHTML = `<i class="bi bi-box-arrow-in-right"></i> ${formatTime(data.time_in)}`;
            timeInEl.classList.add('text-success', 'fw-bold');
        } else {
            timeInEl.innerHTML = `<i class="bi bi-box-arrow-in-right"></i> --:--`;
            timeInEl.classList.remove('text-success', 'fw-bold');
        }

        if(data.time_out) {
            timeOutEl.innerHTML = `<i class="bi bi-box-arrow-left"></i> ${formatTime(data.time_out)}`;
            timeOutEl.classList.add('text-danger', 'fw-bold');
        } else {
            timeOutEl.innerHTML = `<i class="bi bi-box-arrow-left"></i> --:--`;
            timeOutEl.classList.remove('text-danger', 'fw-bold');
        }
        
        filterWorkers(); // re-apply filter in case it was on "unmarked" and someone was marked
    }

    function formatTime(timeString) {
        if(!timeString) return '';
        const [hour, minute] = timeString.split(':');
        const d = new Date();
        d.setHours(hour, minute);
        return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    }

    function recalculateStats() {
        const cards = document.querySelectorAll('.worker-card');
        let p = 0, a = 0, u = 0;
        
        cards.forEach(c => {
            const s = c.getAttribute('data-status');
            if(s === 'present' || s === 'half_day') p++;
            else if (s === 'absent') a++;
            else u++;
        });

        document.getElementById('stat-present').innerText = p;
        document.getElementById('stat-absent').innerText = a;
        document.getElementById('stat-unmarked').innerText = u;

        const bulkDiv = document.getElementById('bulkActionDiv');
        if (bulkDiv) {
            bulkDiv.style.display = u > 0 ? 'block' : 'none';
        }
    }

    let currentNoteWorker = null;
    let noteModal = null;
    
    document.addEventListener('DOMContentLoaded', function() {
        noteModal = new bootstrap.Modal(document.getElementById('noteModal'));
    });

    function addNote(workerId) {
        currentNoteWorker = workerId;
        document.getElementById('noteText').value = ''; // We can't easily fetch existing note without extra logic, but keeping it simple.
        noteModal.show();
    }

    async function saveNote() {
        const note = document.getElementById('noteText').value;
        const card = document.querySelector(`.worker-card:has(button[onclick*="markAttendance(${currentNoteWorker}"])`) || document.querySelector(`.worker-card:nth-child(${currentNoteWorker})`); // Fix selector later
        
        // Find card by searching buttons. This is safer.
        let targetCard = null;
        document.querySelectorAll('.worker-card').forEach(c => {
            if(c.innerHTML.includes(`markAttendance(${currentNoteWorker}`)) {
                targetCard = c;
            }
        });

        if(!targetCard) return;

        const status = targetCard.getAttribute('data-status');
        
        try {
            await fetch("{{ route('supervisor.attendance.store') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    worker_id: currentNoteWorker,
                    status: status === 'unmarked' ? 'present' : status, // default to present if adding note to unmarked
                    date: '{{ $date }}',
                    notes: note
                })
            });
            
            // Add note UI
            let notesDisplay = targetCard.querySelector('.notes-display');
            if(!notesDisplay) {
                notesDisplay = document.createElement('div');
                notesDisplay.className = 'mt-2 text-muted small bg-light p-2 rounded notes-display';
                targetCard.querySelector('.card-body').insertBefore(notesDisplay, targetCard.querySelector('.text-end'));
            }
            notesDisplay.innerHTML = `<i class="bi bi-chat-left-text text-primary"></i> ${note}`;
            
            noteModal.hide();
        } catch (error) {
            alert('حدث خطأ');
        }
    }
</script>
@endpush
