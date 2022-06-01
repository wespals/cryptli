<?php

namespace Cryptli\Command;

use Defuse\Crypto\Exception as Ex;
use Defuse\Crypto\Key;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateKeyCommand extends Command
{

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('cryptli:create-key')
            ->setDescription('Generates a new random key and returns the ASCII safe key string')
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
        $key = Key::createNewRandomKey();
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
