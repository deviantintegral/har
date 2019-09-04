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

class SplitCommand extends Command
{

    protected function configure()
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $har_path = $input->getArgument('har');
        $destination_path = $input->getArgument('destination') ?: getcwd();
        $md5 = $input->getOption('md5');
        $force = $input->getOption('force');
        $contents = file_get_contents($har_path);
        $serializer = new Serializer();
        $har = $serializer->deserializeHar($contents);

        $io->text("Splitting $har_path into one file per entry");
        $io->progressStart(count($har->getLog()->getEntries()));

        foreach ($har->getLog()->getEntries() as $index => $entry) {
            $cloned = clone $har;
            $cloned->getLog()->setEntries([$entry]);
            $filename = $index + 1 . ".har";
            if ($md5) {
                $filename = md5((string) $entry->getRequest()->getUrl()) . '.har';
            }
            $destination = $destination_path."/$filename";
            if ($force || !file_exists($destination)) {
                if (false === file_put_contents($destination, $serializer->serializeHar($cloned))) {
                    throw new \RuntimeException(sprintf("Unable to write to %s.", $destination));
                }
            }
            else {
                throw new \RuntimeException(sprintf("%s exists. Use --force to overwrite it and all other existing files", $destination));
            }
            $io->progressAdvance();
        }
        $io->progressFinish();
    }
}
