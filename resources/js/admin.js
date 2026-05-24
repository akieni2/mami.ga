const POLL_INTERVAL_MS = 10000;

const page = document.body.dataset.adminPage;

const endpoints = {
    dashboard: '/admin/live/dashboard',
    drivers: '/admin/live/drivers',
    map: '/admin/live/map',
};

function setIndicator(loading) {
    const el = document.getElementById('live-refresh-indicator');
    if (!el) return;
    el.classList.toggle('hidden', !loading);
}

async function fetchJson(url) {
    const response = await fetch(url, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }

    return response.json();
}

function formatPrice(value) {
    if (value == null) return '—';
    return `${Number(value).toLocaleString('fr-FR')} FCFA`;
}

function statusBadge(status) {
    const styles = {
        pending: 'bg-amber-100 text-amber-800',
        accepted: 'bg-sky-100 text-sky-800',
        arrived: 'bg-indigo-100 text-indigo-800',
        started: 'bg-blue-100 text-blue-800',
        completed: 'bg-emerald-100 text-emerald-800',
        cancelled: 'bg-slate-100 text-slate-700',
        online: 'bg-emerald-100 text-emerald-800',
        offline: 'bg-slate-100 text-slate-700',
        busy: 'bg-amber-100 text-amber-800',
        on_ride: 'bg-amber-100 text-amber-800',
    };
    const cls = styles[status] ?? 'bg-slate-100 text-slate-700';
    const label = String(status).replace(/_/g, ' ');
    return `<span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ${cls}">${label}</span>`;
}

function updateDashboard(data) {
    const map = {
        'stat-total-drivers': data.stats?.total_drivers,
        'stat-total-drivers-side': data.stats?.total_drivers,
        'stat-online-drivers': data.stats?.online_drivers,
        'stat-online-drivers-side': data.stats?.online_drivers,
        'stat-active-rides': data.stats?.active_rides,
        'stat-completed-rides': data.stats?.completed_rides,
        'stat-busy-drivers': data.stats?.busy_drivers,
    };

    Object.entries(map).forEach(([id, value]) => {
        const el = document.getElementById(id);
        if (el && value !== undefined) el.textContent = value;
    });

    const tbody = document.getElementById('recent-rides-body');
    if (!tbody || !data.recent_rides) return;

    if (data.recent_rides.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">Aucune course.</td></tr>';
        return;
    }

    tbody.innerHTML = data.recent_rides.map((ride) => `
        <tr>
            <td class="px-5 py-3 font-medium">${ride.id}</td>
            <td class="px-5 py-3">${ride.client ?? '—'}</td>
            <td class="px-5 py-3">${ride.driver ?? '—'}</td>
            <td class="px-5 py-3">${statusBadge(ride.status)}</td>
            <td class="px-5 py-3">${formatPrice(ride.estimated_price)}</td>
        </tr>
    `).join('');
}

function updateDriversTable(drivers) {
    const tbody = document.getElementById('drivers-table-body');
    if (!tbody) return;

    if (!drivers?.length) {
        tbody.innerHTML = '<tr><td colspan="9" class="px-5 py-10 text-center text-slate-500">Aucun chauffeur.</td></tr>';
        return;
    }

    tbody.innerHTML = drivers.map((d) => `
        <tr>
            <td class="px-5 py-3 font-medium">${d.id}</td>
            <td class="px-5 py-3">${d.name}</td>
            <td class="px-5 py-3">${d.phone ?? '—'}</td>
            <td class="px-5 py-3">${d.license_number ?? '—'}</td>
            <td class="px-5 py-3 text-xs font-mono">${d.latitude?.toFixed(5) ?? '—'}, ${d.longitude?.toFixed(5) ?? '—'}</td>
            <td class="px-5 py-3">${d.vehicle ?? '—'}</td>
            <td class="px-5 py-3">${statusBadge(d.presence)}</td>
            <td class="px-5 py-3">${d.rating ?? '—'}</td>
            <td class="px-5 py-3 text-slate-500">${d.last_seen_human ?? '—'}</td>
        </tr>
    `).join('');
}

function enrichDriversForTable(drivers) {
    return drivers.map((d) => ({
        ...d,
        name: d.name,
        license_number: d.license_number,
        phone: d.phone,
        rating: d.rating,
        last_seen_human: d.last_seen_at ? new Date(d.last_seen_at).toLocaleString('fr-FR') : 'Jamais',
    }));
}

async function poll() {
    const url = endpoints[page];
    if (!url) return;

    setIndicator(true);

    try {
        const data = await fetchJson(url);

        if (page === 'dashboard') {
            updateDashboard(data);
        } else if (page === 'drivers') {
            updateDriversTable(enrichDriversForTable(data.drivers ?? []));
        } else if (page === 'map' && typeof window.mamiUpdateMapMarkers === 'function') {
            window.mamiUpdateMapMarkers(data.drivers ?? []);
        }
    } catch (e) {
        console.warn('Live refresh failed', e);
    } finally {
        setIndicator(false);
    }
}

function initSidebar() {
    const sidebar = document.getElementById('admin-sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    const openBtn = document.getElementById('sidebar-open');
    const closeBtn = document.getElementById('sidebar-close');

    const open = () => {
        sidebar?.classList.remove('-translate-x-full');
        backdrop?.classList.remove('hidden');
    };
    const close = () => {
        sidebar?.classList.add('-translate-x-full');
        backdrop?.classList.add('hidden');
    };

    openBtn?.addEventListener('click', open);
    closeBtn?.addEventListener('click', close);
    backdrop?.addEventListener('click', close);
}

document.addEventListener('DOMContentLoaded', () => {
    initSidebar();

    if (endpoints[page]) {
        poll();
        setInterval(poll, POLL_INTERVAL_MS);
    }
});
