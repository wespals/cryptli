<?php

namespace Cryptli\Command;

use Cryptli\PasswordQuestion;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception as Ex;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TypeError;

class EncryptWithPasswordCommand extends Command
{

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('cryptli:encrypt-with-password')
            ->setDescription('Encrypts a plaintext string using a secret password')
            ->setHelp(<<<EOD
When encrypting non-alphanumeric characters, it may be necessary to wrap the plaintext in single-quotes as shown below:            
<info>%command.name% [options] [--] 'mySecretP@$\$w0rd'</info>
EOD
            )
            ->addArgument(
                'plaintext',
                InputArgument::REQUIRED,
                'The string to encrypt'
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
     * @throws Ex\EnvironmentIsBrokenException
     * @throws TypeError
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

        $encryptedText = Crypto::encryptWithPassword(
            $input->getArgument('plaintext'),
            $password,
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
