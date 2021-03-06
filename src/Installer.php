<?php
/**
 * PHP Domain Parser: Public Suffix List based URL parsing.
 *
 * @see http://github.com/jeremykendall/php-domain-parser for the canonical source repository
 *
 * @copyright Copyright (c) 2017 Jeremy Kendall (http://jeremykendall.net)
 * @license   http://github.com/jeremykendall/php-domain-parser/blob/master/LICENSE MIT License
 */
declare(strict_types=1);

namespace Pdp;

use Composer\Script\Event;
use Throwable;

/**
 * A class to manage PSL ICANN Section rules updates
 *
 * @author Ignace Nyamagana Butera <nyamsprod@gmail.com>
 */
final class Installer
{
    /**
     * Script to update the local cache using composer hook
     *
     * @param Event $event
     */
    public static function updateLocalCache(Event $event = null)
    {
        $io = static::getIO($event);
        $vendor = static::getVendorPath($event);
        if (null === $vendor) {
            $io->writeError([
                'You must set up the project dependencies using composer',
                'see https://getcomposer.org',
            ]);
            die(1);
        }

        require $vendor.'/autoload.php';

        $io->write('Updating your Public Suffix List local cache.');
        if (!extension_loaded('curl')) {
            $io->writeError([
                '😓 😓 😓 Your local cache could not be updated. 😓 😓 😓',
                'The PHP cURL extension is missing.',
            ]);
            die(1);
        }

        try {
            $manager = new Manager(new Cache(), new CurlHttpClient());
            if ($manager->refreshRules()) {
                $io->write([
                    '💪 💪 💪 Your local cache has been successfully updated. 💪 💪 💪',
                    'Have a nice day!',
                ]);
                die(0);
            }
            $io->writeError([
                '😓 😓 😓 Your local cache could not be updated. 😓 😓 😓',
                'Please verify you can write in your local cache directory.',
            ]);
            die(1);
        } catch (Throwable $e) {
            $io->writeError([
                '😓 😓 😓 Your local cache could not be updated. 😓 😓 😓',
                'An error occurred during the update.',
                '----- Error Message ----',
            ]);
            $io->writeError($e->getMessage());
            die(1);
        }
    }

    /**
     * Detect the vendor path
     *
     * @param Event $event
     *
     * @return string|null
     */
    private static function getVendorPath(Event $event = null)
    {
        if (null !== $event) {
            return $event->getComposer()->getConfig()->get('vendor-dir');
        }

        if (is_dir($vendor = dirname(__DIR__, 2).'/vendor')) {
            return $vendor;
        }

        if (is_dir($vendor = dirname(__DIR__, 5).'/vendor')) {
            return $vendor;
        }

        return null;
    }

    /**
     * Detect the I/O interface to use
     *
     * @param Event|null $event
     *
     * @return mixed
     */
    private static function getIO(Event $event = null)
    {
        if (null !== $event) {
            return $event->getIO();
        }

        return new class() {
            public function write($messages, bool $newline = true, int $verbosity = 2)
            {
                $this->doWrite($messages, $newline, false, $verbosity);
            }

            public function writeError($messages, bool $newline = true, int $verbosity = 2)
            {
                $this->doWrite($messages, $newline, true, $verbosity);
            }

            private function doWrite($messages, bool $newline, bool $stderr, int $verbosity)
            {
                fwrite(
                    $stderr ? STDERR : STDOUT,
                    implode($newline ? PHP_EOL : '', (array) $messages).PHP_EOL
                );
            }
        };
    }
}
