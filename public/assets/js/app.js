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
});

function handleRegenerate(button) {
    const leadId = button.dataset.leadId;
    button.disabled = true;
    const spinner = button.querySelector('.spinner-border');
    if (spinner) spinner.classList.remove('d-none');
    fetch(withBase('/?route=leads/regenerate'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + leadId
    })
        .then(res => res.json())
        .then(data => {
            document.querySelector('#leadSummary').textContent = data.summary.summary || data.summary;
            document.querySelector('#callScript').textContent = data.scripts.call_script;
            document.querySelector('#emailScript').textContent = data.scripts.email_template;
            const list = document.querySelector('#talkingPoints');
            list.innerHTML = '';
            data.scripts.talking_points.forEach(point => {
                const li = document.createElement('li');
                li.textContent = point;
                list.appendChild(li);
            });
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
        .catch(() => alert('Failed to regenerate.'))
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
        .then(res => res.json())
        .then(data => {
            const content = data.proposal.content || data.proposal;
            modalBody.innerHTML = '<pre class="bg-light p-3 rounded">' + content + '</pre>' +
                '<button class="btn btn-outline-secondary" id="copyProposal">Copy to Clipboard</button>';
            document.getElementById('copyProposal').addEventListener('click', () => {
                navigator.clipboard.writeText(content);
            });
        })
        .catch(() => {
            modalBody.innerHTML = '<div class="alert alert-danger">Failed to generate proposal.</div>';
        })
        .finally(() => button.disabled = false);
}
