<?php

namespace PHPUnit\Extensions\Randomizer;

use PHPUnit_Framework_TestSuite;

/**
 * A PHPUnit test suite listener which, at the start of a test suite, flattens
 * the suite and randomizes the order of the tests to expose hidden test
 * dependencies.
 */
class TestSuiteListener extends \PHPUnit_Framework_BaseTestListener
{
    /**
     * The order in which tests will be run: random or normal
     * @var string
     */
    protected $order;

    /**
     * The seed for our random number generator
     * @var int
     */
    protected $seed = null;

    /**
     * Constructs a new TestSuiteListener
     *
     * @param string $order
     *      The order in which tests will be run: random or normal
     */
    public function __construct($order = 'random')
    {
        if (isset($_SERVER['ORDER'])) {
            switch ($_SERVER['ORDER']) {
                case 'normal':
                case 'random':
                    $order = $_SERVER['ORDER'];
                    break;
            }
        }

        if (isset($_SERVER['SEED'])) {
            $order = 'random';
            $this->seed = $_SERVER['SEED'];
        } elseif ($order === 'random') {
            $this->seed = mt_rand();
        }

        $this->order = $order;
    }

    /**
     * Callback for when a test suite is started
     *
     * Flattens nested test suites into a single suite and randomizes the order
     * of the tests
     *
     * @param PHPUnit_Framework_TestSuite $suite
     *      The test suite to be executed
     */
    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        if ($this->order === 'random') {
            $tests = $this->getFlattenedTests($suite);
            $this->shuffleTests($tests);
            $suite->setTests($tests);
        }
    }

    /**
     * Callback for when a test suite is finished
     *
     * Prints the seed used for the random number generator.  This is a bit of
     * a hack, but absent the ability to access PHPUnit's results printer, this
     * is about all that can be done.
     *
     * @param PHPUnit_Framework_TestSuite $suite
     *      The test suite which was executed
     */
    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        if ($this->seed) {
            echo "\n";
            echo "\n";
            echo "Randomized test order using seed {$this->seed}\n";
        }
    }

    /**
     * Flattens a test suite and returns an array of the tests to be executed
     *
     * PHPUnit test suites can actually be nested such that a test suite is
     * made up of test suites of test suites of tests.  But if we want to
     * randomize the whole lot, we must first flatten them into a single suite.
     *
     * @param PHPUnit_Framework_TestSuite $suite
     *      The test suite to be flattened
     * @return array
     *      An array of all of the tests nested within the test suite
     */
    protected function getFlattenedTests(PHPUnit_Framework_TestSuite $suite)
    {
        $tests = array();
        foreach ($suite->tests() as $test) {
            if ($test instanceof \PHPUnit_Framework_TestSuite) {
                foreach ($this->getFlattenedTests($test) as $suiteTest) {
                    $tests[] = $suiteTest;
                }
            } else {
                $tests[] = $test;
            }
        }

        return $tests;
    }

    /**
     * Shuffles the tests using mt_rand() and a fixed seed
     *
     * @param array $tests
     *      An array of tests to be shuffled
     */
    protected function shuffleTests(array &$tests)
    {
        mt_srand($this->seed);

        $max   = count($tests);
        $order = array();

        for ($i = 0; $i < $max; $i++) {
            $order[] = mt_rand();
        }

        array_multisort($order, $tests);
    }
}
