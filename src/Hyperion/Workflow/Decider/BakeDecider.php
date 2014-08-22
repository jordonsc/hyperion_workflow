<?php
namespace Hyperion\Workflow\Decider;

use Bravo3\CloudCtrl\Enum\ImageState;
use Hyperion\Dbal\Entity\Project;
use Hyperion\Dbal\Enum\BakeStatus;
use Hyperion\Dbal\Enum\DistributionStatus;
use Hyperion\Dbal\Enum\Entity;
use Hyperion\Dbal\Enum\InstanceState;
use Hyperion\Framework\Utility\ConfigTrait;
use Hyperion\Workflow\Entity\WorkflowCommand;
use Hyperion\Workflow\Enum\ActionPhase;
use Hyperion\Workflow\Enum\BakeStage;
use Hyperion\Workflow\Enum\CommandType;
use Hyperion\Workflow\Enum\WorkflowResult;

class BakeDecider extends AbstractDecider implements DeciderInterface
{
    const NS_STAGE    = 'stage';
    const NS_INSTANCE = 'instance';
    const NS_IMAGE    = 'image';
    const CHECK_DELAY = 5;

    /**
     * Get the action that should be taken
     *
     * @return WorkflowResult
     */
    public function getResult()
    {
        // State information
        $bake_stage = $this->getState(self::NS_STAGE, BakeStage::SPAWNING);

        switch ($bake_stage) {
            // Launch instance, bake
            case BakeStage::SPAWNING:
                return $this->processSpawning();
            // Shutdown instance
            case BakeStage::BAKING:
            case BakeStage::SHUTDOWN:
                return $this->processShutdown();
            // Wait for image
            case BakeStage::SAVING:
                return $this->processSaving();
            // Cleanup stage, will complete here
            case BakeStage::CLEANUP:
                return $this->processCleanup();

            // Default
            default:
                $this->reason = "Workflow default at stage '".$bake_stage."'";
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
        $dereg_state = $this->getState(self::NS_STAGE.'.deregister');

        if ($dereg_state === '0') {
            // Something failed (workflow will fail anyway)
            $this->reason = 'Cleanup failed';
            return WorkflowResult::FAIL();
        } elseif ($dereg_state === '1') {
            // Everything is done
            return WorkflowResult::COMPLETE();
        }

        // Nothing to do - wait for deregister task
        // NB: this shouldn't happen as deregistering is the only thing in the cleanup phase
        return WorkflowResult::COMMAND();
    }

    /**
     * Image is saving, wait for it to complete then terminate all
     *
     * @return WorkflowResult
     */
    protected function processSaving()
    {
        $image_state = $this->getState(self::NS_IMAGE.'.state');

        // Haven't created it yet
        if ($image_state === null) {
            $this->reason = 'Image state missing!';
            return WorkflowResult::FAIL();
        }

        switch ($image_state) {
            case ImageState::AVAILABLE:
                return $this->actionCleanup();
            case ImageState::PENDING:
                return $this->actionCheckImage();
            default:
                $this->reason = 'Invalid image in save phase';
                return WorkflowResult::FAIL();
        }

    }

    /**
     * Shutdown the baked instance and save an image
     *
     * @return WorkflowResult
     */
    protected function processShutdown()
    {
        $instance_state = $this->getState(self::NS_INSTANCE.'.0.state');

        // Haven't created it yet
        if ($instance_state === null) {
            $this->reason = 'Instance state missing!';
            return WorkflowResult::FAIL();
        }

        // Wait for ready
        switch ($instance_state) {
            // Still running, shut it down
            case InstanceState::RUNNING:
                return $this->actionShutdownInstance();

            // Wait for instance to shutdown, assume terminating means shutting-down
            case InstanceState::STOPPING:
            case InstanceState::TERMINATING:
                return $this->actionCheckInstance();

            // Stopped - save an image
            case InstanceState::STOPPED:
                return $this->actionCreateImage();

            // These are all error cases at this stage of the process and shouldn't occur
            default:
            case InstanceState::STARTING:
            case InstanceState::PENDING:
            case InstanceState::TERMINATED:
                $this->reason = 'Invalid instance status in shutdown phase';
                return WorkflowResult::FAIL();
        }

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
            $this->setBakeStatus(BakeStatus::BAKING());
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
                return $this->actionBakeInstance();

            // These are all error cases at this stage of the process and shouldn't occur
            default:
            case InstanceState::STOPPING:
            case InstanceState::TERMINATING:
            case InstanceState::STOPPED:
            case InstanceState::TERMINATED:
                $this->reason = 'Failed to spawn bake template';
                return WorkflowResult::FAIL();
        }

    }

    /**
     * Check the image status
     *
     * @return WorkflowResult
     */
    protected function actionCheckImage()
    {
        $this->commands[] = new WorkflowCommand(
            $this->action,
            CommandType::CHECK_IMAGE,
            [
                'delay'    => self::CHECK_DELAY,
                'image-id' => $this->getState(self::NS_IMAGE.'.id'),
            ],
            $this->getNsPrefix().self::NS_IMAGE
        );
        return WorkflowResult::COMMAND();
    }

