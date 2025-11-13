<?php

namespace Tests\Unit;

use App\Models\Person;
use Tests\TestCase;

class PersonTest extends TestCase
{
    public function test_person_fillable_attributes_match_expected_columns(): void
    {
        $this->assertSame(['nome', 'telefone'], (new Person())->getFillable());
    }

    public function test_factory_persists_person_record(): void
    {
        $this->setUpSqliteDatabase();

        $person = Person::factory()->create();

        $this->assertDatabaseHas('persons', [
            'id' => $person->id,
            'nome' => $person->nome,
            'telefone' => $person->telefone,
        ]);
    }

    private function setUpSqliteDatabase(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('PDO SQLite driver is required to run factory persistence tests.');
        }

        $databasePath = database_path('testing.sqlite');

        if (! file_exists($databasePath)) {
            touch($databasePath);
        }

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => $databasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $this->beforeApplicationDestroyed(function () use ($databasePath) {
            if (file_exists($databasePath)) {
                @unlink($databasePath);
            }
        });

        $this->artisan('migrate:fresh', ['--database' => 'sqlite']);
    }
}
