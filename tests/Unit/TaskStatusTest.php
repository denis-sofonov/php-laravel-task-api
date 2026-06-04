<?php

use App\Enums\TaskStatus;

it('exposes all status values', function () {
    expect(TaskStatus::values())->toBe(['todo', 'in_progress', 'done']);
});

it('maps backing values to cases', function () {
    expect(TaskStatus::from('in_progress'))->toBe(TaskStatus::InProgress);
});

it('has three cases', function () {
    expect(TaskStatus::cases())->toHaveCount(3);
});
