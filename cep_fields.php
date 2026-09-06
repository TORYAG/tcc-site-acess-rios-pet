<?php
/**
 * cep_fields.php — Bloco reutilizável de campos de endereço via CEP
 * 
 * Uso: include __DIR__ . '/config/cep_fields.php';
 * Parâmetros via variáveis PHP antes do include:
 *   $cep_prefix  — prefixo dos IDs (ex: 'item', 'usuario')
 *   $cep_dados   — array com dados existentes (opcional)
 *   $cep_required — bool, se CEP é obrigatório (padrão false)
 */

$cep_prefix   = $cep_prefix   ?? 'end';
$cep_dados    = $cep_dados    ?? [];
$cep_required = $cep_required ?? false;

$v = fn(string $k) => e($cep_dados[$k] ?? $_POST[$k] ?? '');
$req = $cep_required ? 'required' : '';
?>

<div class="form-grupo">
    <label for="<?= $cep_prefix ?>-cep">CEP <?= $cep_required ? '*' : '' ?></label>
    <div class="cep-wrap">
        <input type="text"
               id="<?= $cep_prefix ?>-cep"
               name="cep"
               placeholder="00000-000"
               maxlength="9"
               value="<?= $v('cep') ?>"
               data-cep-prefix="<?= $cep_prefix ?>"
               oninput="mascaraCep(this)"
               autocomplete="postal-code"
               <?= $req ?>>
        <button type="button" class="cep-btn" onclick="buscarCepManual('<?= $cep_prefix ?>-cep')">🔍 Buscar</button>
    </div>
    <span class="form-hint">Digite o CEP — os campos abaixo serão preenchidos automaticamente</span>
</div>

<div class="form-row">
    <div class="form-grupo" style="flex:2">
        <label>Rua / Logradouro</label>
        <input type="text" id="<?= $cep_prefix ?>-rua" name="rua"
               placeholder="Preenchido pelo CEP"
               value="<?= $v('rua') ?>"
               readonly autocomplete="street-address">
    </div>
    <div class="form-grupo" style="flex:1">
        <label>Número</label>
        <input type="text" id="<?= $cep_prefix ?>-numero" name="numero"
               placeholder="Nº"
               value="<?= $v('numero') ?>"
               autocomplete="address-line2">
    </div>
</div>

<div class="form-row">
    <div class="form-grupo">
        <label>Complemento</label>
        <input type="text" id="<?= $cep_prefix ?>-complemento" name="complemento"
               placeholder="Apto, bloco, casa..."
               value="<?= $v('complemento') ?>"
               autocomplete="address-line3">
    </div>
    <div class="form-grupo">
        <label>Bairro</label>
        <input type="text" id="<?= $cep_prefix ?>-bairro" name="bairro"
               placeholder="Preenchido pelo CEP"
               value="<?= $v('bairro') ?>"
               readonly autocomplete="address-level3">
    </div>
</div>

<div class="form-row">
    <div class="form-grupo">
        <label>Cidade <?= $cep_required ? '*' : '' ?></label>
        <input type="text" id="<?= $cep_prefix ?>-cidade" name="cidade"
               placeholder="Preenchida pelo CEP"
               value="<?= $v('cidade') ?>"
               readonly autocomplete="address-level2"
               <?= $req ?>>
    </div>
    <div class="form-grupo" style="max-width:100px">
        <label>UF</label>
        <input type="text" id="<?= $cep_prefix ?>-estado" name="estado"
               placeholder="UF" maxlength="2"
               value="<?= $v('estado') ?>"
               readonly autocomplete="address-level1">
    </div>
</div>
