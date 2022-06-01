<?php

namespace Test\Cryptli\Command;

use Cryptli\Command\DecryptFileWithPasswordCommand;
use Defuse\Crypto\Exception as Ex;
use Defuse\Crypto\File;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass DecryptFileWithPasswordCommand
 */
class DecryptFileWithPasswordCommandTest extends TestCase
{

    /**
     * @var string
     */
    private const SECRET_PASSWORD = 'mySecretPassword';

    /**
     * @covers ::nothing
     * @return CommandTester
     */
    private function getCommandTester(): CommandTester
    {
        $application = new Application();
        $application->add(new DecryptFileWithPasswordCommand());

        return new CommandTester($application->find('cryptli:decrypt-file-with-password'));
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
            'decrypt-file-with-password-command-plaintext.test'
        );
    }

    /**
     * @covers ::nothing
     * @throws Ex\EnvironmentIsBrokenException
     * @throws Ex\IOException
     * @throws RuntimeException
     */
    public static function setUpBeforeClass(): void
    {
        if (file_put_contents(self::getPlaintextFilename(), 'the quick brown fox jumps over the lazy dog') === false) {
            throw new RuntimeException("Unable to create test file");
        }

        File::encryptFileWithPassword(
            self::getPlaintextFilename(),
            self::getEncryptedFilename(),
            self::SECRET_PASSWORD
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
            'decrypt-file-with-password-command-encrypted.test'
        );
    }

    /**
     * @covers ::execute
     */
    public function testExecuteWithPasswordOption(): void
    {
        $decryptedOutputFilename = sprintf(
            "%s%s%s",
            $_ENV['OUTPUT_DIR'],
            DIRECTORY_SEPARATOR,
            'decrypt-file-with-password-command-decrypted.test'
        );

        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'inputFilename' => self::getEncryptedFilename(),
            'outputFilename' => $decryptedOutputFilename,
            '--password' => self::SECRET_PASSWORD
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
    public function testExecuteWithBadPasswordOption(): void
    {
        $decryptedOutputFilename = sprintf(
            "%s%s%s",
            $_ENV['OUTPUT_DIR'],
            DIRECTORY_SEPARATOR,
            'decrypt-file-with-password-command-decrypted.test'
        );

        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'inputFilename' => self::getEncryptedFilename(),
            'outputFilename' => $decryptedOutputFilename,
            '--password' => 'myNotSoGoodPassword',
        ]);

        $output = trim($commandTester->getDisplay());
        $this->assertEquals("Integrity check failed.", $output);
        $this->assertFileNotExists($decryptedOutputFilename);
    }

    /**
     * @covers ::execute
     */
    public function testExecuteWithPasswordPrompt(): void
    {
        $decryptedOutputFilename = sprintf(
            "%s%s%s",
            $_ENV['OUTPUT_DIR'],
            DIRECTORY_SEPARATOR,
            'decrypt-file-with-password-command-decrypted.test'
        );

        $commandTester = $this->getCommandTester();
        $commandTester->setInputs([self::SECRET_PASSWORD, self::SECRET_PASSWORD])
            ->execute([
                'inputFilename' => self::getEncryptedFilename(),
                'outputFilename' => $decryptedOutputFilename
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
    public function testExecuteWithBadPasswordPrompt(): void
    {
        $decryptedOutputFilename = sprintf(
            "%s%s%s",
            $_ENV['OUTPUT_DIR'],
            DIRECTORY_SEPARATOR,
            'decrypt-file-with-password-command-decrypted.test'
        );

        $commandTester = $this->getCommandTester();
        $commandTester->setInputs(['myNotSoGoodPassword', 'myNotSoGoodPassword'])
            ->execute([
                'inputFilename' => self::getEncryptedFilename(),
                'outputFilename' => $decryptedOutputFilename
            ]);

        $output = trim($commandTester->getDisplay());
        $matches = [];
        preg_match("/(.*)$/", $output, $matches);
        $this->assertEquals("Integrity check failed.", $matches[0]);
        $this->assertFileNotExists($decryptedOutputFilename);
    }

    /**
     * @covers ::execute
     */
    public function testExecuteWithNonMatchingPasswordPrompt(): void
    {
        $decryptedOutputFilename = sprintf(
            "%s%s%s",
            $_ENV['OUTPUT_DIR'],
            DIRECTORY_SEPARATOR,
            'decrypt-file-with-password-command-decrypted.test'
        );

        $commandTester = $this->getCommandTester();
        $commandTester->setInputs([self::SECRET_PASSWORD, 'myNotSoGoodPassword'])
            ->execute([
                'inputFilename' => self::getEncryptedFilename(),
                'outputFilename' => $decryptedOutputFilename
            ]);

        $output = trim($commandTester->getDisplay());
        $matches = [];
        preg_match("/(.*)$/", $output, $matches);
        $this->assertEquals("The passwords do not match", $matches[0]);
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
