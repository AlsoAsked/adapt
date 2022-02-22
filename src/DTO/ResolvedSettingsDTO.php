<?php

namespace CodeDistortion\Adapt\DTO;

use CodeDistortion\Adapt\DTO\Traits\DTOBuildTrait;
use CodeDistortion\Adapt\Exceptions\AdaptRemoteShareException;

class ResolvedSettingsDTO
{
    use DTOBuildTrait;



    /** @var string The name of the current project. */
    public $projectName;

    /** @var string The name of the current test. */
    public $testName;



    /** @var string The database connection used. */
    public $connection;

    /** @var string|null The database driver to use when building the database ("mysql", "sqlite" etc). */
    public $driver;

    /** @var string|null The database host (if relevant). */
    public $host;

    /** @var string|null The name of the database to use. */
    public $database;



    /** @var boolean Whether the database was built remotely or not. */
    public $builtRemotely;

    /** @var string|null The remote Adapt installation to send "build" requests to. */
    public $remoteBuildUrl;

    /** @var boolean Whether snapshots are turned on or not. */
    public $snapshotsEnabled;

    /** @var string The directory to store database snapshots in. */
    public $storageDir;

    /** @var string[] The files to import before the migrations are run. */
    public $preMigrationImports;

    /** @var boolean|string Should the migrations be run? / migrations location - if not, the db will be empty. */
    public $migrations;

    /** @var boolean Whether seeding is allowed or not. */
    public $isSeedingAllowed;

    /** @var string[] The seeders to run after migrating - will only be run if migrations were run. */
    public $seeders;

    /** @var boolean When turned on, databases will be created for each scenario (based on migrations and seeders etc). */
    public $usingScenarios;

    /** @var string|null The calculated build-hash. */
    public $buildHash;

    /** @var string|null The calculated snapshot scenario-hash. */
    public $snapshotHash;

    /** @var string|null The calculated scenario-hash. */
    public $scenarioHash;

    /** @var boolean Is a browser test being run?. */
    public $isBrowserTest;

    /** @var string|null The session-driver being used. */
    public $sessionDriver;

    /** @var boolean When turned on, databases will be reused when possible instead of rebuilding them. */
    public $databaseIsReusable;



    /**
     * Set the project-name.
     *
     * @param string $projectName The name of this project.
     * @return static
     */
    public function projectName($projectName): self
    {
        $this->projectName = $projectName;
        return $this;
    }

    /**
     * Set the current test-name.
     *
     * @param string $testName The name of the current test.
     * @return static
     */
    public function testName($testName): self
    {
        $this->testName = $testName;
        return $this;
    }

    /**
     * Set the connection used.
     *
     * @param string $connection The database connection to prepare.
     * @return static
     */
    public function connection($connection): self
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * Set the database driver to use when building the database ("mysql", "sqlite" etc).
     *
     * @param string $driver The database driver to use.
     * @return static
     */
    public function driver($driver): self
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Set the database host.
     *
     * @param string|null $host The database host (if relevant).
     * @return static
     */
    public function host($host): self
    {
        $this->host = $host;
        return $this;
    }

    /**
     * Set the database to use.
     *
     * @param string|null $database The name of the database to use.
     * @return static
     */
    public function database($database): self
    {
        $this->database = $database;
        return $this;
    }

    /**
     * Set the directory to store database snapshots in.
     *
     * @param string $storageDir The storage directory to use.
     * @return static
     */
    public function storageDir($storageDir): self
    {
        $this->storageDir = $storageDir;
        return $this;
    }

    /**
     * Specify the database dump files to import before migrations run.
     *
     * @param string[] $preMigrationImports The database dump files to import, one per database type.
     * @return static
     */
    public function preMigrationImports($preMigrationImports): self
    {
        $this->preMigrationImports = $preMigrationImports;
        return $this;
    }

