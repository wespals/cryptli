<?php

namespace Test\Cryptli\Command;

use Cryptli\Command\DecryptCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass DecryptCommand
 */
class DecryptCommandTest extends TestCase
{

    /**
     * @var string
     */
    private const CIPHERTEXT = 'def50200a120eb99d88f52c37a75f6599a92ed158998ae60411e7740e4607e193c5c3ae65269beb8f6e49cfbfbc402da0623ee0bdee6da7c529c62cf21f5eb1268ce4068fd2938774b7da40a6a95f5def4f3ada467ff1acb3194b8573f94effd4cf7144e';

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
        $application->add(new DecryptCommand());

        return new CommandTester($application->find('cryptli:decrypt'));
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
            'decrypt-command.test'
        );
    }

    /**
     * @covers ::execute
     */
    public function testExecuteDisplay(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'ciphertext' => self::CIPHERTEXT,
            'key' => self::KEY_STRING,
            '--do-not-trim' => false,
            '--raw-binary' => false
        ]);

        $output = trim($commandTester->getDisplay());
        $this->assertEquals(self::PLAINTEXT_SECRET, $output);
    }

    /**
     * @covers ::execute
     */
    public function testExecuteDisplayWithBadKey(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'ciphertext' => self::CIPHERTEXT,
            'key' => substr_replace(self::KEY_STRING, 'a', (strlen(self::KEY_STRING) - 1), 1),
            '--do-not-trim' => false,
            '--raw-binary' => false
        ]);

        $output = trim($commandTester->getDisplay());
        $this->assertEquals("Data is corrupted, the checksum doesn't match", $output);
    }

    /**
     * @covers ::execute
     */
    public function testExecuteWithOutputFilename(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'ciphertext' => self::CIPHERTEXT,
            'key' => self::KEY_STRING,
            '--do-not-trim' => false,
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
    public function testExecuteWithOutputFilenameAndBadKey(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'ciphertext' => self::CIPHERTEXT,
            'key' => substr_replace(self::KEY_STRING, 'a', (strlen(self::KEY_STRING) - 1), 1),
            '--do-not-trim' => false,
            '--raw-binary' => false,
            '--outputFilename' => self::getTestFilename()
        ]);

        $output = trim($commandTester->getDisplay());
        $this->assertEquals("Data is corrupted, the checksum doesn't match", $output);
        $this->assertFileNotExists(self::getTestFilename());
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
            'key' => self::KEY_STRING,
            '--do-not-trim' => false,
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
            'key' => self::KEY_STRING,
            '--do-not-trim' => false,
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
            'key' => self::KEY_STRING,
            '--do-not-trim' => false,
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
