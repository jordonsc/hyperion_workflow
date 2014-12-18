<?php
namespace Hyperion\Workflow\Services;

use Hyperion\Dbal\Entity\Action;
use Hyperion\Dbal\Entity\Distribution;
use Hyperion\Dbal\Entity\Environment;
use Hyperion\Dbal\Entity\Project;

class DnsParser
{
    /**
     * @var Action
     */
    protected $action;

    /**
     * @var Project
     */
    protected $project;

    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @var Distribution
     */
    protected $distribution;

    public function __construct(Action $action, Project $project, Environment $environment, Distribution $distribution)
    {
        $this->action       = $action;
        $this->distribution = $distribution;
        $this->environment  = $environment;
        $this->project      = $project;
    }

    /**
     * Parse a DNS string, replacing variable names
     *
     * @param string $value
     * @return string
     */
    public function parse($value)
    {
        $value = str_replace('$EVENT_ID', $this->action->getId(), $value);

        $value = str_replace('$PROJECT_ID', $this->project->getId(), $value);
        $value = str_replace('$PROJECT_NAME', $this->project->getName(), $value);

        $value = str_replace('$ENVIRONMENT_ID', $this->environment->getId(), $value);
        $value = str_replace('$ENVIRONMENT_NAME', $this->environment->getName(), $value);

        $value = str_replace('$BUILD_ID', $this->distribution->getId(), $value);
        $value = str_replace('$BUILD_NAME', $this->distribution->getName(), $value);

        $out = '';
        for ($i = 0; $i < strlen($value); $i++) {
            $c = strtolower($value{$i});
            if (ctype_alnum($c) || ($c == '.' || ($c == '*'))) {
                $out .= $c;
            } else {
                $out .= '-';
            }
        }

        return $out;
    }
}
