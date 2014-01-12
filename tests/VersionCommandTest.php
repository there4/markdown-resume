<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class VersionCommandTest extends \ResumeTest
{
    public function testExecute()
    {
        $command = $this->console->find('version');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command' => $command->getName()
        ));
        $this->assertEquals($this->console->project->version, trim($commandTester->getDisplay()));
    }
}


/* End of file VersionCommandTest.php */
