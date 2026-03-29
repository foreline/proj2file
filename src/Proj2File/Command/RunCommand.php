<?php

declare(strict_types=1);

namespace Foreline\Proj2File\Command;

use Exception;
use Foreline\IO\Response;
use Foreline\Proj2File\ProjectPacker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'run', description: 'Pack project files into a single file')]
class RunCommand extends Command
{
    
    private ProjectPacker $projectPacker;
    
    /**
     * @param ProjectPacker $projectPacker
     */
    public function __construct(ProjectPacker $projectPacker)
    {
        parent::__construct();
        $this->projectPacker = $projectPacker;
    }
    
    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setHelp('This command packs all project files (excluding .gitignore file rules) into a single file in .proj2file directory.')
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Path to the directory to pack (defaults to current working directory)'
            )
            ->addOption('line-numbers', 'l', null, 'Include line numbers in file contents')
            ->addOption('number-format', 'f', InputOption::VALUE_OPTIONAL, 'Format for line numbers (e.g., "4d", "03d", "left:4")', '4d')
            ->addOption('no-redact', null, null, 'Disable automatic redaction of secrets and private data')
            ->addOption('exec', 'x', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Shell command(s) to execute and include output in the pack')
            ->addOption('include', 'i', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Additional file or directory path(s) to include')
            ->addOption('tail', 't', InputOption::VALUE_REQUIRED, 'Only include the last N lines of each file and command output', '0')
        ;
    }
    
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            Response::info('Proj2file');
    
            $path = $input->getArgument('path') ?? getcwd();
            
            /*if ( !is_dir($path) ) {
                throw new InvalidArgumentException('The path ' . $path . ' is not a valid directory');
            }*/
            
            $this->projectPacker->setIncludeLineNumbers((bool)$input->getOption('line-numbers'));
            $this->projectPacker->setNumberFormat((string)$input->getOption('number-format'));
            $this->projectPacker->setRedact(!$input->getOption('no-redact'));
            $this->projectPacker->setTailLines((int)$input->getOption('tail'));
            $this->projectPacker->setCommands($input->getOption('exec'));
            $this->projectPacker->setExtraPaths($input->getOption('include'));
            
            $this->projectPacker->setPath($path);
            $outputFile = $this->projectPacker->pack();
            
            Response::info("Project packed successfully!");
            Response::info("Output file: $outputFile");
            Response::info("Lines in file: {$this->projectPacker->getLinesCount()}");
            Response::info("File size: " . ( number_format($this->projectPacker->getSize()/1024 , 1) ) . " Kb");
            Response::info("Tokens count: " . $this->projectPacker->getTokensCount() . " tokens");
            
            $redactor = $this->projectPacker->getRedactor();
            if ($redactor !== null) {
                Response::info("Redactions applied: " . $redactor->getRedactionCount());
            }
            
            return Command::SUCCESS;
            
        } catch (Exception $e) {
            return Response::exception($e, Command::FAILURE);
        }
    }
}