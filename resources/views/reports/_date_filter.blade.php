<form method="GET" class="card p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label" for="from">از تاریخ (شمسی)</label>
            <input type="text" name="from" id="from" value="{{ $fromShamsi ?? request('from') }}"
                   class="form-control jalali-datepicker" autocomplete="off" placeholder="۱۴۰۴/۰۷/۰۱">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="to">تا تاریخ (شمسی)</label>
            <input type="text" name="to" id="to" value="{{ $toShamsi ?? request('to') }}"
                   class="form-control jalali-datepicker" autocomplete="off" placeholder="۱۴۰۴/۰۷/۳۰">
        </div>
        @isset($statusLabels)
            <div class="col-md-3">
                <label class="form-label" for="status">وضعیت</label>
                <select name="status" id="status" class="form-select">
                    <option value="">همه</option>
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        @endisset
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-primary" type="submit">اعمال فیلتر</button>
            <a href="{{ url()->current() }}" class="btn btn-outline-secondary">بازنشانی</a>
        </div>
    </div>
</form>
