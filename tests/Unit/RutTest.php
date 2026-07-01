<?php

use App\Support\Rut;

test('it formats rut values regardless of separators', function (string $input, string $formatted) {
    expect(Rut::formatear($input))->toBe($formatted);
})->with([
    'without separators' => ['123456785', '12.345.678-5'],
    'with hyphen only' => ['12345678-5', '12.345.678-5'],
    'lowercase k' => ['1.000.005-k', '1.000.005-K'],
]);

test('it validates the check digit', function (string $rut, bool $valid) {
    expect(Rut::esValido($rut))->toBe($valid);
})->with([
    'valid numeric check digit' => ['12.345.678-5', true],
    'valid k check digit' => ['1.000.005-K', true],
    'invalid check digit' => ['12.345.678-9', false],
    'invalid characters' => ['12.345.ABC-5', false],
]);
