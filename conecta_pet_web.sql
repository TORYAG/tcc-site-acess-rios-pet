-- ============================================================
--  Conecta Pet Web — Banco de dados v3.0
--  CEP completo, chat, recuperação de senha, relatórios
-- ============================================================

CREATE DATABASE IF NOT EXISTS conecta_pet_web
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE conecta_pet_web;

-- -------------------------------------------------------
-- USUÁRIOS
-- -------------------------------------------------------
CREATE TABLE usuario (
    id_usuario    INT          AUTO_INCREMENT PRIMARY KEY,
    nome          VARCHAR(100) NOT NULL,
    email         VARCHAR(120) NOT NULL UNIQUE,
    senha         VARCHAR(255) NOT NULL,
    telefone      VARCHAR(20),
    bio           TEXT,
    foto_perfil   VARCHAR(255),
    tipo_usuario  ENUM('doador','receptor','ong','admin') NOT NULL DEFAULT 'receptor',
    ativo         TINYINT(1) NOT NULL DEFAULT 1,
    token_reset   VARCHAR(64),
    token_expira  DATETIME,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- -------------------------------------------------------
-- ENDEREÇO DO USUÁRIO (via CEP — ViaCEP)
-- -------------------------------------------------------
CREATE TABLE endereco_usuario (
    id_endereco INT          AUTO_INCREMENT PRIMARY KEY,
    cep         VARCHAR(9)   NOT NULL,
    rua         VARCHAR(150),
    numero      VARCHAR(20),
    complemento VARCHAR(100),
    bairro      VARCHAR(100),
    cidade      VARCHAR(100),
    estado      VARCHAR(2),
    id_usuario  INT          NOT NULL UNIQUE,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

-- -------------------------------------------------------
-- CATEGORIAS
-- -------------------------------------------------------
CREATE TABLE categoria_acessorio (
    id_categoria   INT         AUTO_INCREMENT PRIMARY KEY,
    nome_categoria VARCHAR(80) NOT NULL,
    descricao      TEXT,
    icone          VARCHAR(10) DEFAULT '🎁'
);

-- -------------------------------------------------------
-- TIPOS DE PET
-- -------------------------------------------------------
CREATE TABLE pet (
    id_pet   INT         AUTO_INCREMENT PRIMARY KEY,
    tipo_pet VARCHAR(50) NOT NULL,
    porte    VARCHAR(30) NOT NULL
);

-- -------------------------------------------------------
-- ACESSÓRIOS
-- -------------------------------------------------------
CREATE TABLE acessorio (
    id_acessorio       INT          AUTO_INCREMENT PRIMARY KEY,
    nome               VARCHAR(100) NOT NULL,
    descricao          TEXT,
    estado_conservacao VARCHAR(50),
    -- Localização completa via CEP (ViaCEP)
    cep                VARCHAR(9),
    rua                VARCHAR(150),
    numero             VARCHAR(20),
    complemento        VARCHAR(100),
    bairro             VARCHAR(100),
    cidade             VARCHAR(100),
    estado             VARCHAR(2),
    -- Foto, status e métricas
    foto               VARCHAR(255),
    status             ENUM('disponivel','solicitado','doado','inativo') DEFAULT 'disponivel',
    visualizacoes      INT          NOT NULL DEFAULT 0,
    data_cadastro      DATETIME     DEFAULT CURRENT_TIMESTAMP,
    -- Chaves estrangeiras
    id_usuario         INT          NOT NULL,
    id_categoria       INT          NOT NULL,
    id_pet             INT          NOT NULL,
    FOREIGN KEY (id_usuario)   REFERENCES usuario(id_usuario),
    FOREIGN KEY (id_categoria) REFERENCES categoria_acessorio(id_categoria),
    FOREIGN KEY (id_pet)       REFERENCES pet(id_pet)
);

-- -------------------------------------------------------
-- SOLICITAÇÕES
-- -------------------------------------------------------
CREATE TABLE solicitacao_doacao (
    id_solicitacao     INT      AUTO_INCREMENT PRIMARY KEY,
    mensagem           TEXT,
    status_solicitacao ENUM('pendente','aceita','recusada','finalizada') DEFAULT 'pendente',
    data_solicitacao   DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao   DATETIME ON UPDATE CURRENT_TIMESTAMP,
    id_usuario         INT      NOT NULL,
    id_acessorio       INT      NOT NULL,
    FOREIGN KEY (id_usuario)   REFERENCES usuario(id_usuario),
    FOREIGN KEY (id_acessorio) REFERENCES acessorio(id_acessorio)
);

-- -------------------------------------------------------
-- FAVORITOS
-- -------------------------------------------------------
CREATE TABLE favorito (
    id_favorito   INT      AUTO_INCREMENT PRIMARY KEY,
    id_usuario    INT      NOT NULL,
    id_acessorio  INT      NOT NULL,
    data_favorito DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_fav (id_usuario, id_acessorio),
    FOREIGN KEY (id_usuario)   REFERENCES usuario(id_usuario)  ON DELETE CASCADE,
    FOREIGN KEY (id_acessorio) REFERENCES acessorio(id_acessorio) ON DELETE CASCADE
);

-- -------------------------------------------------------
-- AVALIAÇÕES
-- -------------------------------------------------------
CREATE TABLE avaliacao (
    id_avaliacao   INT      AUTO_INCREMENT PRIMARY KEY,
    nota           TINYINT  NOT NULL CHECK (nota BETWEEN 1 AND 5),
    comentario     TEXT,
    data_avaliacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_avaliador   INT      NOT NULL,
    id_avaliado    INT      NOT NULL,
    id_solicitacao INT      NOT NULL UNIQUE,
    FOREIGN KEY (id_avaliador)   REFERENCES usuario(id_usuario),
    FOREIGN KEY (id_avaliado)    REFERENCES usuario(id_usuario),
    FOREIGN KEY (id_solicitacao) REFERENCES solicitacao_doacao(id_solicitacao)
);

-- -------------------------------------------------------
-- MENSAGENS (chat entre doador e solicitante)
-- -------------------------------------------------------
CREATE TABLE mensagem (
    id_mensagem    INT      AUTO_INCREMENT PRIMARY KEY,
    conteudo       TEXT     NOT NULL,
    lida           TINYINT(1) NOT NULL DEFAULT 0,
    data_envio     DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_remetente   INT      NOT NULL,
    id_destinatario INT     NOT NULL,
    id_solicitacao INT      NOT NULL,
    FOREIGN KEY (id_remetente)    REFERENCES usuario(id_usuario),
    FOREIGN KEY (id_destinatario) REFERENCES usuario(id_usuario),
    FOREIGN KEY (id_solicitacao)  REFERENCES solicitacao_doacao(id_solicitacao)
);

-- -------------------------------------------------------
-- NOTIFICAÇÕES
-- -------------------------------------------------------
CREATE TABLE notificacao (
    id_notificacao INT          AUTO_INCREMENT PRIMARY KEY,
    mensagem       VARCHAR(255) NOT NULL,
    link           VARCHAR(255),
    tipo           ENUM('solicitacao','aceite','recusa','avaliacao','mensagem','sistema') DEFAULT 'sistema',
    lida           TINYINT(1)   NOT NULL DEFAULT 0,
    data_criacao   DATETIME     DEFAULT CURRENT_TIMESTAMP,
    id_usuario     INT          NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE
);

-- -------------------------------------------------------
-- DADOS INICIAIS
-- -------------------------------------------------------
INSERT INTO categoria_acessorio (nome_categoria, descricao, icone) VALUES
('Brinquedos',  'Brinquedos e acessórios de entretenimento',   '🧸'),
('Coleiras',    'Coleiras, guias, peitorais e identificações',  '📿'),
('Caminhas',    'Camas, mantas, cobertores e almofadas',        '🛏️'),
('Roupas',      'Roupas, fantasias e acessórios de vestuário',  '👕'),
('Higiene',     'Produtos de higiene, banho e tosa',            '🛁'),
('Alimentação', 'Comedouros, bebedouros e petiscos',            '🍖'),
('Transporte',  'Caixas de transporte, bolsas e mochilas',      '🎒'),
('Saúde',       'Itens de saúde e bem-estar animal',            '💊'),
('Casinhas',    'Casinhas, gaiolas e ambientes',                '🏠'),
('Outros',      'Outros itens para pets',                       '📦');

INSERT INTO pet (tipo_pet, porte) VALUES
('Cachorro','Mini (até 4kg)'),('Cachorro','Pequeno (4–10kg)'),
('Cachorro','Médio (10–25kg)'),('Cachorro','Grande (25–45kg)'),
('Cachorro','Gigante (acima de 45kg)'),
('Gato','Pequeno'),('Gato','Médio'),('Gato','Grande'),
('Pássaro','Pequeno'),('Pássaro','Médio'),
('Coelho','Pequeno'),('Coelho','Médio'),
('Hamster','Pequeno'),('Peixe','Qualquer'),
('Réptil','Pequeno'),('Réptil','Médio'),('Outro','Qualquer');

-- Admin inicial (senha: admin123)
INSERT INTO usuario (nome, email, senha, tipo_usuario) VALUES
('Administrador', 'admin@conectapet.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
