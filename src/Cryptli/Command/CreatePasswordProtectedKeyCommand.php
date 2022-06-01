<?php

namespace Cryptli\Command;

use Cryptli\PasswordQuestion;
use Defuse\Crypto\Exception as Ex;
use Defuse\Crypto\KeyProtectedByPassword;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreatePasswordProtectedKeyCommand extends Command
{

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('cryptli:create-password-protected-key')
            ->setDescription("Generates a new random key that's protected by the password string and returns the ASCII safe key string")
            ->addOption(
                'password',
                'p',
                InputOption::VALUE_OPTIONAL,
                'The password used to protect the random key. If a password is not provided, you will be prompted to enter one.'
            )
            ->addOption(
                'outputFilename',
                'f',
                InputOption::VALUE_OPTIONAL,
                'The path to save the generated key to a file'
            );
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws Ex\EnvironmentIsBrokenException
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

        $key = KeyProtectedByPassword::createRandomPasswordProtectedKey($password);
        $encodedKey = $key->saveToAsciiSafeString();

        if ($input->getOption('outputFilename') !== null && $input->getOption('outputFilename') !== '') {
            if (false === file_put_contents($input->getOption('outputFilename'), $encodedKey)) {
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

        $output->writeln($encodedKey);
    }
}
