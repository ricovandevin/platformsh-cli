<?php
namespace Platformsh\Cli\Command\Integration;

use Platformsh\Cli\Util\Table;
use Platformsh\Client\Model\Integration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IntegrationListCommand extends IntegrationCommandBase
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('integration:list')
            ->setAliases(['integrations'])
            ->setDescription('View a list of project integration(s)');
        Table::addFormatOption($this->getDefinition());
        $this->addProjectOption();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->validateInput($input);

        $integrations = $this->getSelectedProject()
                        ->getIntegrations();
        if (!$integrations) {
            $this->stdErr->writeln('No integrations found');

            return 1;
        }

        $table = new Table($input, $output);
        $header = ['ID', 'Type', 'Summary'];
        $rows = [];

        foreach ($integrations as $integration) {
            $rows[] = [$integration->id, $integration->type, $this->getIntegrationSummary($integration)];
        }

        $table->render($rows, $header);

        $this->stdErr->writeln('');
        $this->stdErr->writeln('Add a new integration with: <info>' . self::$config->get('application.executable') . ' integration:add</info>');
        $this->stdErr->writeln('View integration details with: <info>' . self::$config->get('application.executable') . ' integration:get [id]</info>');
        $this->stdErr->writeln('Delete an integration with: <info>' . self::$config->get('application.executable') . ' integration:delete [id]</info>');

        return 0;
    }

    /**
     * @param Integration $integration
     *
     * @return string
     */
    protected function getIntegrationSummary(Integration $integration)
    {
        $details = $integration->getProperties();
        unset($details['id'], $details['type']);

        switch ($integration->type) {
            case 'github':
            case 'bitbucket':
                $summary = sprintf('Repository: %s', $details['repository']);
                break;

            case 'hipchat':
                $summary = sprintf('Room ID: %s', $details['room']);
                break;

            case 'webhook':
                $summary = sprintf('URL: %s', $details['url']);
                break;

            default:
                $summary = json_encode($details);
        }

        if (strlen($summary) > 240) {
            $summary = substr($summary, 0, 237) . '...';
        }
        $summary = wordwrap($summary, 75, "\n", true);

        return $summary;
    }
}
