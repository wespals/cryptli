<?php

namespace Cryptli\Command;

use Cryptli\PasswordQuestion;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception as Ex;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TypeError;

class DecryptWithPasswordCommand extends Command
{

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('cryptli:decrypt-with-password')
            ->setDescription('Decrypts a ciphertext string using a secret password')
            ->addArgument(
                'ciphertext',
                InputArgument::REQUIRED,
                'The ciphertext to be decrypted'
            )
            ->addOption(
                'password',
                'p',
                InputOption::VALUE_OPTIONAL,
                'A string containing the secret password used for encryption. If a password is not provided, you will be prompted to enter one.'
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
     * @throws TypeError
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $password = $input->getOption('password');

        if ($password === null || $password === '') {
            $helper = $this->getHelper('question');
            $password = $helper->ask($input, $output, new PasswordQuestion());
            $verify = $helper->ask($input, $output, new PasswordQuestion('Please verify the password'));

            if ($password !== $verify) {
                $output->writeln("<error>The passwords do not match</error>");

                return;
            }
        }

        try {
            $decryptedText = Crypto::decryptWithPassword(
                $input->getArgument('ciphertext'),
                $password,
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

            $output->writeln("<info>$decryptedText</info>");
        } catch (Ex\WrongKeyOrModifiedCiphertextException $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
        }
    }
}
