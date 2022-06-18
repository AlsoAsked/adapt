<?php

namespace CodeDistortion\Adapt\Boot;

use CodeDistortion\Adapt\DatabaseBuilder;
use CodeDistortion\Adapt\DI\DIContainer;
use CodeDistortion\Adapt\DI\Injectable\Interfaces\LogInterface;
use CodeDistortion\Adapt\DTO\PropBagDTO;
use CodeDistortion\Adapt\Exceptions\AdaptConfigException;
use CodeDistortion\Adapt\Support\Settings;

/**
 * Bootstrap Adapt for tests.
 */
abstract class BootTestAbstract implements BootTestInterface
{
    /** @var LogInterface The LogInterface to use. */
    protected LogInterface $log;

    /** @var string The name of the test being run. */
    protected string $testName;

    /** @var PropBagDTO The properties that were present in the test-class. */
    protected PropBagDTO $propBag;

    /** @var boolean Whether a browser test is being run. */
    protected bool $browserTestDetected = false;

    /** @var boolean Whether Pest is being used for this test or not. */
    protected bool $usingPest = false;

    /** @var callable|null The callback closure to call that will initialise the DatabaseBuilder/s. */
    private $initCallback;

    /** @var DatabaseBuilder[] The database builders made by this object (so they can be executed afterwards). */
    private array $builders = [];

//    /** @var DIContainer|null The DIContainer to be used. */
//    protected ?DIContainer $di = null;



    /**
     * Set the LogInterface to use.
     *
     * @param LogInterface $log The logger to use.
     * @return static
     */
    public function log(LogInterface $log): self
    {
        $this->log = $log;
        return $this;
    }

    /**
     * Set the name of the test being run.
     *
     * @param string $testName The name of the test being run.
     * @return static
     */
    public function testName(string $testName): self
    {
        $this->testName = $testName;
        return $this;
    }

    /**
     * Specify the properties that were present in the test-class.
     *
     * @param PropBagDTO $propBag A populated PropBagDTO.
     * @return static
     */
    public function props(PropBagDTO $propBag): self
    {
        $this->propBag = $propBag;
        return $this;
    }

    /**
     * Specify if a browser test is being run.
     *
     * @param boolean $browserTestDetected Whether or not a browser test is being run.
     * @return static
     */
    public function browserTestDetected(bool $browserTestDetected): self
    {
        $this->browserTestDetected = $browserTestDetected;
        return $this;
    }

    /**
     * Specify whether Pest is being used or not.
     *
     * @param boolean $usingPest Whether Pest is being used for this test or not.
     * @return static
     */
    public function usingPest(bool $usingPest): self
    {
        $this->usingPest = $usingPest;
        return $this;
    }

    /**
     * Specify the callback closure to call that will initialise the DatabaseBuilder/s.
     *
     * @param callable|null $initCallback The closure to use.
     * @return static
     */
    public function initCallback(?callable $initCallback): self
    {
        $this->initCallback = $initCallback;
        return $this;
    }

    /**
     * Specify the DIContainer to use.
     *
     * @param DIContainer $di The DIContainer to use.
     * @return static
     */
//    public function setDI(DIContainer $di): self
//    {
//        $this->di = $di;
//        return $this;
//    }

    /**
     * Store the give DatabaseBuilder.
     *
     * @param DatabaseBuilder $builder The database builder to store.
     * @return void
     */
    public function addBuilder(DatabaseBuilder $builder): void
    {
        $this->builders[] = $builder;
    }



    /**
     * Run the process to build the databases.
     *
     * @return void
     */
    public function runBuildSteps(): void
    {
        $this->isAllowedToRun();

//        $this->resolveDI();
        $this->initBuilders();
        $this->purgeStaleThings();

        foreach ($this->pickBuildersToExecute() as $builder) {
            $builder->execute();
        }

        $this->registerConnectionDBs();
    }

    /**
     * Perform things AFTER BUILDING, but BEFORE the TEST has run.
     *
     * e.g. Start the re-use transaction.
     *
     * @return void
     */
    public function runPostBuildSteps(): void
    {
        foreach ($this->pickExecutedBuilders() as $builder) {
            $builder->runPostBuildSteps();
        }

        $this->log->vDebug("The test will run now");
    }

