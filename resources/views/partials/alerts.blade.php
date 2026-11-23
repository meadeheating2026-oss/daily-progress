@if(session('success'))
<div class="alert alert-success alert-dismissible fade show d-flex align-items-center justify-content-between gap-3 kon-static-alert" role="alert">
    <div class="d-flex align-items-center gap-2">
        <i class="fas fa-check-circle flex-shrink-0"></i>
        <span>{{ session('success') }}</span>
    </div>
    <button type="button" class="btn-close position-static p-2" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger d-flex align-items-start justify-content-between gap-3 kon-static-alert kon-persistent-alert" role="alert" data-scroll-invalid="1" style="cursor: pointer;" title="اضغط للانتقال إلى أول حقل به خطأ">
    <div class="d-flex align-items-start gap-2">
        <i class="fas fa-circle-exclamation mt-1 flex-shrink-0"></i>
        <div>
            <div class="fw-semibold">{{ session('error') }}</div>
        </div>
    </div>
    <button type="button" class="btn-close position-static p-2 flex-shrink-0" onclick="event.stopPropagation(); this.closest('.alert').remove();" aria-label="Close"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger d-flex align-items-start justify-content-between gap-3 kon-static-alert kon-persistent-alert" role="alert" data-scroll-invalid="1" style="cursor: pointer;" title="اضغط للانتقال إلى الحقل المطلوب">
    <div class="d-flex align-items-start gap-2 flex-grow-1">
        <i class="fas fa-triangle-exclamation mt-1 flex-shrink-0"></i>
        <div class="flex-grow-1">
            <div class="fw-bold mb-1">يرجى مراجعة الحقول التالية (اضغط على التنبيه للانتقال مباشرةً للحقل):</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->keys() as $fieldKey)
                    @foreach($errors->get($fieldKey) as $errorMsg)
                        <li class="kon-error-jump-item py-1" data-field-key="{{ $fieldKey }}" style="cursor: pointer; text-decoration: underline dotted;">
                            {{ $errorMsg }}
                        </li>
                    @endforeach
                @endforeach
            </ul>
        </div>
    </div>
    <button type="button" class="btn-close position-static p-2 flex-shrink-0" onclick="event.stopPropagation(); this.closest('.alert').remove();" aria-label="Close"></button>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function jumpToFieldByKey(fieldKey) {
        let el = null;
        if (fieldKey) {
            const bracketName = fieldKey.includes('.')
                ? fieldKey.split('.').map((p, i) => i === 0 ? p : `[${p}]`).join('')
                : fieldKey;
            el = document.querySelector(`[name="${CSS.escape(bracketName)}"]`)
                || document.querySelector(`[name="${CSS.escape(fieldKey)}"]`)
                || document.getElementById(fieldKey);
        }
        if (!el) {
            el = document.querySelector('.is-invalid');
        }
        if (!el) return;
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.classList.add('kon-alert-target-pulse');
        setTimeout(() => el.classList.remove('kon-alert-target-pulse'), 3800);
        setTimeout(() => { try { el.focus({ preventScroll: true }); } catch (_e) { el.focus(); } }, 250);
    }

    document.querySelectorAll('.kon-error-jump-item').forEach(function (li) {
        li.addEventListener('click', function (e) {
            e.stopPropagation();
            jumpToFieldByKey(this.dataset.fieldKey);
        });
    });

    document.querySelectorAll('.kon-persistent-alert[data-scroll-invalid="1"]').forEach(function (box) {
        box.addEventListener('click', function (e) {
            if (e.target.closest('.btn-close')) return;
            const firstKey = box.querySelector('.kon-error-jump-item')?.dataset.fieldKey || '';
            jumpToFieldByKey(firstKey);
        });
    });
});
</script>
@endif
