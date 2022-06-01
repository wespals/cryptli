<?php

namespace Test\Cryptli\Command;

use Cryptli\Command\CreateKeyCommand;
use Defuse\Crypto\Core;
use Defuse\Crypto\Exception as Ex;
use Defuse\Crypto\Key;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @coversDefaultClass CreateKeyCommand
 */
class CreateKeyCommandTest extends TestCase
{

    /**
     * @covers ::nothing
     * @return CommandTester
     */
    private function getCommandTester(): CommandTester
    {
        $application = new Application();
        $application->add(new CreateKeyCommand());

        return new CommandTester($application->find('cryptli:create-key'));
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
            'create-key-command.test'
        );
    }

    /**
     * @covers ::execute
     * @throws Ex\BadFormatException
     * @throws Ex\EnvironmentIsBrokenException
     */
    public function testExecuteDisplay(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([]);

        $output = trim($commandTester->getDisplay());
        $key = Key::loadFromAsciiSafeString($output);
        $this->assertSame(32, Core::ourStrlen($key->getRawBytes()));
    }

    /**
     * @covers ::execute
     */
    public function testExecuteWithOutputFilename(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
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
            '--outputFilename' => $badFilename
        ]);

        $output = trim($commandTester->getDisplay());
        $this->assertEquals(sprintf("Unable to write file: %s", $badFilename), $output);
        $this->assertFileNotExists($badFilename);
        unlink($badFilename);
    }

    /**
     * @covers ::execute
     * @throws Ex\BadFormatException
     * @throws Ex\EnvironmentIsBrokenException
     */
    public function testExecuteWithBlankOutputFilename(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            '--outputFilename' => ''
        ]);

        $output = trim($commandTester->getDisplay());
        $key = Key::loadFromAsciiSafeString($output);
        $this->assertSame(32, Core::ourStrlen($key->getRawBytes()));
        $this->assertFileNotExists(self::getTestFilename());
    }

    /**
     * @covers ::execute
     * @throws Ex\BadFormatException
     * @throws Ex\EnvironmentIsBrokenException
     */
    public function testExecuteWithNullOutputFilename(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            '--outputFilename' => null
        ]);

        $output = trim($commandTester->getDisplay());
        $key = Key::loadFromAsciiSafeString($output);
        $this->assertSame(32, Core::ourStrlen($key->getRawBytes()));
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
