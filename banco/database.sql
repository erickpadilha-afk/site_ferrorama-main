
DROP DATABASE IF EXISTS ferrovias;
CREATE DATABASE ferrovias CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ferrovias;

CREATE TABLE usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    login VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    papel ENUM('administrador', 'maquinista', 'cliente') NOT NULL DEFAULT 'cliente',
    ativo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE trens (
    id_trem INT AUTO_INCREMENT PRIMARY KEY,
    prefixo_trem VARCHAR(50) NOT NULL UNIQUE,
    modelo_trem VARCHAR(100) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'em operacao'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sensores (
    id_sensor INT AUTO_INCREMENT PRIMARY KEY,
    fk_id_trem INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    limite_critico DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (fk_id_trem) REFERENCES trens(id_trem) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE leitura_sensor (
    id_leitura INT AUTO_INCREMENT PRIMARY KEY,
    fk_id_trem INT NOT NULL,
    id_sensor INT NOT NULL,
    velocidade_kmh DECIMAL(5,2) DEFAULT 0.00,
    consumo_litros_hora DECIMAL(5,2) DEFAULT 0.00,
    temperatura DECIMAL(5,2) DEFAULT 0.00,
    data_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fk_id_trem) REFERENCES trens(id_trem) ON DELETE CASCADE,
    FOREIGN KEY (id_sensor) REFERENCES sensores(id_sensor) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE alertas (
    id_alerta INT AUTO_INCREMENT PRIMARY KEY,
    id_leitura INT NOT NULL,
    severidade ENUM('baixa', 'media', 'critico') NOT NULL,
    mensagem TEXT NOT NULL,
    data_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_leitura) REFERENCES leitura_sensor(id_leitura) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE rotas (
    id_rota INT AUTO_INCREMENT PRIMARY KEY,
    id_trem INT NOT NULL,
    origem VARCHAR(100) NOT NULL,
    destino VARCHAR(100) NOT NULL,
    data_viagem DATE NOT NULL,
    hora_partida TIME NOT NULL,
    hora_chegada TIME DEFAULT NULL,
    status ENUM('programada', 'em andamento', 'concluida', 'cancelada') NOT NULL DEFAULT 'programada',
    FOREIGN KEY (id_trem) REFERENCES trens(id_trem) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



INSERT INTO usuario (id, nome, login, senha, papel, ativo) VALUES
(1, 'Administrador Geral', 'admin', '$2y$10$e0MYzXypJS/Pd0HVwHwHe11x3F3Oj/qW5zFky2C8N...', 'administrador', 1),
(4, 'Ricardo Pereira', 'pereira@gmail.com', '$2y$10$1YodPx1H-ALdyO-Cmn0a3ub2G53A2WYUpvms5Ceu/...', 'administrador', 1),
(6, 'Lucas Boeing', 'boeing@gmail.com', '$2y$10$MsKyjCZSTBasea/gCuBRu1kxHS4N4H2srbX6LTK2k...', 'maquinista', 1),
(7, 'Plínio Silva', 'silva_plinio@gmail.com', '$2y$10$e0MYzXypJS/Pd0HVwHwHe11x3F3Oj/qW5zFky2C8N...', 'cliente', 1);

INSERT INTO trens (id_trem, prefixo_trem, modelo_trem, status) VALUES
(1, 'GTT-001', 'Locomotiva Eletro-Diesel GE A22', 'em operacao'),
(2, 'GTT-002', 'Expresso Bala Siemens Velaro', 'em operacao'),
(3, 'GTT-003', 'Locomotiva de Carga EMD SD70', 'manutencao');

INSERT INTO sensores (id_sensor, fk_id_trem, tipo, limite_critico) VALUES
(1, 1, 'Velocidade', 110.00),
(2, 1, 'Temperatura', 90.00),
(3, 2, 'Velocidade', 180.00),
(4, 3, 'Consumo', 250.00);

INSERT INTO leitura_sensor (id_leitura, fk_id_trem, id_sensor, velocidade_kmh, consumo_litros_hora, temperatura, data_hora) VALUES
(1, 1, 1, 85.50, 45.20, 75.00, NOW() - INTERVAL 2 HOUR),
(2, 1, 2, 115.00, 50.00, 95.50, NOW() - INTERVAL 1 HOUR),
(3, 2, 3, 160.00, 80.00, 70.00, NOW() - INTERVAL 30 MINUTE),
(4, 3, 4, 0.00, 0.00, 30.00, NOW());

INSERT INTO alertas (id_alerta, id_leitura, severidade, mensagem, data_hora) VALUES
(1, 2, 'critico', 'Superaquecimento detectado no motor principal (95.5°C)', NOW() - INTERVAL 1 HOUR),
(2, 2, 'media', 'Velocidade levemente acima da média operacional', NOW() - INTERVAL 1 HOUR);

INSERT INTO rotas (id_rota, id_trem, origem, destino, data_viagem, hora_partida, hora_chegada, status) VALUES
(1, 1, 'Estação Central - Joinville', 'Estação Serra - Curitiba', CURDATE(), '08:00:00', '11:30:00', 'em andamento'),
(2, 2, 'Estação Litoral - Florianópolis', 'Estação Porto - Paranaguá', CURDATE(), '14:00:00', '17:45:00', 'programada'),
(3, 3, 'Pátio de Cargas - Mafra', 'Terminal de Cargas - Itajaí', CURDATE() - INTERVAL 1 DAY, '06:00:00', '12:00:00', 'concluida');