    /**
     * Perform things AFTER the TEST has run.
     *
     * @return void
     */
    public function runPostTestSteps(): void
    {
        $count = 0;
        foreach ($this->builders as $builder) {
            $isLast = (++$count == count($this->builders));
            $builder->runPostTestSteps($isLast);
        }
    }

    /**
     * Perform any clean-up needed after the test has finished.
     *
     * @return void
     */
    abstract public function runPostTestCleanUp(): void;



    /**
     * Check that it's safe to run.
     *
     * @return void
     * @throws AdaptConfigException When the .env.testing file wasn't used to build the environment.
     */
    abstract protected function isAllowedToRun(): void;

    /**
     * Initialise the builders, calling the custom databaseInit(…) method if it has been defined.
     *
     * @return void
     */
    private function initBuilders(): void
    {
        if (!$this->propBag->adaptConfig('build_databases', 'buildDatabases')) {
            return;
        }

        $builder = $this->newDefaultBuilder();

        if (!$this->initCallback) {
            return;
        }

        $callback = $this->initCallback;
        $callback($builder);
    }

    /**
     * Create a new DatabaseBuilder object based on the "default" database connection.
     *
     * @return DatabaseBuilder
     */
    abstract protected function newDefaultBuilder(): DatabaseBuilder;

    /**
     * Pick the list of Builders that haven't been executed yet.
     *
     * @return DatabaseBuilder[]
     */
    private function pickBuildersToExecute(): array
    {
        $builders = [];
        foreach ($this->builders as $builder) {
            if (!$builder->hasExecuted()) {
                $builders[] = $builder;
            }
        }
        return $builders;
    }

    /**
     * Pick the list of Builders that have been executed.
     *
     * @return DatabaseBuilder[]
     */
    private function pickExecutedBuilders(): array
    {
        $builders = [];
        foreach ($this->builders as $builder) {
            if ($builder->hasExecuted()) {
                $builders[] = $builder;
            }
        }
        return $builders;
    }

    /**
     * Check to see if any builders will build locally.
     *
     * @return boolean
     */
    private function hasBuildersThatWillBuildLocally(): bool
    {
        $builders = $this->pickBuildersToExecute();
        foreach ($builders as $builder) {
            if (!$builder->shouldBuildRemotely()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Pick the connections' databases, and register them with the framework.
     *
     * This is done so that user-land code can get the list. e.g. to include in headers to the
     *
     * @return void
     */
    private function registerConnectionDBs(): void
    {
        $this->registerPreparedConnectionDBsWithFramework($this->buildConnectionDBsList());
    }

    /**
     * Record the list of connections and their databases with the framework.
     *
     * @param array<string,string> $connectionDatabases The connections and the databases created for them.
     * @return void
     */
    abstract protected function registerPreparedConnectionDBsWithFramework(array $connectionDatabases): void;

    /**
     * Build the list of connections that Adapt has prepared, and their corresponding databases.
     *
     * @return array<string, string>
     */
    public function buildConnectionDBsList(): array
    {
        $connectionDatabases = [];
        foreach ($this->builders as $builder) {
            $connectionDatabases[$builder->getConnection()] = $builder->getResolvedDatabase();
        }
        return $connectionDatabases;
    }

    /**
     * Use the existing DIContainer, but build a default one if it hasn't been set.
     *
     * @return void
     */
//    private function resolveDI(): void
//    {
//        if (!$this->di) {
//            $this->setDI($this->defaultDI());
//        }
//    }

    /**
     * Build a default DIContainer object.
     *
     * @param string $connection The connection to start using.
     * @return DIContainer
     */
    abstract protected function defaultDI(string $connection): DIContainer;

    /**
     * Handle the process to remove stale databases, snapshots and orphaned config files.
     *
     * @return void
     */
    private function purgeStaleThings(): void
    {
        if (!Settings::$isFirstTest) {
            return;
        }
        Settings::$isFirstTest = false;

        if (!$this->hasBuildersThatWillBuildLocally()) {
            return;
        }

        $this->performPurgeOfStaleThings();
    }

    /**
     * Remove stale databases, snapshots and orphaned config files.
     *
     * @return void
     */
    abstract protected function performPurgeOfStaleThings(): void;

    /**
     * Work out if stale things are allowed to be purged.
     *
     * @return boolean
     */
    abstract protected function canPurgeStaleThings(): bool;
}
