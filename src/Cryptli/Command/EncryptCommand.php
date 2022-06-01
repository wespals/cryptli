<?php

namespace Cryptli\Command;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception as Ex;
use Defuse\Crypto\Key;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TypeError;

class EncryptCommand extends Command
{

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('cryptli:encrypt')
            ->setDescription('Encrypts a plaintext string using a secret key')
            ->setHelp(<<<EOD
When encrypting non-alphanumeric characters, it may be necessary to wrap the plaintext in single-quotes as shown below:            
<info>%command.name% [options] [--] 'mySecretP@$\$w0rd' <key></info>
EOD
            )
            ->addArgument(
                'plaintext',
                InputArgument::REQUIRED,
                'The string to encrypt'
            )
            ->addArgument(
                'key',
                InputArgument::REQUIRED,
                'The ASCII safe key string'
            )
            ->addOption(
                'do-not-trim',
                't',
                InputOption::VALUE_OPTIONAL,
                'Value should be set to TRUE if you do not wish for the library to automatically strip trailing whitespace from the key string',
                false
            )
            ->addOption(
                'raw-binary',
                'b',
                InputOption::VALUE_OPTIONAL,
                'Determines whether the output will be a byte string (true) or hex encoded (false, the default)',
                false
            )
            ->addOption(
                'outputFilename',
                'f',
                InputOption::VALUE_OPTIONAL,
                'The path to save the encrypted string to a file'
            );
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws Ex\BadFormatException
     * @throws Ex\EnvironmentIsBrokenException
     * @throws TypeError
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $encryptedText = Crypto::encrypt(
            $input->getArgument('plaintext'),
            Key::loadFromAsciiSafeString(
                $input->getArgument('key'),
                filter_var($input->getOption('do-not-trim'), FILTER_VALIDATE_BOOLEAN)
            ),
            filter_var($input->getOption('raw-binary'), FILTER_VALIDATE_BOOLEAN)
        );

        if ($input->getOption('outputFilename') !== null && $input->getOption('outputFilename') !== '') {
            if (false === file_put_contents($input->getOption('outputFilename'), $encryptedText)) {
                $output->writeln(sprintf(
                    '<error>Unable to write file: %s</error>',
                    $input->getOption('outputFilename')
                ));

                return;
            }

            $output->writeln(sprintf(
                '<info>File written: %s</info>',
                $input->getOption('outputFilename')
            ));

            return;
        }

        $output->writeln($encryptedText);
    }
}
