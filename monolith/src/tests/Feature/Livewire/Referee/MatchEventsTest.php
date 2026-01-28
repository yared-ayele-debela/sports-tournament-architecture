<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('referee.match-events')
        ->assertStatus(200);
});
