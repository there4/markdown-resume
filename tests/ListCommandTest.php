<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ListCommandTest extends \ResumeTest
{
    public function testExecute()
    {
        $command = $this->console->find('list');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command' => $command->getName()
        ));
        $this->assertRegExp('/Available commands/', $commandTester->getDisplay());
        $this->assertRegExp('/Options/', $commandTester->getDisplay());
        $this->assertRegExp('/list/', $commandTester->getDisplay());
    }
}


/* End of file ListCommandTest.php */
