<?php
namespace Resume\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SelfUpdateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('selfupdate')
            ->setDescription('Updates md2resume.phar to the latest version.')
            ->setHelp(
<<<EOT
The <info>self-update</info> command checks github for newer
versions of the command line client and if found, installs the latest.

EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->app = $this->getApplication();
        $localFilename = realpath($_SERVER['argv'][0]) ?: $_SERVER['argv'][0];
        $tempFilename = dirname($localFilename) . '/' . basename($localFilename, '.phar').'-temp.phar';

        if (substr($localFilename, -4) === '.php') {
            throw new \Exception('You must run this from the compiled phar file.');
        }

        // check for permissions in local filesystem before start connection process
        if (!is_writable($tempDirectory = dirname($tempFilename))) {
            throw new \Exception(
                'Self update failed: the "' . $tempDirectory
                . '" directory used to download the temp file could not be written'
            );
        }

        if (!is_writable($localFilename)) {
            throw new \Exception(
                'Self update failed: the "' . $localFilename . '" file could not be written'
            );
        }

        $protocol = extension_loaded('openssl') ? 'https' : 'http';
        $latest = trim(file_get_contents($protocol . $this->app->project->selfupdateversion, false));

        if ($this->app->project->version !== $latest) {
            $output->writeln(sprintf("Updating to version <info>%s</info>.", $latest));

            $remoteFilename = $protocol . $this->app->project->selfupdatepath;

            $phar = file_get_contents($remoteFilename);
            file_put_contents($tempFilename, $phar);

            if (!file_exists($tempFilename)) {
                $output->writeln('<error>The download of the new version failed for an unexpected reason</error>');

                return 1;
            }

            try {
                @chmod($tempFilename, 0777 & ~umask());
                // test the phar validity
                $phar = new \Phar($tempFilename);
                // free the variable to unlock the file
                unset($phar);
                rename($tempFilename, $localFilename);
            } catch (\Exception $e) {
                @unlink($tempFilename);
                if (!$e instanceof \UnexpectedValueException && !$e instanceof \PharException) {
                    throw $e;
                }
                $output->writeln('<error>The download is corrupted ('.$e->getMessage().').</error>');
                $output->writeln('<error>Please re-run the self-update command to try again.</error>');
            }
        } else {
            $output->writeln("<info>You are using the latest version.</info>");
        }
    }
}

/* End of file SelfUpdateCommand.php */
