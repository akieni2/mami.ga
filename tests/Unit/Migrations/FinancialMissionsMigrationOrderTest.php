<?php

namespace Tests\Unit\Migrations;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FinancialMissionsMigrationOrderTest extends TestCase
{
    private const CREATE_MIGRATION = '2026_06_28_100000_create_municipality_sprint4_financial_governance_tables.php';

    private const FINANCE_TABLES = [
        'financial_missions',
        'municipal_finance_journal_entries',
        'municipal_treasury_remittances',
    ];

    public function test_sprint4_create_migration_exists(): void
    {
        $this->assertFileExists(database_path('migrations/'.self::CREATE_MIGRATION));
    }

    public function test_no_migration_alters_financial_missions_before_create(): void
    {
        $createOrder = $this->migrationOrder(self::CREATE_MIGRATION);

        foreach ($this->migrationFiles() as $file) {
            $basename = basename($file);

            if ($basename === self::CREATE_MIGRATION) {
                continue;
            }

            $content = file_get_contents($file);

            if (! $this->altersFinancialMissions($content)) {
                continue;
            }

            $this->assertGreaterThan(
                $createOrder,
                $this->migrationOrder($basename),
                sprintf(
                    'La migration %s modifie financial_missions avant sa création (%s).',
                    $basename,
                    self::CREATE_MIGRATION,
                ),
            );
        }
    }

    #[DataProvider('financeTableProvider')]
    public function test_no_migration_creates_finance_table_before_sprint4_create(string $table): void
    {
        $createOrder = $this->migrationOrder(self::CREATE_MIGRATION);

        foreach ($this->migrationFiles() as $file) {
            $basename = basename($file);

            if ($basename === self::CREATE_MIGRATION) {
                continue;
            }

            $content = file_get_contents($file);

            if (! $this->createsTable($content, $table)) {
                continue;
            }

            $this->assertGreaterThan(
                $createOrder,
                $this->migrationOrder($basename),
                sprintf(
                    'La migration %s crée %s avant la migration Sprint 4.0 (%s).',
                    $basename,
                    $table,
                    self::CREATE_MIGRATION,
                ),
            );
        }
    }

    public function test_sprint41_workflow_migration_runs_after_sprint4_create(): void
    {
        $workflowMigration = '2026_06_29_100000_add_workflow_to_financial_missions_sprint41.php';

        $this->assertFileExists(database_path('migrations/'.$workflowMigration));

        $this->assertGreaterThan(
            $this->migrationOrder(self::CREATE_MIGRATION),
            $this->migrationOrder($workflowMigration),
        );
    }

    public function test_misordered_sprint41_migration_file_is_removed(): void
    {
        $this->assertFileDoesNotExist(
            database_path('migrations/2026_06_16_200000_add_workflow_to_financial_missions_sprint41.php'),
        );
    }

    /**
     * @return list<string>
     */
    public static function financeTableProvider(): array
    {
        return array_map(
            fn (string $table) => [$table],
            self::FINANCE_TABLES,
        );
    }

    /**
     * @return list<string>
     */
    private function migrationFiles(): array
    {
        $files = glob(database_path('migrations/*.php'));

        $this->assertNotFalse($files);

        sort($files);

        return $files;
    }

    private function migrationOrder(string $filename): int
    {
        if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})_/', $filename, $matches) !== 1) {
            $this->fail("Impossible d'extraire l'ordre chronologique de {$filename}.");
        }

        return (int) str_replace('_', '', $matches[1]);
    }

    private function altersFinancialMissions(string $content): bool
    {
        return str_contains($content, "Schema::table('financial_missions'")
            || str_contains($content, 'Schema::table("financial_missions"');
    }

    private function createsTable(string $content, string $table): bool
    {
        return str_contains($content, "Schema::create('{$table}'")
            || str_contains($content, 'Schema::create("'.$table.'"');
    }
}
