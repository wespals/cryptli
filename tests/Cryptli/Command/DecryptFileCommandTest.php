<?php

namespace Test\Cryptli\Command;

use Cryptli\Command\DecryptFileCommand;
use Defuse\Crypto\Exception as Ex;
use Defuse\Crypto\File;
use Defuse\Crypto\Key;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass DecryptFileCommand
 */
class DecryptFileCommandTest extends TestCase
{

    /**
     * @var string
     */
    private const KEY_STRING = 'def0000049a9509e6fa9702e363adc827c4dc18df793ff5ace7531c4f39733604502a090d6f08dec869c7a06e959c68e94d400cc21750a53724af151de482c09de253d4d';

    /**
     * @covers ::nothing
     * @throws Ex\EnvironmentIsBrokenException
     * @throws Ex\IOException
     * @throws Ex\BadFormatException
     * @throws RuntimeException
     */
    public static function setUpBeforeClass(): void
    {
        if (file_put_contents(self::getPlaintextFilename(), 'the quick brown fox jumps over the lazy dog') === false) {
            throw new RuntimeException("Unable to create test file");
        }

        File::encryptFile(
            self::getPlaintextFilename(),
            self::getEncryptedFilename(),
            Key::loadFromAsciiSafeString(
                self::KEY_STRING
            )
        );
    }

    /**
     * @covers ::nothing
     * @return CommandTester
     */
    private function getCommandTester(): CommandTester
    {
        $application = new Application();
        $application->add(new DecryptFileCommand());

        return new CommandTester($application->find('cryptli:decrypt-file'));
    }

    /**
     * @covers ::nothing
     * @return string
     */
    private static function getPlaintextFilename(): string
    {
        return sprintf(
            "%s%s%s",
            $_ENV['OUTPUT_DIR'],
            DIRECTORY_SEPARATOR,
            'decrypt-file-command-plaintext.test'
        );
    }

    /**
     * @covers ::nothing
     * @return string
     */
    private static function getEncryptedFilename(): string
    {
        return sprintf(
            "%s%s%s",
            $_ENV['OUTPUT_DIR'],
            DIRECTORY_SEPARATOR,
            'decrypt-file-command-encrypted.test'
        );
    }

    /**
     * @covers ::execute
     */
    public function testExecute(): void
    {
        $decryptedOutputFilename = sprintf(
            "%s%s%s",
            $_ENV['OUTPUT_DIR'],
            DIRECTORY_SEPARATOR,
            'decrypt-file-command-decrypted.test'
        );

        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'inputFilename' => self::getEncryptedFilename(),
            'outputFilename' => $decryptedOutputFilename,
            'key' => self::KEY_STRING,
            '--do-not-trim' => false
        ]);

        $this->assertFileExists($decryptedOutputFilename);
        $this->assertFileEquals(
            self::getPlaintextFilename(),
            $decryptedOutputFilename
        );

        unlink($decryptedOutputFilename);
    }

    /**
     * @covers ::execute
     */
    public function testExecuteDisplayWithBadKey(): void
    {
        $decryptedOutputFilename = sprintf(
            "%s%s%s",
            $_ENV['OUTPUT_DIR'],
            DIRECTORY_SEPARATOR,
            'decrypt-file-command-decrypted.test'
        );

        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'inputFilename' => self::getEncryptedFilename(),
            'outputFilename' => $decryptedOutputFilename,
            'key' => substr_replace(self::KEY_STRING, 'a', (strlen(self::KEY_STRING) - 1), 1),
            '--do-not-trim' => false
        ]);

        $output = trim($commandTester->getDisplay());
        $this->assertEquals("Data is corrupted, the checksum doesn't match", $output);
        $this->assertFileNotExists($decryptedOutputFilename);
    }

    /**
     * @covers ::nothing
     */
    public static function tearDownAfterClass(): void
    {
        unlink(self::getPlaintextFilename());
        unlink(self::getEncryptedFilename());
    }
}
