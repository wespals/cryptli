<?php

namespace Test\Cryptli\Command;

use Cryptli\Command\EncryptFileWithPasswordCommand;
use Defuse\Crypto\Exception as Ex;
use Defuse\Crypto\File;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass EncryptFileWithPasswordCommand
 */
class EncryptFileWithPasswordCommandTest extends TestCase
{

    /**
     * @var string
     */
    private const SECRET_PASSWORD = 'mySecretPassword';

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
        $application->add(new EncryptFileWithPasswordCommand());

        return new CommandTester($application->find('cryptli:encrypt-file-with-password'));
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
            'encrypt-file-with-password-command-plaintext.test'
        );
    }

    /**
     * @covers ::execute
     * @throws Ex\EnvironmentIsBrokenException
     * @throws Ex\IOException
     * @throws Ex\WrongKeyOrModifiedCiphertextException
     */
    public function testExecuteWithPasswordOption(): void
    {
        $encryptedOutputFilename = sprintf(
            "%s%s%s",
            $_ENV['OUTPUT_DIR'],
            DIRECTORY_SEPARATOR,
            'encrypt-file-with-password-command-encrypted.test'
        );

        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'inputFilename' => self::getPlaintextFilename(),
            'outputFilename' => $encryptedOutputFilename,
            '--password' => self::SECRET_PASSWORD
        ]);

        $this->assertFileExists($encryptedOutputFilename);

        $decryptedOutputFilename = sprintf(
            "%s%s%s",
            $_ENV['OUTPUT_DIR'],
            DIRECTORY_SEPARATOR,
            'encrypt-file-with-password-command-decrypted.test'
        );

        File::decryptFileWithPassword(
            $encryptedOutputFilename,
            $decryptedOutputFilename,
            self::SECRET_PASSWORD
        );

        $this->assertFileEquals(
            self::getPlaintextFilename(),
            $decryptedOutputFilename
        );

        unlink($encryptedOutputFilename);
        unlink($decryptedOutputFilename);
    }

    /**
     * @covers ::execute
     * @throws Ex\EnvironmentIsBrokenException
     * @throws Ex\IOException
     * @throws Ex\WrongKeyOrModifiedCiphertextException
     */
    public function testExecuteWithPasswordPrompt(): void
    {
        $encryptedOutputFilename = sprintf(
            "%s%s%s",
            $_ENV['OUTPUT_DIR'],
            DIRECTORY_SEPARATOR,
            'encrypt-file-with-password-command-encrypted.test'
        );

        $commandTester = $this->getCommandTester();
        $commandTester->setInputs([self::SECRET_PASSWORD, self::SECRET_PASSWORD])
            ->execute([
                'inputFilename' => self::getPlaintextFilename(),
                'outputFilename' => $encryptedOutputFilename,
            ]);

        $this->assertFileExists($encryptedOutputFilename);

        $decryptedOutputFilename = sprintf(
            "%s%s%s",
            $_ENV['OUTPUT_DIR'],
            DIRECTORY_SEPARATOR,
            'encrypt-file-with-password-command-decrypted.test'
        );

        File::decryptFileWithPassword(
            $encryptedOutputFilename,
            $decryptedOutputFilename,
            self::SECRET_PASSWORD
        );

        $this->assertFileEquals(
            self::getPlaintextFilename(),
            $decryptedOutputFilename
        );

        unlink($encryptedOutputFilename);
        unlink($decryptedOutputFilename);
    }

    /**
     * @covers ::execute
     */
    public function testExecuteWithNonMatchingPasswordPrompt(): void
    {
        $encryptedOutputFilename = sprintf(
            "%s%s%s",
            $_ENV['OUTPUT_DIR'],
            DIRECTORY_SEPARATOR,
            'encrypt-file-with-password-command-encrypted.test'
        );

        $commandTester = $this->getCommandTester();
        $commandTester->setInputs([self::SECRET_PASSWORD, 'myNonMatchingPassword'])
            ->execute([
                'inputFilename' => self::getPlaintextFilename(),
                'outputFilename' => $encryptedOutputFilename,
            ]);

        $output = trim($commandTester->getDisplay());
        $matches = [];
        preg_match("/(.*)$/", $output, $matches);
        $this->assertEquals("The passwords do not match", $matches[0]);
        $this->assertFileNotExists($encryptedOutputFilename);
    }

    /**
     * @covers ::nothing
     */
    public static function tearDownAfterClass(): void
    {
        unlink(self::getPlaintextFilename());
    }
}
