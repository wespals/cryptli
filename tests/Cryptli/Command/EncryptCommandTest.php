<?php

namespace Test\Cryptli\Command;

use Cryptli\Command\EncryptCommand;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception as Ex;
use Defuse\Crypto\Key;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use TypeError;

/**
 * @coversDefaultClass EncryptCommand
 */
class EncryptCommandTest extends TestCase
{

    /**
     * @var string
     */
    private const KEY_STRING = 'def0000049a9509e6fa9702e363adc827c4dc18df793ff5ace7531c4f39733604502a090d6f08dec869c7a06e959c68e94d400cc21750a53724af151de482c09de253d4d';

    /**
     * @var string
     */
    private const PLAINTEXT_SECRET = 'mySecretP@$$w0rd';

    /**
     * @covers ::nothing
     * @return CommandTester
     */
    private function getCommandTester(): CommandTester
    {
        $application = new Application();
        $application->add(new EncryptCommand());

        return new CommandTester($application->find('cryptli:encrypt'));
    }

    /**
     * @covers ::nothing
     * @return string
     */
    private static function getTestFilename(): string
    {
        return sprintf(
            "%s%s%s",
            $_ENV['OUTPUT_DIR'],
            DIRECTORY_SEPARATOR,
            'encrypt-command.test'
        );
    }

    /**
     * @covers ::execute
     * @throws TypeError
     * @throws Ex\EnvironmentIsBrokenException
     * @throws Ex\WrongKeyOrModifiedCiphertextException
     * @throws Ex\BadFormatException
     */
    public function testExecuteDisplay(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'plaintext' => self::PLAINTEXT_SECRET,
            'key' => self::KEY_STRING,
            '--do-not-trim' => false,
            '--raw-binary' => false
        ]);

        $output = trim($commandTester->getDisplay());
        $decrypted = Crypto::decrypt($output, Key::loadFromAsciiSafeString(self::KEY_STRING));
        $this->assertEquals(self::PLAINTEXT_SECRET, $decrypted);
    }

    /**
     * @covers ::execute
     */
    public function testExecuteWithOutputFilename(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'plaintext' => self::PLAINTEXT_SECRET,
            'key' => self::KEY_STRING,
            '--outputFilename' => self::getTestFilename()
        ]);

        $output = trim($commandTester->getDisplay());
        $this->assertEquals(sprintf("File written: %s", self::getTestFilename()), $output);
        $this->assertFileExists(self::getTestFilename());
        unlink(self::getTestFilename());
    }

    /**
     * @covers ::execute
     */
    public function testExecuteWithBadOutputFilename(): void
    {
        $badFilename = sprintf(
            "%s%s%s",
            $_ENV['PROTECTED_DIR'],
            DIRECTORY_SEPARATOR,
            "cryptli.test"
        );

        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'plaintext' => self::PLAINTEXT_SECRET,
            'key' => self::KEY_STRING,
            '--outputFilename' => $badFilename
        ]);

        $output = trim($commandTester->getDisplay());
        $this->assertEquals(sprintf("Unable to write file: %s", $badFilename), $output);
        $this->assertFileNotExists($badFilename);
        unlink($badFilename);
    }

    /**
     * @covers ::execute
     * @throws TypeError
     * @throws Ex\EnvironmentIsBrokenException
     * @throws Ex\WrongKeyOrModifiedCiphertextException
     * @throws Ex\BadFormatException
     */
    public function testExecuteWithBlankOutputFilename(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'plaintext' => self::PLAINTEXT_SECRET,
            'key' => self::KEY_STRING,
            '--outputFilename' => ''
        ]);

        $output = trim($commandTester->getDisplay());
        $decrypted = Crypto::decrypt($output, Key::loadFromAsciiSafeString(self::KEY_STRING));
        $this->assertEquals(self::PLAINTEXT_SECRET, $decrypted);
        $this->assertFileNotExists(self::getTestFilename());
    }

    /**
     * @covers ::execute
     * @throws TypeError
     * @throws Ex\EnvironmentIsBrokenException
     * @throws Ex\WrongKeyOrModifiedCiphertextException
     * @throws Ex\BadFormatException
     */
    public function testExecuteWithNullOutputFilename(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'plaintext' => self::PLAINTEXT_SECRET,
            'key' => self::KEY_STRING,
            '--outputFilename' => null
        ]);

        $output = trim($commandTester->getDisplay());
        $decrypted = Crypto::decrypt($output, Key::loadFromAsciiSafeString(self::KEY_STRING));
        $this->assertEquals(self::PLAINTEXT_SECRET, $decrypted);
        $this->assertFileNotExists(self::getTestFilename());
    }

    /**
     * @covers ::nothing
     */
    public static function tearDownAfterClass(): void
    {
        unlink(self::getTestFilename());
    }
}
