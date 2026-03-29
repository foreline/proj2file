<?php

declare(strict_types=1);

namespace Foreline\Tests\Proj2File\Command;

use Foreline\Proj2File\Command\RunCommand;
use Foreline\Proj2File\ProjectPacker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class RunCommandTest extends TestCase
{
    /**
     * Ensures the command can be instantiated without triggering a fatal error.
     * This prevents regressions like adding a type to $defaultName which conflicts
     * with the untyped parent property in Symfony\Component\Console\Command\Command.
     */
    public function testCommandCanBeInstantiated(): void
    {
        $command = new RunCommand(new ProjectPacker());
        $this->assertInstanceOf(Command::class, $command);
    }

    /**
     * Verifies the command is registered under the correct name.
     */
    public function testCommandName(): void
    {
        $command = new RunCommand(new ProjectPacker());
        $this->assertSame('run', $command->getName());
    }

    /**
     * Verifies the command description is set.
     */
    public function testCommandDescription(): void
    {
        $command = new RunCommand(new ProjectPacker());
        $this->assertNotEmpty($command->getDescription());
    }

    /**
     * Ensures the command can be registered in an Application and resolved by name.
     */
    public function testCommandRegistersInApplication(): void
    {
        $application = new Application('test', '0.0.1');
        $application->add(new RunCommand(new ProjectPacker()));

        $this->assertTrue($application->has('run'));
        $this->assertInstanceOf(RunCommand::class, $application->get('run'));
    }

    /**
     * Verifies all expected options are defined.
     */
    public function testCommandHasExpectedOptions(): void
    {
        $command = new RunCommand(new ProjectPacker());
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('line-numbers'));
        $this->assertTrue($definition->hasOption('number-format'));
        $this->assertTrue($definition->hasOption('no-redact'));
        $this->assertTrue($definition->hasOption('exec'));
        $this->assertTrue($definition->hasOption('include'));
        $this->assertTrue($definition->hasOption('tail'));
    }

    /**
     * Verifies the optional 'path' argument exists.
     */
    public function testCommandHasPathArgument(): void
    {
        $command = new RunCommand(new ProjectPacker());
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasArgument('path'));
        $this->assertFalse($definition->getArgument('path')->isRequired());
    }

    /**
     * Ensures --help does not crash (regression guard for the original fatal error).
     */
    public function testHelpOutputDoesNotCrash(): void
    {
        $application = new Application('test', '0.0.1');
        $application->add(new RunCommand(new ProjectPacker()));
        $application->setAutoExit(false);

        $tester = new \Symfony\Component\Console\Tester\ApplicationTester($application);
        $tester->run(['command' => 'help', 'command_name' => 'run']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('run', $tester->getDisplay());
    }
}
