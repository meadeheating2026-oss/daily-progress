/**
 * Generic helpers with no dependency on the form's state.
 */

export function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

export function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * `escapeHtml` leaves quotes untouched, which breaks out of an attribute, so
 * anything interpolated inside `value="..."` has to go through this instead.
 */
export function escapeAttr(text) {
    return String(text ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

const subprojectKeys = new Map();

export function subprojectKey(name) {
    if (!subprojectKeys.has(name)) {
        subprojectKeys.set(name, `cat_${subprojectKeys.size + 1}`);
    }

    return subprojectKeys.get(name);
}

export function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

export function parseLocalDate(value) {
    if (value instanceof Date) {
        return new Date(value.getFullYear(), value.getMonth(), value.getDate());
    }

    if (!value) return null;

    const [year, month, day] = String(value).slice(0, 10).split('-').map(Number);
    if (!year || !month || !day) return null;

    return new Date(year, month - 1, day);
}

function getToastStack() {
    let stack = document.getElementById('kon-toast-stack');
    if (!stack) {
        stack = document.createElement('div');
        stack.id = 'kon-toast-stack';
        stack.className = 'kon-toast-stack no-print';
        document.body.appendChild(stack);
    }
    return stack;
}

/**
 * Expand any collapsed parent group / accordion, scroll smoothly to the target element,
 * pulse an unmistakable highlight ring, and focus the relevant input if requested.
 */
export function navigateToAlertTarget(target, focusSelector = null) {
    if (!target) return;

    let el = null;
    if (typeof target === 'function') {
        el = target();
    } else if (typeof target === 'string') {
        el = document.querySelector(`tr[data-item-id="${CSS.escape(target)}"]`) || document.querySelector(target);
    } else if (target instanceof Element) {
        el = target;
    }

    if (!el) return;

    // 1. If inside a collapsed subproject category-group (Grouped mode), expand it first!
    const categoryBody = el.closest('.category-body.collapsed');
    if (categoryBody) {
        const group = categoryBody.closest('.category-group');
        const header = group?.querySelector('.category-header');
        const icon = header?.querySelector('.category-toggle i');
        categoryBody.classList.remove('collapsed');
        header?.classList.remove('collapsed');
        if (icon) icon.className = 'fas fa-chevron-down';
    }

    // 2. If inside a collapsed Bootstrap accordion, open it
    const collapsedPane = el.closest('.accordion-collapse:not(.show)');
    if (collapsedPane) {
        collapsedPane.classList.add('show');
    }

    // 3. Scroll both inner table scroll container (if any) and page window smoothly
    el.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });

    // 4. Pulse highlight on the row / field
    document.querySelectorAll('.kon-alert-target-pulse').forEach(prev => {
        prev.classList.remove('kon-alert-target-pulse');
    });
    el.classList.add('kon-alert-target-pulse');
    setTimeout(() => {
        el.classList.remove('kon-alert-target-pulse');
    }, 4000);

    // 5. Focus specific control inside target (or target itself)
    const focusEl = focusSelector
        ? el.querySelector(focusSelector)
        : (el.matches('input, select, textarea') ? el : el.querySelector('.is-invalid, .predecessor-select, input:not([type="hidden"]), select'));

    if (focusEl && typeof focusEl.focus === 'function') {
        setTimeout(() => {
            try {
                focusEl.focus({ preventScroll: true });
            } catch (_e) {
                focusEl.focus();
            }
        }, 280);
    }
}

export function dismissNotificationByKey(key) {
    if (!key) return;
    document.querySelectorAll(`.kon-toast-item[data-toast-key="${CSS.escape(key)}"]`).forEach(el => el.remove());
}

/**
 * Show a KON notification.
 * - `error` and `warning` stay FIXED (persistent) until manually closed (or resolved via `key`).
 * - Passing `options.target` or `options.targets` makes the notification (and individual chips)
 *   jump directly to the exact row/input that triggered the alert!
 */
export function showNotification(type, message, options = {}) {
    const isPersistent = options.persistent !== undefined
        ? Boolean(options.persistent)
        : (type === 'error' || type === 'warning');

    const stack = getToastStack();

    if (options.key) {
        dismissNotificationByKey(options.key);
    }

    const alertClass = type === 'success' ? 'alert-success' :
        type === 'error' ? 'alert-danger' :
            type === 'warning' ? 'alert-warning' : 'alert-info';

    const iconClass = type === 'success' ? 'fa-check-circle' :
        type === 'error' ? 'fa-circle-exclamation' :
            type === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-info';

    const hasNavTarget = Boolean(options.target || (Array.isArray(options.targets) && options.targets.length > 0));

    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} kon-toast-item${hasNavTarget ? ' is-clickable' : ''}`;
    if (options.key) {
        notification.dataset.toastKey = options.key;
    }

    // Build interactive chips if `options.targets` is provided (e.g. circular dependency chain or validation fields)
    let richBodyHtml = `<div class="kon-toast-msg">${options.allowHtml ? message : escapeHtml(message)}</div>`;

    if (Array.isArray(options.targets) && options.targets.length > 0) {
        const chipsHtml = options.targets.map((t, idx) => `
            <button type="button" class="kon-toast-chip" data-target-idx="${idx}" title="اضغط للانتقال إلى هذا الموضع">
                <i class="fas fa-location-crosshairs"></i>
                <span>${escapeHtml(t.label)}</span>
            </button>
        `).join(options.joinWithArrow ? '<span class="kon-toast-arrow">←</span>' : '');

        richBodyHtml += `<div class="kon-toast-chips">${chipsHtml}</div>`;
    }

    const navHintHtml = hasNavTarget
        ? `<span class="kon-toast-goto"><i class="fas fa-arrow-up-right-from-square"></i> اضغط للانتقال لموضع التنبيه</span>`
        : '';

    notification.innerHTML = `
        <div class="kon-toast-icon"><i class="fas ${iconClass}"></i></div>
        <div class="kon-toast-content">
            ${richBodyHtml}
            ${navHintHtml}
        </div>
        <button type="button" class="kon-toast-close" aria-label="إغلاق التنبيه" title="إغلاق">
            <i class="fas fa-times"></i>
        </button>
    `;

    notification.querySelector('.kon-toast-close')?.addEventListener('click', e => {
        e.stopPropagation();
        notification.remove();
    });

    if (Array.isArray(options.targets) && options.targets.length > 0) {
        notification.querySelectorAll('.kon-toast-chip').forEach(btn => {
            btn.addEventListener('click', e => {
                e.stopPropagation();
                const idx = parseInt(btn.dataset.targetIdx, 10);
                const item = options.targets[idx];
                if (item) {
                    navigateToAlertTarget(item.target, item.focusSelector || options.focusSelector || null);
                }
            });
        });
    }

    if (hasNavTarget) {
        notification.addEventListener('click', e => {
            if (e.target.closest('.kon-toast-close') || e.target.closest('.kon-toast-chip')) return;
            const primaryTarget = options.target || options.targets?.[0]?.target;
            const primaryFocus = options.focusSelector || options.targets?.[0]?.focusSelector || null;
            navigateToAlertTarget(primaryTarget, primaryFocus);
        });
    }

    stack.appendChild(notification);

    if (!isPersistent) {
        const duration = options.durationMs || 3200;
        setTimeout(() => {
            notification.classList.add('kon-toast-fadeout');
            setTimeout(() => notification.remove(), 220);
        }, duration);
    }

    return notification;
}
