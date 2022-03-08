<?php

namespace ResearchBriefing\Services\Logger;

use DateTimeZone;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;

class ImportLogger extends MonologLogger
{
    /**
     * ImportLogger constructor.
     *
     * @param string             $name       The logging channel, a simple descriptive name that is attached to all log records
     * @param HandlerInterface[] $handlers   Optional stack of handlers, the first one in the array is called first, etc.
     * @param callable[]         $processors Optional array of processors
     * @param ?DateTimeZone      $timezone   Optional timezone, if not provided date_default_timezone_get() will be used
     * @throws \Exception
     */
    public function __construct(string $name, array $handlers = [], array $processors = [], ?DateTimeZone $timezone = null)
    {
        parent::__construct($name, $handlers, $processors, $timezone);

        $logFile = 'import.' . $name . '.log';
        $logFileLocation = __DIR__ . '/../../../../../../../../var/log/' . $logFile;
        if (!file_exists($logFileLocation)) {
            touch($logFileLocation);
        }

        $this->pushHandler(new StreamHandler($logFileLocation, ImportLogger::DEBUG));
    }
}
