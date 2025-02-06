<?php

declare(strict_types=1);

namespace Foreline\Proj2File\Command;

use Exception;
use Foreline\IO\Message;
use Foreline\Proj2File\ProjectPacker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
class RunCommand extends Command
{
    protected static string $defaultName = 'run';
    
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
            ->setName(self::$defaultName)
            ->setDescription('Pack project files into a single file')
            ->setHelp('This command packs all project files (excluding .gitignore file rules) into a single file in .proj2file directory.');
    }
    
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            Message::info('Proj2file');
            
            $outputFile = $this->projectPacker->pack();
            
            Message::info("Project packed successfully!");
            Message::info("Output file: {$outputFile}");
            
            return Command::SUCCESS;
            
        } catch (Exception $e) {
            Message::exception($e);
            return Command::FAILURE;
        }
    }
}