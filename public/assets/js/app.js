const baseUrl = window.APP_BASE_URL || '';
const withBase = (path) => {
    const normalized = path.startsWith('/') ? path : '/' + path;
    return (baseUrl || '') + normalized;
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-regenerate]').forEach(btn => {
        btn.addEventListener('click', () => handleRegenerate(btn));
    });

    document.querySelectorAll('[data-proposal]').forEach(btn => {
        btn.addEventListener('click', () => handleProposal(btn));
    });

    document.querySelectorAll('[data-discover-form]').forEach(form => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('[data-discover-submit]');
            if (!button) return;
            button.disabled = true;
            const defaultLabel = button.querySelector('.default-label');
            const loadingLabel = button.querySelector('.loading-label');
            if (defaultLabel) defaultLabel.classList.add('d-none');
            if (loadingLabel) loadingLabel.classList.remove('d-none');
        });
    });
});

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
            const proposalError = body.proposal && body.proposal.error;
            const error = body.error || proposalError || fallbackMessage;
            throw new Error(error || fallbackMessage);
        }
        return body;
    });
}

function handleRegenerate(button) {
    const leadId = button.dataset.leadId;
    button.disabled = true;
    const spinner = button.querySelector('.spinner-border');
    if (spinner) spinner.classList.remove('d-none');
    document.querySelector('#callScript').textContent = 'Refreshing call script with the latest AI context...';
    document.querySelector('#emailScript').textContent = 'Refreshing email template with the latest AI context...';
    const talkingList = document.querySelector('#talkingPoints');
    if (talkingList) {
        talkingList.innerHTML = '<li>Updating talking points...</li>';
    }
    fetch(withBase('/?route=leads/regenerate'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + leadId
    })
        .then(res => parseJsonResponse(res, 'Unable to regenerate scripts.'))
        .then(data => {
            const summary = data.summary || {};
            const summaryText = typeof summary === 'string' ? summary : summary.summary || 'Summary unavailable.';
            const scripts = data.scripts || {};
            document.querySelector('#leadSummary').textContent = summaryText;
            document.querySelector('#callScript').textContent = scripts.call_script || 'Call script unavailable.';
            document.querySelector('#emailScript').textContent = scripts.email_template || 'Email template unavailable.';
            const list = document.querySelector('#talkingPoints');
            list.innerHTML = '';
            (scripts.talking_points || []).forEach(point => {
                const li = document.createElement('li');
                li.textContent = point;
                list.appendChild(li);
            });
            if (scripts.error) {
                showToast(scripts.error, 'warning');
            }
            if (data.apps) {
                const container = document.querySelector('#recommendedApps');
                if (container) {
                    container.innerHTML = '';
                    data.apps.forEach(app => {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'border rounded p-3 mb-3';
                        const header = document.createElement('div');
                        header.className = 'd-flex justify-content-between';
                        const title = document.createElement('strong');
                        title.textContent = app.app_name;
                        const price = document.createElement('span');
                        price.className = 'badge bg-success';
                        price.textContent = `$${Number(app.price_min).toLocaleString()} - $${Number(app.price_max).toLocaleString()}`;
                        header.appendChild(title);
                        header.appendChild(price);
                        const desc = document.createElement('p');
                        desc.className = 'text-muted small mb-2';
                        desc.textContent = app.description || '';
                        const featureTitle = document.createElement('p');
                        featureTitle.className = 'fw-semibold mb-1';
                        featureTitle.textContent = 'Key Modules';
                        const featureList = document.createElement('ul');
                        (app.key_features || []).forEach(f => {
                            const li = document.createElement('li');
                            li.textContent = f;
                            featureList.appendChild(li);
                        });
                        const benefitTitle = document.createElement('p');
                        benefitTitle.className = 'fw-semibold mb-1';
                        benefitTitle.textContent = 'Benefits';
                        const benefitList = document.createElement('ul');
                        (app.benefits || []).forEach(b => {
                            const li = document.createElement('li');
                            li.textContent = b;
                            benefitList.appendChild(li);
                        });
                        wrapper.appendChild(header);
                        wrapper.appendChild(desc);
                        wrapper.appendChild(featureTitle);
                        wrapper.appendChild(featureList);
                        wrapper.appendChild(benefitTitle);
                        wrapper.appendChild(benefitList);
                        container.appendChild(wrapper);
                    });
                }
            }
        })
        .catch(err => {
            showToast(err.message || 'Failed to regenerate.', 'danger');
            document.querySelector('#callScript').textContent = 'Unable to refresh call script.';
            document.querySelector('#emailScript').textContent = 'Unable to refresh email template.';
            const list = document.querySelector('#talkingPoints');
            if (list) {
                list.innerHTML = '<li>Unable to refresh talking points.</li>';
            }
        })
        .finally(() => {
            button.disabled = false;
            if (spinner) spinner.classList.add('d-none');
        });
}

function handleProposal(button) {
    const leadId = button.dataset.leadId;
    button.disabled = true;
    const modalBody = document.querySelector('#proposalBody');
    modalBody.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary"></div><p class="mt-3">Generating proposal...</p></div>';
    const myModal = new bootstrap.Modal(document.getElementById('proposalModal'));
    myModal.show();
    fetch(withBase('/?route=leads/proposal'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + leadId
    })
        .then(res => parseJsonResponse(res, 'Unable to generate proposal.'))
        .then(data => {
            const proposal = data.proposal || {};
            if (proposal.error) {
                showToast(proposal.error, 'warning');
            }
            const sections = [
                { label: 'Executive Summary', value: proposal.executive_summary },
                { label: 'Solution Overview', value: proposal.solution_overview },
                { label: 'Pricing Context', value: proposal.pricing_context },
                { label: 'ROI Projection', value: proposal.roi_projection },
                { label: 'Next Steps', value: proposal.next_steps },
            ];
            let html = '';
            sections.forEach(section => {
                if (!section.value) return;
                html += `<section class="mb-3"><h6>${section.label}</h6><p>${section.value}</p></section>`;
            });
            if (proposal.proposal_body) {
                html += `<section class="mb-3"><h6>Full Proposal</h6><pre class="bg-light p-3 rounded">${proposal.proposal_body}</pre></section>`;
            }
            if (!html) {
                html = '<div class="alert alert-info">Proposal content is not available.</div>';
            }
            html += '<button class="btn btn-outline-secondary" id="copyProposal">Copy to Clipboard</button>';
            modalBody.innerHTML = html;
            document.getElementById('copyProposal').addEventListener('click', () => {
                navigator.clipboard.writeText([
                    proposal.executive_summary,
                    proposal.solution_overview,
                    proposal.pricing_context,
                    proposal.roi_projection,
                    proposal.next_steps,
                    proposal.proposal_body
                ].filter(Boolean).join('\n\n'));
            });
        })
        .catch(err => {
            showToast(err.message || 'Failed to generate proposal.', 'danger');
            modalBody.innerHTML = '<div class="alert alert-danger">Failed to generate proposal.</div>';
        })
        .finally(() => button.disabled = false);
}
