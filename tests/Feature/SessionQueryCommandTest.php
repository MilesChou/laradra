<?php

test('inspiring command', function () {
    $this->markTestIncomplete();
    $this->artisan('hydra:session:query')
         ->expectsOutput('Simplicity is the ultimate sophistication.')
         ->assertExitCode(0);
});
