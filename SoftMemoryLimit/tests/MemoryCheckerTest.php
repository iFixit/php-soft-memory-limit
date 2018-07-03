<?php
declare(strict_types = 1);

use PHPUnit\Framework\TestCase;

require_once 'MemoryChecker.php';

class MemoryCheckerTest extends TestCase {
    // A small enough ratio to trigger the soft memory limit.
    const SMALL_RATIO = 0.00001;

    public function testInitializedMemoryChecker() {
        $memoryChecker = \SoftMemoryLimit\MemoryChecker::getSingleton();

        $hardLimit = $memoryChecker->getHardLimit();
        $softLimit = $memoryChecker->getSoftLimit();

        $this->assertSame(intval($hardLimit * 0.8), $softLimit);
    }

    public function testGetHardLimit() {
        $memoryLimit = 1024 * 1024;
        $memoryChecker = \SoftMemoryLimit\MemoryChecker::getSingleton();

        // Set it to a MB or something.
        ini_set('memory_limit', "$memoryLimit");
        $this->assertSame($memoryLimit, $memoryChecker->getHardLimit());
    }

    public function testGetSoftLimit() {
        $ratio = 0.5;
        $memoryChecker = \SoftMemoryLimit\MemoryChecker::getSingleton();

        $expectedSoftLimit = intval($memoryChecker->getHardLimit() * $ratio);
        $memoryChecker->setSoftLimitRatio($ratio);

        $this->assertSame($expectedSoftLimit, $memoryChecker->getSoftLimit());
    }

    /**
     * @expectedException \SoftMemoryLimit\MemoryLimitExceededException
     */
    public function testCheckPeakMemoryUsage() {
        $memoryChecker = \SoftMemoryLimit\MemoryChecker::getSingleton();

        $memoryChecker->setSoftLimitRatio(self::SMALL_RATIO);
        $memoryChecker->checkPeakMemoryUsage();
    }

    public function testNoDoubleException() {
        $memoryChecker = \SoftMemoryLimit\MemoryChecker::getSingleton();
        $memoryChecker->setSoftLimitRatio(self::SMALL_RATIO);

        // Catch the exception thrown on the first time.
        try {
            $memoryChecker->checkPeakMemoryUsage();
        } catch (\SoftMemoryLimit\MemoryLimitExceededException $e) {}

        $thrown = false;

        // Assert that no exception is thrown on the second time.
        try {
            $memoryChecker->checkPeakMemoryUsage();
        } catch (\SoftMemoryLimit\MemoryLimitExceededException $e) {
            $thrown = true;
        } finally {
            $this->assertFalse($thrown);
        }

        // Assert that the singleton keeps the state for whether or not
        // an exception has already been thrown.
        $memoryChecker2 = \SoftMemoryLimit\MemoryChecker::getSingleton();
        $thrown = false;

        try {
            $memoryChecker2->checkPeakMemoryUsage();
        } catch (\SoftMemoryLimit\MemoryLimitExceededException $e) {
            $thrown = true;
        } finally {
            $this->assertFalse($thrown);
        }
    }

    /**
     * @expectedException \SoftMemoryLimit\ParseException
     */
    public function testParseMemoryLimitBadAlphaString() {
        $memoryChecker = new MockMemoryChecker();
        $memoryChecker->parseMemoryLimit('abc');
    }

    /**
     * @expectedException \SoftMemoryLimit\ParseException
     */
    public function testParseMemoryLimitEmptyString() {
        $memoryChecker = new MockMemoryChecker();
        $memoryChecker->parseMemoryLimit('');
    }

    public function testParseMemoryLimitNumericString() {
        $memoryChecker = new MockMemoryChecker();
        $this->assertSame(1234, $memoryChecker->parseMemoryLimit('1234'));
    }

    public function testParseMemoryLimitGoodAlphaNumericString() {
        $memoryChecker = new MockMemoryChecker();

        $this->assertSame(100 * 1024, $memoryChecker->parseMemoryLimit('100K'));
        $this->assertSame(10 * 1024 * 1024, $memoryChecker->parseMemoryLimit('10M'));
        $this->assertSame(1 * 1024 * 1024 * 1024, $memoryChecker->parseMemoryLimit('1G'));
    }

    /**
     * @expectedException \SoftMemoryLimit\ParseException
     */
    public function testParseMemoryLimitTooManyAlphaCharacters() {
        $memoryChecker = new MockMemoryChecker();
        $memoryChecker->parseMemoryLimit('100KK');
    }

    /**
     * @expectedException \SoftMemoryLimit\ParseException
     */
    public function testParseMemoryLimitInvalidShorthand() {
        $memoryChecker = new MockMemoryChecker();
        $memoryChecker->parseMemoryLimit('10J');
    }
}

/**
 * Mocked version of \SoftMemoryLimit\MemoryChecker so that we can unit test
 * protected methods.
 */
class MockMemoryChecker extends \SoftMemoryLimit\MemoryChecker {
    public function __construct() {
        parent::__construct();
    }

    public function parseMemoryLimit(string $memoryLimit): int {
        return parent::parseMemoryLimit($memoryLimit);
    }
}
