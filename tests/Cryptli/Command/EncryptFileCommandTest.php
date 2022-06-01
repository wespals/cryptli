<?php

namespace Test\Cryptli\Command;

use Cryptli\Command\EncryptFileCommand;
use Defuse\Crypto\Exception as Ex;
use Defuse\Crypto\File;
use Defuse\Crypto\Key;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass EncryptFileCommand
 */
class EncryptFileCommandTest extends TestCase
{

    /**
     * @var string
     */
    private const KEY_STRING = 'def0000049a9509e6fa9702e363adc827c4dc18df793ff5ace7531c4f39733604502a090d6f08dec869c7a06e959c68e94d400cc21750a53724af151de482c09de253d4d';

    /**
     * @covers ::nothing
     * @throws RuntimeException
     */
    public static function setUpBeforeClass(): void
    {
        if (file_put_contents(self::getPlaintextFilename(), 'the quick brown fox jumps over the lazy dog') === false) {
            throw new RuntimeException("Unable to create test file");
        }
    }

    /**
     * @covers ::nothing
     * @return CommandTester
     */
    private function getCommandTester(): CommandTester
    {
        $application = new Application();
        $application->add(new EncryptFileCommand());

        return new CommandTester($application->find('cryptli:encrypt-file'));
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
            'encrypt-file-command-plaintext.test'
        );
    }

    /**
     * @covers ::execute
     * @throws Ex\EnvironmentIsBrokenException
     * @throws Ex\IOException
     * @throws Ex\WrongKeyOrModifiedCiphertextException
     * @throws Ex\BadFormatException
     */
    public function testExecute(): void
    {
        $encryptedOutputFilename = sprintf(
            "%s%s%s",
            $_ENV['OUTPUT_DIR'],
            DIRECTORY_SEPARATOR,
            'encrypt-file-command-encrypted.test'
        );

        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'inputFilename' => self::getPlaintextFilename(),
            'outputFilename' => $encryptedOutputFilename,
            'key' => self::KEY_STRING,
            '--do-not-trim' => false
        ]);

        $this->assertFileExists($encryptedOutputFilename);

        $decryptedOutputFilename = sprintf(
            "%s%s%s",
            $_ENV['OUTPUT_DIR'],
            DIRECTORY_SEPARATOR,
            'encrypt-file-command-decrypted.test'
        );

        File::decryptFile(
            $encryptedOutputFilename,
            $decryptedOutputFilename,
            Key::loadFromAsciiSafeString(
                self::KEY_STRING
            )
        );

        $this->assertFileEquals(
            self::getPlaintextFilename(),
            $decryptedOutputFilename
        );

        unlink($encryptedOutputFilename);
        unlink($decryptedOutputFilename);
    }

    /**
     * @covers ::nothing
     */
    public static function tearDownAfterClass(): void
    {
        unlink(self::getPlaintextFilename());
    }
}
