USE ferrovias;

CREATE TABLE IF NOT EXISTS leituras (
    id_leitura INT NOT NULL AUTO_INCREMENT,
    fk_id_trem INT NOT NULL,
    tipo_sensor VARCHAR(50) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    unidade VARCHAR(10) NOT NULL,
    data_hora DATETIME NOT NULL,
    PRIMARY KEY (id_leitura),
    CONSTRAINT fk_leituras_trens FOREIGN KEY (fk_id_trem) REFERENCES trens (id_trem) ON DELETE CASCADE
);

CREATE INDEX ix_leituras_trem ON leituras (fk_id_trem);
CREATE INDEX ix_leituras_data ON leituras (data_hora);

CREATE TABLE IF NOT EXISTS alertas (
    id_alerta INT NOT NULL AUTO_INCREMENT,
    id_leitura INT NOT NULL,
    tipo VARCHAR(40) NOT NULL,
    severidade VARCHAR(20) NOT NULL, -- 'normal', 'atencao', 'critico'
    mensagem VARCHAR(200) NOT NULL,
    data_hora DATETIME NOT NULL,
    PRIMARY KEY (id_alerta),
    CONSTRAINT fk_alertas_leituras FOREIGN KEY (id_leitura) REFERENCES leituras (id_leitura) ON DELETE CASCADE
);

CREATE INDEX ix_alertas_severidade ON alertas (severidade);