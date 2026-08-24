<?php

use App\Domain\Capacity\Enums\PerformanceTier;
use App\Domain\Capacity\Services\PerformanceTierResolver;

beforeEach(function () {
    $this->resolver = new PerformanceTierResolver;
});

it('computes H_poss and H_thresh for an analyst', function () {
    // H_poss = 160 - 16 = 144.00 ; H_thresh = 144 * 0.80 = 115.20
    $hPoss = 160 - 16;
    expect((float) $hPoss)->toBe(144.0);

    $hThresh = round($hPoss * 0.80, 2);
    expect($hThresh)->toBe(115.20);
});

it('computes H_thresh for a team lead', function () {
    $hThresh = round(144 * 0.40, 2);
    expect($hThresh)->toBe(57.60);
});

it('computes the monthly performance percentage', function () {
    $result = $this->resolver->resolve(hThresh: 115.20, hProd: 100.80, perfBelowMax: 90, perfOverMin: 110);

    expect($result->percentage)->toBe(87.50);
    expect($result->tier)->toBe(PerformanceTier::Below);
});

it('maps the acceptable band inclusively at both boundaries', function () {
    expect($this->resolver->tierForPercentage(100.00, 90, 110))->toBe(PerformanceTier::Acceptable);
    expect($this->resolver->tierForPercentage(90.00, 90, 110))->toBe(PerformanceTier::Acceptable);
    expect($this->resolver->tierForPercentage(110.00, 90, 110))->toBe(PerformanceTier::Acceptable);
});

it('maps just over the ceiling to over', function () {
    expect($this->resolver->tierForPercentage(110.01, 90, 110))->toBe(PerformanceTier::Over);
});

it('never divides by zero on full-month leave', function () {
    $result = $this->resolver->resolve(hThresh: 0, hProd: 0, perfBelowMax: 90, perfOverMin: 110);

    expect($result->percentage)->toBeNull();
    expect($result->tier)->toBe(PerformanceTier::NotApplicable);
});
