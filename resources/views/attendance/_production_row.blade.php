<div class="production-row glass-card water-card mb-3 p-3" data-row="{{ $index }}">
    <div class="row g-3 align-items-end">
        <div class="col-lg-2">
            <label class="form-label text-muted small fw-bold">المكنجي</label>
            @if($machinist)
                <input type="hidden" name="production[{{ $index }}][machinist_worker_id]" value="{{ $machinist->id }}">
                <div class="worker-chip"><i class="bi bi-person-gear"></i> {{ $machinist->name }}</div>
            @elseif(isset($machinists) && $machinists->count())
                <select name="production[{{ $index }}][machinist_worker_id]" class="form-select form-control-glass" required>
                    @foreach($machinists as $m)
                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                    @endforeach
                </select>
            @endif
        </div>
        <div class="col-lg-3">
            <label class="form-label text-muted small fw-bold">نوع المنتج (المنتج)</label>
            <select name="production[{{ $index }}][product_id]" class="form-select form-control-glass product-select" required onchange="updatePreview(this)">
                <option value="">— اختر المنتج —</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" data-labor="{{ $p->labor_cost }}" data-scissors="{{ $p->scissors_cost }}">{{ $p->name }} (م:{{ $p->labor_cost }} / ق:{{ $p->scissors_cost }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-1">
            <label class="form-label text-muted small fw-bold">الكمية</label>
            <input type="number" name="production[{{ $index }}][quantity]" class="form-control form-control-glass qty-input text-center fw-bold" min="1" value="1" required>
        </div>
        <div class="col-lg-3">
            <label class="form-label text-muted small fw-bold"><i class="bi bi-scissors text-danger"></i> موظف المقص</label>
            <select name="production[{{ $index }}][scissors_worker_id]" class="form-select form-control-glass">
                <option value="">— بدون مقص —</option>
                @foreach($scissorsWorkers as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
            <small class="text-muted">يُسجَّل تلقائياً بنفس الكمية بسعر المقص</small>
        </div>
        <div class="col-lg-2">
            <label class="form-label text-muted small fw-bold">الحالة</label>
            <select name="production[{{ $index }}][status]" class="form-select form-control-glass">
                <option value="present">حاضر</option>
                <option value="half_day">نصف يوم</option>
            </select>
        </div>
        <div class="col-lg-1 text-end">
            <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="this.closest('.production-row').remove()"><i class="bi bi-trash"></i></button>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-12">
            <div class="production-preview d-flex flex-wrap gap-3">
                <span class="preview-tag"><i class="bi bi-person-gear"></i> المكنجي: <strong class="preview-machinist">0 ج</strong></span>
                <span class="preview-tag scissors"><i class="bi bi-scissors"></i> المقص: <strong class="preview-scissors">0 ج</strong></span>
                <span class="preview-tag inventory"><i class="bi bi-box-seam"></i> يُضاف للمخزن فوراً</span>
            </div>
        </div>
    </div>
</div>

