{{-- Schedule assistant: a rule-based chat panel for teachers and students. --}}
<style>
    .chatbot-fab {
        position: fixed; right: 20px; bottom: 20px; z-index: 1040;
        width: 56px; height: 56px; border-radius: 50%; border: 0;
        background: var(--grad-primary); color: #fff; font-size: 1.45rem;
        box-shadow: var(--shadow-lg), var(--shadow-glow);
        display: flex; align-items: center; justify-content: center;
        transition: transform .2s ease;
    }
    .chatbot-fab:hover { transform: translateY(-2px) scale(1.04); }
    .chatbot-fab:focus-visible { outline: 2px solid var(--indigo-soft); outline-offset: 3px; }

    .chatbot-panel {
        position: fixed; right: 20px; bottom: 88px; z-index: 1041;
        width: min(420px, calc(100vw - 32px)); height: min(620px, calc(100vh - 120px));
        display: none; flex-direction: column; overflow: hidden;
        background: var(--bg-card-solid); border: 1px solid var(--border-strong);
        border-radius: var(--radius-lg); box-shadow: var(--shadow-lg);
    }
    .chatbot-panel.open { display: flex; }
    @media (max-width: 575px) {
        .chatbot-fab { right: 16px; bottom: 16px; }
        .chatbot-panel { right: 16px; bottom: 82px; }
    }

    .chatbot-head {
        display: flex; align-items: center; gap: 10px; padding: 12px 14px;
        border-bottom: 1px solid var(--border-color); background: var(--bg-elevated);
    }
    .chatbot-head .avatar {
        width: 34px; height: 34px; border-radius: 10px; flex: none;
        display: flex; align-items: center; justify-content: center;
        background: var(--grad-primary); color: #fff;
    }
    .chatbot-head h6 { margin: 0; font-size: .95rem; font-weight: 700; }
    .chatbot-head small { color: var(--text-dim); font-size: .72rem; }
    .chatbot-head .btn-icon {
        background: none; border: 0; color: var(--text-muted); font-size: 1.05rem;
        padding: 4px 6px; border-radius: 8px;
    }
    .chatbot-head .btn-icon:hover { color: #fff; background: rgba(255,255,255,.06); }

    .chatbot-body { flex: 1; overflow-y: auto; padding: 14px; display: flex; flex-direction: column; gap: 10px; }

    .chat-msg { max-width: 88%; font-size: .86rem; line-height: 1.45; }
    .chat-msg.user {
        align-self: flex-end; background: var(--primary); color: #fff;
        padding: 8px 12px; border-radius: 14px 14px 4px 14px; overflow-wrap: anywhere;
    }
    .chat-msg.bot {
        align-self: flex-start; background: var(--bg-elevated); color: var(--text-main);
        border: 1px solid var(--border-color); padding: 10px 12px; border-radius: 14px 14px 14px 4px;
        overflow-wrap: anywhere;
    }
    .chat-msg.bot.error { border-color: rgba(245, 158, 11, .45); }
    .chat-msg.bot .stage {
        display: inline-block; font-size: .66rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .06em; color: var(--warning); margin-bottom: 4px;
    }
    .chat-items { list-style: none; margin: 8px 0 0; padding: 0; display: flex; flex-direction: column; gap: 6px; }
    .chat-items li {
        background: rgba(99, 102, 241, .08); border: 1px solid var(--border-color);
        border-radius: 10px; padding: 7px 10px;
    }
    .chat-items .t { font-weight: 600; font-size: .82rem; }
    .chat-items .s { color: var(--indigo-soft); font-size: .78rem; }
    .chat-items .m { color: var(--text-muted); font-size: .74rem; }

    .chat-parse { margin-top: 8px; font-size: .72rem; color: var(--text-dim); }
    .chat-parse summary { cursor: pointer; user-select: none; }
    .chat-parse .tok {
        display: inline-block; margin: 3px 3px 0 0; padding: 1px 6px; border-radius: 6px;
        background: rgba(139, 92, 246, .14); color: var(--indigo-soft); font-family: ui-monospace, monospace;
    }
    .chat-parse code { color: var(--gold); font-size: .72rem; }

    .chat-typing { align-self: flex-start; color: var(--text-dim); font-size: .8rem; }

    .chatbot-quick { display: flex; gap: 6px; overflow-x: auto; padding: 8px 14px 0; scrollbar-width: thin; }
    .chat-chip {
        flex: none; border: 1px solid var(--border-strong); background: transparent; color: var(--indigo-soft);
        font-size: .76rem; padding: 4px 10px; border-radius: 999px; white-space: nowrap;
    }
    .chat-chip:hover { background: rgba(139, 92, 246, .16); color: #fff; }

    .chatbot-formats {
        display: none; max-height: 52%; overflow-y: auto; padding: 10px 14px;
        border-top: 1px solid var(--border-color); background: var(--bg-elevated);
    }
    .chatbot-formats.open { display: block; }
    .chatbot-formats h6 {
        font-size: .72rem; text-transform: uppercase; letter-spacing: .07em;
        color: var(--text-dim); font-weight: 700; margin: 10px 0 6px;
    }
    .chatbot-formats h6:first-of-type { margin-top: 0; }
    .format-row {
        display: block; width: 100%; text-align: left; background: none; border: 0;
        border-radius: 8px; padding: 5px 8px; color: var(--text-main);
    }
    .format-row:hover { background: rgba(139, 92, 246, .12); }
    .format-row .f { font-size: .8rem; font-family: ui-monospace, monospace; }
    .format-row .f var { font-style: normal; color: var(--gold); }
    .format-row .e { font-size: .72rem; color: var(--text-dim); }
    .format-legend { font-size: .72rem; color: var(--text-muted); margin-bottom: 4px; }
    .format-legend var { font-style: normal; color: var(--gold); font-family: ui-monospace, monospace; }

    .chatbot-foot { padding: 10px 14px 12px; border-top: 1px solid var(--border-color); }
    .chatbot-foot form { display: flex; gap: 8px; }
    .chatbot-foot input {
        flex: 1; min-width: 0; background: var(--bg-input); color: var(--text-main);
        border: 1px solid var(--border-strong); border-radius: 10px; padding: 8px 12px; font-size: .86rem;
    }
    .chatbot-foot input:focus { outline: none; border-color: var(--border-accent); }
    .chatbot-foot .send {
        border: 0; border-radius: 10px; padding: 0 14px; color: #fff; background: var(--grad-primary);
    }
    .chatbot-foot .send:disabled { opacity: .5; }
    .chatbot-foot .toggle-formats {
        background: none; border: 0; padding: 0; margin-top: 6px;
        color: var(--indigo-soft); font-size: .74rem;
    }
</style>

<button type="button" class="chatbot-fab" id="chatbotFab" aria-controls="chatbotPanel" aria-expanded="false" title="Schedule assistant">
    <i class="bi bi-chat-dots-fill"></i>
</button>

<section class="chatbot-panel" id="chatbotPanel" role="dialog" aria-label="Schedule assistant"
         data-ask-url="{{ route('assistant.ask') }}" data-questions-url="{{ route('assistant.questions') }}">
    <div class="chatbot-head">
        <div class="avatar"><i class="bi bi-robot"></i></div>
        <div class="flex-grow-1">
            <h6>Schedule Assistant</h6>
            <small>Fixed questions about your published routine</small>
        </div>
        <button type="button" class="btn-icon" id="chatbotFormatsBtn" title="All question formats"><i class="bi bi-list-ul"></i></button>
        <button type="button" class="btn-icon" id="chatbotClose" title="Close"><i class="bi bi-x-lg"></i></button>
    </div>

    <div class="chatbot-body" id="chatbotBody" aria-live="polite"></div>

    <div class="chatbot-formats" id="chatbotFormats"></div>

    <div class="chatbot-quick" id="chatbotQuick"></div>

    <div class="chatbot-foot">
        <form id="chatbotForm" autocomplete="off">
            <input type="text" id="chatbotInput" maxlength="{{ config('intellisched.chatbot.max_length', 200) }}"
                   placeholder="e.g. When is my next class?" aria-label="Your question">
            <button type="submit" class="send" id="chatbotSend" title="Send"><i class="bi bi-send-fill"></i></button>
        </form>
        <button type="button" class="toggle-formats" id="chatbotFormatsLink">
            <i class="bi bi-info-circle"></i> All question formats
        </button>
    </div>
</section>

<script>
(function () {
    var panel = document.getElementById('chatbotPanel');
    var fab = document.getElementById('chatbotFab');
    var body = document.getElementById('chatbotBody');
    var formats = document.getElementById('chatbotFormats');
    var quick = document.getElementById('chatbotQuick');
    var form = document.getElementById('chatbotForm');
    var input = document.getElementById('chatbotInput');
    var send = document.getElementById('chatbotSend');
    var token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var loaded = false;
    var busy = false;

    function el(tag, cls, text) {
        var node = document.createElement(tag);
        if (cls) node.className = cls;
        if (text !== undefined && text !== null) node.textContent = text;
        return node;
    }

    function scrollDown() { body.scrollTop = body.scrollHeight; }

    function setOpen(open) {
        panel.classList.toggle('open', open);
        fab.setAttribute('aria-expanded', open ? 'true' : 'false');
        fab.innerHTML = open ? '<i class="bi bi-x-lg"></i>' : '<i class="bi bi-chat-dots-fill"></i>';
        if (open) {
            if (!loaded) loadCatalog();
            setTimeout(function () { input.focus(); }, 50);
        }
    }

    function toggleFormats(force) {
        var open = force !== undefined ? force : !formats.classList.contains('open');
        formats.classList.toggle('open', open);
        if (open) formats.scrollTop = 0;
    }

    // "<course> class when" → highlighted placeholders, safely escaped.
    function renderFormat(text) {
        var wrap = el('div', 'f');
        text.split(/(<[a-z]+>)/).forEach(function (part) {
            if (!part) return;
            wrap.appendChild(/^<[a-z]+>$/.test(part) ? el('var', null, part) : document.createTextNode(part));
        });
        return wrap;
    }

    function loadCatalog() {
        loaded = true;
        botMessage({ ok: true, answer: 'Hi! Ask me about your class routine. Tap a suggestion below, or open the list to see every question format I understand.', items: [] });

        fetch(panel.dataset.questionsUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
            .then(function (data) {
                (data.quick || []).forEach(function (q) {
                    var chip = el('button', 'chat-chip', q);
                    chip.type = 'button';
                    chip.addEventListener('click', function () { ask(q); });
                    quick.appendChild(chip);
                });

                var legend = el('div', 'format-legend');
                Object.keys(data.placeholders || {}).forEach(function (key) {
                    var line = el('div');
                    line.appendChild(el('var', null, key));
                    line.appendChild(document.createTextNode(' = ' + data.placeholders[key]));
                    legend.appendChild(line);
                });
                formats.appendChild(legend);

                (data.groups || []).forEach(function (group) {
                    var h = el('h6');
                    h.innerHTML = '<i class="bi ' + group.icon.replace(/[^a-z0-9-]/gi, '') + ' me-1"></i>';
                    h.appendChild(document.createTextNode(group.title));
                    formats.appendChild(h);

                    group.formats.forEach(function (f) {
                        var row = el('button', 'format-row');
                        row.type = 'button';
                        row.title = 'Ask: ' + f.example;
                        row.appendChild(renderFormat(f.format));
                        if (f.example !== f.format) row.appendChild(el('div', 'e', 'e.g. ' + f.example));
                        row.addEventListener('click', function () { toggleFormats(false); ask(f.example); });
                        formats.appendChild(row);
                    });
                });
            })
            .catch(function () {
                formats.appendChild(el('div', 'format-legend', 'Could not load the question list.'));
            });
    }

    var stageLabels = { lexical: 'Unknown words', syntax: 'Unsupported question', semantic: 'Not found', error: 'Error' };

    function botMessage(data) {
        var msg = el('div', 'chat-msg bot' + (data.ok ? '' : ' error'));
        if (!data.ok && stageLabels[data.stage]) msg.appendChild(el('div', 'stage', stageLabels[data.stage]));
        msg.appendChild(el('div', null, data.answer));

        if (data.items && data.items.length) {
            var list = el('ul', 'chat-items');
            data.items.forEach(function (item) {
                var li = el('li');
                li.appendChild(el('div', 't', item.title));
                if (item.subtitle) li.appendChild(el('div', 's', item.subtitle));
                if (item.meta) li.appendChild(el('div', 'm', item.meta));
                list.appendChild(li);
            });
            msg.appendChild(list);
        }

        // How the lexer and parser read the question.
        if (data.analysis && data.analysis.tokens) {
            var det = el('details', 'chat-parse');
            det.appendChild(el('summary', null, 'How I parsed this'));
            var toks = el('div');
            if (!data.analysis.tokens.length) toks.appendChild(el('span', null, '(no tokens)'));
            data.analysis.tokens.forEach(function (t) {
                toks.appendChild(el('span', 'tok', t.type + (t.type === 'UNKNOWN' || t.value !== undefined ? ' "' + t.lexeme + '"' : '')));
            });
            det.appendChild(toks);
            if (data.analysis.rule) {
                var rule = el('div');
                rule.style.marginTop = '4px';
                rule.appendChild(document.createTextNode('Rule: '));
                rule.appendChild(el('code', null, data.analysis.rule));
                det.appendChild(rule);
            }
            msg.appendChild(det);
        }

        body.appendChild(msg);
        if (data.show_formats) toggleFormats(true);
        scrollDown();
    }

    function ask(question) {
        question = (question || '').trim();
        if (!question || busy) return;

        busy = true;
        send.disabled = true;
        body.appendChild(el('div', 'chat-msg user', question));
        var typing = el('div', 'chat-typing', 'Looking it up…');
        body.appendChild(typing);
        scrollDown();
        input.value = '';

        fetch(panel.dataset.askUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ question: question })
        })
            .then(function (r) {
                return r.json().catch(function () { return {}; }).then(function (data) {
                    if (r.ok) return data;
                    if (r.status === 422) {
                        var errors = data.errors && data.errors.question;
                        return { ok: false, stage: 'error', answer: errors ? errors[0] : 'Please check your question.' };
                    }
                    if (r.status === 429) return { ok: false, stage: 'error', answer: 'Too many questions at once — please wait a minute.' };
                    if (r.status === 419 || r.status === 401) return { ok: false, stage: 'error', answer: 'Your session has expired. Please reload the page.' };
                    return { ok: false, stage: 'error', answer: 'The assistant is unavailable right now.' };
                });
            })
            .catch(function () { return { ok: false, stage: 'error', answer: 'Could not reach the server.' }; })
            .then(function (data) {
                typing.remove();
                botMessage(data);
                busy = false;
                send.disabled = false;
                input.focus();
            });
    }

    fab.addEventListener('click', function () { setOpen(!panel.classList.contains('open')); });
    document.getElementById('chatbotClose').addEventListener('click', function () { setOpen(false); });
    document.getElementById('chatbotFormatsBtn').addEventListener('click', function () { toggleFormats(); });
    document.getElementById('chatbotFormatsLink').addEventListener('click', function () { toggleFormats(); });
    form.addEventListener('submit', function (e) { e.preventDefault(); ask(input.value); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && panel.classList.contains('open')) setOpen(false);
    });
})();
</script>
