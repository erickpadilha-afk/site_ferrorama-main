USE ferrovias;

CREATE TABLE IF NOT EXISTS rotas (
    id_rota INT NOT NULL AUTO_INCREMENT,
    id_trem INT NOT NULL,
    origem VARCHAR(80) NOT NULL,
    destino VARCHAR(80) NOT NULL,
    data_viagem DATE NOT NULL,
    hora_partida TIME NOT NULL,
    hora_chegada TIME NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'programada',
    PRIMARY KEY (id_rota),
    CONSTRAINT fk_rotas_trens FOREIGN KEY (id_trem) REFERENCES trens (id_trem) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX ix_rotas_trem ON rotas (id_trem);
CREATE INDEX ix_rotas_status ON rotas (status);
CREATE INDEX ix_rotas_data ON rotas (data_viagem);