// cep.js — Lógica global de busca de CEP (ViaCEP) v3.1

window.mascaraCep = function(input) {
    let v = input.value.replace(/\D/g,'');
    if (v.length > 5) v = v.slice(0,5)+'-'+v.slice(5,8);
    input.value = v;
    if (v.replace('-','').length === 8) buscarCepAuto(input);
};

window.buscarCepManual = async function(cepInputId) {
    const input = document.getElementById(cepInputId);
    if (!input) return;
    const cep = input.value.replace(/\D/g,'');
    if (cep.length !== 8) { _cepErro(input,'CEP deve ter 8 dígitos.'); return; }
    await _buscarCep(cep, input);
};

window.buscarCepAuto = async function(inputEl) {
    const cep = inputEl.value.replace(/\D/g,'');
    if (cep.length !== 8) return;
    await _buscarCep(cep, inputEl);
};

async function _buscarCep(cep, inputEl) {
    _cepLoading(inputEl, true);
    try {
        const res  = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await res.json();
        if (data.erro) { _cepErro(inputEl,'CEP não encontrado.'); return; }

        const prefix = inputEl.dataset.cepPrefix;
        const p = id => document.getElementById(prefix ? prefix+'-'+id : id);

        // Campos preenchidos pelo CEP: ficam readonly se vieram preenchidos
        const fillLock = (id, val) => {
            const el = p(id);
            if (el) { el.value = val || ''; el.readOnly = !!val; }
        };
        // Campos que o usuário sempre pode editar livremente
        const fillFree = (id, val) => {
            const el = p(id);
            if (el) { el.value = val || ''; el.readOnly = false; }
        };

        fillLock('rua',    data.logradouro);
        fillLock('bairro', data.bairro);
        fillLock('cidade', data.localidade);
        fillLock('estado', data.uf);
        fillFree('complemento', ''); // sempre editável
        fillFree('numero', '');      // sempre editável

        // Foca no número para o usuário preencher
        const num = p('numero');
        if (num) num.focus();

        _cepOk(inputEl);
    } catch(e) {
        _cepErro(inputEl,'Erro de conexão. Verifique sua internet.');
    } finally {
        _cepLoading(inputEl, false);
    }
}

function _cepLoading(el, on) {
    const btn = el.closest('.cep-wrap')?.querySelector('.cep-btn');
    if (btn) { btn.textContent = on ? '⏳' : '🔍 Buscar'; btn.disabled = on; }
}
function _cepOk(el) { el.style.borderColor='var(--verde)'; setTimeout(()=>el.style.borderColor='',3000); }
function _cepErro(el, msg) { el.style.borderColor='#ef4444'; alert(msg); setTimeout(()=>el.style.borderColor='',3000); }