<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ProductionReadinessCheck extends Command
{
    protected $signature = 'app:production-check
        {--allow-sqlite : Allow an explicitly reviewed SQLite production deployment}';

    protected $description = 'Run read-only checks for a safe AgencyOS production deployment';

    /** @var array<int, array{check: string, status: string, detail: string}> */
    private array $results = [];

    public function handle(): int
    {
        $this->check(
            'Environment',
            app()->environment('production'),
            'APP_ENV must be production.',
        );

        $this->check(
            'Debug mode',
            config('app.debug') === false,
            'APP_DEBUG must be false.',
        );

        $this->check(
            'Application key',
            filled(config('app.key')),
            'APP_KEY must be generated and kept secret.',
        );

        $this->check(
            'Application name',
            filled(config('app.name')) && config('app.name') !== 'Laravel',
            'Set APP_NAME to the deployed business application name.',
        );

        $appUrl = (string) config('app.url');
        $this->check(
            'HTTPS application URL',
            Str::startsWith($appUrl, 'https://'),
            'APP_URL must use HTTPS.',
        );

        $this->check(
            'Public registration',
            config('agencyos.registration_enabled') === false,
            'AGENCYOS_REGISTRATION_ENABLED must be false.',
        );

        $this->check(
            'Secure session cookie',
            config('session.secure') === true,
            'SESSION_SECURE_COOKIE must be true.',
        );

        $this->warning(
            'Encrypted sessions',
            config('session.encrypt') === true,
            'SESSION_ENCRYPT=true is recommended.',
        );

        $this->check(
            'Production mailer',
            ! in_array(config('mail.default'), ['log', 'array', 'null'], true),
            'Configure a real production mail transport.',
        );

        $this->check(
            'Log level',
            strtolower((string) config('logging.channels.single.level')) !== 'debug',
            'Use LOG_LEVEL=warning or error in production.',
        );

        $this->warning(
            'Queue connection',
            ! in_array(config('queue.default'), ['sync', 'null'], true),
            'Use database, Redis, SQS, or another worker-backed queue.',
        );

        $this->warning(
            'Cache store',
            ! in_array(config('cache.default'), ['array', 'null'], true),
            'Use database, Redis, or another persistent cache.',
        );

        $databaseConnection = (string) config('database.default');
        $sqliteAllowed = (bool) $this->option('allow-sqlite')
            || (bool) config('agencyos.allow_sqlite_production');

        $this->check(
            'Production database',
            $databaseConnection !== 'sqlite' || $sqliteAllowed,
            'Use MySQL/PostgreSQL, or explicitly approve SQLite.',
        );

        try {
            DB::connection()->getPdo();
            $this->pass('Database connectivity', 'Database connection succeeded.');
        } catch (Throwable $exception) {
            $this->recordFailure('Database connectivity', 'Database connection failed.');
        }

        if ($databaseConnection === 'sqlite') {
            $this->check(
                'SQLite foreign keys',
                (bool) config('database.connections.sqlite.foreign_key_constraints'),
                'DB_FOREIGN_KEYS must be true.',
            );

            try {
                $journalMode = DB::selectOne('PRAGMA journal_mode');
                $mode = strtolower((string) ($journalMode->journal_mode ?? 'unknown'));

                $this->warning(
                    'SQLite journal mode',
                    $mode === 'wal',
                    'WAL is recommended for a deliberately approved SQLite deployment.',
                );
            } catch (Throwable $exception) {
                $this->warnResult(
                    'SQLite journal mode',
                    'Journal mode could not be inspected.',
                );
            }
        }

        $this->checkPath('Private document storage', storage_path('app/private'));
        $this->checkPath('Log storage', storage_path('logs'));
        $this->checkPath('Bootstrap cache', base_path('bootstrap/cache'));

        $this->check(
            'Frontend build',
            is_file(public_path('build/manifest.json')),
            'Run npm ci and npm run build.',
        );

        $this->checkPendingMigrations();

        $this->warning(
            'Configuration cache',
            app()->configurationIsCached(),
            'Run php artisan config:cache after final environment configuration.',
        );

        $this->warning(
            'Route cache',
            app()->routesAreCached(),
            'Run php artisan route:cache during deployment.',
        );

        $this->newLine();
        $this->table(
            ['Check', 'Status', 'Detail'],
            array_map(
                static fn (array $result): array => [
                    $result['check'],
                    $result['status'],
                    $result['detail'],
                ],
                $this->results,
            ),
        );

        $failed = count(array_filter(
            $this->results,
            static fn (array $result): bool => $result['status'] === 'FAIL',
        ));

        $warnings = count(array_filter(
            $this->results,
            static fn (array $result): bool => $result['status'] === 'WARN',
        ));

        $this->newLine();
        $this->line("Failures: {$failed}; warnings: {$warnings}.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function check(
        string $name,
        bool $passed,
        string $failureDetail,
    ): void {
        $passed
            ? $this->pass($name, 'Ready.')
            : $this->recordFailure($name, $failureDetail);
    }

    private function warning(
        string $name,
        bool $passed,
        string $warningDetail,
    ): void {
        $passed
            ? $this->pass($name, 'Ready.')
            : $this->warnResult($name, $warningDetail);
    }

    private function checkPath(string $name, string $path): void
    {
        $this->check(
            $name,
            is_dir($path) && is_writable($path),
            "Directory must exist and be writable: {$path}",
        );
    }

    private function checkPendingMigrations(): void
    {
        try {
            /** @var Migrator $migrator */
            $migrator = app(Migrator::class);
            $files = $migrator->getMigrationFiles([database_path('migrations')]);
            $ran = $migrator->getRepository()->getRan();
            $pending = array_diff(array_keys($files), $ran);

            $this->check(
                'Pending migrations',
                count($pending) === 0,
                count($pending).' migration(s) are pending.',
            );
        } catch (Throwable $exception) {
            $this->recordFailure(
                'Pending migrations',
                'Migration state could not be inspected.',
            );
        }
    }

    private function pass(string $name, string $detail): void
    {
        $this->results[] = [
            'check' => $name,
            'status' => 'PASS',
            'detail' => $detail,
        ];
    }

    private function recordFailure(string $name, string $detail): void
    {
        $this->results[] = [
            'check' => $name,
            'status' => 'FAIL',
            'detail' => $detail,
        ];
    }

    private function warnResult(string $name, string $detail): void
    {
        $this->results[] = [
            'check' => $name,
            'status' => 'WARN',
            'detail' => $detail,
        ];
    }
}
