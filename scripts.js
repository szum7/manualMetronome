document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
        document.getElementById(btn.dataset.tab).style.display = 'block';
    });
});

function getFormattedTime() {
    const now = new Date();
    const h = now.getHours().toString().padStart(2, '0');
    const m = now.getMinutes().toString().padStart(2, '0');
    const s = now.getSeconds().toString().padStart(2, '0');
    let ms = now.getMilliseconds().toString().padStart(4, '0');
    ms = ms + '0';
    return `${h}:${m}:${s}.${ms}`;
}

function formatUsec(usec) {
    const ms = Math.floor(usec / 1000);
    const date = new Date(ms);
    const hh = String(date.getHours()).padStart(2, '0');
    const mm = String(date.getMinutes()).padStart(2, '0');
    const ss = String(date.getSeconds()).padStart(2, '0');
    const msec = String(date.getMilliseconds()).padStart(3, '0');
    const remUsec = String(usec % 1000000).padStart(6, '0');
    return `${hh}:${mm}:${ss}.${remUsec}`;
}

async function postJson(url, payload) {
    console.log(JSON.stringify(payload));
    const r = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    });
    return r.json();
}

function generateClientId() {
    let id = localStorage.getItem('cs_clientId');
    if (!id) {
        id = 'c_' + Math.random().toString(36).slice(2, 10);
        localStorage.setItem('cs_clientId', clientId);
    }
    document.getElementById('clientId').textContent = id;
    return id;
}

function getEpochUsec() {
    const ms = performance.timeOrigin + performance.now();
    return Math.round(ms * 1000);
}

function log(msg) {
    const el = document.getElementById('log');
    el.textContent = getFormattedTime() + ' - ' + msg + '\n' + el.textContent;
}

function safeSetTimeout(fn, ms) {
    const MAX = 2147483647;
    let cancelled = false;
    let id = null;

    function clear() {
        cancelled = true;
        if (id !== null) clearTimeout(id);
    }

    function schedule(remaining) {
        if (cancelled) return;
        if (remaining <= MAX) {
            id = setTimeout(() => {
                if (!cancelled) fn();
            }, remaining);
        } else {
            id = setTimeout(() => schedule(remaining - MAX), MAX);
        }
    }

    schedule(ms);
    return {
        clear
    };
}