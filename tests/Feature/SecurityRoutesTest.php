<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityRoutesTest extends TestCase
{
    public function test_development_database_endpoint_is_not_exposed(): void
    {
        $this->get('/debug-db')->assertNotFound();
    }

    public function test_migrations_cannot_be_triggered_from_the_web(): void
    {
        $this->get('/dev/migrate')->assertNotFound();
    }

}
