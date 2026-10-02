<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        // Existing UI assertions exercise the English admin variant.
        $this->withSession(['admin_locale' => 'en']);
    }
}
