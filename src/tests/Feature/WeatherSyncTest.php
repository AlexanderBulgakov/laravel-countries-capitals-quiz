<?php

use App\Models\Capital;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;

it('splits capitals into batched jobs of 50', function () {
    Bus::fake();

    Capital::factory()->count(51)->create();

    $this->artisan('weather:sync')->assertSuccessful();

    Bus::assertBatched(fn (PendingBatch $batch) => $batch->name === 'weather:sync'
        && $batch->allowsFailures()
        && $batch->jobs->map(fn ($job) => count($job->capitalIds))->all() === [50, 1]
        && array_is_list($batch->jobs[1]->capitalIds)
    );
});

it('fails without dispatching when no capital has coordinates', function () {
    Bus::fake();

    Capital::factory()->nullLatLng()->create();

    $this->artisan('weather:sync')->assertFailed();

    Bus::assertNothingBatched();
});

it('skips capitals without coordinates', function () {
    Bus::fake();

    $a = Capital::factory()->create();
    $b = Capital::factory()->create();
    Capital::factory()->nullLatLng()->create();

    $this->artisan('weather:sync')->assertSuccessful();

    Bus::assertBatched(fn (PendingBatch $batch) => $batch->jobs->count() === 1
        && $batch->jobs[0]->capitalIds === [$a->id, $b->id]
    );
});
