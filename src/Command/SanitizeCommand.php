<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Command;

use Deviantintegral\Har\HarSanitizer;
use Deviantintegral\Har\Serializer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to sanitize sensitive data from a HAR file.
 */
class SanitizeCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('har:sanitize')
            ->setDescription('Sanitize sensitive data from a HAR file')
            ->setHelp('Redact sensitive values like authorization headers, API keys, and passwords from HAR files.')
            ->addArgument('har', InputArgument::REQUIRED, 'The source HAR file to sanitize.')
            ->addArgument('output', InputArgument::OPTIONAL, 'The output file path. Defaults to stdout.')
            ->addOption('header', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Header name to redact (can be specified multiple times).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $source = $input->getArgument('har');

        if (!file_exists($source)) {
            $io->error(\sprintf('File not found: %s', $source));

            return Command::FAILURE;
        }

        if (is_dir($source)) {
            $io->error(\sprintf('Path is a directory, not a file: %s', $source));

            return Command::FAILURE;
        }

        $contents = file_get_contents($source);
        if (false === $contents) {
            $io->error(\sprintf('Unable to read file: %s', $source));

            return Command::FAILURE;
        }

        $serializer = new Serializer();
        $har = $serializer->deserializeHar($contents);

        $sanitizer = new HarSanitizer();

        $headers = $input->getOption('header');
        if (!empty($headers)) {
            $sanitizer->redactHeaders($headers);
        }

        $sanitized = $sanitizer->sanitize($har);
        $result = $serializer->serializeHar($sanitized);

        $outputPath = $input->getArgument('output');
        if (null !== $outputPath) {
            if (false === file_put_contents($outputPath, $result)) {
                $io->error(\sprintf('Unable to write to file: %s', $outputPath));

                return Command::FAILURE;
            }
            $io->success(\sprintf('Sanitized HAR written to %s', $outputPath));
        } else {
            $output->write($result);
        }

        return Command::SUCCESS;
    }
}
