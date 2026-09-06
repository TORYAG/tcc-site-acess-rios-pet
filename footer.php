<?php // config/footer.php v3 ?>
<footer class="rodape">
    <div class="rodape-inner">
        <div class="rodape-brand">
            <a href="<?= $base ?? '' ?>index.php" class="rodape-logo">🐾 Conecta Pet Web</a>
            <p>Conectamos doadores e receptores de acessórios para pets em todo o Brasil. Juntos, cuidamos melhor de cada animal.</p>
            <div class="rodape-social">
                <span title="Instagram">📸</span>
                <span title="Facebook">📘</span>
                <span title="WhatsApp">📱</span>
            </div>
        </div>

        <div class="rodape-col">
            <h5>Plataforma</h5>
            <a href="<?= $base ?? '' ?>index.php">Início</a>
            <a href="<?= $base ?? '' ?>acessorios.php">Ver Acessórios</a>
            <a href="<?= $base ?? '' ?>cadastrar_acessorio.php">Quero Doar</a>
            <a href="<?= $base ?? '' ?>sobre.php">Sobre nós</a>
        </div>

        <div class="rodape-col">
            <h5>Minha Conta</h5>
            <?php if (isset($_SESSION['id_usuario'])): ?>
                <a href="<?= $base ?? '' ?>perfil.php">Meu Perfil</a>
                <a href="<?= $base ?? '' ?>minhas_solicitacoes.php">Minhas Solicitações</a>
                <a href="<?= $base ?? '' ?>favoritos.php">Favoritos</a>
                <a href="<?= $base ?? '' ?>notificacoes.php">Notificações</a>
            <?php else: ?>
                <a href="<?= $base ?? '' ?>login.php">Login</a>
                <a href="<?= $base ?? '' ?>cadastro.php">Cadastro grátis</a>
            <?php endif; ?>
        </div>

        <div class="rodape-col">
            <h5>Categorias</h5>
            <a href="<?= $base ?? '' ?>acessorios.php?categoria=1">🧸 Brinquedos</a>
            <a href="<?= $base ?? '' ?>acessorios.php?categoria=2">📿 Coleiras</a>
            <a href="<?= $base ?? '' ?>acessorios.php?categoria=3">🛏️ Caminhas</a>
            <a href="<?= $base ?? '' ?>acessorios.php?categoria=6">🍖 Alimentação</a>
        </div>
    </div>

    <div class="rodape-bottom">
        <span>© <?= date('Y') ?> Conecta Pet Web — Feito com 🧡 para os pets do Brasil</span>
        <span class="rodape-tech">PHP 8 + MySQL</span>
    </div>
</footer>

<script>
// ── Hamburger menu ──────────────────────────────────────
const ham     = document.getElementById('hamburger');
const nav     = document.getElementById('nav');
const overlay = document.getElementById('nav-overlay');

function toggleMenu(open) {
    nav.classList.toggle('aberto', open);
    overlay.classList.toggle('visivel', open);
    ham.setAttribute('aria-expanded', open);
    ham.classList.toggle('ativo', open);
    document.body.style.overflow = open ? 'hidden' : '';
}

if (ham) {
    ham.addEventListener('click', () => toggleMenu(!nav.classList.contains('aberto')));
    overlay.addEventListener('click', () => toggleMenu(false));
}

// ── Dropdown de perfil ──────────────────────────────────
document.querySelectorAll('.nav-dropdown-trigger').forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        btn.closest('.nav-dropdown').classList.toggle('ativo');
    });
});
document.addEventListener('click', () => {
    document.querySelectorAll('.nav-dropdown.ativo').forEach(d => d.classList.remove('ativo'));
});

// ── Header scroll shadow ────────────────────────────────
const topo = document.getElementById('topo');
window.addEventListener('scroll', () => {
    topo && topo.classList.toggle('scrolled', window.scrollY > 10);
}, { passive: true });

// ── Animações de entrada (IntersectionObserver) ─────────
const io = new IntersectionObserver(entries => {
    entries.forEach(en => { if (en.isIntersecting) { en.target.classList.add('visivel'); io.unobserve(en.target); } });
}, { threshold: 0.1 });
document.querySelectorAll('.fade-up').forEach(el => io.observe(el));

// ── CEP global ──────────────────────────────────────────
window.mascaraCep = function(input) {
    let v = input.value.replace(/\D/g,'');
    if (v.length > 5) v = v.slice(0,5) + '-' + v.slice(5,8);
    input.value = v;
    if (v.replace('-','').length === 8) buscarCepAuto(input);
};

window.buscarCepAuto = async function(inputEl) {
    const cep = inputEl.value.replace(/\D/g,'');
    if (cep.length !== 8) return;
    const prefix = inputEl.dataset.cepPrefix || '';
    await _buscarCep(cep, prefix, inputEl);
};

window.buscarCepManual = async function(inputId) {
    const inputEl = document.getElementById(inputId);
    if (!inputEl) return;
    const cep = inputEl.value.replace(/\D/g,'');
    if (cep.length !== 8) { mostrarCepErro(inputEl, 'CEP deve ter 8 dígitos.'); return; }
    const prefix = inputEl.dataset.cepPrefix || '';
    await _buscarCep(cep, prefix, inputEl);
};

async function _buscarCep(cep, prefix, inputEl) {
    setCepLoading(inputEl, true);
    try {
        const res  = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await res.json();
        if (data.erro) { mostrarCepErro(inputEl, 'CEP não encontrado. Verifique e tente novamente.'); return; }

        const preenche = (sufixo, val) => {
            const el = document.getElementById((prefix ? prefix + '-' : '') + sufixo);
            if (el) { el.value = val || ''; el.dispatchEvent(new Event('input')); }
        };

        preenches(preenche, data);
        // Foca no número
        const numEl = document.getElementById((prefix ? prefix + '-' : '') + 'numero');
        if (numEl) { numEl.readOnly = false; numEl.focus(); }
        mostrarCepOk(inputEl);
    } catch(err) {
        mostrarCepErro(inputEl, 'Erro de conexão. Verifique sua internet.');
    } finally {
        setCepLoading(inputEl, false);
    }
}

function preenches(fn, data) {
    fn('rua',         data.logradouro);
    fn('bairro',      data.bairro);
    fn('cidade',      data.localidade);
    fn('estado',      data.uf);
    fn('complemento', '');
}

function setCepLoading(input, loading) {
    const btn = input.closest('.cep-wrap')?.querySelector('.cep-btn');
    if (btn) { btn.textContent = loading ? '⏳' : '🔍'; btn.disabled = loading; }
    input.style.borderColor = loading ? 'var(--laranja)' : '';
}
function mostrarCepOk(input) {
    input.style.borderColor = 'var(--verde)';
    setTimeout(() => input.style.borderColor = '', 3000);
}
function mostrarCepErro(input, msg) {
    input.style.borderColor = '#ef4444';
    alert(msg);
    setTimeout(() => input.style.borderColor = '', 3000);
}
</script>
</body>
</html>
