<?php

namespace Test\Cryptli\Command;

use Cryptli\Command\DecryptWithPasswordCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass DecryptWithPasswordCommand
 */
class DecryptWithPasswordCommandTest extends TestCase
{

    /**
     * @var string
     */
    private const CIPHERTEXT = 'def5020098661832cfef556004450c008528770d7577fb4a0d6026ccb4be217fe891e9fa133c01cb5edba5b73ec3fe86adc97ab332f4eb2f2137cd0d940c8a34a5b034c4da086e5f31fc24254142035546840616819c7d1e317acb7b14ca7d58';

    /**
     * @var string
     */
    private const PLAINTEXT_SECRET = 'mySecretText';

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
        $application->add(new DecryptWithPasswordCommand());

        return new CommandTester($application->find('cryptli:decrypt-with-password'));
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
            'decrypt-with-password-command.test'
        );
    }

    /**
     * @covers ::execute
     */
    public function testExecuteWithPasswordOption(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'ciphertext' => self::CIPHERTEXT,
            '--password' => self::SECRET_PASSWORD,
            '--raw-binary' => false
        ]);

        $output = trim($commandTester->getDisplay());
        $this->assertEquals(self::PLAINTEXT_SECRET, $output);
    }

    /**
     * @covers ::execute
     */
    public function testExecuteWithBadPasswordOption(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'ciphertext' => self::CIPHERTEXT,
            '--password' => 'myNotSoGoodPassword',
            '--raw-binary' => false
        ]);

        $output = trim($commandTester->getDisplay());
        $this->assertEquals("Integrity check failed.", $output);
    }

    /**
     * @covers ::execute
     */
    public function testExecuteWithShortCiphertext(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'ciphertext' => 'def5020098661832cfef556004450c008528770d6ccb424254142035546840616819c7d1e317acb7b14ca7d58',
            '--password' => self::SECRET_PASSWORD,
            '--raw-binary' => false
        ]);

        $output = trim($commandTester->getDisplay());
        $this->assertEquals("Ciphertext is too short.", $output);
    }

    /**
     * @covers ::execute
     */
    public function testExecuteWithPasswordPrompt(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->setInputs([self::SECRET_PASSWORD, self::SECRET_PASSWORD])
            ->execute([
                'ciphertext' => self::CIPHERTEXT,
                '--raw-binary' => false
            ]);

        $output = trim($commandTester->getDisplay());
        $matches = [];
        preg_match("/(.*)$/", $output, $matches);
        $this->assertEquals(self::PLAINTEXT_SECRET, $matches[0]);
    }

    /**
     * @covers ::execute
     */
    public function testExecuteWithBadPasswordPrompt(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->setInputs(['myNotSoGoodPassword', 'myNotSoGoodPassword'])
            ->execute([
                'ciphertext' => self::CIPHERTEXT,
                '--raw-binary' => false
            ]);

        $output = trim($commandTester->getDisplay());
        $matches = [];
        preg_match("/(.*)$/", $output, $matches);
        $this->assertEquals("Integrity check failed.", $matches[0]);
    }

    /**
     * @covers ::execute
     */
    public function testExecuteWithNonMatchingPasswordPrompt(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->setInputs([self::SECRET_PASSWORD, 'myNonMatchingPassword'])
            ->execute([
                'ciphertext' => self::CIPHERTEXT,
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
            'ciphertext' => self::CIPHERTEXT,
            '--password' => self::SECRET_PASSWORD,
            '--raw-binary' => false,
            '--outputFilename' => self::getTestFilename()
        ]);

        $output = trim($commandTester->getDisplay());
        $this->assertEquals(sprintf("File written: %s", self::getTestFilename()), $output);
        $this->assertFileExists(self::getTestFilename());
        $this->assertEquals(self::PLAINTEXT_SECRET, file_get_contents(self::getTestFilename()));
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
            'ciphertext' => self::CIPHERTEXT,
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
     */
    public function testExecuteWithBlankOutputFilename(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'ciphertext' => self::CIPHERTEXT,
            '--password' => self::SECRET_PASSWORD,
            '--raw-binary' => false,
            '--outputFilename' => ''
        ]);

        $output = trim($commandTester->getDisplay());
        $this->assertEquals(self::PLAINTEXT_SECRET, $output);
        $this->assertFileNotExists(self::getTestFilename());
    }

    /**
     * @covers ::execute
     */
    public function testExecuteWithNullOutputFilename(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'ciphertext' => self::CIPHERTEXT,
            '--password' => self::SECRET_PASSWORD,
            '--raw-binary' => false,
            '--outputFilename' => null
        ]);

        $output = trim($commandTester->getDisplay());
        $this->assertEquals(self::PLAINTEXT_SECRET, $output);
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
