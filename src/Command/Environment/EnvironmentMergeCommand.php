<?php
namespace Platformsh\Cli\Command\Environment;

use Platformsh\Cli\Command\CommandBase;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnvironmentMergeCommand extends CommandBase
{

    protected function configure()
    {
        $this
            ->setName('environment:merge')
            ->setAliases(['merge'])
            ->setDescription('Merge an environment')
            ->addArgument('environment', InputArgument::OPTIONAL, 'The environment to merge');
        $this->addProjectOption()
             ->addEnvironmentOption()
             ->addNoWaitOption();
        $this->addExample('Merge the environment "sprint-2" into its parent', 'sprint-2');
        $this->setHelp('This command will initiate a Git merge of the specified environment into its parent environment.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->validateInput($input);

        $selectedEnvironment = $this->getSelectedEnvironment();
        $environmentId = $selectedEnvironment->id;

        if (!$selectedEnvironment->operationAvailable('merge')) {
            $this->stdErr->writeln("Operation not available: The environment <error>$environmentId</error> can't be merged.");

            return 1;
        }

        $parentId = $selectedEnvironment->parent;

        $confirmText = "Are you sure you want to merge <info>$environmentId</info> with its parent, <info>$parentId</info>?";
        /** @var \Platformsh\Cli\Service\QuestionHelper $questionHelper */
        $questionHelper = $this->getService('question_helper');
        if (!$questionHelper->confirm($confirmText)) {
            return 1;
        }

        $this->stdErr->writeln("Merging <info>$environmentId</info> with <info>$parentId</info>");

        $this->api()->clearEnvironmentsCache($selectedEnvironment->project);

        $activity = $selectedEnvironment->merge();
        if (!$input->getOption('no-wait')) {
            /** @var \Platformsh\Cli\Service\ActivityMonitor $activityMonitor */
            $activityMonitor = $this->getService('activity_monitor');
            $success = $activityMonitor->waitAndLog(
                $activity,
                'Merge complete',
                'Merge failed'
            );
            if (!$success) {
                return 1;
            }
        }

        return 0;
    }
}
