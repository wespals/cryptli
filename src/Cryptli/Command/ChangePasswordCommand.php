<?php

namespace Cryptli\Command;

use Cryptli\PasswordQuestion;
use Defuse\Crypto\Exception as Ex;
use Defuse\Crypto\KeyProtectedByPassword;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ChangePasswordCommand extends Command
{

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('cryptli:change-password')
            ->setDescription('Changes the password on the password-protected key')
            ->addArgument(
                'key',
                InputArgument::REQUIRED,
                'The ASCII safe key string'
            )
            ->addOption(
                'currentPassword',
                'c',
                InputOption::VALUE_OPTIONAL,
                'The password that the key is currently protected with. If a current password is not provided, you will be prompted to enter one.'
            )
            ->addOption(
                'newPassword',
                'w',
                InputOption::VALUE_OPTIONAL,
                'The new password which will be used to protect the key. If a new password is not provided, you will be prompted to enter one.'
            )
            ->addOption(
                'outputFilename',
                'f',
                InputOption::VALUE_OPTIONAL,
                'The path to save the changed password-protected key to a file'
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
        $currentPassword = $input->getOption('currentPassword');

        if ($currentPassword === null) {
            $helper = $this->getHelper('question');
            $currentPassword = $helper->ask($input, $output, new PasswordQuestion('Please enter the current password'));
            $verifyCurrent = $helper->ask($input, $output, new PasswordQuestion('Please verify the current password'));

            if ($currentPassword !== $verifyCurrent) {
                $output->writeln("<error>The current passwords do not match</error>");

                return;
            }
        }

        $newPassword = $input->getOption('newPassword');

        if ($newPassword === null) {
            $helper = $this->getHelper('question');
            $newPassword = $helper->ask($input, $output, new PasswordQuestion('Please enter the new password'));
            $verifyNew = $helper->ask($input, $output, new PasswordQuestion('Please verify the new password'));

            if ($newPassword !== $verifyNew) {
                $output->writeln("<error>The new passwords do not match</error>");

                return;
            }
        }

        try {
            $protectedKey = KeyProtectedByPassword::loadFromAsciiSafeString($input->getArgument('key'));
            $newKey = $protectedKey->changePassword($currentPassword, $newPassword)
                ->saveToAsciiSafeString();

            if ($input->getOption('outputFilename') !== null && $input->getOption('outputFilename') !== '') {
                if (false === file_put_contents($input->getOption('outputFilename'), $newKey)) {
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

            $output->writeln($newKey);
        } catch (Exception $e) {
            switch (true) {
                case $e instanceof Ex\BadFormatException:
                case $e instanceof Ex\WrongKeyOrModifiedCiphertextException:
                    $output->writeln("<error>{$e->getMessage()}</error>");
                    break;
                default:
                    throw $e;
            }
        }
    }
}
