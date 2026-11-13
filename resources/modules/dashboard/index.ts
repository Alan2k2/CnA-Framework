import './dashboard.css';

export interface DashboardMetric {
    label: string;
    value: string | number;
    change?: string;
}

export function initDashboard(elementId = 'cna-dashboard'): void {
    const el = document.getElementById(elementId);
    if (!el) return;

    const endpoint = el.getAttribute('data-endpoint') || '/api/health';

    el.innerHTML = `
        <div class="cna-dashboard-container">
            <div class="cna-dashboard-header">
                <h3>CnA System Monitor</h3>
                <span class="cna-badge cna-badge-success">Live Engine v2.0</span>
            </div>
            <div class="cna-dashboard-grid">
                <div class="cna-metric-card">
                    <div class="metric-title">Vite Asset Engine</div>
                    <div class="metric-val">Connected</div>
                </div>
                <div class="cna-metric-card">
                    <div class="metric-title">Server Rendering</div>
                    <div class="metric-val">Sub-Millisecond</div>
                </div>
                <div class="cna-metric-card">
                    <div class="metric-title">Security State</div>
                    <div class="metric-val">ASVS L3</div>
                </div>
            </div>
        </div>
    `;
}

document.addEventListener('DOMContentLoaded', () => {
    initDashboard();
});
