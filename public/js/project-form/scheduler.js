/**
 * Pure project-item scheduling: working days, lag, FS/SS, and cycle detection.
 * No DOM access — the form collects row data and writes the results back.
 */

import { parseLocalDate } from './utils.js';

/**
 * Sort the items so every predecessor comes before its dependents.
 *
 * Each item points at a single predecessor, so one Kahn sweep settles the whole
 * graph in O(V+E) and whatever it cannot reach is, by definition, inside a cycle.
 *
 * @returns {{order: string[], cycle: string[], unresolvable: string[]}}
 */
export function topologicalOrder(itemsData) {
    const successors = new Map();
    const remaining = new Map();
    const queue = [];
    const order = [];
    const unresolvable = new Set();

    itemsData.forEach((data, rowId) => {
        if (!data.predecessorId || !itemsData.has(data.predecessorId)) {
            // A predecessor that no longer exists leaves the whole branch alone.
            if (data.predecessorId) unresolvable.add(rowId);
            remaining.set(rowId, 0);
            queue.push(rowId);
            return;
        }

        remaining.set(rowId, 1);
        if (!successors.has(data.predecessorId)) {
            successors.set(data.predecessorId, []);
        }
        successors.get(data.predecessorId).push(rowId);
    });

    for (let i = 0; i < queue.length; i++) {
        const rowId = queue[i];
        const dependents = successors.get(rowId) || [];

        if (unresolvable.has(rowId)) {
            dependents.forEach(id => unresolvable.add(id));
        } else {
            order.push(rowId);
        }

        dependents.forEach(id => {
            const left = remaining.get(id) - 1;
            remaining.set(id, left);
            if (left === 0) queue.push(id);
        });
    }

    const cycle = [];
    remaining.forEach((left, rowId) => {
        if (left > 0) cycle.push(rowId);
    });

    return { order, cycle, unresolvable: Array.from(unresolvable) };
}

/**
 * Walk the predecessor chain from a trapped row until it repeats, so the error
 * names the actual loop rather than every affected row.
 *
 * @param {function(string, object): string} [labelFor]
 */
export function describeCycle(itemsData, cycle, labelFor) {
    const path = [];
    const seen = new Set();
    let current = cycle[0];

    while (current && !seen.has(current)) {
        seen.add(current);
        path.push(current);
        current = itemsData.get(current)?.predecessorId;
    }

    if (current) path.push(current);

    return path.map(rowId => {
        const data = itemsData.get(rowId);
        return labelFor ? labelFor(rowId, data) : rowId;
    });
}

/**
 * Add or subtract working days from a date.
 * @param {Date|string} startDate
 * @param {number} days - positive = forward, negative = backward
 * @param {number[]} weeklyHolidays - day numbers (0=Sun … 6=Sat)
 */
export function addWorkingDays(startDate, days, weeklyHolidays) {
    if (days === 0) return parseLocalDate(startDate);

    let remainingDays = Math.abs(days);
    const currentDate = parseLocalDate(startDate);
    const direction = days > 0 ? 1 : -1;

    while (remainingDays > 0) {
        currentDate.setDate(currentDate.getDate() + direction);
        if (!weeklyHolidays.includes(currentDate.getDay())) {
            remainingDays--;
        }
    }

    return currentDate;
}

/**
 * Counts the working days an item spanning `start` to `end` takes, mirroring how
 * `calculateWorkingEndDate` walks forward: the start day itself is not counted.
 */
export function workingDaysBetween(start, end, weeklyHolidays) {
    let days = 0;
    const cursor = parseLocalDate(start);
    const endDate = parseLocalDate(end);
    if (!cursor || !endDate) return 0;

    while (cursor < endDate) {
        cursor.setDate(cursor.getDate() + 1);
        if (!weeklyHolidays.includes(cursor.getDay())) days++;
    }

    return days;
}

/**
 * If a calculated start lands on a weekly holiday, walk forward to the next
 * working day so items never begin on a non-working day.
 */
export function snapToWorkingDay(date, weeklyHolidays) {
    const current = parseLocalDate(date);
    if (!current) return current;

    let guard = 0;
    while (weeklyHolidays.includes(current.getDay()) && guard < 14) {
        current.setDate(current.getDate() + 1);
        guard++;
    }

    return current;
}

/**
 * @returns {{endDate: Date, overflow: boolean}}
 */
export function calculateWorkingEndDate(startDate, durationDays, weeklyHolidays) {
    if (durationDays <= 0) {
        return { endDate: parseLocalDate(startDate), overflow: false };
    }

    let remainingDays = durationDays;
    const currentDate = parseLocalDate(startDate);
    let iterations = 0;
    const MAX_ITERATIONS = 10000;

    while (remainingDays > 0 && iterations < MAX_ITERATIONS) {
        currentDate.setDate(currentDate.getDate() + 1);
        if (!weeklyHolidays.includes(currentDate.getDay())) {
            remainingDays--;
        }
        iterations++;
    }

    return {
        endDate: currentDate,
        overflow: iterations >= MAX_ITERATIONS
    };
}

export function startDateFromPredecessor(data, predecessor, weeklyHolidays) {
    let baseDate = parseLocalDate(
        data.dependencyType === 'start_to_start'
            ? predecessor.startDate
            : predecessor.endDate
    );

    // Lag: موجب = تأخير، سالب = تداخل
    if (data.lag) {
        baseDate = addWorkingDays(baseDate, data.lag, weeklyHolidays);
    }

    return baseDate;
}

/**
 * Fill startDate/endDate on each item in `order`. Does not touch the DOM.
 * @returns {{overflow: boolean}}
 */
export function applyCalculatedDates(itemsData, order, projectStart, weeklyHolidays) {
    let overflow = false;

    order.forEach(rowId => {
        const data = itemsData.get(rowId);
        const predecessor = data.predecessorId ? itemsData.get(data.predecessorId) : null;

        const rawStart = predecessor
            ? startDateFromPredecessor(data, predecessor, weeklyHolidays)
            : parseLocalDate(projectStart);

        data.startDate = snapToWorkingDay(rawStart, weeklyHolidays);

        const result = calculateWorkingEndDate(data.startDate, data.duration, weeklyHolidays);
        data.endDate = result.endDate;
        data.calculated = true;

        if (result.overflow) overflow = true;
    });

    return { overflow };
}

export function maxEndDate(itemsData) {
    let latest = null;

    itemsData.forEach(data => {
        if (data.endDate && (!latest || data.endDate > latest)) {
            latest = data.endDate;
        }
    });

    return latest;
}

/**
 * Full pass: order the graph and fill startDate/endDate on each resolvable item.
 * On a cycle, dates are left untouched.
 *
 * @returns {{order: string[], cycle: string[], unresolvable: string[], overflow: boolean}}
 */
export function scheduleItems(itemsData, projectStart, weeklyHolidays) {
    const { order, cycle, unresolvable } = topologicalOrder(itemsData);

    if (cycle.length > 0) {
        return { order: [], cycle, unresolvable, overflow: false };
    }

    const { overflow } = applyCalculatedDates(itemsData, order, projectStart, weeklyHolidays);
    return { order, cycle, unresolvable, overflow };
}
