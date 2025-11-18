const baseUrl = window.APP_BASE_URL || '';
const withBase = (path) => {
    const normalized = path.startsWith('/') ? path : '/' + path;
    return (baseUrl || '') + normalized;
};

document.addEventListener('DOMContentLoaded', () => {
    initLeadRows();
    initLeadDetailPage();
    initCopyButtons();
    initDownloadButtons();
});

function initLeadRows() {
    document.querySelectorAll('[data-lead-row]').forEach(row => {
        row.addEventListener('click', (event) => {
            if (event.target.closest('a, button, input, select, label, form')) {
                return;
            }
            const url = row.dataset.leadUrl;
            if (url) {
                window.location.href = url;
            }
        });
    });
}

function initLeadDetailPage() {
    const container = document.querySelector('[data-lead-detail]');
    if (!container) {
        return;
    }
    const generateButton = container.querySelector('[data-generate-ai]');
    if (generateButton) {
        generateButton.addEventListener('click', () => handleGenerateAi(container, generateButton));
    }
}

function initCopyButtons() {
    document.querySelectorAll('[data-copy-target]').forEach(button => {
        button.addEventListener('click', () => {
            const selector = button.getAttribute('data-copy-target');
            const target = selector ? document.querySelector(selector) : null;
            if (!target) {
                showToast('Nothing to copy.', 'warning');
                return;
            }
            const text = target.innerText || target.textContent || '';
            if (!navigator.clipboard) {
                showToast('Clipboard not available in this browser.', 'warning');
                return;
            }
            navigator.clipboard.writeText(text.trim()).then(() => {
                showToast('Copied to clipboard.', 'success');
            }).catch(() => showToast('Unable to copy text.', 'danger'));
        });
    });
}

function initDownloadButtons() {
    document.querySelectorAll('[data-download-proposal]').forEach(button => {
        button.addEventListener('click', () => {
            const target = document.querySelector('#aiProposalText');
            if (!target) {
                showToast('Proposal content missing.', 'warning');
                return;
            }
            const text = target.innerText || target.textContent || '';
            if (!text.trim()) {
                showToast('Proposal content missing.', 'warning');
                return;
            }
            const blob = new Blob([text.trim()], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `proposal-${button.dataset.leadId || 'lead'}.txt`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        });
    });
}

function handleGenerateAi(container, button) {
    const leadId = button.dataset.leadId;
    if (!leadId) {
        return;
    }
    setButtonLoading(button, true);
    toggleError(container, '');
    fetch(withBase('/?route=leads/generate-ai'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(leadId)
    })
        .then(res => parseJsonResponse(res, 'Unable to refresh AI collateral.'))
        .then(data => {
            populateAiBlocks(container, data.ai || {});
            showToast('AI collateral updated.', 'success');
        })
        .catch(err => {
            toggleError(container, err.message || 'Failed to refresh AI collateral.');
        })
        .finally(() => setButtonLoading(button, false));
}

function populateAiBlocks(container, ai) {
    const statusBadge = container.querySelector('#aiStatusBadge');
    if (statusBadge) {
        const needsRefresh = !ai.last_generated_at;
        statusBadge.textContent = needsRefresh ? 'Needs refresh' : 'Fresh insight';
        statusBadge.classList.toggle('bg-warning', needsRefresh);
        statusBadge.classList.toggle('text-dark', needsRefresh);
        statusBadge.classList.toggle('bg-success', !needsRefresh);
        if (!needsRefresh) {
            statusBadge.classList.remove('text-dark');
        }
    }
    const lastGenerated = container.querySelector('#aiLastGenerated');
    if (lastGenerated) {
        lastGenerated.textContent = ai.last_generated_at ? `Last refreshed ${new Date(ai.last_generated_at).toLocaleString()}` : 'Not generated yet';
    }
    setHtml(container.querySelector('#aiInsightSummary'), ai.insight_summary, 'Click Generate / Refresh AI to craft a personalized narrative.');
    updateList(container.querySelector('#aiInsightPoints'), ai.app_benefits ? ai.app_benefits.slice(0, 3) : []);
    container.querySelector('#aiInsightPoints')?.classList.toggle('d-none', !ai.app_benefits || ai.app_benefits.length === 0);
    setText(container.querySelector('#aiAppType'), ai.app_type || '—');
    setHtml(container.querySelector('#aiAppConcept'), ai.app_concept);
    const priceRange = container.querySelector('#aiPriceRange');
    if (priceRange) {
        if (ai.price_min && ai.price_max) {
            priceRange.textContent = `${formatCurrency(ai.price_min)} - ${formatCurrency(ai.price_max)}`;
        } else {
            priceRange.innerHTML = '<span class="text-muted">—</span>';
        }
    }
    updateList(container.querySelector('#aiFeaturesList'), ai.app_features || []);
    updateList(container.querySelector('#aiBenefitsList'), ai.app_benefits || []);
    const callScript = container.querySelector('#aiCallScript');
    if (callScript) {
        callScript.textContent = ai.call_script || 'AI call script will appear here.';
    }
    updateList(container.querySelector('#aiTalkingPoints'), ai.talking_points && ai.talking_points.length ? ai.talking_points : ['Refresh AI to surface the sharpest angles.']);
    setText(container.querySelector('#aiEmailSubject'), ai.email_subject || '—');
    const emailBody = container.querySelector('#aiEmailBody');
    if (emailBody) {
        emailBody.textContent = ai.email_body || 'Email body will populate after running AI.';
    }
    const proposal = container.querySelector('#aiProposalText');
    if (proposal) {
        if (ai.full_proposal) {
            proposal.innerHTML = escapeHtml(ai.full_proposal).replace(/\n/g, '<br>');
        } else {
            proposal.innerHTML = '<div class="alert alert-info">Generate AI collateral to see the full proposal.</div>';
        }
    }
}

function setButtonLoading(button, isLoading) {
    if (!button) return;
    button.disabled = isLoading;
    const spinner = button.querySelector('.spinner-border');
    if (spinner) {
        spinner.classList.toggle('d-none', !isLoading);
    }
}

function toggleError(container, message) {
    const alert = container.querySelector('#aiErrorAlert');
    if (!alert) return;
    if (!message) {
        alert.classList.add('d-none');
        alert.textContent = '';
        return;
    }
    alert.classList.remove('d-none');
    alert.textContent = message;
}

function updateList(element, items) {
    if (!element) return;
    element.innerHTML = '';
    if (!items || items.length === 0) {
        return;
    }
    items.forEach(item => {
        const li = document.createElement('li');
        li.textContent = item;
        element.appendChild(li);
    });
}

function setText(element, value, fallback = '—') {
    if (!element) return;
    element.textContent = value ? value : fallback;
}

function setHtml(element, value, fallback = '') {
    if (!element) return;
    if (!value) {
        element.innerHTML = fallback ? `<span class="text-muted">${fallback}</span>` : '';
        return;
    }
    element.innerHTML = escapeHtml(value).replace(/\n/g, '<br>');
}

function formatCurrency(value) {
    const number = Number(value || 0);
    return `$${number.toLocaleString()}`;
}

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function showToast(message, type = 'danger') {
    if (!message) return;
    let container = document.getElementById('appToastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'appToastContainer';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(container);
    }
    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center text-bg-${type} border-0`;
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>`;
    container.appendChild(toastEl);
    const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
    toast.show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}

function parseJsonResponse(response, fallbackMessage) {
    return response.json().catch(() => ({})).then(body => {
        if (!response.ok) {
            const error = body.error || fallbackMessage;
            throw new Error(error || fallbackMessage);
        }
        return body;
    });
}