    /**
     * Turn migrations on or off, or specify the location of the migrations to run.
     *
     * @param boolean|string $migrations Should the migrations be run? / the path of the migrations to run.
     * @return static
     */
    public function migrations($migrations): self
    {
        $this->migrations = $migrations;
        return $this;
    }

    /**
     * Specify the seeders to run.
     *
     * @param boolean  $isSeedingAllowed Whether seeding is allowed or not.
     * @param string[] $seeders          The seeders to run after migrating.
     * @return static
     */
    public function seeders($isSeedingAllowed, $seeders): self
    {
        $this->isSeedingAllowed = $isSeedingAllowed;
        $this->seeders = $isSeedingAllowed ? $seeders : [];
        return $this;
    }

    /**
     * Specify the url to send "build" requests to.
     *
     * @param boolean     $builtRemotely  Whether the database was built remotely or not.
     * @param string|null $remoteBuildUrl The remote Adapt installation to send "build" requests to.
     * @return static
     */
    public function builtRemotely($builtRemotely, $remoteBuildUrl = null): self
    {
        $this->builtRemotely = $builtRemotely;
        $this->remoteBuildUrl = $remoteBuildUrl;
        return $this;
    }

    /**
     * Turn the snapshots-enabled setting on (or off).
     *
     * @param boolean $snapshotsEnabled Whether snapshots are enabled or not.
     * @return static
     */
    public function snapshotsEnabled($snapshotsEnabled): self
    {
        $this->snapshotsEnabled = $snapshotsEnabled;
        return $this;
    }

    /**
     * Turn the is-browser-test setting on (or off).
     *
     * @param boolean $isBrowserTest Is this test a browser-test?.
     * @return static
     */
    public function isBrowserTest($isBrowserTest): self
    {
        $this->isBrowserTest = $isBrowserTest;
        return $this;
    }

    /**
     * Set the session-driver that's being used.
     *
     * @param string|null $sessionDriver The session-driver being used.
     * @return static
     */
    public function sessionDriver($sessionDriver): self
    {
        $this->sessionDriver = $sessionDriver;
        return $this;
    }

    /**
     * Turn the reusable-database setting on (or off).
     *
     * @param boolean $databaseIsReusable Is the database reusable?.
     * @return static
     */
    public function databaseIsReusable($databaseIsReusable): self
    {
        $this->databaseIsReusable = $databaseIsReusable;
        return $this;
    }

    /**
     * Turn the scenario-test-dbs setting on (or off).
     *
     * @param boolean     $usingScenarios Create databases as needed for the database-scenario?.
     * @param string|null $buildHash      The calculated build-hash.
     * @param string|null $snapshotHash   The calculated snapshot-hash.
     * @param string|null $scenarioHash   The calculated scenario-hash.
     * @return static
     */
    public function scenarioTestDBs(
        $usingScenarios,
        $buildHash,
        $snapshotHash,
        $scenarioHash
    ): self {

        $this->usingScenarios = $usingScenarios;
        $this->buildHash = $this->usingScenarios ? $buildHash : null;
        $this->snapshotHash = $this->usingScenarios ? $snapshotHash : null;
        $this->scenarioHash = $this->usingScenarios ? $scenarioHash : null;
        return $this;
    }



    /**
     * Build a new ResolvedSettingsDTO from the data given in a response from a remote Adapt installation.
     *
     * @param string $payload The raw ResolvedSettingsDTO data from the response.
     * @return self
     * @throws AdaptRemoteShareException When the version doesn't match.
     */
    public static function buildFromPayload($payload): self
    {
        if (!mb_strlen($payload)) {
            throw AdaptRemoteShareException::couldNotReadResolvedSettingsDTO();
        }

        $values = json_decode($payload, true);
        if (!is_array($values)) {
            throw AdaptRemoteShareException::couldNotReadResolvedSettingsDTO();
        }

        return static::buildFromArray($values);
    }

