<?php

namespace Cryptli\Command;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception as Ex;
use Defuse\Crypto\Key;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DecryptCommand extends Command
{

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('cryptli:decrypt')
            ->setDescription('Decrypts a ciphertext string using a secret key')
            ->addArgument(
                'ciphertext',
                InputArgument::REQUIRED,
                'The ciphertext to be decrypted'
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
                'Must be the same value as the raw binary given to the encrypt call to that generated ciphertext',
                false
            )
            ->addOption(
                'outputFilename',
                'f',
                InputOption::VALUE_OPTIONAL,
                'The path to save the decrypted string to a file'
            );
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $decryptedText = Crypto::decrypt(
                $input->getArgument('ciphertext'),
                Key::loadFromAsciiSafeString(
                    $input->getArgument('key'),
                    filter_var($input->getOption('do-not-trim'), FILTER_VALIDATE_BOOLEAN)
                ),
                filter_var($input->getOption('raw-binary'), FILTER_VALIDATE_BOOLEAN)
            );

            if ($input->getOption('outputFilename') !== null && $input->getOption('outputFilename') !== '') {
                if (false === file_put_contents($input->getOption('outputFilename'), $decryptedText)) {
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

            $output->writeln($decryptedText);
        } catch (Ex\BadFormatException $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
        }
    }
}
