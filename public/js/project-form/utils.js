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

/**
 * Element ids and form keys have to be ascii, but stripping the non-ascii
 * characters out of the name collapsed every arabic name of the same length
 * onto one key, so subprojects overwrote each other. Hand out a stable
 * sequential key per name instead.
 */
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

/**
 * `new Date('YYYY-MM-DD')` is UTC midnight, which shifts the calendar day west of
 * UTC (Egypt included). Build a local-midnight Date so getDay() and formatDate
 * agree with the value the user typed into <input type="date">.
 */
export function parseLocalDate(value) {
    if (value instanceof Date) {
        return new Date(value.getFullYear(), value.getMonth(), value.getDate());
    }

    if (!value) return null;

    const [year, month, day] = String(value).slice(0, 10).split('-').map(Number);
    if (!year || !month || !day) return null;

    return new Date(year, month - 1, day);
}

export function showNotification(type, message) {
    const alertClass = type === 'success' ? 'alert-success' :
        type === 'error' ? 'alert-danger' :
            type === 'warning' ? 'alert-warning' : 'alert-info';

    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-triangle' : 'info'}-circle me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

    document.body.appendChild(notification);

    setTimeout(() => notification.remove(), 3000);
}
