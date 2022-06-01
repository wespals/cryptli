<?php

namespace Cryptli\Command;

use Defuse\Crypto\Exception as Ex;
use Defuse\Crypto\File;
use Defuse\Crypto\Key;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EncryptFileCommand extends Command
{

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('cryptli:encrypt-file')
            ->setDescription('Encrypts a file using a secret key')
            ->addArgument(
                'inputFilename',
                InputArgument::REQUIRED,
                'The path to a file containing the plaintext to encrypt'
            )
            ->addArgument(
                'outputFilename',
                InputArgument::REQUIRED,
                'The path to save the ciphertext file'
            )
            ->addArgument(
                'key',
                InputArgument::REQUIRED,
                'The ASCII safe secret key string for encryption'
            )
            ->addOption(
                'do-not-trim',
                't',
                InputOption::VALUE_OPTIONAL,
                'Value should be set to TRUE if you do not wish for the library to automatically strip trailing whitespace from the key string',
                false
            );
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws Ex\EnvironmentIsBrokenException
     * @throws Ex\IOException
     * @throws Ex\BadFormatException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        File::encryptFile(
            $input->getArgument('inputFilename'),
            $input->getArgument('outputFilename'),
            Key::loadFromAsciiSafeString(
                $input->getArgument('key'),
                filter_var($input->getOption('do-not-trim'), FILTER_VALIDATE_BOOLEAN)
            )
        );
    }
}
