<?php
namespace Search\Shell;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\I18n\Time;

class SearchShell extends Shell
{
    /**
     * Default date-time string for outdated searches.
     */
    const DEFAULT_MAX_LENGTH = '-3 hours';

    /**
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser() : ConsoleOptionParser
    {
        $parser = parent::getOptionParser();

        $parser->addSubcommand('cleanup', [
            'help' => 'Outdated pre-saved searches cleanup.',
            'parser' => [
                'arguments' => [
                    'time' => [
                        'help' => 'Time, as a date-time string, of pre-saved searches to keep in the database. ' .
                            'Valid formats are explained in http://php.net/manual/en/datetime.formats.php'
                    ]
                ]
            ]
        ]);

        return $parser;
    }

    /**
     * Method responsible for outdated pre-saved searches cleanup.
     *
     * @param string $time A date/time string. Valid formats are explained in http://php.net/manual/en/datetime.formats.php
     * @return void
     */
    public function cleanup(string $time = self::DEFAULT_MAX_LENGTH) : void
    {
        $table = $this->loadModel('Search.SavedSearches');

        $date = strtotime($time);

        if (false === $date) {
            $this->err(sprintf('Failed to remove pre-saved searches, unsupported time value provided: %s', $time));

            return;
        }

        $count = $table->deleteAll([
            'modified <' => $date,
            'OR' => ['name' => '', 'name IS' => null]
        ]);

        $this->info($count . ' outdated pre-saved searches removed.');
    }
}