    /**
     * Terminate bakery instance, deregister old image, update project
     *
     * @return WorkflowResult
     */
    protected function actionCleanup()
    {
        // Project from DBAL
        /** @var Project $project */
        $project = $this->dbal->retrieve(Entity::PROJECT(), $this->action->getProject());
        if (!$project) {
            $this->reason = 'Unable to retrieve project from DBAL';
            return WorkflowResult::FAIL();
        }

        // Deregister former image
        if ($project->getBakedImageId()) {
            $this->commands[] = new WorkflowCommand(
                $this->action,
                CommandType::DEREGISTER_IMAGE,
                [
                    'image-id' => $project->getBakedImageId(),
                ],
                $this->getNsPrefix().self::NS_STAGE.'.deregister'
            );
        } else {
            $this->setState(self::NS_STAGE.'.deregister', '1');
        }

        // Update DBAL with new image
        $project->setBakedImageId($this->getState(self::NS_IMAGE.'.id'));
        $this->dbal->update($project);

        $this->setState(self::NS_STAGE, BakeStage::CLEANUP);
        $this->progress(ActionPhase::CLEANUP);
        return WorkflowResult::COMMAND();
    }

    /**
     * Create a machine image
     *
     * @return WorkflowResult
     */
    protected function actionCreateImage()
    {
        $this->commands[] = new WorkflowCommand(
            $this->action,
            CommandType::CREATE_IMAGE,
            [
                'instance-id' => $this->getState(self::NS_INSTANCE.'.0.instance-id'),
                'image-name'  => 'prj-'.$this->action->getProject().'.a-'.$this->action->getId(),
            ],
            $this->getNsPrefix().self::NS_IMAGE
        );
        $this->setState(self::NS_STAGE, BakeStage::SAVING);
        $this->progress(ActionPhase::SAVING);
        return WorkflowResult::COMMAND();
    }

    /**
     * Spawn a new bakery instance
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
     * Shutdown the bakery instance
     *
     * @return WorkflowResult
     */
    protected function actionShutdownInstance()
    {
        $this->commands[] = new WorkflowCommand(
            $this->action,
            CommandType::SHUTDOWN_INSTANCE,
            [
                'instance-id' => $this->getState(self::NS_INSTANCE.'.0.instance-id'),
            ],
            $this->getNsPrefix().self::NS_STAGE.'.shutdown'
        );
        $this->setState(self::NS_INSTANCE.'.0.state', InstanceState::STOPPING);
        $this->setState(self::NS_STAGE, BakeStage::SHUTDOWN);
        $this->progress(ActionPhase::SHUTTING_DOWN);
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
     * Kick off the bakery process
     *
     * @return WorkflowResult
     */
    protected function actionBakeInstance()
    {
        $connectivity = ($this->getState(self::NS_INSTANCE.'.0.connectivity', 'false')) == 'true';

        if (!$connectivity) {
            // Test that the SSH service has come up
            $this->commands[] = new WorkflowCommand(
                $this->action,
                CommandType::CHECK_CONNECTIVITY,
                [
                    'delay'   => self::CHECK_DELAY,
                    'address' => $this->getState(self::NS_INSTANCE.'.0.ip.public.ip4'),
                ],
                $this->getNsPrefix().self::NS_INSTANCE.'.0.connectivity'
            );

        } else {
            // OK to bake
            $this->commands[] = new WorkflowCommand(
                $this->action,
                CommandType::BAKE_INSTANCE,
                [
                    'instance-id' => $this->getState(self::NS_INSTANCE.'.0.instance-id'),
                    'address'     => $this->getState(self::NS_INSTANCE.'.0.ip.public.ip4'),
                ],
                $this->getNsPrefix().self::NS_INSTANCE
            );

            $this->setState(self::NS_STAGE, BakeStage::BAKING);
        }

        return WorkflowResult::COMMAND();
    }


    /**
     * Update the bake status for the project
     *
     * @param BakeStatus $status
     * @return bool
     */
    protected function setBakeStatus(BakeStatus $status)
    {
        /** @var Project $project */
        $project = $this->dbal->retrieve(Entity::PROJECT(), $this->action->getProject());
        if (!$project) {
            return false;
        }
        $project->setBakeStatus($status);
        $this->dbal->update($project);
        return true;
    }

    /**
     * Called by the DecisionManager when the workflow completes
     */
    public function onComplete()
    {
        $this->setDistributionStatus(DistributionStatus::ACTIVE());
        $this->setBakeStatus(BakeStatus::BAKED());
        $this->tearDown();
    }

    /**
     * Called by the DecisionManager when the workflow fails
     */
    public function onFail()
    {
        $this->setDistributionStatus(DistributionStatus::FAILED());
        $this->setBakeStatus(BakeStatus::UNBAKED());
        $this->tearDown();
    }

}
