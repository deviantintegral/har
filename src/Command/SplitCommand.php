<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Command;

use Deviantintegral\Har\Serializer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to split a HAR file into multiple files.
 */
class SplitCommand extends Command
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('har:split')
          ->setDescription('Split a HAR file into one file per entry')
          ->setHelp('Each entry in the supplied HAR will be split into a single file.')
          ->addArgument('har', InputArgument::REQUIRED, 'The source HAR file to split.')
          ->addArgument('destination', InputArgument::OPTIONAL, 'The source directory to save the split files to. Defaults to the current directory.')
          ->addOption('md5', null, InputOption::VALUE_NONE, 'Save split files with an MD5 hash of the request URL instead of a numeric index.')
          ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite destination files that already exist.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $source = $input->getArgument('har');

        if (!file_exists($source)) {
            $io->error(\sprintf('File not found: %s', $source));

            return Command::FAILURE;
        }

        $contents = file_get_contents($source);
        if (false === $contents) {
            $io->error(\sprintf('Unable to read file: %s', $source));

            return Command::FAILURE;
        }

        $serializer = new Serializer();
        $har = $serializer->deserializeHar($contents);

        $io->text(\sprintf('Splitting %s into one file per entry',
            $source
        ));
        $io->progressStart(\count($har->getLog()->getEntries()));

        foreach ($har->splitLogEntries() as $index => $cloned) {
            $destination = $this->getSplitDestination(
                $index,
                $input->getOption('md5'),
                $cloned,
                $input->getArgument('destination') ?: getcwd()
            );

            if ($input->getOption('force') || !file_exists($destination)) {
                if (false === file_put_contents($destination, $serializer->serializeHar($cloned))) {
                    throw new \RuntimeException(\sprintf('Unable to write to %s.', $destination));
                }
            } else {
                throw new \RuntimeException(\sprintf('%s exists. Use --force to overwrite it and all other existing files', $destination));
            }
            $io->progressAdvance();
        }
        $io->progressFinish();

        return Command::SUCCESS;
    }

    private function getSplitDestination(
        $index,
        $md5,
        \Deviantintegral\Har\Har $cloned,
        string $destination_path,
    ): string {
        $filename = $index + 1 .'.har';
        if ($md5) {
            $filename = md5(
                (string) $cloned->getLog()->getEntries()[0]->getRequest()
                  ->getUrl()
            ).'.har';
        }
        $destination = $destination_path."/$filename";

        return $destination;
    }
}
