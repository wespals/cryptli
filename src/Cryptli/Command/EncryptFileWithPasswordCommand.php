<?php

namespace Cryptli\Command;

use Cryptli\PasswordQuestion;
use Defuse\Crypto\Exception as Ex;
use Defuse\Crypto\File;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class EncryptFileWithPasswordCommand extends Command
{

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('cryptli:encrypt-file-with-password')
            ->setDescription('Encrypts a file with a password')
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
            ->addOption(
                'password',
                'p',
                InputOption::VALUE_OPTIONAL,
                'The password used for decryption. If a password is not provided, you will be prompted to enter one.'
            );
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws Ex\EnvironmentIsBrokenException
     * @throws Ex\IOException
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

        File::encryptFileWithPassword(
            $input->getArgument('inputFilename'),
            $input->getArgument('outputFilename'),
            $password
        );
    }
}
