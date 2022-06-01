<?php

namespace Test\Cryptli\Command;

use Cryptli\Command\EncryptWithPasswordCommand;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception as Ex;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use TypeError;

/**
 * @coversDefaultClass EncryptWithPasswordCommand
 */
class EncryptWithPasswordCommandTest extends TestCase
{

    /**
     * @var string
     */
    private const PLAINTEXT_SECRET = '4111111111111111';

    /**
     * @var string
     */
    private const SECRET_PASSWORD = 'mySeceretPassword';

    /**
     * @covers ::nothing
     * @return CommandTester
     */
    private function getCommandTester(): CommandTester
    {
        $application = new Application();
        $application->add(new EncryptWithPasswordCommand());

        return new CommandTester($application->find('cryptli:encrypt-with-password'));
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
            'encrypt-with-password-command.test'
        );
    }

    /**
     * @covers ::execute
     * @throws Ex\EnvironmentIsBrokenException
     * @throws Ex\WrongKeyOrModifiedCiphertextException
     * @throws TypeError
     */
    public function testExecuteWithPasswordOption(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'plaintext' => self::PLAINTEXT_SECRET,
            '--password' => self::SECRET_PASSWORD,
            '--raw-binary' => false
        ]);

        $output = trim($commandTester->getDisplay());
        $decrypted = Crypto::decryptWithPassword($output, self::SECRET_PASSWORD);
        $this->assertEquals(self::PLAINTEXT_SECRET, $decrypted);
    }

    /**
     * @covers ::execute
     * @throws Ex\EnvironmentIsBrokenException
     * @throws Ex\WrongKeyOrModifiedCiphertextException
     * @throws TypeError
     */
    public function testExecuteWithPasswordPrompt(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->setInputs([self::SECRET_PASSWORD, self::SECRET_PASSWORD])
            ->execute([
                'plaintext' => self::PLAINTEXT_SECRET,
                '--raw-binary' => false
            ]);

        $output = trim($commandTester->getDisplay());
        $matches = [];
        preg_match("/(.*)$/", $output, $matches);
        $decrypted = Crypto::decryptWithPassword($matches[0], self::SECRET_PASSWORD);
        $this->assertEquals(self::PLAINTEXT_SECRET, $decrypted);
    }

    /**
     * @covers ::execute
     */
    public function testExecuteWithNonMatchingPasswordPrompt(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->setInputs([self::SECRET_PASSWORD, 'myNonMatchingPassword'])
            ->execute([
                'plaintext' => self::PLAINTEXT_SECRET,
                '--raw-binary' => false
            ]);

        $output = trim($commandTester->getDisplay());
        $matches = [];
        preg_match("/(.*)$/", $output, $matches);
        $this->assertEquals("The passwords do not match", $matches[0]);
    }

    /**
     * @covers ::execute
     */
    public function testExecuteWithOutputFilename(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'plaintext' => self::PLAINTEXT_SECRET,
            '--password' => self::SECRET_PASSWORD,
            '--raw-binary' => false,
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
            '--password' => self::SECRET_PASSWORD,
            '--raw-binary' => false,
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
     */
    public function testExecuteWithBlankOutputFilename(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'plaintext' => self::PLAINTEXT_SECRET,
            '--password' => self::SECRET_PASSWORD,
            '--raw-binary' => false,
            '--outputFilename' => ''
        ]);

        $output = trim($commandTester->getDisplay());
        $decrypted = Crypto::decryptWithPassword($output, self::SECRET_PASSWORD);
        $this->assertEquals(self::PLAINTEXT_SECRET, $decrypted);
        $this->assertFileNotExists(self::getTestFilename());
    }

    /**
     * @covers ::execute
     * @throws TypeError
     * @throws Ex\EnvironmentIsBrokenException
     * @throws Ex\WrongKeyOrModifiedCiphertextException
     */
    public function testExecuteWithNullOutputFilename(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'plaintext' => self::PLAINTEXT_SECRET,
            '--password' => self::SECRET_PASSWORD,
            '--raw-binary' => false,
            '--outputFilename' => null
        ]);

        $output = trim($commandTester->getDisplay());
        $decrypted = Crypto::decryptWithPassword($output, self::SECRET_PASSWORD);
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
