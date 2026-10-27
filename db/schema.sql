CREATE TABLE IF NOT EXISTS scripts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT NULL,
    conteudo MEDIUMTEXT NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ambientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,

    cpu_percentual TINYINT UNSIGNED NOT NULL,
    memoria_mb INT UNSIGNED NOT NULL,
    io_leitura_mbps INT UNSIGNED NULL,
    io_escrita_mbps INT UNSIGNED NULL,
    isolar_rede BOOLEAN NOT NULL DEFAULT FALSE,

    tipo ENUM('comando', 'comandos', 'script') NOT NULL,
    script_id INT UNSIGNED NULL,
    conteudo_execucao MEDIUMTEXT NOT NULL,

    status ENUM(
        'criado',
        'em_execucao',
        'terminado',
        'erro'
    ) NOT NULL DEFAULT 'criado',

    pid INT UNSIGNED NULL,
    exit_code INT NULL,
    motivo TEXT NULL,

    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_ambientes_scripts
        FOREIGN KEY (script_id)
        REFERENCES scripts(id)
        ON DELETE SET NULL,

    CONSTRAINT chk_cpu
        CHECK (cpu_percentual BETWEEN 5 AND 100),

    CONSTRAINT chk_memoria
        CHECK (memoria_mb BETWEEN 32 AND 2048),

    CONSTRAINT chk_io_leitura
        CHECK (io_leitura_mbps IS NULL OR io_leitura_mbps > 0),

    CONSTRAINT chk_io_escrita
        CHECK (io_escrita_mbps IS NULL OR io_escrita_mbps > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;