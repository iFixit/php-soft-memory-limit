<?php
declare(strict_types = 1);

namespace SoftMemoryLimit;

class MemoryChecker {
   const DEFAULT_MEMORY_RATIO = 0.8;
   private $memoryRatio;
   private $exceptionThrown;

   private static $instance = null;

   public static function getSingleton(): self {
      if (!self::$instance) {
         self::$instance = new MemoryChecker();
      }

      return self::$instance;
   }

   /**
    * Initializes the memory ratio to DEFAULT_MEMORY_RATIO and exceptionThrown to false.
    */
   protected function __construct() {
      $this->memoryRatio = self::DEFAULT_MEMORY_RATIO;
      $this->exceptionThrown = false;
   }

   /**
    * Sets the ratio that the soft memory limit should be of the hard memory limit. 
    */
   public function setSoftLimitRatio(float $ratio): void {
      $this->memoryRatio = $ratio;
   }

   /**
    * Throws a MemoryLimitExceededException
    * if peak memory usage has exceeded the soft limit.
    * 
    * Only throws an exception once during the script.
    */
   public function checkPeakMemoryUsage(): void {
      $peakMemoryUsage = memory_get_peak_usage();

      if ($peakMemoryUsage > $this->getSoftLimit()) {
         if (!$this->exceptionThrown) {
            $this->exceptionThrown = true;
            throw new MemoryLimitExceededException();
         }
      }
   }

   /**
    * Returns a integer representation of the max number of bytes
    * the script can allocate.
    */
   public function getHardLimit(): int {
      return $this->parseMemoryLimit(ini_get('memory_limit'));
   }

   public function getSoftLimit(): int {
      return intval($this->getHardLimit() * $this->memoryRatio);
   }

   /**
    * Parses
    */
   protected function parseMemoryLimit(string $memoryLimit): int {
      // If the ini value is numeric, it is a number of bytes.
      if (is_numeric($memoryLimit)) {
         return (int)$memoryLimit;
      }

      if (empty($memoryLimit)) {
          throw new ParseException("Memory limit string must not be empty.");
      }

      // memory_limit byte shorthand looks like 512K, 64M, 1G.
      $numeral = substr($memoryLimit, 0, -1);
      if (!is_numeric($numeral)) {
        throw new ParseException("Memory limit is non-numerical: $memoryLimit");
      }

      $shorthand = substr($memoryLimit, -1);

      switch (strtolower($shorthand)) {
         case 'k':
            $multiplier = 1024;
            break;
         case 'm':
            $multiplier = 1024 * 1024;
            break;
         case 'g':
            $multiplier = 1024 * 1024 * 1024;
            break;
         default:
            throw new ParseException("Unknown byte shorthand: $shorthand");
      }

      return intval($numeral) * $multiplier;
   }

   // No Cloning or Serializing.
   private function __clone() {}
   private function __wakeup() {}
}

class MemoryLimitExceededException extends \Exception {}
class ParseException extends \Exception {}
