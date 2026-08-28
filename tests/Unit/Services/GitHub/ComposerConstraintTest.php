<?php

use App\Services\GitHub\ComposerConstraint;

test('constraint floors resolve to the lowest admitted version', function (string $constraint, ?string $floor) {
    $floorParts = $floor === null
        ? null
        : array_map(intval(...), explode('.', $floor));

    expect(ComposerConstraint::parse($constraint)->floor())->toBe($floorParts);
})->with([
    'caret' => ['^12.0', '12.0.0'],
    'caret with patch' => ['^8.3.12', '8.3.12'],
    'tilde' => ['~8.3', '8.3.0'],
    'greater than or equal' => ['>=11.2', '11.2.0'],
    'exact' => ['12.4.1', '12.4.1'],
    'v prefix' => ['v12.4.0', '12.4.0'],
    'any version' => ['*', null],
    'whole major wildcard' => ['12.*', null],
    'bare major' => ['12', null],
    'dev branch' => ['dev-main', null],
    'alternatives pick the highest floor' => ['^8.1 || ^8.3', '8.3.0'],
    'upper bounds do not raise the floor' => ['>=8.1 <8.4', '8.1.0'],
    'unparseable' => ['latest', null],
]);

test('a key constraint admits the floor of the declared constraint', function (string $key, string $declared, bool $expected) {
    expect(
        ComposerConstraint::parse($key)->admitsFloorOf(ComposerConstraint::parse($declared)),
    )->toBe($expected);
})->with([
    'caret matches its own major' => ['^12.0', '^12.0', true],
    'caret rejects a higher major' => ['^12.0', '^13.0', false],
    'caret rejects a lower major' => ['^12.0', '^11.0', false],
    'caret accepts a higher floor within the major' => ['^8.1', '^8.4', true],
    'php caret does not cross the major' => ['^8.4', '^9.0', false],
    'tilde is minor-locked' => ['~8.3', '~8.4', false],
    'greater-or-equal admits its floor' => ['>=8.2', '>=8.2', true],
    'exact admits equality' => ['=12.4.0', '12.4.0', true],
    'any key admits nothing without a floor' => ['^12.0', '*', false],
    'lower bound rejects below the bound' => ['>=12.0', '^11.9', false],
]);
