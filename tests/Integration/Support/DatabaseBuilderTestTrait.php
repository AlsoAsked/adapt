<?php

namespace CodeDistortion\Adapt\Tests\Integration\Support;

use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DI\Injectable\Exec;
use CodeDistortion\Adapt\DI\Injectable\Filesystem;
use CodeDistortion\Adapt\DI\Injectable\LaravelArtisan;
use CodeDistortion\Adapt\DI\Injectable\LaravelConfig;
use CodeDistortion\Adapt\DI\Injectable\LaravelDB;
use CodeDistortion\Adapt\DI\Injectable\LaravelLog;
use CodeDistortion\Adapt\DTO\ConfigDTO;
use CodeDistortion\Adapt\Tests\Database\Seeders\DatabaseSeeder;
use DB;
use ErrorException;
use Exception;

/**
 * Contains methods to set up a /database directory structure for testing and create a DatabaseBuilder.
 */
trait DatabaseBuilderTestTrait
{
    /**
     * The directory containing the test-workspaces.
     *
     * @var string
     */
    private $workspaceBaseDir = 'tests/workspaces';

    /**
     * The current workspace directory - used during testing.
     *
     * @var string
     */
    private $wsCurrentDir = 'tests/workspaces/current';

    /**
     * The current workspace config directory.
     *
     * @var string
     */
    private $wsConfigDir = 'tests/workspaces/config';

    /**
     * The current workspace adapt-test-storage directory.
     *
     * @var string
     */
    private $wsAdaptStorageDir = 'tests/workspaces/current/database/adapt-test-storage';

    /**
     * The current workspace databases directory.
     *
     * @var string
     */
    private $wsDatabaseDir = 'tests/workspaces/current/database/databases';

    /**
     * The current workspace factories directory.
     *
     * @var string
     */
    private $wsFactoriesDir = 'tests/workspaces/current/database/factories';

    /**
     * The current workspace migrations directory.
     *
     * @var string
     */
    private $wsMigrationsDir = 'tests/workspaces/current/database/migrations';

    /**
     * The current workspace pre-migration-imports directory.
     *
     * @var string
     */
    private $wsPreMigrationsDir = 'tests/workspaces/current/database/pre-migration-imports';

    /**
     * The current workspace seeds directory.
     *
     * @var string
     */
    private $wsSeedsDir = 'tests/workspaces/current/database/seeds';


    /**
     * Build a new DIContainer object with defaults set.
     *
     * @param string $connection The connection to build a database for.
     * @return DIContainer
     */
    private function newDIContainer(string $connection): DIContainer
    {
        return (new DIContainer())
            ->artisan(new LaravelArtisan)
            ->config(new LaravelConfig)
            ->db((new LaravelDB)->useConnection($connection))
            ->dbTransactionClosure(function () {
            })
            ->log(new LaravelLog(false, false))
            ->exec(new Exec)
            ->filesystem(new Filesystem);
    }

    /**
     * Build a new ConfigDTO object with defaults set.
     *
     * @param string $connection The connection to build a database for.
     * @return ConfigDTO
     */
    private function newConfigDTO(string $connection): ConfigDTO
    {
        return (new ConfigDTO)
            ->projectName('')
            ->connection($connection)
//            ->database('test_db')
            ->storageDir($this->wsAdaptStorageDir)
            ->snapshotPrefix('snapshot.')
            ->databasePrefix('test-')
            ->hashPaths([
                $this->wsFactoriesDir,
                $this->wsMigrationsDir,
                $this->wsPreMigrationsDir,
                $this->wsSeedsDir,
            ])
            ->buildSettings(
                [],
                $this->wsMigrationsDir,
                [DatabaseSeeder::class],
                false
            )
            ->cacheTools(true, true, true)
            ->snapshots(false, false, true)
            ->mysqlSettings('mysql', 'mysqldump')
            ->postgresSettings('psql', 'pg_dump');
    }

    /**
     * Build a new DatabaseBuilder object.
     *
     * @param ConfigDTO|null   $config The ConfigDTO to use.
     * @param DIContainer|null $di     The DIContainer to use.
     * @return DatabaseBuilder
     */
    private function newDatabaseBuilder($config = null, $di = null): DatabaseBuilder
    {
        $config = $config ?? $this->newConfigDTO('sqlite');
        $di = $di ?? $this->newDIContainer($config->connection);

        $pickDriver = function (string $connection) {
            return config("database.connections.$connection.driver", 'unknown');
        };

        return new DatabaseBuilder('laravel', 'A test', $di, $config, $pickDriver);
    }


    /**
     * Prepare the workspace directory by emptying it and copying the contents of another in to it.
     *
     * @param string $sourceDir The directory to make a copy of.
     * @param string $destDir   The directory to replace.
     * @return void
     */
    private function prepareWorkspace(string $sourceDir, string $destDir)
    {
        $this->delTree($destDir);
        $this->copyDirRecursive($sourceDir, $destDir);
        $this->createGitIgnoreFile($destDir.'/.gitignore');
        $this->loadConfigs($destDir.'/config');
    }

