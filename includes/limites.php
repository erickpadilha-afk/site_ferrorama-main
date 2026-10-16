<?php
define('LIMITES_SENSORES', [
    'velocidade'  => 80.00,  // km/h
    'temperatura' => 90.00,  // °C
    'consumo'     => 50.00,  // L/h
    'vibracao'    => 3.00    // mm/s
]);

function classificar_leitura($tipo, $valor) {
    $limite = LIMITES_SENSORES[$tipo] ?? 100.00;

    if ($valor <= $limite) {
        return 'normal';
    } elseif ($valor <= ($limite * 1.10)) {
        return 'atencao';
    } else {
        return 'critico';
    }
}
?>