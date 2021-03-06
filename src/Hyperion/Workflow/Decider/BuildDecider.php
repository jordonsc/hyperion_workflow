<?php
namespace Hyperion\Workflow\Decider;

use Hyperion\Dbal\Entity\Environment;
use Hyperion\Dbal\Enum\DistributionStatus;
use Hyperion\Dbal\Enum\Entity;
use Hyperion\Dbal\Enum\InstanceState;
use Hyperion\Workflow\Entity\WorkflowCommand;
use Hyperion\Workflow\Enum\ActionPhase;
use Hyperion\Workflow\Enum\BuildStage;
use Hyperion\Workflow\Enum\ChangeType;
use Hyperion\Workflow\Enum\CommandType;
use Hyperion\Workflow\Enum\WorkflowResult;
use Hyperion\Workflow\Exception\UnexpectedValueException;

class BuildDecider extends AbstractDecider implements DeciderInterface
{
    const NS_STAGE                = 'stage';
    const NS_INSTANCE             = 'instance';
    const CHECK_DELAY             = 5;
    const DEFAULT_DNS_TTL         = 60;
    const DEFAULT_DNS_RECORD_TYPE = 'A';
    const BUILD_TIMEOUT           = 3600;

    /**
     * Get the action that should be taken
     *
     * @return WorkflowResult
     */
    public function getResult()
    {
        // State information
        $build_stage = $this->getState(self::NS_STAGE, BuildStage::SPAWNING);

        switch ($build_stage) {
            // Launch instance, build
            case BuildStage::SPAWNING:
                return $this->processSpawning();
            // Build complete, close
            case BuildStage::BUILDING:
                return $this->processCleanup();
            case BuildStage::CONFIGURING:
                return WorkflowResult::COMPLETE();
            // Default
            default:
                $this->reason = "Workflow default at stage '".$build_stage."'";
                return WorkflowResult::FAIL();
        }
    }

    /**
     * Process the final stages of the bakery
     *
     * @return WorkflowResult
     */
    protected function processCleanup()
    {
        // Check if we should be binding a DNS record against this build
        /** @var Environment $env */
        $env = $this->dbal->retrieve(Entity::ENVIRONMENT(), $this->action->getEnvironment());
        if (!$env) {
            throw new UnexpectedValueException("Unknown environment ID (".$this->action->getEnvironment().")");
        }

        if ($env->getDnsZone()) {
            // Need to add a DNZ zone
            return $this->actionDns($env);
        } else {
            // Nothing else to do
            return WorkflowResult::COMPLETE();
        }
    }

    /**
     * Bind a DNS entry to the build
     *
     * @return WorkflowResult
     */
    protected function actionDns(Environment $env)
    {
        $this->commands[] = new WorkflowCommand(
            $this->action,
            CommandType::BIND_DNS,
            [
                'action' => ChangeType::UPDATE,
                'zone'   => $env->getDnsZone(),
                'name'   => $env->getDnsName(),
                'type'   => self::DEFAULT_DNS_RECORD_TYPE,
                'ttl'    => (int)$env->getDnsTtl() ?: self::DEFAULT_DNS_TTL,
                'value'  => $this->getState(self::NS_INSTANCE.'.0.ip.public.ip4'),
            ],
            $this->getNsPrefix().self::NS_INSTANCE
        );
        $this->setState(self::NS_INSTANCE.'.0.state', InstanceState::PENDING);
        $this->setState(self::NS_STAGE, BuildStage::CONFIGURING);
        $this->progress(ActionPhase::CONFIGURING);
        return WorkflowResult::COMMAND();
    }

    /**
     * Sequence of events that happen while the bake template is spawning
     *
     * @return WorkflowResult
     */
    protected function processSpawning()
    {
        $instance_state = $this->getState(self::NS_INSTANCE.'.0.state');

        // Haven't created it yet
        if ($instance_state === null) {
            $this->setDistributionStatus(DistributionStatus::BUILDING());
            return $this->actionSpawnInstance();
        }

        // Wait for ready
        switch ($instance_state) {
            // Waiting for instance to spawn
            case InstanceState::PENDING:
            case InstanceState::STARTING:
                return $this->actionCheckInstance();

            // Ready - start baking
            case InstanceState::RUNNING:
                return $this->actionBuildInstance();

            // These are all error cases at this stage of the process and shouldn't occur
            default:
            case InstanceState::STOPPING:
            case InstanceState::TERMINATING:
            case InstanceState::STOPPED:
            case InstanceState::TERMINATED:
                $this->reason = 'Failed to spawn project template';
                return WorkflowResult::FAIL();
        }
    }

    /**
     * Spawn a new build instance
     *
     * @return WorkflowResult
     */
    protected function actionSpawnInstance()
    {
        $this->commands[] = new WorkflowCommand(
            $this->action,
            CommandType::LAUNCH_INSTANCE,
            [],
            $this->getNsPrefix().self::NS_INSTANCE
        );
        $this->setState(self::NS_INSTANCE.'.0.state', InstanceState::PENDING);
        $this->progress(ActionPhase::SPAWNING);
        return WorkflowResult::COMMAND();
    }

    /**
     * Check an instance
     *
     * @return WorkflowResult
     */
    protected function actionCheckInstance()
    {
        $this->commands[] = new WorkflowCommand(
            $this->action,
            CommandType::CHECK_INSTANCE,
            [
                'delay'       => self::CHECK_DELAY,
                'instance-id' => $this->getState(self::NS_INSTANCE.'.0.instance-id'),
            ],
            $this->getNsPrefix().self::NS_INSTANCE
        );
        return WorkflowResult::COMMAND();
    }

    /**
     * Kick off the build process
     *
     * @return WorkflowResult
     */
    protected function actionBuildInstance()
    {
        $connectivity = ($this->getState(self::NS_INSTANCE.'.0.connectivity', 'false')) == 'true';

        if (!$connectivity) {
            // Test that the SSH service has come up
            $this->commands[] = new WorkflowCommand(
                $this->action,
                CommandType::CHECK_CONNECTIVITY,
                [
                    'delay'           => self::CHECK_DELAY,
                    'address-private' => $this->getState(self::NS_INSTANCE.'.0.ip.private.ip4'),
                    'address-public'  => $this->getState(self::NS_INSTANCE.'.0.ip.public.ip4'),
                ],
                $this->getNsPrefix().self::NS_INSTANCE.'.0.connectivity'
            );

        } else {
            // OK to build
            $this->commands[] = new WorkflowCommand(
                $this->action,
                CommandType::BAKE_INSTANCE,
                [
                    'instance-id'     => $this->getState(self::NS_INSTANCE.'.0.instance-id'),
                    'address-private' => $this->getState(self::NS_INSTANCE.'.0.ip.private.ip4'),
                    'address-public'  => $this->getState(self::NS_INSTANCE.'.0.ip.public.ip4'),
                ],
                $this->getNsPrefix().self::NS_INSTANCE,
                self::BUILD_TIMEOUT
            );

            $this->setState(self::NS_STAGE, BuildStage::BUILDING);
        }

        return WorkflowResult::COMMAND();
    }


    /**
     * Called by the DecisionManager when the workflow completes
     */
    public function onComplete()
    {
        $this->setDistributionStatus(DistributionStatus::ACTIVE());
        $this->tearDownPrevious();
    }

    /**
     * Called by the DecisionManager when the workflow fails
     */
    public function onFail()
    {
        $this->setDistributionStatus(DistributionStatus::FAILED());
        $this->tearDownPrevious();
    }

}