    /**
     * Remove the given directory and it's contents.
     *
     * @param string $dir The directory to remove.
     * @return boolean
     */
    private function delTree(string $dir): bool
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            if (is_dir("$dir/$file")) {
                $this->delTree("$dir/$file");
            } else {
                unlink("$dir/$file");
            }
        }
        return rmdir($dir);
    }

    /**
     * Recursively copy a directory.
     *
     * @param string $sourceDir The directory to read from.
     * @param string $destDir   The directory to write to (will be created if it doesn't exist).
     * @return void
     */
    private function copyDirRecursive(string $sourceDir, string $destDir)
    {
        @mkdir($destDir);
        $files = array_diff(scandir($sourceDir), ['.', '..']);
        foreach ($files as $file) {
            if (is_dir("$sourceDir/$file")) {
                $this->copyDirRecursive("$sourceDir/$file", "$destDir/$file");
            } else {
                copy("$sourceDir/$file", "$destDir/$file");
            }
        }
    }

    /**
     * Create a .gitignore file in the given directory to ignore all files.
     *
     * @param string $destPath The location to write the file in.
     * @return boolean
     */
    private function createGitIgnoreFile(string $destPath): bool
    {
        $fp = fopen($destPath, 'w');
        if (!$fp) {
            return false;
        }

        fwrite($fp, '*'.PHP_EOL);
        fwrite($fp, '!.gitignore'.PHP_EOL);
        fclose($fp);
        return true;
    }


    /**
     * Load the laravel config settings from the files in $dir.
     *
     * @param string $dir The directory to look for config files in.
     * @return void
     */
    private function loadConfigs(string $dir)
    {
        foreach ($this->pickConfigFiles($dir) as $configName => $path) {
            config([$configName => require($path)]);
        }

        // put the default sqlite database within the workspace
        config(['database.connections.sqlite.database' => "$this->wsDatabaseDir/database.sqlite"]);
    }

    /**
     * Find the Laravel config files in the given directory.
     *
     * @param string $dir The directory to look in.
     * @return array<string, string>
     */
    private function pickConfigFiles(string $dir): array
    {
        try {
            $files = scandir($dir);
            $files = (is_array($files) ? $files : []);
            return $this->mapConfigPaths($dir, $files);
        } catch (ErrorException $e) {
            return [];
        }
    }

    /**
     * @param string $dir   The directory the files are in.
     * @param array  $files The files in the directory.
     * @return array<string, string>
     */
    private function mapConfigPaths(string $dir, array $files): array
    {
        $return = [];
        foreach ($files as $file) {

            if (!$this->isPHPFile("$dir/$file")) {
                continue;
            }

            $configName = mb_substr($file, 0, -4);
            $return[$configName] = "$dir/$file";
        }
        return $return;
    }

    /**
     * Check if the given path is a php file.
     *
     * @param string $path The path to check.
     * @return boolean
     */
    private function isPHPFile(string $path): bool
    {
        return ((mb_substr($path, -4) == '.php') && (is_file($path)));
    }

    /**
     * Determine the database driver for the given connection.
     *
     * @param string $connection The connection to grab the database-driver for.
     * @return string|null
     */
    private function getDBDriver(string $connection)
    {
        return config("database.connections.$connection.driver", 'unknown');
    }

    /**
     * Check that the existing tables match an expected list.
     *
     * @param string   $connection     The connection to check on.
     * @param string[] $expectedTables The expected tables.
     * @return void
     * @throws Exception Thrown when an unknown database driver is found.
     */
    private function assertTableList(string $connection, array $expectedTables)
    {
        switch ($this->getDBDriver($connection)) {
            case 'mysql':
                throw new Exception('mysql driver not implemented yet');
                break;
            case 'sqlite':
                $this->assertQueryValues(
                    $connection,
                    "SELECT name FROM sqlite_master WHERE type='table'",
                    [],
                    $expectedTables,
                    true
                );
                break;
            default:
                throw new Exception('Unknown database driver');
        }
    }


    /**
     * Check that the values of a particular field in a table match the expected values.
     *
     * @param string            $connection     The connection to query on.
     * @param ExpectedValuesDTO $expectedValues The expected values.
     * @return void
     */
    private function assertTableValues(string $connection, ExpectedValuesDTO $expectedValues)
    {
        $escFields = "`".implode('`, `', $expectedValues->fields)."`";
        $rows = DB::connection($connection)->select("SELECT ".$escFields." FROM `".$expectedValues->table."`");

        $values = collect($rows)->map(function ($row) use ($expectedValues) {
            $return = [];
            foreach ($expectedValues->fields as $field) {
                $return[] = $row->$field;
            }
            return $return;
        })->toArray();

        $this->assertSame($expectedValues->values, $values);
    }

    /**
     * Check that the values of a particular field in a table match the expected values.
     *
     * @param string  $connection The connection to query on.
     * @param string  $query      The query to run.
     * @param mixed[] $values     The values to use in the query.
     * @param array   $expected   The expected values.
     * @param boolean $sort       Sort the values before comparing?.
     * @return void
     */
    private function assertQueryValues(
        string $connection,
        string $query,
        array $values,
        array $expected,
        bool $sort = false
    ) {
        $rows = DB::connection($connection)->select($query, $values);

        $values =[];
        if (count($rows)) {
            $fieldNames = array_keys((array) $rows[0]);
            $firstField = reset($fieldNames);
            $values = collect($rows)->pluck($firstField)->toArray();
        }
        if ($sort) {
            sort($values);
            sort($expected);
        }

        $this->assertSame($expected, $values);
    }
}