    /**
     * Build the value to send in responses.
     *
     * @return string
     */
    public function buildPayload(): string
    {
        return json_encode(get_object_vars($this));
    }



    /**
     * Render the "build settings" ready to be logged.
     *
     * @return array<string, string>
     */
    public function renderBuildSettings(): array
    {
        $remoteExtra = $this->builtRemotely ? ' (remote)' : '';

        $storageDir = $this->snapshotsEnabled
            ? $this->escapeString($this->storageDir) . $remoteExtra
            : null;

        $migrations = is_bool($this->migrations)
            ? ($this->migrations ? 'Yes' : 'No')
            : "\"" . $this->migrations . "\"";
        $migrations .= $remoteExtra;

        $seeders = $this->isSeedingAllowed
            ? $this->renderList($this->seeders, $remoteExtra)
            : 'n/a';

        $preMigrationImportsTitle = count($this->preMigrationImports) == 1
            ? 'Pre-migration import:'
            : 'Pre-migration imports:';

        $seedersTitle = $this->isSeedingAllowed && count($this->seeders) == 1 ? 'Seeder:' : 'Seeders:';

        $isBrowserTest = $this->renderBoolean($this->isBrowserTest, "Yes (session-driver: \"$this->sessionDriver\")");

        $isReusable = $this->renderBoolean(
            $this->databaseIsReusable,
            'Yes',
            'No, it will be rebuilt for each test'
        );

        return array_filter([
            'Project name:' => $this->escapeString($this->projectName, 'n/a'),
            'Remote-build url:' => $this->escapeString($this->remoteBuildUrl),
            'Snapshots enabled?' => $this->renderBoolean($this->snapshotsEnabled),
            'Snapshot storage dir:' => $storageDir,
            $preMigrationImportsTitle => $this->renderList($this->preMigrationImports, $remoteExtra),
            'Migrations:' => $migrations,
            $seedersTitle => $seeders,
            'Is a browser-test?' => $isBrowserTest,
            'Is reusable?' => ' ',
            '- Using transactions:' => $isReusable,
            'Using scenarios?' => $this->renderBoolean($this->usingScenarios),
            '- Build-hash:' => $this->escapeString($this->buildHash),
            '- Snapshot-hash:' => $this->escapeString($this->snapshotHash),
            '- Scenario-hash:' => $this->escapeString($this->scenarioHash),
        ]);
    }

    /**
     * Render the "resolved database" details ready to be logged.
     *
     * @return array<string, string>
     */
    public function renderResolvedDatabaseSettings(): array
    {
        return array_filter([
            'Connection:' => $this->escapeString($this->connection),
            'Driver:' => $this->escapeString($this->driver),
            'Host:' => $this->escapeString($this->host),
            'Database:' => $this->escapeString($this->database),
        ]);
    }

    /**
     * Escape a string.
     *
     * @param string|null $value   The value to escape.
     * @param string|null $default A default to use if it's empty.
     * @return string|null
     */
    private function escapeString($value, $default = null)
    {
        return mb_strlen($value)
            ? "\"$value\""
            : $default;
    }

    /**
     * Escape a string.
     *
     * @param boolean $value      The boolean value to render.
     * @param string  $trueValue  The string to use when true.
     * @param string  $falseValue The string to use when false.
     * @return string
     */
    private function renderBoolean(bool $value, string $trueValue = 'Yes', string $falseValue = 'No'): string
    {
        return $value ? $trueValue : $falseValue;
    }


    /**
     * Render a list of things, or "None when empty".
     *
     * @param string[] $things      The things to render.
     * @param string   $remoteExtra The text to add when being handled remotely.
     * @return string
     */
    private function renderList(array $things, string $remoteExtra): string
    {
        $newThings = [];
        foreach ($things as $thing) {
            $newThings[] = "\"$thing\"" . $remoteExtra;
        }

        return count($newThings)
            ? implode(PHP_EOL, $newThings)
            : 'None';
    }
}